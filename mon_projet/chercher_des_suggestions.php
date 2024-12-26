<?php
$mysqli = mysqli_connect('127.0.0.1', 'root', '', 'ProjetRecettes') or die("Erreur de connexion à MySQL");

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if ($query) {
    $stmt = $mysqli->prepare("
        SELECT titre 
        FROM recettes 
        WHERE titre LIKE ? 
        LIMIT 10
    ");
    $like_query = "%{$query}%";
    $stmt->bind_param("s", $like_query);
    $stmt->execute();
    $result = $stmt->get_result();

    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row;
    }

    echo json_encode($suggestions);
    $stmt->close();
}
$mysqli->close();
?>

