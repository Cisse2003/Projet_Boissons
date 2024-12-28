<?php
session_start();

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: ../authentification/connexion.php");
    exit();
}

// Connexion à la base de données
$mysqli = new mysqli('127.0.0.1', 'root', '', 'ProjetRecettes');
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

$username = $_SESSION['username'];

// Récupération des informations de l'utilisateur (toujours à jour)
$stmt = $mysqli->prepare("SELECT * FROM utilisateurs WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $sexe = htmlspecialchars(trim($_POST['sexe']));
    $date_naissance = !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : NULL;
    $adresse = htmlspecialchars(trim($_POST['adresse']));
    $code_postal = htmlspecialchars(trim($_POST['code_postal']));
    $ville = htmlspecialchars(trim($_POST['ville']));
    $telephone = htmlspecialchars(trim($_POST['telephone']));
    $email = htmlspecialchars(trim($_POST['email']));

    // Gestion de la photo de profil
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../Photos/uploads/';
        $file_name = uniqid() . '.' . strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $target_file = $upload_dir . $file_name;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (getimagesize($_FILES['profile_picture']['tmp_name'])) {
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file);

            // Mise à jour du chemin de la photo
            $update_photo_stmt = $mysqli->prepare("UPDATE utilisateurs SET photo_path = ? WHERE username = ?");
            $update_photo_stmt->bind_param("ss", $file_name, $username);
            $update_photo_stmt->execute();
        }
    }

    // Mise à jour des informations utilisateur
    $update_stmt = $mysqli->prepare(
        "UPDATE utilisateurs SET nom = ?, prenom = ?, sexe = ?, date_naissance = ?, adresse = ?, code_postal = ?, ville = ?, telephone = ?, email = ? WHERE username = ?"
    );
    $update_stmt->bind_param("ssssssssss", $nom, $prenom, $sexe, $date_naissance, $adresse, $code_postal, $ville, $telephone, $email, $username);
    $update_stmt->execute();

    // Recharge les informations à jour pour les afficher
    header("Location: modifier_profil.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Profil</title>
    <link rel="stylesheet" href="styles/modifier_profil.css">
   
</head>
<body>
    <div class="container">
        <h1>Modifier votre profil</h1>
        <form method="POST" enctype="multipart/form-data">
        	<?php
		$photoPath = (!empty($user['photo_path']) && $user['photo_path'] !== 'Photos/default-photo.png') ? $user['photo_path'] : 'default-photo.png';
		?>
            <div class="preview">
                <img id="profile-picture-preview" src="../Photos/uploads/<?php echo htmlspecialchars($photoPath); ?>" alt="Photo de profil">
            </div>

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

            <label for="profile_picture">Photo de profil :</label>
            <input type="file" name="profile_picture" id="profile_picture" accept="image/*">

            <button type="submit">Mettre à jour</button>
        </form>

        <!-- Bouton de retour à la page précédente -->
        <button class="btn-back" onclick="window.history.back();">Retour</button>
    </div>

    <script src="scripts/modifier_profil.js"></script>
</body>
</html>

