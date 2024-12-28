<?php
// Fonction pour exécuter les requêtes avec gestion des erreurs
function query($link, $requete) {
    $resultat = mysqli_query($link, $requete) or die("$requete : " . mysqli_error($link));
    return $resultat;
}

$mysqli = mysqli_connect('127.0.0.1', 'root', '') or die("Erreur de connexion à MySQL");
$base = "ProjetRecettes";

// Création de la base de données et des tables
$Sql = "
    DROP DATABASE IF EXISTS $base;
    CREATE DATABASE $base;
    USE $base;

    CREATE TABLE aliments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL UNIQUE
    );

    CREATE TABLE hierarchie (
        aliment_id INT,
        type_relation ENUM('super', 'sous') NOT NULL,
        categorie_id INT,
        FOREIGN KEY (aliment_id) REFERENCES aliments(id),
        FOREIGN KEY (categorie_id) REFERENCES aliments(id)
    );

    CREATE TABLE recettes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titre VARCHAR(255) NOT NULL,
        preparation TEXT,
        index_aliments TEXT
    );

    CREATE TABLE ingredients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recette_id INT,
        aliment_id INT,
        quantite VARCHAR(50),
        unite VARCHAR(50),
        FOREIGN KEY (recette_id) REFERENCES recettes(id),
        FOREIGN KEY (aliment_id) REFERENCES aliments(id)
    );

    CREATE TABLE photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recette_id INT,
        chemin_photo VARCHAR(255),
        FOREIGN KEY (recette_id) REFERENCES recettes(id)
    );

    CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    sexe ENUM('M', 'F', 'Autre') DEFAULT NULL,
    email VARCHAR(255) UNIQUE,
    date_naissance DATE DEFAULT NULL,
    adresse VARCHAR(255) DEFAULT NULL,
    code_postal VARCHAR(10) DEFAULT NULL,
    ville VARCHAR(100) DEFAULT NULL,
    telephone VARCHAR(20) DEFAULT NULL,
    photo_path VARCHAR(255) DEFAULT 'Photos/default-photo.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


    CREATE TABLE recettes_favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        utilisateur_id INT NOT NULL,
        recette_id INT NOT NULL,
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
        FOREIGN KEY (recette_id) REFERENCES recettes(id) ON DELETE CASCADE
    );

";

// Exécution des requêtes SQL pour créer la base et les tables
foreach (explode(';', $Sql) as $Requete) {
    if (trim($Requete)) {
        query($mysqli, $Requete);
    }
}

// Inclusion des données depuis Donnees.inc.php
include 'Donnees.inc.php';

// Insérer les aliments dans la table aliments et définir la hiérarchie des super-catégories et sous-catégories
$aliment_ids = [];
foreach ($Hierarchie as $aliment => $details) {
    // Insertion de l'aliment
    $stmt = $mysqli->prepare("INSERT INTO aliments (nom) VALUES (?)");
    $stmt->bind_param("s", $aliment);
    $stmt->execute();
    $aliment_id = $stmt->insert_id;
    $aliment_ids[$aliment] = $aliment_id;  
    $stmt->close();
}

// Insertion des relations hiérarchiques pour les sous-catégories et super-catégories
foreach ($Hierarchie as $aliment => $details) {
    $aliment_id = $aliment_ids[$aliment];

    // Insertion des super-catégories dans la table hierarchie
    if (!empty($details['super-categorie'])) {
        foreach ($details['super-categorie'] as $super) {
            if (isset($aliment_ids[$super])) {
                $super_id = $aliment_ids[$super];
                $stmt = $mysqli->prepare("INSERT INTO hierarchie (aliment_id, type_relation, categorie_id) VALUES (?, 'super', ?)");
                $stmt->bind_param("ii", $aliment_id, $super_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Insertion des sous-catégories dans la table hierarchie
    if (!empty($details['sous-categorie'])) {
        foreach ($details['sous-categorie'] as $sous) {
            if (isset($aliment_ids[$sous])) {
                $sous_id = $aliment_ids[$sous];
                $stmt = $mysqli->prepare("INSERT INTO hierarchie (aliment_id, type_relation, categorie_id) VALUES (?, 'sous', ?)");
                $stmt->bind_param("ii", $aliment_id, $sous_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

// Insérer les recettes et leurs ingrédients
foreach ($Recettes as $recette) {
    // Insertion de la recette
    $stmt = $mysqli->prepare("INSERT INTO recettes (titre, preparation, index_aliments) VALUES (?, ?, ?)");
    $titre = $recette['titre'];
    $preparation = $recette['preparation'];
    $index_aliments = implode('|', $recette['index']);  // Conversion du tableau en chaîne
    $stmt->bind_param("sss", $titre, $preparation, $index_aliments);
    $stmt->execute();
    $recette_id = $stmt->insert_id;
    $stmt->close();

    // Associer les ingrédients à la recette
    $ingredients = explode('|', $recette['ingredients']); // Liste des quantités, unités et descriptions
    $index = $recette['index']; 
    if (count($ingredients) !== count($index)) {
        echo "Erreur entre ingrédients et index pour la recette : {$recette['titre']}<br>";
    } else {
        foreach ($index as $i => $nom_aliment) { // Parcourir les aliments par index
            $ingredient = trim($ingredients[$i]);
    
            // Diviser l'ingrédient pour extraire quantité, unité et nom
            $parts = explode(' ', $ingredient, 3); // Diviser en 3 parties : quantité, unité, reste
            $quantite = isset($parts[0]) ? $parts[0] : null;
            $unite = isset($parts[1]) ? $parts[1] : null;
            // Trouver l'aliment dans la hiérarchie
            $found = false;
            foreach ($aliment_ids as $nom_hierarchie => $id) {
                if ($nom_hierarchie === $nom_aliment) {
                    $aliment_id = $id;
                    $found = true;
                    break;
                }
            }
    
            if ($found) {
                // Insertion dans la table ingredients
                $stmt = $mysqli->prepare("INSERT INTO ingredients (recette_id, aliment_id, quantite, unite) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $recette_id, $aliment_id, $quantite, $unite);
                $stmt->execute();
                $stmt->close();
            } else {
                echo "Aliment inconnu : $nom_aliment<br>";
            }
        }
    }

    // Insertion de la photo associée (si disponible)
    $photo_chemin = "../Photos/" . str_replace(' ', '_', $titre) . ".jpg";
    if (!file_exists($photo_chemin)) {
        $photo_chemin = "Photos/photo_non_trouver.jpg";
    }else{
    	$photo_chemin = "Photos/" . str_replace(' ', '_', $titre) . ".jpg";
    }
    $stmt = $mysqli->prepare("INSERT INTO photos (recette_id, chemin_photo) VALUES (?, ?)");
    $stmt->bind_param("is", $recette_id, $photo_chemin);
    $stmt->execute();
    $stmt->close();
    
}

echo "Base de données, tables et données initialisées avec succès.";
$result = $mysqli->query("SELECT DATABASE()");
$row = $result->fetch_row();

$mysqli->close();
?>

