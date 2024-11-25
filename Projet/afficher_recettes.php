<?php
session_start(); 
$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') or die("Erreur de connexion à MySQL");
$aliment = isset($_GET['aliment']) ? $_GET['aliment'] : 'Aliment';

if (!isset($_SESSION['favorites'])) {
    $_SESSION['favorites'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['recette_id'])) {
    $recette_id = intval($_POST['recette_id']);

    if ($_POST['action'] === 'add') {
        $_SESSION['favorites'][$recette_id] = true;
    } elseif ($_POST['action'] === 'remove') {
        unset($_SESSION['favorites'][$recette_id]);
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit();
}

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
            $chemin[] = "<a href='?aliment=" . urlencode($row['aliment']) . "'>" . htmlspecialchars($row['aliment']) . "</a>";
            $aliment = $row['super_categorie'];
        } else {
            break;
        }
    }
    echo "<div class='chemin'><strong>Aliment courant :</strong><br>" . implode(" / ", array_reverse($chemin)) . "</div>";
}

function afficher_sous_categories($mysqli, $aliment) {
    $query = "SELECT a.nom AS sous_categorie
              FROM aliments a
              LEFT JOIN hierarchie h ON a.id = h.categorie_id
              WHERE h.aliment_id = (SELECT id FROM aliments WHERE nom = '$aliment') AND h.type_relation = 'sous'";
    $result = $mysqli->query($query);

    echo "<div class='sous-categories'><h3>Sous-catégories :</h3><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li><a href='?aliment=" . urlencode($row['sous_categorie']) . "'>" . htmlspecialchars($row['sous_categorie']) . "</a></li>";
    }
    echo "</ul></div>";
}

function afficher_recettes($mysqli, $aliment) {
    $query = "
        SELECT r.id AS recette_id, r.titre, r.preparation, p.chemin_photo
        FROM recettes r
        LEFT JOIN photos p ON r.id = p.recette_id
        WHERE r.index_aliments LIKE '%$aliment%'
        GROUP BY r.id
    ";
    $result = $mysqli->query($query);

    echo "<div class='recettes'><h3>Recettes :</h3>";
    while ($row = $result->fetch_assoc()) {
        $recette_id = $row['recette_id'];
        $est_favorite = isset($_SESSION['favorites'][$recette_id]);
        $coeur = $est_favorite ? "❤️" : "🤍";

        echo "<div class='recette'>";
        echo "<h2>" . htmlspecialchars($row['titre']) . "</h2>";
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
            $texte = (!empty($unite) && mb_strtolower(mb_substr($unite, 0, 2)) === mb_strtolower(mb_substr($aliment, 0, 2)))
                ? "$quantite $unite" 
                : "$quantite $unite de $aliment";
            echo "<li>" . trim($texte) . "</li>";
        }
        echo "</ul>";
        echo "<p><strong>Préparation :</strong> " . htmlspecialchars($row['preparation']) . "</p>";
        echo "<div class='photo'>";
        echo !empty($row['chemin_photo']) && file_exists($row['chemin_photo']) 
            ? "<img src='" . htmlspecialchars($row['chemin_photo']) . "' alt='Photo de " . htmlspecialchars($row['titre']) . "'>"
            : "<img src='Photos/Image-Not-Found.jpg' alt='Photo non disponible'>";
        echo "</div>";
        echo "<form method='POST'><input type='hidden' name='recette_id' value='$recette_id'>
            <button type='submit' name='action' value='" . ($est_favorite ? 'remove' : 'add') . "'>$coeur</button>
        </form>";
        echo "</div>";
    }
    echo "</div>";
}

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Recettes</title>
    <link rel='stylesheet' type='text/css' href='styles/styles_recettes.css'>
</head>
<body>
    <header> 
    	 <nav>
            <a href='mes_recettes_favorites.php' target='_blank'  class='btn-favorites'>Recettes ❤️ </a>
        </nav>
    </header>
    <h1>Recettes pour : " . htmlspecialchars($aliment) . "</h1>";
afficher_chemin($mysqli, $aliment);
afficher_sous_categories($mysqli, $aliment);
afficher_recettes($mysqli, $aliment);
$mysqli->close();
echo "</body></html>";

