<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérification connexion et rôle
if (!isLoggedIn() || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3)) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

if (!isset($_GET['cours_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'error' => 'Paramètre cours_id manquant']);
    exit();
}

$cours_id = intval($_GET['cours_id']);
$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role_id'] == 3);

// Vérifier que l'utilisateur a accès à ce cours
if (!$is_admin) {
    $stmt = $pdo->prepare("SELECT id FROM cours WHERE id = ? AND enseignant_id = ?");
    $stmt->execute([$cours_id, $user_id]);
    if (!$stmt->fetch()) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['success' => false, 'error' => 'Accès non autorisé à ce cours']);
        exit();
    }
}

// Récupérer le dernier ordre utilisé pour ce cours
$stmt = $pdo->prepare("SELECT MAX(ordre) as last_order FROM modules WHERE cours_id = ?");
$stmt->execute([$cours_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'last_order' => $result['last_order'] ? intval($result['last_order']) : 0
]);