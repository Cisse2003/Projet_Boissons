<?php
session_start();

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: connexion.php");
    exit();
}

$mysqli = new mysqli('127.0.0.1', 'root', '', 'ProjetRecettes');
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

$username = $_SESSION['username'];
$stmt = $mysqli->prepare("SELECT * FROM utilisateurs WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $sexe = htmlspecialchars(trim($_POST['sexe']));
    $date_naissance = $_POST['date_naissance'] ?: NULL;
    $adresse = htmlspecialchars(trim($_POST['adresse']));
    $code_postal = htmlspecialchars(trim($_POST['code_postal']));
    $ville = htmlspecialchars(trim($_POST['ville']));
    $telephone = htmlspecialchars(trim($_POST['telephone']));
    $email = htmlspecialchars(trim($_POST['email']));

    $update_stmt = $mysqli->prepare(
        "UPDATE utilisateurs SET nom = ?, prenom = ?, sexe = ?, date_naissance = ?, adresse = ?, code_postal = ?, ville = ?, telephone = ?, email = ? WHERE username = ?"
    );
    $update_stmt->bind_param("ssssssssss", $nom, $prenom, $sexe, $date_naissance, $adresse, $code_postal, $ville, $telephone, $email, $username);
    $update_stmt->execute();

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
        }
        form {
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
<h1>Modifier votre profil</h1>
<form method="POST">
    <label for="nom">Nom :</label>
    <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>

    <label for="prenom">Prénom :</label>
    <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>

    <label for="sexe">Sexe :</label>
    <select name="sexe" id="sexe">
        <option value="M" <?php echo ($user['sexe'] === 'M') ? 'selected' : ''; ?>>Masculin</option>
        <option value="F" <?php echo ($user['sexe'] === 'F') ? 'selected' : ''; ?>>Féminin</option>
        <option value="Autre" <?php echo ($user['sexe'] === 'Autre') ? 'selected' : ''; ?>>Autre</option>
    </select>

    <label for="date_naissance">Date de naissance :</label>
    <input type="date" name="date_naissance" id="date_naissance" value="<?php echo htmlspecialchars($user['date_naissance']); ?>">

    <label for="adresse">Adresse :</label>
    <input type="text" name="adresse" id="adresse" value="<?php echo htmlspecialchars($user['adresse']); ?>">

    <label for="code_postal">Code postal :</label>
    <input type="text" name="code_postal" id="code_postal" value="<?php echo htmlspecialchars($user['code_postal']); ?>">

    <label for="ville">Ville :</label>
    <input type="text" name="ville" id="ville" value="<?php echo htmlspecialchars($user['ville']); ?>">

    <label for="telephone">Téléphone :</label>
    <input type="text" name="telephone" id="telephone" value="<?php echo htmlspecialchars($user['telephone']); ?>">

    <label for="email">Email :</label>
    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

    <button type="submit">Mettre à jour</button>
</form>
</body>
</html>
