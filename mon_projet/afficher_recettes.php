<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') 
    or die("Erreur de connexion à MySQL");

// Récupère l'aliment spécifié dans l'URL ou utilise 'Aliment' par défaut
$aliment = isset($_GET['aliment']) ? $_GET['aliment'] : 'Aliment';

// Initialise le tableau des recettes favorites si ce n'est pas déjà fait
if (!isset($_SESSION['favorites'])) {
    $_SESSION['favorites'] = [];
}

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username']; 

    // Récupère la photo de profil de l'utilisateur
    $query = "SELECT photo_path FROM utilisateurs WHERE username = '$username'";
    $result = $mysqli->query($query);

    if ($result && $row = $result->fetch_assoc()) {
    $_SESSION['photo'] = !empty($row['photo_path']) && ($row['photo_path'] != "Photos/default-photo.png") ? "Photos/uploads/" . $row['photo_path'] : 'Photos/default-photo.png';
}
    
     $user_id_query = "SELECT id FROM utilisateurs WHERE username = '$username'";
    $result = $mysqli->query($user_id_query);
    if ($result && $user = $result->fetch_assoc()) {
        $user_id = $user['id'];

        // Charger les favoris depuis la base
        $query = "SELECT recette_id FROM recettes_favorites WHERE utilisateur_id = $user_id";
        $favorites_result = $mysqli->query($query);
        if ($favorites_result) {
            while ($row = $favorites_result->fetch_assoc()) {
                $_SESSION['favorites'][$row['recette_id']] = true;
            }
        }
    }
}


// Gère l'ajout ou la suppression d'une recette des favoris
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['recette_id'])) {
    $recette_id = intval($_POST['recette_id']); // Assure que l'ID est un entier

    if (isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
        $user_id_query = "SELECT id FROM utilisateurs WHERE username = '$username'";
        $result = $mysqli->query($user_id_query);
        if ($result && $user = $result->fetch_assoc()) {
            $user_id = $user['id'];

            if ($_POST['action'] === 'add') {
                $_SESSION['favorites'][$recette_id] = true;
                $mysqli->query("INSERT IGNORE INTO recettes_favorites (utilisateur_id, recette_id) VALUES ($user_id, $recette_id)");
            } elseif ($_POST['action'] === 'remove') {
                unset($_SESSION['favorites'][$recette_id]);
                $mysqli->query("DELETE FROM recettes_favorites WHERE utilisateur_id = $user_id AND recette_id = $recette_id");
            }
        }
    } else {
        if ($_POST['action'] === 'add') {
            $_SESSION['favorites'][$recette_id] = true;
        } elseif ($_POST['action'] === 'remove') {
            unset($_SESSION['favorites'][$recette_id]);
        }
    }

    // Recharge la page pour éviter un double traitement du formulaire
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit();
}

// Fonction pour afficher le chemin hiérarchique des aliments
function afficher_chemin($mysqli, $aliment) {
    $chemin = [];
    while ($aliment) {
        // Requête pour obtenir l'aliment courant et sa super-catégorie
        $query = "SELECT a.nom AS aliment, s.nom AS super_categorie
                  FROM aliments a
                  LEFT JOIN hierarchie h ON a.id = h.aliment_id
                  LEFT JOIN aliments s ON h.categorie_id = s.id AND h.type_relation = 'super'
                  WHERE a.nom = '$aliment'";
        $result = $mysqli->query($query);

        if ($row = $result->fetch_assoc()) {
            // Ajoute l'aliment au chemin
            $chemin[] = "<a href='?aliment=" . urlencode($row['aliment']) . "'>" . htmlspecialchars($row['aliment']) . "</a>";
            $aliment = $row['super_categorie']; // Passe à la super-catégorie
        } else {
            break;
        }	
    }

    // Affiche le chemin sous forme de liens cliquables
    echo "<div class='chemin'><strong><h3>Aliment courant :</h3></strong><br>" . implode(" / ", array_reverse($chemin)) . "</div>";
}

