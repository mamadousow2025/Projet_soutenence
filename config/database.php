<?php
$host = "localhost";
$dbname = "lms_isep"; 
$user = "root";  // à adapter le nom d'utilisateur
$password = "";  // à adapter mot de passe pour la base de données

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    // Mode erreur en exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
