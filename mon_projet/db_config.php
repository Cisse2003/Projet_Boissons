<?php
// Informations de connexion
$host = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'ProjetRecettes';

// Connexion à la base de données
$mysqli = mysqli_connect($host, $username, $password, $database);

// Vérification de la connexion
if (!$mysqli) {
    die("Erreur de connexion à MySQL : " . mysqli_connect_error());
}
?>