// Fonction pour afficher les sous-catégories d'un aliment
function afficher_sous_categories($mysqli, $aliment) {
echo"<div class='chemin-sous-categories fixed-size'>";
afficher_chemin($mysqli, $aliment);
    // Requête pour obtenir les sous-catégories de l'aliment
    $query = "SELECT a.nom AS sous_categorie
              FROM aliments a
              LEFT JOIN hierarchie h ON a.id = h.categorie_id
              WHERE h.aliment_id = (SELECT id FROM aliments WHERE nom = '$aliment') AND h.type_relation = 'sous'";
    $result = $mysqli->query($query);

    echo "<div class='sous-categories'><h3>Sous-catégories :</h3><ul>";
    while ($row = $result->fetch_assoc()) {
        // Affiche chaque sous-catégorie sous forme de lien
        echo "<li><a href='?aliment=" . urlencode($row['sous_categorie']) . "'>" . htmlspecialchars($row['sous_categorie']) . "</a></li>";
    }
    echo "</ul></div>";
    echo"</div>";
}

// Fonction pour afficher les recettes associées à un aliment
function afficher_recettes($mysqli, $aliment) {
    // Requête pour obtenir les recettes contenant l'aliment
    $query = "
        SELECT r.id AS recette_id, r.titre, r.preparation, p.chemin_photo
        FROM recettes r
        LEFT JOIN photos p ON r.id = p.recette_id
        WHERE r.index_aliments LIKE '%$aliment%'
        GROUP BY r.id
    ";
    $result = $mysqli->query($query);

    echo "<div class='recettes'>";
    while ($row = $result->fetch_assoc()) {
        $recette_id = $row['recette_id']; 
        $est_favorite = isset($_SESSION['favorites'][$recette_id]); // Vérifie si la recette est dans les favoris
        $coeur = $est_favorite ? "❤️" : "🤍"; 

        echo "<div class='recette'>";
        // Formulaire pour ajouter/supprimer une recette des favoris
        echo "<form method='POST'>
                <input type='hidden' name='recette_id' value='$recette_id'>
                <button type='submit' name='action' value='" . ($est_favorite ? 'remove' : 'add')."'>$coeur</button>
              </form>";

        echo "<h2 class='titre-recette'>";
        echo htmlspecialchars($row['titre']);
        echo "</h2>";      

        // Affiche la photo de la recette ou une image par défaut si absente
        echo "<div class='photo'>";
        echo !empty($row['chemin_photo']) && file_exists($row['chemin_photo']) 
            ? "<img src='" . htmlspecialchars($row['chemin_photo']) . "' alt='Photo de " . htmlspecialchars($row['titre']) . "'>"
            : "<img src='Photos/photo_non_trouver.jpg' alt='Photo non disponible'>";
        echo "</div>";

        // Affiche la préparation de la recette
        echo "<p><strong>Préparation :</strong> " . htmlspecialchars($row['preparation']) . "</p>";
        echo "</div>";
    }
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recettes</title>
    <link rel="stylesheet" type="text/css" href="styles/afficher_recettes.css">
</head>
<body>

    <header> 
        <nav class="alignement_des_btns">
            <a href="mes_recettes_favorites.php" target="_blank" class="btn-favorites">Recettes ❤️</a>
            <form action="resultats_recherche.php" method="get" class="form-research" onsubmit="return false;" style="position: relative;">
                <input type="text" id="search-input" name="query" placeholder="Rechercher..." class="input-research" onkeyup="fetchSuggestions(this.value)">
                <div id="suggestions" class="suggestions-list"></div>
                <button type="submit" class="btn-research" onclick="performSearch()">Recherche</button>
            </form>

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
        <?php
        afficher_sous_categories($mysqli, $aliment);
        afficher_recettes($mysqli, $aliment);
        ?>
    </div>
    <script src="scripts/rechercher.js"></script>
    <script>
        // Fonction pour afficher/masquer le menu de profil
        function toggleProfilMenu() {
            var menu = document.getElementById("profil-menu");
            // Si le menu est déjà visible, on le cache
            if (menu.style.display === "block") {
                menu.style.display = "none";
            } else {
                // Sinon, on l'affiche
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

