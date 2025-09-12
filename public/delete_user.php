<?php
// Connexion à la base
require_once __DIR__ . '/../config/database.php';

// Authentification
require_once __DIR__ . '/../includes/auth.php';

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Vérifier que l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID utilisateur manquant !");
}

$user_id = intval($_GET['id']);

// Supprimer utilisateur
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$user_id]);

// Redirection avec message de succès
header("Location: admin_dashboard.php?msg=Utilisateur supprimé avec succès");
exit();
