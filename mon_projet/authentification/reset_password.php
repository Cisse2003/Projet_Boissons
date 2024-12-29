<?php
$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') or die("Erreur de connexion à MySQL");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = $_POST['username_or_email'];
    $new_password = $_POST['new_password'];

    // Vérifier si c'est un e-mail ou un nom d'utilisateur
    $stmt = $mysqli->prepare("SELECT * FROM utilisateurs WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $update_stmt = $mysqli->prepare("UPDATE utilisateurs SET password_hash = ? WHERE id = ?");
        $update_stmt->bind_param("si", $hashed_password, $user['id']);
        $update_stmt->execute();

        $message = "Mot de passe réinitialisé avec succès.";
        $update_stmt->close();
    } else {
        $error = "Aucun compte trouvé avec ces informations.";
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
    <title>Réinitialiser le mot de passe</title>
</head>
<body>
<div class="container small">
    <h2>Réinitialiser le mot de passe</h2>
    <form method="POST" action="reset_password.php">
        <div class="form-group">
            <label for="username_or_email">Nom d'utilisateur ou e-mail</label>
            <input type="text" name="username_or_email" required>
        </div>
        <div class="form-group">
            <label for="new_password">Nouveau mot de passe</label>
            <input type="password" name="new_password" required>
        </div>
        <button type="submit">Réinitialiser</button>
    </form>
    
    <!-- Bouton Nom oublié -->
    <div class="forgot-section">
        <a href="recuperation_nom.php">Nom oublié ?</a>
    </div>

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

