<?php
session_start();

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: connexion.php");
    exit();
}

$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes')
or die("Erreur de connexion à MySQL");

// Récupère l'utilisateur connecté
$username = $_SESSION['username'];
$query = "SELECT * FROM utilisateurs WHERE username = '$username'";
$result = $mysqli->query($query);
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupère les nouvelles informations du profil
    $nom_complet = $_POST['nom_complet'];
    $email = $_POST['email'];

    // Met à jour les informations dans la base de données
    $update_query = "UPDATE utilisateurs SET nom_complet = '$nom_complet', email = '$email' WHERE username = '$username'";
    $mysqli->query($update_query);

    // Redirige vers la page de profil après la mise à jour
    header("Location: profil.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Profil</title>
    <link rel="stylesheet" type="text/css" href="styles/profil.css">
</head>
<body>
<header>
    <nav class='alignement_des_btns'>
        <a href="profil.php" class="btn-home">Retour au Profil</a>
    </nav>
</header>

<h1>Modifier votre profil</h1>

<form method="POST">
    <label for="nom_complet">Nom complet :</label>
    <input type="text" name="nom_complet" id="nom_complet" value="<?php echo htmlspecialchars($user['nom_complet']); ?>" required>

    <label for="email">Email :</label>
    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

    <button type="submit">Mettre à jour</button>
</form>

<footer>
    <p>&copy; 2024 Projet Recettes</p>
</footer>
</body>
</html>
