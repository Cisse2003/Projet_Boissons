<?php
session_start();

// Connexion à la base de données
$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') or die("Erreur de connexion à MySQL");

// Vérifiez si l'utilisateur est connecté
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

function afficher_recettes_favorites($mysqli, $user_id) {
    if ($user_id) {
        // Récupérer les recettes favorites depuis la base de données
        $query = "
            SELECT r.id, r.titre, p.chemin_photo
            FROM recettes r
            JOIN recettes_favorites rf ON r.id = rf.recette_id
            LEFT JOIN photos p ON r.id = p.recette_id
            WHERE rf.utilisateur_id = ?
        ";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        // Si l'utilisateur n'est pas connecté, utiliser les favoris en session
        if (empty($_SESSION['favorites'])) {
            echo "<p>Vous n'avez pas encore de recettes préférées.</p>";
            return;
        }
        $recette_ids = implode(',', array_keys($_SESSION['favorites']));
        $query = "
            SELECT id, titre, chemin_photo
            FROM recettes
            LEFT JOIN photos ON recettes.id = photos.recette_id
            WHERE id IN ($recette_ids)
        ";
        $result = $mysqli->query($query);
    }

    if ($result->num_rows > 0) {
        echo "<div class='recettes'>";
        while ($row = $result->fetch_assoc()) {
            echo "<div class='recette'>";
            echo "<h2>" . htmlspecialchars($row['titre']) . "</h2>";
            if (!empty($row['chemin_photo']) && file_exists($row['chemin_photo'])) {
                echo "<img src='" . htmlspecialchars($row['chemin_photo']) . "' alt='Photo de " . htmlspecialchars($row['titre']) . "'>";
            } else {
                echo "<img src='Photos/Image-Not-Found.jpg' alt='Photo non disponible'>";
            }
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<p>Vous n'avez pas encore de recettes préférées.</p>";
    }
}

// Affichage HTML
echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Mes recettes préférées</title>
    <style>
    body { font-family: Arial, sans-serif; }
    .recettes { display: flex; flex-wrap: wrap; gap: 20px; }
    .recette { border: 1px solid #ddd; padding: 10px; width: 300px; text-align: center; }
    .recette img { max-width: 100%; height: auto; }
</style>

</head>
<body>
    <h1>Mes recettes préférées</h1>";

// Afficher les recettes favorites
afficher_recettes_favorites($mysqli, $user_id);

// Fermer la connexion
$mysqli->close();

echo "</body>
</html>";
?>
