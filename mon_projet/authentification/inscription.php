<?php
session_start();

$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') or die("Erreur de connexion à MySQL");

$message = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Champs facultatifs
    $email = !empty($_POST['email']) ? $_POST['email'] : NULL;
    $nom = !empty($_POST['name']) ? $_POST['name'] : NULL;
    $prenom = !empty($_POST['prenom']) ? $_POST['prenom'] : NULL;
    $sexe = isset($_POST['sexe']) ? $_POST['sexe'] : NULL;
    $date_naissance = !empty($_POST['birthdate']) ? $_POST['birthdate'] : date('Y-m-d');
    $adresse = !empty($_POST['address']) ? $_POST['address'] : NULL;
    $code_postal = !empty($_POST['postal_code']) ? $_POST['postal_code'] : '00000';
    $ville = !empty($_POST['city']) ? $_POST['city'] : NULL;
    $telephone = !empty($_POST['phone']) ? $_POST['phone'] : '07 77 77 77 77';
    $photo = 'Photos/default-photo.png';

    // Gestion de la photo de profil
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['photo']['size'] > 2 * 1024 * 1024 || !in_array($_FILES['photo']['type'], ['image/jpeg', 'image/png', 'image/gif'])) {
            $message = "Photo invalide.";
        } else {
            $uploadDir = '../Photos/uploads/';
            $photoName = uniqid() . '-' . basename($_FILES['photo']['name']);
            $uploadFile = $uploadDir . $photoName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFile)) {
                $photo = $photoName;
            } else {
                $message = "Erreur lors du téléchargement de la photo.";
            }
        }
    }

    // Vérifier si le nom d'utilisateur est déjà pris
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM utilisateurs WHERE username = ?");
    if (!$stmt) {
        die("Erreur de préparation de la requête : " . $mysqli->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($usernameCount);
    $stmt->fetch();
    $stmt->close();

    if ($usernameCount > 0) {
        $message = "Le nom d'utilisateur est déjà pris.";
    } else {
        // Vérification des formats
        if ($code_postal !== "" && !preg_match('/^[0-9]{5}$/', $code_postal)) {
            $message = "Code postal invalide : 5 chiffres requis.";
        } elseif ($telephone !== "" && !preg_match('/^0[1-9]([- ]?[0-9]{2}){4}$/', $telephone)) {
            $message = "Numéro de téléphone invalide.";
        } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/\W/', $password)) {
            $message = "Mot de passe non sécurisé : il doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
        } else {
            // Hacher le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insérer l'utilisateur dans la base de données
            $sql = "INSERT INTO utilisateurs 
            (username, password_hash, nom, prenom, sexe, email, date_naissance, adresse, code_postal, ville, telephone, photo_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);

            if (!$stmt) {
                $message = "Erreur de préparation de la requête : " . $mysqli->error;
            } else {
                $stmt->bind_param(
                    "ssssssssssss",
                    $username,
                    $hashedPassword,
                    $nom,
                    $prenom,
                    $sexe,
                    $email,
                    $date_naissance,
                    $adresse,
                    $code_postal,
                    $ville,
                    $telephone,
                    $photo
                );

                if ($stmt->execute()) {
                    $message = "<span class='succes'>Inscription réussie !</span>";
                    // Stocker le nom d'utilisateur dans la session après l'inscription
        	    $_SESSION['username'] = $username;

        	   // Rediriger vers la page des recettes après l'inscription réussie
        	  define('BASE_URL', '/projet/Projet/mon_projet/');
		  header("Location: " . BASE_URL . "afficher_recettes.php");
        
   	          exit();
                } else {
                    $message = "Erreur lors de l'inscription : " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$mysqli->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style.css">
    <script src="../scripts/inscription.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <title>TDC - Inscription</title>
</head>
<body>

<div class="container middle">
    <h2>Inscription</h2>
    <!-- Section pour afficher les messages -->
    <?php if (!empty($message)): ?>
        <div class="incorrect">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    <form action="inscription.php" method="POST" enctype="multipart/form-data">

       <div class="photo-section">
    <label for="photo">
        <img src="../Photos/default-photo.png" alt="Photo de profil" class="photo-preview" id="photoPreview">
    </label>
    <input type="file" id="photo" name="photo" accept="image/*" style="display: none;" onchange="previewPhoto(event)">
</div>


        <div class='sexeDiv'>
            <label for="sexe" class="elemSexeInput">Sexe : </label>
            <label for="fem" class="elemSexe">Femme</label>
            <input type="radio" name="sexe" class="elemSexeInput" value="F">
            <label for="hom" class="elemSexe">Homme</label>
            <input type="radio" name="sexe" class="elemSexeInput" value="M">
            <label for="hom" class="elemSexe">Autre</label>
            <input type="radio" name="sexe" class="elemSexeInput" value="Autre">
        </div>

        <div class="deuxBlocs">
            <div class="bloc">
                <div>
                    <label for="username">Nom d'utilisateur (Requis)</label>
                    <input type="text" name="username" value="<?php if (isset($username)) echo $username; ?>" required>
                    <?php if (isset($usernameTaken) && $usernameTaken) echo "<span class='errone'>Nom d'utilisateur déjà pris</span>"; ?>
                </div>

                <div>
                    <label for="name">Nom</label>
                    <input type="text" name="name" value="<?php if (isset($name)) echo $name; ?>">
                </div>

                <div>
                    <label for="pname">Prénom</label>
                    <input type="text" name="prenom" value="<?php if (isset($prenom)) echo $prenom; ?>">
                </div>

                <div>
                    <label for="email">Adresse e-mail</label>
                    <input type="email" name="email" value="<?php if (isset($email)) echo $email; ?>">
                </div>

                <div>
                    <label for="password">Mot de passe (Requis)</label>
                    <?php if (isset($motDePasseNonSecurise) && $motDePasseNonSecurise) echo "<span class='errone'>Le mot de passe ne respecte pas les conditions</span>"; ?>
                    <span class="errone cache" id="nouveauMotDePasseErreur"></span>
                    <div class="blocMDP">
                        <input type="password" name="password" id='mdp' class='mdp' value="<?php if (isset($password)) echo $password; ?>" required>
                        <span class="material-icons visibilite" onclick="toggleVisiblite('mdp', this)">visibility</span>
                    </div>

                    <span class='condMotDePasse'>Le mot de passe doit avoir au moins 8 caractères, dont au moins 1 lettre majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.</span>
                </div>
            </div>

            <div class="bloc">

                <div>
                    <label for="birthdate">Date de naissance</label>
                    <input type="date" name="birthdate" value="<?php if (isset($birthdate)) echo $birthdate; ?>">
                </div>

                <div>
                    <label for="address">Adresse</label>
                    <input type="text" name="address" value="<?php if (isset($adress)) echo $adress; ?>">
                </div>

                <div>
                    <label for="postal_code">Code Postal</label>
                    <input type="text" name="postal_code" placeholder="54000" value="<?php if (isset($postal_code)) echo $postal_code; ?>">
                    <?php if (isset($badformatCP) && $badformatCP) echo "<span class='errone'>code postal invalide : 5 chiffres</span>"; ?>
                </div>

                <div>
                    <label for="city">Ville</label>
                    <input type="text" name="city" value="<?php if (isset($city)) echo $city; ?>">
                </div>

                <div>
                    <label for="phone">Numéro de téléphone</label>
                    <input type="tel" name="phone" placeholder="07 77 77 77 77 ou 0777777777" value="<?php if (isset($phone)) echo $phone; ?>">
                    <?php if (isset($badformatPhone) && $badformatPhone)
                        echo "<span class='errone'>numéro de téléphone invalide : 10 chiffres, 2 à 2 séparés (ou pas) par des ' ' ou '-'</span>"; ?>
                </div>
            </div>
        </div>
        <button type="submit">S'inscrire</button>

    </form>

    <p>Déjà inscrit ? <a href="connexion.php">Connectez-vous ici</a></p>
</div>
</body>
</html>
