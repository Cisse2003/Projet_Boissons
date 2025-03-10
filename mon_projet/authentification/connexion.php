<?php
session_start();

$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') or die("Erreur de connexion à MySQL");


// Vérification du mot de passe crypter
function verifierMotDePasseCrypter($motDePasseSaisi, $motDePasseStocke) {
    return password_verify($motDePasseSaisi, $motDePasseStocke);
}

// Vérification si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Préparer la requête pour récupérer les informations de l'utilisateur
    $stmtGetUser = $mysqli->prepare("SELECT * FROM utilisateurs WHERE username = ?");
    if ($stmtGetUser === false) {
        die("Erreur de préparation de la requête : " . $mysqli->error);
    }

    // Lier le paramètre (nom d'utilisateur)
    $stmtGetUser->bind_param("s", $_POST['username']);
    $stmtGetUser->execute();
    $result = $stmtGetUser->get_result();

    // Vérification si l'utilisateur existe
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Utilisation de la fonction pour vérifier le mot de passe sans cryptage
        if (verifierMotDePasseCrypter($_POST['password'], $row['password_hash'])) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['photo'] = $row['photo_path'] ?? 'Photos/default-photo.png'; // Photo ou valeur par défaut

            // Rediriger vers la page des recettes après la connexion réussie
            header("Location: ../index.php");
        
            exit();
        }
    }

    // Si les identifiants sont incorrects, afficher une erreur
    $erreurConnexion = TRUE;
}

// Fermer la connexion à la base de données
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <title>Connexion</title>
</head>
<body>
<div class="container small">
    <form action="connexion.php" method="POST">
    <?php
    if (isset($erreurConnexion) && $erreurConnexion) {
        echo "<span class='incorrect'>Nom d'utilisateur ou mot de passe incorrect.</span>";
    }
    ?>
    <h2>Connexion</h2>

    <div class="form-group">
        <label for="username">Nom d'utilisateur</label>
        <input type="text" name="username" value="<?php if (isset($_POST['username'])) echo $_POST['username']; ?>" required>
    </div>

    <div class="form-group">
        <label for="password">Mot de passe</label>
        <div class='blocMDP'>
            <input type="password" name="password" id='mdp' class='mdp' required>
            <span class="material-icons visibilite" onclick="toggleVisibilite('mdp', this)">visibility</span>
        </div>
    </div>

    <button type="submit">Se connecter</button>

    <!-- Nouveaux boutons -->
    <div class="forgot-section">
        <a href="reset_password.php">Mot de passe oublié</a>
    </div>
</form>


    <p>Pas encore inscrit ? <a href="inscription.php">Inscrivez-vous ici</a></p>
</div>
<script>
        function toggleVisibilite(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.textContent = "visibility_off";
            } else {
                input.type = "password";
                icon.textContent = "visibility";
            }
        }
</script>
</body>
</html>

