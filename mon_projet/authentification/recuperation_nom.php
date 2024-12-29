<?php
$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') or die("Erreur de connexion à MySQL");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $stmt = $mysqli->prepare("SELECT username FROM utilisateurs WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $message = "Votre nom d'utilisateur est : " . htmlspecialchars($user['username']);
    } else {
        $error = "Aucun compte trouvé avec cet e-mail.";
    }
    $stmt->close();
}
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style.css">
    <title>Récupération de nom d'utilisateur</title>
</head>
<body>
<div class="container small">
    <h2>Récupérer votre nom d'utilisateur</h2>
    <form method="POST" action="recuperation_nom.php">
        <div class="form-group">
            <label for="email">Adresse e-mail</label>
            <input type="email" name="email" required>
        </div>
        <button type="submit">Récupérer</button>
    </form>

    <!-- Bouton Retour à la connexion -->
    <div class="back-to-login">
        <a href="connexion.php" class="btn-secondary">Retour à la connexion</a>
    </div>
    
    <?php
    if (isset($message)) echo "<span class='succes'>$message</span>";
    if (isset($error)) echo "<span class='incorrect'>$error</span>";
    ?>
</div>
</body>
</html>

