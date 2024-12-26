<?php
session_start();

$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') or die("Erreur de connexion à MySQL");

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; // L'utilisateur doit être connecté pour ajouter aux favoris

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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats de la recherche</title>
    <link rel="stylesheet" type="text/css" href="styles/affichage_recettes.css">
</head>
<body>
    <header>
        <nav class="alignement_des_btns">
            <a href="mes_recettes_favorites.php" class="btn-favorites">Recettes ❤️</a>
            <a href="utilisateurs/connexion.php" class="btn-connexion">Connexion</a>
        </nav>
    </header>
    
    <div class="recherche-container">
        <form method="GET" action="resultats_recherche.php">
            <input type="text" name="query" placeholder="Rechercher une recette..." required>
            <button type="submit" class="search-button">Recherche</button>
        </form>
    </div>

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
</body>
</html>

