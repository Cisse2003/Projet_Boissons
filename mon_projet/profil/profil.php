<?php
session_start();

// Définit le fuseau horaire pour éviter les avertissements
date_default_timezone_set('Europe/Paris');

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: ../authentification/connexion.php");
    exit();
}

// Connexion à la base de données MySQL
$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') or die("Erreur de connexion à MySQL");


// Récupère les informations de l'utilisateur depuis la base de données
$username = $_SESSION['username'];
$stmt = $mysqli->prepare("SELECT * FROM utilisateurs WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Gestion du téléchargement de la photo de profil
$success_message = $error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $upload_dir = __DIR__ . '/../Photos/uploads/';
        $file_name = uniqid() . '.' . strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $target_file = $upload_dir . $file_name;
        $upload_ok = 1;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Vérifie si le fichier est une image
        if (!getimagesize($_FILES['profile_picture']['tmp_name'])) {
            $upload_ok = 0;
            $error_message = "Le fichier n'est pas une image valide.";
        }

        // Vérifie la taille du fichier
        if ($_FILES['profile_picture']['size'] > 5000000) {
            $upload_ok = 0;
            $error_message = "Le fichier est trop volumineux.";
        }

        // Autorise uniquement certains formats
        if (!in_array($image_file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
            $upload_ok = 0;
            $error_message = "Seuls les formats JPG, JPEG, PNG et GIF sont autorisés.";
        }

        // Enregistre le fichier si tout est bon
        if ($upload_ok === 1) {
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $update_query = "UPDATE utilisateurs SET photo_path = ? WHERE username = ?";
                $stmt = $mysqli->prepare($update_query);
                $stmt->bind_param("ss", $file_name, $username);
                if ($stmt->execute()) {
                    $success_message = "La photo de profil a été mise à jour avec succès.";
                    $user['profile_picture'] = $file_name;
                } else {
                    $error_message = "Erreur lors de la mise à jour de la base de données.";
                }
            } else {
                $error_message = "Erreur lors du téléchargement du fichier.";
            }
        }
    } else {
        // Aucun fichier téléchargé, affichage d'un message d'information si nécessaire
        $error_message = "Aucun fichier n'a été sélectionné. Veuillez choisir une photo à télécharger.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil</title>
    <link rel="stylesheet" href="styles/profil.css">
</head>
<body>
    <div class="container middle">
        <h2>Profil de <?php echo htmlspecialchars($user['username']); ?></h2>

        <?php if ($success_message): ?>
            <p style="color: green;"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        
        <?php
        $photoPath = (!empty($user['photo_path']) && $user['photo_path'] !== 'Photos/default-photo.png') ? $user['photo_path'] : 'default-photo.png';
        ?>

        <div class="photo-section">
            <label for="profile_picture">
                <img src="../Photos/uploads/<?php echo htmlspecialchars($photoPath); ?>" alt="Photo de profil" id="photoPreview">
            </label>
        </div>

        <form action="" method="post" enctype="multipart/form-data">
            <label for="profile_picture">Changer votre photo de profil</label>
            <input type="file" name="profile_picture" id="profile_picture" accept="image/*">

            <button type="submit">Mettre à jour</button>
        </form>

        <div>
            <p><strong>Nom :</strong> <?php echo htmlspecialchars($user['nom']); ?></p>
            <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Date d'inscription :</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
        </div>

        <!-- Centrer le bouton "Modifier le profil" -->
        <div class="btn-container">
            <a href="modifier_profil.php" class="btn">Modifier le profil</a>
        </div>

        <!-- Bouton de retour -->
        <button class="btn btn-back" onclick="window.history.back();">Retour</button>
    </div>

    <script src="scripts/profil.js" ></script>
</body>
</html>

