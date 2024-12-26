<?php
session_start();

$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') 
    or die("Erreur de connexion à MySQL");

// Vérifie si l'utilisateur a des recettes favorites dans la session
if (!isset($_SESSION['favorites']) || empty($_SESSION['favorites']) || count($_SESSION['favorites']) === 0) {
    // Si aucune recette favorite n'est enregistrée, affiche une page avec un message vide
    echo "<!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <title>Mes recettes préférées</title>
        <link rel='stylesheet' type='text/css' href='styles/pas_de_recettes_favories.css'>
    </head>
    <body>
        <div class='empty-state'>
            <h1>Mes recettes préférées</h1>
            <p>Vous n'avez pas encore de recettes préférées. Ajoutez-en pour les retrouver ici !</p>
            <a href='afficher_recettes.php' class='btn-primary'>Découvrir des recettes</a>
        </div>
    </body>
    </html>";
    exit(); 
}

// Récupère les IDs des recettes favorites
$favorite_ids = array_keys($_SESSION['favorites']); // Les clés du tableau $_SESSION['favorites'] contiennent les IDs des recettes

// Requête SQL pour récupérer les détails des recettes favorites
$ids_placeholders = implode(',', array_fill(0, count($favorite_ids), '?'));
$stmt = $mysqli->prepare("SELECT r.id, r.titre, r.preparation, p.chemin_photo 
                          FROM recettes r
                          LEFT JOIN photos p ON r.id = p.recette_id
                          WHERE r.id IN ($ids_placeholders)");

$stmt->bind_param(str_repeat('i', count($favorite_ids)), ...$favorite_ids);
$stmt->execute();
$result = $stmt->get_result(); 

// Fonction pour afficher les recettes favorites
function afficher_recettes_favorites($result) {
    echo "<div class='recettes-list'>";

    // Parcourt les résultats de la requête et affiche chaque recette
    while ($row = $result->fetch_assoc()) {
        $titre = htmlspecialchars($row['titre']); // Protège le titre contre les injections XSS
        $photo = !empty($row['chemin_photo']) && file_exists($row['chemin_photo']) 
            ? $row['chemin_photo'] 
            : "Photos/photo_non_trouver.jpg"; 
        $preparation = htmlspecialchars($row['preparation']); 

        // Affiche une carte pour chaque recette
        echo "<div class='recette-card'>
                <div class='recette-image-container'>
                    <img src='$photo' alt='$titre' class='recette-img'> <!-- Affiche l'image de la recette -->
                </div>
                <div class='recette-details'>
                    <h3>$titre</h3> <!-- Affiche le titre de la recette -->
                    <p>" . substr($preparation, 0, 100) . "...</p> <!-- Affiche un extrait de la préparation -->
                    <a href='details_ingredients.php?id=" . $row['id'] . "' class='btn-secondary'>Ingrédients</a> <!-- Lien vers la recette -->
                </div>
              </div>";
    }

    echo "</div>";
}


// Document HTML
echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Mes recettes préférées</title>
    <link rel='stylesheet' type='text/css' href='styles/styles_mes_favories.css'> <!-- Lien vers la feuille de styles -->
</head>
<body>
    <header class='main-header'>
        <h1>Mes recettes préférées</h1>
        <p>Découvrez et revisitez vos plats préférés !</p>
    </header>";

afficher_recettes_favorites($result);

echo "</body>
</html>";

$stmt->close(); 
$mysqli->close(); 
?>

