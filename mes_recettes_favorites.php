<?php
session_start();

// Connexion à la base de données
$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') or die("Erreur de connexion à MySQL");

// Vérifiez si des favoris sont définis dans la session
if (!isset($_SESSION['favorites']) || empty($_SESSION['favorites'])) {
    echo "<!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <title>Mes recettes préférées</title>
        <link rel='stylesheet' type='text/css' href='styles_favories.css'>
    </head>
    <body>
        <h1>Mes recettes préférées</h1>
        <p>Vous n'avez pas encore de recettes préférées.</p>
    </body>
    </html>";
    exit();
}

// Récupérer les IDs des recettes favorites depuis la session
$favorite_ids = array_keys($_SESSION['favorites']);

// Construire une requête pour récupérer les informations des recettes favorites
$ids_placeholders = implode(',', array_fill(0, count($favorite_ids), '?'));
$stmt = $mysqli->prepare("SELECT r.id, r.titre, r.preparation, p.chemin_photo 
                          FROM recettes r
                          LEFT JOIN photos p ON r.id = p.recette_id
                          WHERE r.id IN ($ids_placeholders)");

// Associer les IDs des recettes favorites aux paramètres de la requête
$stmt->bind_param(str_repeat('i', count($favorite_ids)), ...$favorite_ids);
$stmt->execute();
$result = $stmt->get_result();

// Vérifiez s'il y a des recettes favorites
if ($result->num_rows === 0) {
    echo "<!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <title>Mes recettes préférées</title>
        <link rel='stylesheet' type='text/css' href='styles_favories.css'>
    </head>
    <body>
        <h1>Mes recettes préférées</h1>
        <p>Vous n'avez pas encore de recettes préférées.</p>
    </body>
    </html>";
    exit();
}

// Fonction pour afficher les recettes favorites
function afficher_recettes_favorites($result) {
    echo "<div class='recettes-list'>";
    while ($row = $result->fetch_assoc()) {
        $titre = htmlspecialchars($row['titre']);
        $photo = !empty($row['chemin_photo']) && file_exists($row['chemin_photo']) ? $row['chemin_photo'] : "Photos/Image-Not-Found.jpg";
        $preparation = htmlspecialchars($row['preparation']);

        echo "<div class='recette-card'>
                <img src='$photo' alt='$titre' class='recette-img'>
                <div class='recette-details'>
                    <h3>$titre</h3>
                    <p>$preparation</p>
                </div>
              </div>";
    }
    echo "</div>";
}

// Affichage HTML
echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Mes recettes préférées</title>
    <link rel='stylesheet' type='text/css' href='styles/styles_favories.css'>
</head>
<body>
    <h1>Mes recettes préférées</h1>";

// Afficher les recettes favorites
afficher_recettes_favorites($result);

echo "</body>
</html>";

// Fermer la connexion à la base de données
$stmt->close();
$mysqli->close();
?>

