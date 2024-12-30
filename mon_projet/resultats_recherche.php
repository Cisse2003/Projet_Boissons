<?php
session_start();

$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') 
    or die("Erreur de connexion à MySQL");

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Initialisation des favoris
if (!isset($_SESSION['favorites'])) {
    $_SESSION['favorites'] = [];
}

// Gestion des favoris
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

// Traitement de la requête AJAX pour l'autocomplétion
if (isset($_GET['autocomplete'])) {
    $query = trim($_GET['autocomplete']);
    $stmt = $mysqli->prepare("SELECT nom FROM aliments WHERE nom LIKE ? LIMIT 10");
    $like_query = $query . '%';
    $stmt->bind_param("s", $like_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['nom'];
    }
    echo json_encode($suggestions);
    exit();
}

// Récupération des recettes
$nom_recette = isset($_GET['query']) ? trim($_GET['query']) : null;
$recettes = [];
if ($nom_recette) {
    $query = "
        SELECT r.id AS recette_id, r.titre, r.preparation, p.chemin_photo,
            (SELECT GROUP_CONCAT(a.nom SEPARATOR ', ') 
             FROM ingredients i 
             JOIN aliments a ON i.aliment_id = a.id 
             WHERE i.recette_id = r.id) AS aliments
        FROM recettes r
        LEFT JOIN photos p ON r.id = p.recette_id
        WHERE r.titre LIKE ? OR r.index_aliments LIKE ?
    ";
    $stmt = $mysqli->prepare($query);
    $like_nom_recette = "%" . $nom_recette . "%";
    $stmt->bind_param("ss", $like_nom_recette, $like_nom_recette);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['score'] = calculate_score($nom_recette, $row['aliments']);
        $recettes[] = $row;
    }

    // Trier les recettes par pertinence
    usort($recettes, function ($a, $b) {
        return $b['score'] - $a['score'];
    });
}

// Fonction pour calculer un score de pertinence
function calculate_score($query, $aliments) {
    $query_terms = explode(' ', strtolower($query));
    $aliments_terms = explode(',', strtolower($aliments));

    $score = 0;
    foreach ($query_terms as $term) {
        foreach ($aliments_terms as $aliment) {
            if (strpos($aliment, trim($term)) !== false) {
                $score++;
            }
        }
    }
    return $score;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats de la recherche</title>
    <link rel="stylesheet" type="text/css" href="styles/styles_affiche_recettes.css">
    <script>
        function autocomplete() {
            const query = document.getElementById("query").value;
            if (query.length > 1) {
                fetch(`resultats_recherche.php?autocomplete=${query}`)
                    .then(response => response.json())
                    .then(data => {
                        const dropdown = document.getElementById("autocomplete-list");
                        dropdown.innerHTML = '';
                        data.forEach(item => {
                            const option = document.createElement("div");
                            option.innerHTML = item;
                            option.onclick = function () {
                                document.getElementById("query").value = item;
                                dropdown.innerHTML = '';
                            };
                            dropdown.appendChild(option);
                        });
                    });
            }
        }
    </script>
</head>
<body>
    <header>
        <nav class="alignement_des_btns">
            <a href="mes_recettes_favorites.php" class="btn-favorites">Recettes ❤️</a>
            <div class="recherche-container">
                <form method="GET" action="resultats_recherche.php">
                    <input type="text" id="query" name="query" placeholder="Rechercher une recette..." onkeyup="autocomplete()" required>
                    <button type="submit" class="search-button">Recherche</button>
                </form>
                <div id="autocomplete-list" class="autocomplete-list"></div>
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
                        <p><strong>Ingrédients :</strong> <?= htmlspecialchars($recette['aliments']) ?></p>
                        <p><strong>Score de pertinence :</strong> <?= $recette['score'] ?></p>
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
        function toggleProfilMenu() {
            const menu = document.getElementById("profil-menu");
            menu.style.display = menu.style.display === "block" ? "none" : "block";
        }
        window.onclick = function(event) {
            const menu = document.getElementById("profil-menu");
            const photoProfil = document.getElementById("photo-profil");
            if (!photoProfil.contains(event.target) && !menu.contains(event.target)) {
                menu.style.display = "none";
            }
        }
    </script>
</body>
</html>

