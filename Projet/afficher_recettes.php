<?php
// Connexion à la base de données
$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') or die("Erreur de connexion à MySQL");

// Récupérer l'aliment sélectionné dans l'URL (ou afficher la racine par défaut)
$aliment = isset($_GET['aliment']) ? $_GET['aliment'] : 'Aliment';

// Initialisation des favoris pour les utilisateurs non connectés
if (!isset($_SESSION['favorites'])) {
    $_SESSION['favorites'] = [];
}

// Gérer les ajouts/suppressions de recettes favorites
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['recette_id'])) {
    $recette_id = intval($_POST['recette_id']);

    if ($_POST['action'] === 'add') {
        // Ajouter aux favoris dans la session
        $_SESSION['favorites'][$recette_id] = true;

        // Ajouter en base de données si l'utilisateur est connecté
        if ($user_id) {
            $stmt = $mysqli->prepare("INSERT IGNORE INTO recettes_favorites (utilisateur_id, recette_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $recette_id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($_POST['action'] === 'remove') {
        // Retirer des favoris dans la session
        unset($_SESSION['favorites'][$recette_id]);

        // Supprimer de la base de données si l'utilisateur est connecté
        if ($user_id) {
            $stmt = $mysqli->prepare("DELETE FROM recettes_favorites WHERE utilisateur_id = ? AND recette_id = ?");
            $stmt->bind_param("ii", $user_id, $recette_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Redirection pour éviter la resoumission du formulaire après actualisation
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit();
}


function afficher_recettes_favorites($mysqli, $user_id) {
    if ($user_id) {
        $query = "
            SELECT r.id, r.titre
            FROM recettes r
            JOIN recettes_favorites rf ON r.id = rf.recette_id
            WHERE rf.utilisateur_id = $user_id
        ";
    } else {
        $recette_ids = implode(',', array_keys($_SESSION['favorites']));
        $query = "
            SELECT id, titre
            FROM recettes
            WHERE id IN ($recette_ids)
        ";
    }
    $result = $mysqli->query($query);

    echo "<h2>Mes recettes préférées</h2><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['titre']) . "</li>";
    }
    echo "</ul>";
}

// Fonction pour afficher le chemin de navigation
function afficher_chemin($mysqli, $aliment) {
    $chemin = [];
    while ($aliment) {
        $query = "SELECT a.nom AS aliment, s.nom AS super_categorie
                  FROM aliments a
                  LEFT JOIN hierarchie h ON a.id = h.aliment_id
                  LEFT JOIN aliments s ON h.categorie_id = s.id AND h.type_relation = 'super'
                  WHERE a.nom = '$aliment'";
        $result = $mysqli->query($query);
        if ($row = $result->fetch_assoc()) {
            $chemin[] = $row['aliment'];
            $aliment = $row['super_categorie'];
        } else {
            break;
        }
    }
    // Afficher le chemin de la hiérarchie de manière inversée
    echo "<div class='chemin'><strong>Chemin :</strong> ";
    echo implode(" -> ", array_reverse($chemin));
    echo "</div>";
}

// Fonction pour afficher les sous-catégories de l'aliment
function afficher_sous_categories($mysqli, $aliment) {
    $query = "SELECT a.nom AS sous_categorie
              FROM aliments a
              LEFT JOIN hierarchie h ON a.id = h.categorie_id
              WHERE h.aliment_id = (SELECT id FROM aliments WHERE nom = '$aliment') AND h.type_relation = 'sous'";
    $result = $mysqli->query($query);

    echo "<ul class='sous-categories'>";
    while ($row = $result->fetch_assoc()) {
        echo "<li><a href='?aliment=" . urlencode($row['sous_categorie']) . "'>" . htmlspecialchars($row['sous_categorie']) . "</a></li>";
    }
    echo "</ul>";
}

// Fonction pour afficher les recettes utilisant un aliment en tant qu'ingrédient
function afficher_recettes($mysqli, $aliment, $user_id = null) {
    // Requête pour les recettes contenant cet aliment dans l'index
    $query = "
        SELECT r.id AS recette_id, r.titre, r.preparation, p.chemin_photo
        FROM recettes r
        LEFT JOIN photos p ON r.id = p.recette_id
        WHERE r.index_aliments LIKE '%$aliment%'
        GROUP BY r.id
    ";
    $result = $mysqli->query($query);

    // Affichage des recettes
    echo "<div class='recettes'>";
    while ($row = $result->fetch_assoc()) {
        $recette_id = $row['recette_id'];

        // Vérifier si la recette est dans les favoris
        $est_favorite = $user_id
            ? $mysqli->query("SELECT 1 FROM recettes_favorites WHERE utilisateur_id = $user_id AND recette_id = $recette_id")->num_rows > 0
            : isset($_SESSION['favorites'][$recette_id]);

        // Définir le cœur à afficher
        $coeur = $est_favorite ? "❤️" : "🤍";

        echo "<div class='recette'>";
        echo "<h2>" . htmlspecialchars($row['titre']) . "</h2>";

        // Afficher les ingrédients de la recette
        echo "<p><strong>Ingrédients :</strong></p><ul>";
        $ingredients_query = "
            SELECT i.quantite, i.unite, a.nom AS aliment
            FROM ingredients i
            LEFT JOIN aliments a ON i.aliment_id = a.id
            WHERE i.recette_id = {$row['recette_id']}
        ";
        $ingredients_result = $mysqli->query($ingredients_query);
        while ($ingredient = $ingredients_result->fetch_assoc()) {
            $quantite = !empty($ingredient['quantite']) ? $ingredient['quantite'] : 'Vide';
            $unite = !empty($ingredient['unite']) ? $ingredient['unite'] : '';
            $aliment = htmlspecialchars($ingredient['aliment']);

            // Affichage formaté de l'ingrédient
            echo "<li>$quantite $unite $aliment</li>";
        }
        echo "</ul>";

        // Afficher la préparation
        echo "<p><strong>Préparation :</strong> " . htmlspecialchars($row['preparation']) . "</p>";

        // Affichage de la photo si disponible
        if (!empty($row['chemin_photo']) && file_exists($row['chemin_photo'])) {
            echo "<div class='photo'><img src='" . htmlspecialchars($row['chemin_photo']) . "' alt='Photo de " . htmlspecialchars($row['titre']) . "' width='100'></div>";
        } else {
            echo "<div class='photo'><img src='Photos/Image-Not-Found.jpg' alt='Photo non disponible' width='100'></div>";
        }

        // Bouton pour ajouter ou retirer des favoris avec des cœurs
        echo "<form method='POST' style='display: inline-block;'>
            <input type='hidden' name='recette_id' value='$recette_id'>
            <button type='submit' name='action' value='" . ($est_favorite ? 'remove' : 'add') . "' style='border: none; background: none; font-size: 1.5em; cursor: pointer;'>
                $coeur
            </button>
        </form>";

        echo "</div>";
    }
    echo "</div>";
}


// Structure HTML et affichage
echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Recettes pour $aliment</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .chemin { font-weight: bold; margin-bottom: 10px; }
        .sous-categories, .recettes { margin-top: 20px; }
        .sous-categories li { list-style-type: disc; margin-left: 20px; }
        .recette { border-bottom: 1px solid #ddd; padding: 10px 0; }
        .photo img { display: block; margin-top: 10px; max-width: 200px; max-height: 200px; object-fit: cover; }
    </style>
</head>
<body>
    <nav>
        // <a href='index.php'>Accueil</a>
        <a href='mes_recettes_favorites.php'target='_blank'>Mes recettes préférées</a>
    </nav>
    <h1>Recettes pour l'aliment : " . htmlspecialchars($aliment) . "</h1>";

// Afficher les recettes selectionnées
echo "<h2>Mes recettes préférées :</h2>";
afficher_recettes_favorites($mysqli, $user_id);

// Afficher le chemin de navigation
afficher_chemin($mysqli, $aliment);

// Afficher les sous-catégories
echo "<h2>Sous-catégories de " . htmlspecialchars($aliment) . " :</h2>";
afficher_sous_categories($mysqli, $aliment);

// Afficher les recettes associées
echo "<h2>Recettes utilisant " . htmlspecialchars($aliment) . " :</h2>";
afficher_recettes($mysqli, $aliment);

// Fermeture de la connexion
$mysqli->close();

echo "</body></html>";
?>
