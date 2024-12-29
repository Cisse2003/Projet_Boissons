<?php
session_start();

$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') 
    or die("Erreur de connexion à MySQL");

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; // Vérifie si l'utilisateur est connecté

// Initialisation des favoris
if (!isset($_SESSION['favorites'])) {
    $_SESSION['favorites'] = [];
}

// Gestion des favoris (ajout/retrait)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['recette_id'])) {
    $recette_id = intval($_POST['recette_id']);
    
    if ($_POST['action'] === 'add') {
        $_SESSION['favorites'][$recette_id] = true;
    } elseif ($_POST['action'] === 'remove') {
        unset($_SESSION['favorites'][$recette_id]);
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Récupérer la requête de recherche
$nom_recette = isset($_GET['query']) ? trim($_GET['query']) : null;

// Récupération des recettes
$recettes = [];
if ($nom_recette) {
    $query = "
        SELECT r.id AS recette_id, r.titre, r.preparation, p.chemin_photo
        FROM recettes r
        LEFT JOIN photos p ON r.id = p.recette_id
        WHERE r.titre LIKE ?";
    $stmt = $mysqli->prepare($query);
    $like_nom_recette = "%{$nom_recette}%";
    $stmt->bind_param("s", $like_nom_recette);
    $stmt->execute();
    $result = $stmt->get_result();

    $recettes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Récupérer la photo de profil de l'utilisateur (si connecté)
if (isset($_SESSION['user_id'])) {
    $username = $_SESSION['username']; 
    $query = "SELECT photo_path FROM utilisateurs WHERE username = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        $_SESSION['photo'] = "Photos/uploads/" . $row['photo_path'] ?: 'Photos/default-photo.png'; // Photo par défaut si non spécifiée
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats de la recherche</title>
    <link rel="stylesheet" type="text/css" href="styles/styles_affiche_recettes.css">
</head>
<body>
    <header>
        <nav class="alignement_des_btns">
            <a href="mes_recettes_favorites.php" class="btn-favorites">Recettes ❤️</a>
            <div class="recherche-container">
        <form method="GET" action="resultats_recherche.php">
            <input type="text" name="query" placeholder="Rechercher une recette..." required>
            <button type="submit" class="search-button">Recherche</button>
        </form>
    </div>

            <?php if (isset($_SESSION['username'])): ?>
                <!-- Si l'utilisateur est connecté, afficher la photo de profil -->
                <a href="javascript:void(0);" class="photo-profil" id="photo-profil" onclick="toggleProfilMenu()">
                    <img src="<?php echo $_SESSION['photo']; ?>" alt="Photo de profil" class="photo-profil-img">
                </a>

                <!-- Menu déroulant pour les paramètres et déconnexion -->
                <div id="profil-menu">
                    <a href="profil/profil.php">Paramètres</a>
                    <a href="profil/deconnexion.php">Déconnexion</a>
                </div>
            <?php else: ?>
                <!-- Si l'utilisateur n'est pas connecté, afficher le bouton de connexion -->
                <a href="authentification/connexion.php" class="btn-connexion">Connexion</a>
            <?php endif; ?>
        </nav>
    </header>
   
    <div class="conteneur">
        <?php if (empty($recettes)): ?>
            <div class="pop-up-message">
                <p>Aucune recette ne correspond au nom : "<strong><?= htmlspecialchars($nom_recette) ?></strong>".</p>
                <button onclick="window.history.back()">Retour</button>
            </div>
        <?php else: ?>
            <div class="recettes">
                <?php foreach ($recettes as $recette): ?>
                    <?php
                    $recette_id = $recette['recette_id'];
                    $est_favorite = isset($_SESSION['favorites'][$recette_id]);
                    $coeur = $est_favorite ? "❤️" : "🤍";
                    ?>
                    <div class="recette">
                        <h2><?= htmlspecialchars($recette['titre']) ?></h2>
                        <div class="photo">
                            <img src="<?= htmlspecialchars($recette['chemin_photo']) ?>" alt="Photo de la recette">
                        </div>
                        <p><strong>Préparation :</strong> <?= htmlspecialchars($recette['preparation']) ?></p>

                        <!-- Bouton cœur -->
                        <form method="POST" action="">
                            <input type="hidden" name="recette_id" value="<?= $recette_id ?>">
                            <button type="submit" name="action" value="<?= $est_favorite ? 'remove' : 'add' ?>" class="heart-button">
                                <?= $coeur ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <button onclick="window.history.back()" class="back-button">Retour à la page précédente</button>

    <script>
        // Fonction pour afficher/masquer le menu de profil
        function toggleProfilMenu() {
            var menu = document.getElementById("profil-menu");
            if (menu.style.display === "block") {
                menu.style.display = "none";
            } else {
                menu.style.display = "block";
            }
        }

        // Fermer le menu si l'utilisateur clique en dehors
        window.onclick = function(event) {
            var menu = document.getElementById("profil-menu");
            var photoProfil = document.getElementById("photo-profil");
            if (!photoProfil.contains(event.target) && !menu.contains(event.target)) {
                menu.style.display = "none";
            }
        }
    </script>
</body>
</html>


