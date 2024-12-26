<?php
session_start();

// Définit le fuseau horaire pour éviter les avertissements
date_default_timezone_set('Europe/Paris');

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: ../utilisateurs/connexion.php");
    exit();
}

// Établit une connexion à la base de données MySQL
$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes')
or die("Erreur de connexion à MySQL");

// Récupère les informations de l'utilisateur depuis la base de données
$username = $_SESSION['username'];
$stmt = $mysqli->prepare("SELECT * FROM utilisateurs WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Gestion du téléchargement de la photo de profil
$success_message = $error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $upload_dir = '../uploads/';
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
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            $update_query = "UPDATE utilisateurs SET profile_picture = ? WHERE username = ?";
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
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            color: #333;
        }
        header {
            background-color: #007BFF;
            color: white;
            padding: 15px 20px;
            text-align: center;
        }
        header a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .profile-picture img {
            border-radius: 50%;
            width: 150px;
            height: 150px;
            object-fit: cover;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .btn {
            background-color: #007BFF;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
<header>
    <a href="../afficher_recettes.php">Retour à l'accueil</a>
</header>

<div class="container">
    <h1>Profil de <?php echo htmlspecialchars($user['username']); ?></h1>

    <?php if ($success_message): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="profile-picture">
        <img src="../uploads/<?php echo htmlspecialchars($user['profile_picture'] ); ?>" alt="Photo de profil">
    </div>

    <p><strong>Nom complet :</strong> <?php echo htmlspecialchars($user['nom']); ?></p>
    <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <p><strong>Date d'inscription :</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="profile_picture">Changer de photo de profil :</label>
            <input type="file" name="profile_picture" id="profile_picture" accept="image/*">
        </div>
        <button type="submit" class="btn">Télécharger</button>
    </form>

    <a href="modifier_profil.php" class="btn">Modifier le profil</a>
</div>
</body>
</html>
