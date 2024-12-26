<?php
session_start();

$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') 
    or die("Erreur de connexion à MySQL");

// Récupère l'aliment spécifié dans l'URL ou utilise 'Aliment' par défaut
$aliment = isset($_GET['aliment']) ? $_GET['aliment'] : 'Aliment';

// Initialise le tableau des recettes favorites si ce n'est pas déjà fait
if (!isset($_SESSION['favorites'])) {
    $_SESSION['favorites'] = [];
}

// Gère l'ajout ou la suppression d'une recette des favoris
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['recette_id'])) {
    $recette_id = intval($_POST['recette_id']); // Assure que l'ID est un entier

    if ($_POST['action'] === 'add') {
        $_SESSION['favorites'][$recette_id] = true;
    } elseif ($_POST['action'] === 'remove') {
        unset($_SESSION['favorites'][$recette_id]);
    }

    // Recharge la page pour éviter un double traitement du formulaire
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit();
}

// Fonction pour afficher le chemin hiérarchique des aliments
function afficher_chemin($mysqli, $aliment) {
    $chemin = [];
    while ($aliment) {
        // Requête pour obtenir l'aliment courant et sa super-catégorie
        $query = "SELECT a.nom AS aliment, s.nom AS super_categorie
                  FROM aliments a
                  LEFT JOIN hierarchie h ON a.id = h.aliment_id
                  LEFT JOIN aliments s ON h.categorie_id = s.id AND h.type_relation = 'super'
                  WHERE a.nom = '$aliment'";
        $result = $mysqli->query($query);

        if ($row = $result->fetch_assoc()) {
            // Ajoute l'aliment au chemin
            $chemin[] = "<a href='?aliment=" . urlencode($row['aliment']) . "'>" . htmlspecialchars($row['aliment']) . "</a>";
            $aliment = $row['super_categorie']; // Passe à la super-catégorie
        } else {
            break;
        }	
    }

    // Affiche le chemin sous forme de liens cliquables
    echo "<div class='chemin'><strong><h3>Aliment courant :</h3></strong><br>" . implode(" / ", array_reverse($chemin)) . "</div>";
}

// Fonction pour afficher les sous-catégories d'un aliment
function afficher_sous_categories($mysqli, $aliment) {
echo"<div class='chemin-sous-categories fixed-size'>";
afficher_chemin($mysqli, $aliment);
    // Requête pour obtenir les sous-catégories de l'aliment
    $query = "SELECT a.nom AS sous_categorie
              FROM aliments a
              LEFT JOIN hierarchie h ON a.id = h.categorie_id
              WHERE h.aliment_id = (SELECT id FROM aliments WHERE nom = '$aliment') AND h.type_relation = 'sous'";
    $result = $mysqli->query($query);

    echo "<div class='sous-categories'><h3>Sous-catégories :</h3><ul>";
    while ($row = $result->fetch_assoc()) {
        // Affiche chaque sous-catégorie sous forme de lien
        echo "<li><a href='?aliment=" . urlencode($row['sous_categorie']) . "'>" . htmlspecialchars($row['sous_categorie']) . "</a></li>";
    }
    echo "</ul></div>";
    echo"</div>";
}

// Fonction pour afficher les recettes associées à un aliment
function afficher_recettes($mysqli, $aliment) {
    // Requête pour obtenir les recettes contenant l'aliment
    $query = "
        SELECT r.id AS recette_id, r.titre, r.preparation, p.chemin_photo
        FROM recettes r
        LEFT JOIN photos p ON r.id = p.recette_id
        WHERE r.index_aliments LIKE '%$aliment%'
        GROUP BY r.id
    ";
    $result = $mysqli->query($query);

    echo "<div class='recettes'>";
    while ($row = $result->fetch_assoc()) {
        $recette_id = $row['recette_id']; 
        $est_favorite = isset($_SESSION['favorites'][$recette_id]); // Vérifie si la recette est dans les favoris
        $coeur = $est_favorite ? "❤️" : "🤍"; 

        echo "<div class='recette'>";
        // Formulaire pour ajouter/supprimer une recette des favoris
        echo "<form method='POST'>
                <input type='hidden' name='recette_id' value='$recette_id'>
                <button type='submit' name='action' value='" . ($est_favorite ? 'remove' : 'add')."'>$coeur</button>
              </form>";
              
        /*echo "<h2 class='titre-recette' data-ingredients='";
$ingredients_query = "
    SELECT i.quantite, i.unite, a.nom AS aliment
    FROM ingredients i
    LEFT JOIN aliments a ON i.aliment_id = a.id
    WHERE i.recette_id = {$row['recette_id']}
";
$ingredients_result = $mysqli->query($ingredients_query);

// Concaténer les ingrédients dans un seul attribut
$ingredients_text = [];
while ($ingredient = $ingredients_result->fetch_assoc()) {
    $quantite = !empty($ingredient['quantite']) ? $ingredient['quantite'] : 'Vide';
    $unite = !empty($ingredient['unite']) ? $ingredient['unite'] : '';
    $aliment = htmlspecialchars($ingredient['aliment']);
    $texte = (!empty($unite) && mb_strtolower(mb_substr($unite, 0, 2)) === mb_strtolower(mb_substr($aliment, 0, 2)))
        ? "$quantite $unite" 
        : "$quantite $unite de $aliment";
    $ingredients_text[] = trim($texte);
}
echo htmlspecialchars(implode(', ', $ingredients_text)) . "'>";
echo htmlspecialchars($row['titre']);
*/
echo "<h2 class='titre-recette'>";
       echo htmlspecialchars($row['titre']);
 echo "</h2>";      

        // Affiche la photo de la recette ou une image par défaut si absente
        echo "<div class='photo'>";
        echo !empty($row['chemin_photo']) && file_exists($row['chemin_photo']) 
            ? "<img src='" . htmlspecialchars($row['chemin_photo']) . "' alt='Photo de " . htmlspecialchars($row['titre']) . "'>"
            : "<img src='Photos/photo_non_trouver.jpg' alt='Photo non disponible'>";
        echo "</div>";
	// Affiche la préparation de la recette
        echo "<p><strong>Préparation :</strong> " . htmlspecialchars($row['preparation']) . "</p>";
        echo "</div>";
    }
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recettes</title>
    <link rel="stylesheet" type="text/css" href="styles/affichage_recettes.css">
</head>
<body>
    <header> 
        <nav class="alignement_des_btns">
            <a href="mes_recettes_favorites.php" target="_blank" class="btn-favorites">Recettes ❤️</a>
             <form action="resultats_recherche.php" method="get" class="form-research" onsubmit="return false;" style="position: relative;">
    <input type="text" id="search-input" name="query" placeholder="Rechercher..." class="input-research" onkeyup="fetchSuggestions(this.value)">
    <div id="suggestions" class="suggestions-list"></div>
    <button type="submit" class="btn-research" onclick="performSearch()">Recherche</button>
</form>

            <a href="utilisateurs/connexion.php" class="btn-connexion">Connexion</a>
        </nav>
    </header>
 
    <div class="conteneur">
        <?php
        afficher_sous_categories($mysqli, $aliment);
        afficher_recettes($mysqli, $aliment);
        ?>
    </div>
    <script src="scripts/recherche.js"></script>
</body>
</html>

