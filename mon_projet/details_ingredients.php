<?php
session_start();

$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') 
    or die("Erreur de connexion à MySQL");

// Vérifie si un ID de recette est passé dans l'URL
if (isset($_GET['id'])) {
    $recette_id = intval($_GET['id']);

    // Requête pour récupérer les détails de la recette
    $query = "SELECT r.titre, r.preparation, p.chemin_photo 
              FROM recettes r
              LEFT JOIN photos p ON r.id = p.recette_id
              WHERE r.id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $recette_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $titre = htmlspecialchars($row['titre']);
        $photo = !empty($row['chemin_photo']) && file_exists($row['chemin_photo']) 
            ? $row['chemin_photo']
            : "Photos/photo_non_trouver.jpg";
        $preparation = htmlspecialchars($row['preparation']);

        // Requête pour récupérer les ingrédients de la recette
        $ingredients_query = "
            SELECT i.quantite, i.unite, a.nom AS aliment
            FROM ingredients i
            LEFT JOIN aliments a ON i.aliment_id = a.id
            WHERE i.recette_id = ?
        ";
        $ingredients_stmt = $mysqli->prepare($ingredients_query);
        $ingredients_stmt->bind_param('i', $recette_id);
        $ingredients_stmt->execute();
        $ingredients_result = $ingredients_stmt->get_result();

        echo "<!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <title>$titre - Ingrédients</title>
            <link rel='stylesheet' type='text/css' href='styles/styles_ingredients.css'>
        </head>
        <body>
            <header class='main-header'>
                <h1>$titre</h1>
            </header>
            <div class='recette-details'>
                <div class='recette-photo'>
                    <img src='$photo' alt='$titre' class='recette-img'>
                </div>
                <div class='ingredients-list'>
                    <h3>Ingrédients :</h3>
                    <ul>";

        while ($ingredient = $ingredients_result->fetch_assoc()) {
            $quantite = $ingredient['quantite'] ? $ingredient['quantite'] : 'Quantité non spécifiée';
            $unite = $ingredient['unite'] ? $ingredient['unite'] : '';
            $aliment = htmlspecialchars($ingredient['aliment']);
            echo "<li>$quantite $unite de $aliment</li>";
        }

        echo "</ul>
            </div>
            <div class='preparation'>
                <h3>Préparation :</h3>
                <p>$preparation</p>
            </div>
        </div>
        </body>
        </html>";

    } else {
        echo "Recette non trouvée.";
    }

    $stmt->close();
    $ingredients_stmt->close();
    $mysqli->close();
} else {
    echo "ID de recette non spécifié.";
}
?>

