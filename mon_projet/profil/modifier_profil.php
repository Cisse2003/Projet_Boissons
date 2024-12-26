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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Profil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fffaf0;
            color: #333;
        }
        header {
            background-color: #ff6f00;
            padding: 15px 20px;
            text-align: center;
        }
        header .btn-home {
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            background-color: #ff8c00;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        header .btn-home:hover {
            background-color: #e65c00;
        }
        h1 {
            text-align: center;
            color: #ff6f00;
            margin-top: 20px;
        }
        form {
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            border: 1px solid #ffd699;
            border-radius: 8px;
            padding: 20px 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        form label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #ff6f00;
        }
        form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ffcc99;
            border-radius: 5px;
        }
        form input:focus {
            border-color: #ff8c00;
            outline: none;
        }
        form button {
            background-color: #ff6f00;
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        form button:hover {
            background-color: #e65c00;
        }
        footer {
            text-align: center;
            padding: 20px;
            background-color: #ff6f00;
            color: white;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
<header>
    <a href="profil.php" class="btn-home">Retour au Profil</a>
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
