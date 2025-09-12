<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit;
}

$enseignant_id = $_SESSION['user_id'];

// Utiliser GET au lieu de POST
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $cours_id = intval($_GET['id']);

    // Vérifier que le cours appartient à l'enseignant
    $stmt = $pdo->prepare("SELECT image_couverture, video_cours, pdf_cours FROM cours WHERE id = ? AND enseignant_id = ?");
    $stmt->execute([$cours_id, $enseignant_id]);
    $cours = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cours) {
        die("Cours non trouvé ou accès refusé.");
    }

    // Supprimer fichiers uploadés
    foreach (['image_couverture', 'video_cours', 'pdf_cours'] as $fileCol) {
        if (!empty($cours[$fileCol]) && file_exists(__DIR__ . '/' . $cours[$fileCol])) {
            unlink(__DIR__ . '/' . $cours[$fileCol]);
        }
    }

    // Supprimer le cours en base
    $stmtDelete = $pdo->prepare("DELETE FROM cours WHERE id = ? AND enseignant_id = ?");
    $stmtDelete->execute([$cours_id, $enseignant_id]);

    // Redirection vers liste des cours
    header("Location: cours.php?deleted=1");
    exit;
} else {
    die("Requête invalide.");
}
