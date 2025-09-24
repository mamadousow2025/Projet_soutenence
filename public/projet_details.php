<?php
session_start();
require_once '../config/database.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Attribution du rôle en fonction de l'ID utilisateur
switch ($_SESSION['role_id']) {
    case 1:
        $_SESSION['role'] = 'etudiant';
        break;
    case 2:
        $_SESSION['role'] = 'enseignant';
        break;
    case 3:
        $_SESSION['role'] = 'admin';
        break;
    default:
        $_SESSION['role'] = 'inconnu';
        break;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Vérifier si l'ID du projet est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: projet.php');
    exit();
}

$projet_id = (int)$_GET['id'];

// Récupérer les détails du projet
$stmt = $pdo->prepare("
    SELECT p.*, u.nom as enseignant_nom, u.prenom as enseignant_prenom, 
           u.email as enseignant_email, f.nom as filiere_nom, f.description as filiere_description
    FROM projets p
    JOIN users u ON p.enseignant_id = u.id
    LEFT JOIN filieres f ON p.filiere_id = f.id
    WHERE p.id = ?
");
$stmt->execute([$projet_id]);
$projet = $stmt->fetch();

if (!$projet) {
    header('Location: projet.php');
    exit();
}

// Vérifier les permissions d'accès
if ($role == 'etudiant') {
    // Vérifier si l'étudiant a accès à ce projet (soit assigné directement, soit via sa filière)
    $stmt = $pdo->prepare("
        SELECT u.filiere_id 
        FROM users u 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as access_count
        FROM projet_etudiants pe
        WHERE pe.projet_id = ? AND pe.etudiant_id = ?
        UNION ALL
        SELECT COUNT(*) as access_count
        FROM projets p
        WHERE p.id = ? AND p.filiere_id = ?
    ");
    $stmt->execute([$projet_id, $user_id, $projet_id, $user_info['filiere_id'] ?? 0]);
    $access_results = $stmt->fetchAll();
    
    $has_access = false;
    foreach ($access_results as $result) {
        if ($result['access_count'] > 0) {
            $has_access = true;
            break;
        }
    }
    
    if (!$has_access) {
        header('Location: projet.php');
        exit();
    }
}

// Récupérer les fichiers du projet
$stmt = $pdo->prepare("
    SELECT * FROM projet_fichiers 
    WHERE projet_id = ? 
    ORDER BY type_fichier, date_upload DESC
");
$stmt->execute([$projet_id]);
$fichiers = $stmt->fetchAll();

// Organiser les fichiers par type
$fichiers_par_type = [
    'image' => [],
    'video' => [],
    'document' => []
];

foreach ($fichiers as $fichier) {
    $type = $fichier['type_fichier'] ?? 'document';
    if (isset($fichiers_par_type[$type])) {
        $fichiers_par_type[$type][] = $fichier;
    }
}

// Récupérer les liens du projet
$stmt = $pdo->prepare("
    SELECT * FROM projet_liens 
    WHERE projet_id = ? 
    ORDER BY date_ajout DESC
");
$stmt->execute([$projet_id]);
$liens = $stmt->fetchAll();

// Récupérer les tâches du projet
$stmt = $pdo->prepare("
    SELECT * FROM taches 
    WHERE projet_id = ? 
    ORDER BY priorite DESC, date_limite ASC, date_creation ASC
");
$stmt->execute([$projet_id]);
$taches = $stmt->fetchAll();

// Si c'est un étudiant, récupérer ses livrables
$mes_livrables = [];
if ($role == 'etudiant') {
    $stmt = $pdo->prepare("
        SELECT l.*, t.titre as tache_titre 
        FROM livrables l
        JOIN taches t ON l.tache_id = t.id
        WHERE l.etudiant_id = ? AND t.projet_id = ?
        ORDER BY l.date_soumission DESC
    ");
    $stmt->execute([$user_id, $projet_id]);
    $mes_livrables = $stmt->fetchAll();
}

// Variables pour les messages
$success_message = '';
$error_message = '';

// Traitement de soumission de livrable
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['soumettre_livrable']) && $role == 'etudiant') {
    $tache_id = (int)$_POST['tache_id'];
    $commentaire = trim($_POST['commentaire'] ?? '');
    
    try {
        // Vérifier que la tâche appartient bien à ce projet
        $stmt = $pdo->prepare("SELECT * FROM taches WHERE id = ? AND projet_id = ?");
        $stmt->execute([$tache_id, $projet_id]);
        $tache = $stmt->fetch();
        
        if (!$tache) {
            throw new Exception("Tâche non trouvée ou non autorisée");
        }
        
        // Vérifier si l'étudiant a déjà soumis un livrable pour cette tâche
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM livrables WHERE tache_id = ? AND etudiant_id = ?");
        $stmt->execute([$tache_id, $user_id]);
        $existing_count = $stmt->fetch()['count'];
        
        if ($existing_count > 0 && !isset($_POST['allow_resubmit'])) {
            throw new Exception("Vous avez déjà soumis un livrable pour cette tâche. Utilisez la fonction de re-soumission si nécessaire.");
        }
        
        // Gérer l'upload de fichier si présent
        $fichier_chemin = null;
        $fichier_nom = null;
        $fichier_taille = 0;
        
        if (isset($_FILES['fichier_livrable']) && $_FILES['fichier_livrable']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/livrables/' . $projet_id . '/' . $user_id . '/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    throw new Exception("Impossible de créer le dossier de destination");
                }
            }
            
            $file_extension = strtolower(pathinfo($_FILES['fichier_livrable']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', '7z', 'jpg', 'jpeg', 'png', 'gif', 'ppt', 'pptx', 'xls', 'xlsx'];
            
            // Vérifier la taille du fichier (max 50MB)
            $max_size = 50 * 1024 * 1024; // 50MB
            if ($_FILES['fichier_livrable']['size'] > $max_size) {
                throw new Exception("Le fichier est trop volumineux. Taille maximum autorisée : 50MB");
            }
            
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception("Type de fichier non autorisé. Extensions acceptées : " . implode(', ', $allowed_extensions));
            }
            
            // Générer un nom de fichier unique et sécurisé
            $fichier_nom = date('Y-m-d_H-i-s') . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['fichier_livrable']['name']);
            $fichier_chemin = $upload_dir . $fichier_nom;
            $fichier_taille = $_FILES['fichier_livrable']['size'];
            
            if (!move_uploaded_file($_FILES['fichier_livrable']['tmp_name'], $fichier_chemin)) {
                throw new Exception("Erreur lors de l'upload du fichier");
            }
        }
        
        // Si c'est une re-soumission, marquer l'ancien livrable comme remplacé
        if ($existing_count > 0) {
            $stmt = $pdo->prepare("UPDATE livrables SET statut = 'remplace' WHERE tache_id = ? AND etudiant_id = ? AND statut != 'remplace'");
            $stmt->execute([$tache_id, $user_id]);
        }
        
        // Insérer le nouveau livrable en base
        $stmt = $pdo->prepare("
            INSERT INTO livrables (tache_id, etudiant_id, fichier_nom, fichier_chemin, fichier_taille, commentaire, date_soumission, statut) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'soumis')
        ");
        $stmt->execute([$tache_id, $user_id, $fichier_nom, $fichier_chemin, $fichier_taille, $commentaire]);
        
        $success_message = "Livrable soumis avec succès ! Votre enseignant sera notifié de cette soumission.";
        
        // Recharger les livrables
        $stmt = $pdo->prepare("
            SELECT l.*, t.titre as tache_titre 
            FROM livrables l
            JOIN taches t ON l.tache_id = t.id
            WHERE l.etudiant_id = ? AND t.projet_id = ?
            ORDER BY l.date_soumission DESC
        ");
        $stmt->execute([$user_id, $projet_id]);
        $mes_livrables = $stmt->fetchAll();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        
        // Supprimer le fichier si l'upload a réussi mais l'insertion en base a échoué
        if (isset($fichier_chemin) && file_exists($fichier_chemin)) {
            unlink($fichier_chemin);
        }
    }
}

// Traitement de suppression de livrable
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['supprimer_livrable']) && $role == 'etudiant') {
    $livrable_id = (int)$_POST['livrable_id'];
    
    try {
        // Vérifier que le livrable appartient à l'étudiant
        $stmt = $pdo->prepare("
            SELECT l.*, t.projet_id 
            FROM livrables l
            JOIN taches t ON l.tache_id = t.id
            WHERE l.id = ? AND l.etudiant_id = ? AND t.projet_id = ?
        ");
        $stmt->execute([$livrable_id, $user_id, $projet_id]);
        $livrable = $stmt->fetch();
        
        if (!$livrable) {
            throw new Exception("Livrable non trouvé ou non autorisé");
        }
        
        // Vérifier que le livrable peut être supprimé (pas encore validé)
        if ($livrable['statut'] == 'valide') {
            throw new Exception("Impossible de supprimer un livrable déjà validé");
        }
        
        // Supprimer le fichier physique
        if (!empty($livrable['fichier_chemin']) && file_exists($livrable['fichier_chemin'])) {
            unlink($livrable['fichier_chemin']);
        }
        
        // Supprimer l'enregistrement de la base de données
        $stmt = $pdo->prepare("DELETE FROM livrables WHERE id = ?");
        $stmt->execute([$livrable_id]);
        
        $success_message = "Livrable supprimé avec succès.";
        
        // Recharger les livrables
        $stmt = $pdo->prepare("
            SELECT l.*, t.titre as tache_titre 
            FROM livrables l
            JOIN taches t ON l.tache_id = t.id
            WHERE l.etudiant_id = ? AND t.projet_id = ?
            ORDER BY l.date_soumission DESC
        ");
        $stmt->execute([$user_id, $projet_id]);
        $mes_livrables = $stmt->fetchAll();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Calculer les statistiques du projet
$stats_taches = [
    'total' => count($taches),
    'a_faire' => 0,
    'en_cours' => 0,
    'termine' => 0
];

foreach ($taches as $tache) {
    $statut = $tache['statut'] ?? 'a_faire';
    if (isset($stats_taches[$statut])) {
        $stats_taches[$statut]++;
    }
}

$progression = $stats_taches['total'] > 0 ? round(($stats_taches['termine'] / $stats_taches['total']) * 100, 1) : 0;

// Fonction pour formater la taille des fichiers
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($projet['titre'] ?? 'Détails du Projet') ?> - LMS ISEP</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c3e50;
            min-height: 100vh;
        }

        .main-header {
            background: linear-gradient(135deg, #009688 0%, #00695c 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 150, 136, 0.3);
            position: relative;
            overflow: hidden;
        }

        .main-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        }

        .main-header .container {
            position: relative;
            z-index: 2;
        }

        .project-title {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .project-meta {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .breadcrumb-nav {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 15px 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .breadcrumb {
            background: transparent;
            margin: 0;
        }

        .breadcrumb-item a {
            color: #FFE0B2;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb-item.active {
            color: white;
            font-weight: 600;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .content-card-header {
            background: linear-gradient(135deg, #009688 0%, #00695c 100%);
            color: white;
            padding: 25px 30px;
        }

        .content-card-header h4 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .content-card-header i {
            color: #FF9800;
            margin-right: 15px;
            font-size: 26px;
        }

        .content-card-body {
            padding: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 150, 136, 0.1);
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #009688, #FF9800);
        }

        .stat-card .icon {
            background: linear-gradient(135deg, #FF9800 0%, #f57c00 100%);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
            font-size: 24px;
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.3);
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: 800;
            color: #009688;
            margin-bottom: 5px;
            line-height: 1;
        }

        .stat-card .label {
            font-size: 14px;
            color: #546e7a;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .progress-bar-custom {
            height: 12px;
            border-radius: 10px;
            background: #e0e0e0;
            overflow: hidden;
            margin: 15px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #009688, #4CAF50);
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .task-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 5px solid;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .task-card.priority-haute {
            border-left-color: #f44336;
        }

        .task-card.priority-moyenne {
            border-left-color: #FF9800;
        }

        .task-card.priority-basse {
            border-left-color: #4CAF50;
        }

        .task-card.status-termine {
            opacity: 0.7;
            background: #e8f5e8;
        }

        .badge {
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .file-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .file-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .file-info {
            display: flex;
            align-items: center;
        }

        .file-icon {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 20px;
        }

        .file-icon.image { background: #4CAF50; }
        .file-icon.video { background: #FF9800; }
        .file-icon.document { background: #2196F3; }

        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #009688 0%, #00695c 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(0, 150, 136, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, #FF9800 0%, #f57c00 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
        }

        .btn-outline-primary {
            border: 2px solid #009688;
            color: #009688;
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: #009688;
            color: white;
        }

        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #009688;
            box-shadow: 0 0 0 0.2rem rgba(0, 150, 136, 0.25);
        }

        .form-label {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .form-label i {
            color: #FF9800;
            margin-right: 8px;
            font-size: 18px;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 20px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
            color: #2e7d32;
            border-left: 4px solid #4CAF50;
        }

        .alert-danger {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .alert i {
            color: #FF9800;
            margin-right: 10px;
            font-size: 18px;
        }

        .nav-tabs {
            border: none;
            margin-bottom: 30px;
            background: white;
            border-radius: 15px;
            padding: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .nav-tabs .nav-link {
            background: transparent;
            border: none;
            color: #546e7a;
            margin: 0 5px;
            border-radius: 10px;
            padding: 15px 25px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            background: rgba(0, 150, 136, 0.1);
            color: #009688;
        }

        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #009688 0%, #00695c 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 150, 136, 0.3);
        }

        .nav-tabs .nav-link i {
            color: #FF9800;
            margin-right: 10px;
            font-size: 18px;
        }

        .nav-tabs .nav-link.active i {
            color: #FFE0B2;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, #009688 0%, #00695c 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px 30px;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .modal-body {
            padding: 30px;
        }

        .livrable-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .livrable-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .livrable-card.status-soumis {
            border-left: 5px solid #FF9800;
            background: #fff3e0;
        }

        .livrable-card.status-valide {
            border-left: 5px solid #4CAF50;
            background: #e8f5e8;
        }

        .livrable-card.status-refuse {
            border-left: 5px solid #f44336;
            background: #ffebee;
        }

        .livrable-card.status-remplace {
            border-left: 5px solid #9E9E9E;
            background: #f5f5f5;
            opacity: 0.7;
        }

        .file-upload-area {
            border: 2px dashed #009688;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-area:hover {
            background: #e8f5e8;
            border-color: #00695c;
        }

        .file-upload-area.dragover {
            background: #e8f5e8;
            border-color: #4CAF50;
        }

        .submission-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .submission-info {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #2196F3;
        }

        @media (max-width: 768px) {
            .project-title {
                font-size: 28px;
            }
            
            .content-card-body {
                padding: 20px;
            }
            
            .stat-card {
                margin-bottom: 15px;
            }

            .submission-actions {
                flex-direction: column;
            }

            .submission-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header principal -->
        <div class="main-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="project-title">
                            <i class="fas fa-project-diagram" style="color: #FF9800; margin-right: 20px;"></i>
                            <?= htmlspecialchars($projet['titre'] ?? 'Projet sans titre') ?>
                        </h1>
                        <div class="project-meta">
                            <i class="fas fa-user-tie" style="color: #FFE0B2; margin-right: 10px;"></i>
                            Par <?= htmlspecialchars(($projet['enseignant_prenom'] ?? '') . ' ' . ($projet['enseignant_nom'] ?? '')) ?>
                            <span class="mx-3">|</span>
                            <i class="fas fa-graduation-cap" style="color: #FFE0B2; margin-right: 10px;"></i>
                            <?= htmlspecialchars($projet['filiere_nom'] ?? 'Filière non spécifiée') ?>
                            <span class="mx-3">|</span>
                            <i class="fas fa-calendar" style="color: #FFE0B2; margin-right: 10px;"></i>
                            Créé le <?= date('d/m/Y', strtotime($projet['date_creation'] ?? 'now')) ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="breadcrumb-nav">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="projet.php">
                                            <i class="fas fa-home"></i> Dashboard
                                        </a>
                                    </li>
                                    <li class="breadcrumb-item active">
                                        <i class="fas fa-eye"></i> Détails du Projet
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- Messages -->
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i>
                <strong>Succès :</strong> <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Erreur :</strong> <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Statistiques du projet -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="number"><?= $stats_taches['total'] ?></div>
                        <div class="label">Tâches Total</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="number"><?= $stats_taches['a_faire'] ?></div>
                        <div class="label">À Faire</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="number"><?= $stats_taches['en_cours'] ?></div>
                        <div class="label">En Cours</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="number"><?= $stats_taches['termine'] ?></div>
                        <div class="label">Terminé</div>
                    </div>
                </div>
            </div>

            <!-- Barre de progression -->
            <div class="content-card">
                <div class="content-card-header">
                    <h4><i class="fas fa-chart-line"></i>Progression du Projet</h4>
                </div>
                <div class="content-card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold fs-5">Avancement Global</span>
                        <span class="badge bg-primary fs-6"><?= $progression ?>% Complété</span>
                    </div>
                    <div class="progress-bar-custom">
                        <div class="progress-fill" style="width: <?= $progression ?>%"></div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-4 text-center">
                            <canvas id="progressChart" width="150" height="150"></canvas>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-4">
                                    <div class="text-center">
                                        <div class="fs-3 fw-bold text-danger"><?= $stats_taches['a_faire'] ?></div>
                                        <div class="text-muted">À Faire</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-center">
                                        <div class="fs-3 fw-bold" style="color: #FF9800;"><?= $stats_taches['en_cours'] ?></div>
                                        <div class="text-muted">En Cours</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-center">
                                        <div class="fs-3 fw-bold text-success"><?= $stats_taches['termine'] ?></div>
                                        <div class="text-muted">Terminé</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation par onglets -->
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#description" data-bs-toggle="tab">
                        <i class="fas fa-info-circle"></i>Description
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#taches" data-bs-toggle="tab">
                        <i class="fas fa-tasks"></i>Tâches (<?= count($taches) ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#ressources" data-bs-toggle="tab">
                        <i class="fas fa-folder"></i>Ressources
                    </a>
                </li>
                <?php if ($role == 'etudiant'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#mes-livrables" data-bs-toggle="tab">
                        <i class="fas fa-upload"></i>Mes Livrables (<?= count($mes_livrables) ?>)
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Contenu des onglets -->
            <div class="tab-content">
                <!-- Description du projet -->
                <div class="tab-pane fade show active" id="description">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-file-alt"></i>Description Complète du Projet</h4>
                        </div>
                        <div class="content-card-body">
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="mb-4">
                                        <h5 class="text-primary mb-3">
                                            <i class="fas fa-align-left text-warning me-2"></i>
                                            Description Générale
                                        </h5>
                                        <p class="fs-6 lh-lg"><?= nl2br(htmlspecialchars($projet['description'] ?? 'Aucune description disponible.')) ?></p>
                                    </div>

                                    <?php if (!empty($projet['objectifs'])): ?>
                                    <div class="mb-4">
                                        <h5 class="text-primary mb-3">
                                            <i class="fas fa-bullseye text-warning me-2"></i>
                                            Objectifs Pédagogiques
                                        </h5>
                                        <div class="bg-light p-3 rounded">
                                            <?= nl2br(htmlspecialchars($projet['objectifs'])) ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($projet['criteres_evaluation'])): ?>
                                    <div class="mb-4">
                                        <h5 class="text-primary mb-3">
                                            <i class="fas fa-check-square text-warning me-2"></i>
                                            Critères d'Évaluation
                                        </h5>
                                        <div class="bg-light p-3 rounded">
                                            <?= nl2br(htmlspecialchars($projet['criteres_evaluation'])) ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($projet['competences_developpees'])): ?>
                                    <div class="mb-4">
                                        <h5 class="text-primary mb-3">
                                            <i class="fas fa-graduation-cap text-warning me-2"></i>
                                            Compétences Développées
                                        </h5>
                                        <div class="bg-light p-3 rounded">
                                            <?= nl2br(htmlspecialchars($projet['competences_developpees'])) ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($projet['ressources_necessaires'])): ?>
                                    <div class="mb-4">
                                        <h5 class="text-primary mb-3">
                                            <i class="fas fa-tools text-warning me-2"></i>
                                            Ressources Nécessaires
                                        </h5>
                                        <div class="bg-light p-3 rounded">
                                            <?= nl2br(htmlspecialchars($projet['ressources_necessaires'])) ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-lg-4">
                                    <div class="bg-light p-4 rounded">
                                        <h5 class="text-primary mb-3">
                                            <i class="fas fa-info-circle text-warning me-2"></i>
                                            Informations du Projet
                                        </h5>
                                        
                                        <div class="mb-3">
                                            <strong>Type de Projet :</strong><br>
                                            <span class="badge bg-secondary mt-1">
                                                <?php 
                                                $types = [
                                                    'cahier_charge' => 'Cahier des charges',
                                                    'sujet_pratique' => 'Sujet pratique',
                                                    'creation' => 'Création'
                                                ];
                                                echo $types[$projet['type_projet'] ?? 'inconnu'] ?? 'Inconnu';
                                                ?>
                                            </span>
                                        </div>

                                        <div class="mb-3">
                                            <strong>Statut :</strong><br>
                                            <span class="badge <?= 
                                                ($projet['statut'] ?? 'actif') == 'actif' ? 'bg-success' : 
                                                (($projet['statut'] ?? 'actif') == 'termine' ? 'bg-secondary' : 'bg-warning')
                                            ?> mt-1">
                                                <i class="fas fa-<?= 
                                                    ($projet['statut'] ?? 'actif') == 'actif' ? 'play' : 
                                                    (($projet['statut'] ?? 'actif') == 'termine' ? 'check' : 'pause')
                                                ?>"></i>
                                                <?= ucfirst($projet['statut'] ?? 'Actif') ?>
                                            </span>
                                        </div>

                                        <?php if (!empty($projet['date_limite'])): ?>
                                        <div class="mb-3">
                                            <strong>Date Limite :</strong><br>
                                            <span class="text-danger fw-bold">
                                                <i class="fas fa-calendar-times me-1"></i>
                                                <?= date('d/m/Y', strtotime($projet['date_limite'])) ?>
                                            </span>
                                            <?php 
                                            $jours_restants = ceil((strtotime($projet['date_limite']) - time()) / (60*60*24));
                                            if ($jours_restants > 0): ?>
                                                <br><small class="text-muted">
                                                    <i class="fas fa-hourglass-half"></i> <?= $jours_restants ?> jour(s) restant(s)
                                                </small>
                                            <?php elseif ($jours_restants == 0): ?>
                                                <br><small class="text-warning fw-bold">
                                                    <i class="fas fa-exclamation-triangle"></i> Échéance aujourd'hui !
                                                </small>
                                            <?php else: ?>
                                                <br><small class="text-danger fw-bold">
                                                    <i class="fas fa-exclamation-triangle"></i> Échéance dépassée !
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>

                                        <div class="mb-3">
                                            <strong>Enseignant Responsable :</strong><br>
                                            <i class="fas fa-user-tie text-primary me-2"></i>
                                            <?= htmlspecialchars(($projet['enseignant_prenom'] ?? '') . ' ' . ($projet['enseignant_nom'] ?? '')) ?>
                                            <?php if (!empty($projet['enseignant_email'])): ?>
                                            <br><small class="text-muted">
                                                <i class="fas fa-envelope me-1"></i>
                                                <a href="mailto:<?= htmlspecialchars($projet['enseignant_email']) ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($projet['enseignant_email']) ?>
                                                </a>
                                            </small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mb-3">
                                            <strong>Filière :</strong><br>
                                            <i class="fas fa-graduation-cap text-success me-2"></i>
                                            <?= htmlspecialchars($projet['filiere_nom'] ?? 'Non spécifiée') ?>
                                            <?php if (!empty($projet['filiere_description'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($projet['filiere_description']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tâches du projet -->
                <div class="tab-pane fade" id="taches">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-tasks"></i>Tâches du Projet (<?= count($taches) ?>)</h4>
                        </div>
                        <div class="content-card-body">
                            <?php if (!empty($taches)): ?>
                                <?php foreach ($taches as $tache): ?>
                                <div class="task-card priority-<?= $tache['priorite'] ?? 'basse' ?> status-<?= $tache['statut'] ?? 'a_faire' ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <h5 class="mb-2 fw-bold">
                                                <i class="fas fa-<?= 
                                                    ($tache['statut'] ?? 'a_faire') == 'termine' ? 'check-circle text-success' : 
                                                    (($tache['statut'] ?? 'a_faire') == 'en_cours' ? 'spinner text-warning' : 'clock text-danger')
                                                ?> me-2"></i>
                                                <?= htmlspecialchars($tache['titre'] ?? 'Tâche sans titre') ?>
                                            </h5>
                                            <p class="text-muted mb-3"><?= nl2br(htmlspecialchars($tache['description'] ?? 'Aucune description.')) ?></p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge <?= 
                                                ($tache['priorite'] ?? 'basse') == 'haute' ? 'bg-danger' : 
                                                (($tache['priorite'] ?? 'basse') == 'moyenne' ? 'bg-warning' : 'bg-success')
                                            ?> mb-2">
                                                <i class="fas fa-flag"></i> <?= ucfirst($tache['priorite'] ?? 'Basse') ?>
                                            </span>
                                            <br>
                                            <span class="badge <?= 
                                                ($tache['statut'] ?? 'a_faire') == 'termine' ? 'bg-success' : 
                                                (($tache['statut'] ?? 'a_faire') == 'en_cours' ? 'bg-warning' : 'bg-danger')
                                            ?>">
                                                <?= ucfirst(str_replace('_', ' ', $tache['statut'] ?? 'À faire')) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-plus me-1"></i>
                                                Créée le : <?= date('d/m/Y', strtotime($tache['date_creation'] ?? 'now')) ?>
                                            </small>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <?php if (!empty($tache['date_limite'])): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-times me-1"></i>
                                                Échéance : <?= date('d/m/Y', strtotime($tache['date_limite'])) ?>
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($role == 'etudiant'): ?>
                                    <div class="mt-3 pt-3 border-top">
                                        <div class="submission-actions">
                                            <button class="btn btn-sm btn-primary" onclick="soumettreLivrable(<?= $tache['id'] ?>, '<?= htmlspecialchars($tache['titre']) ?>')">
                                                <i class="fas fa-upload me-1"></i>Soumettre un Livrable
                                            </button>
                                            
                                            <?php
                                            // Vérifier si l'étudiant a déjà soumis un livrable pour cette tâche
                                            $livrable_existant = false;
                                            foreach ($mes_livrables as $livrable) {
                                                if ($livrable['tache_id'] == $tache['id'] && $livrable['statut'] != 'remplace') {
                                                    $livrable_existant = true;
                                                    break;
                                                }
                                            }
                                            ?>
                                            
                                            <?php if ($livrable_existant): ?>
                                            <button class="btn btn-sm btn-warning" onclick="soumettreLivrable(<?= $tache['id'] ?>, '<?= htmlspecialchars($tache['titre']) ?>', true)">
                                                <i class="fas fa-redo me-1"></i>Re-soumettre
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-tasks fa-5x mb-4" style="color: #FF9800;"></i>
                                    <h3 class="text-muted mb-3">Aucune tâche définie</h3>
                                    <p class="text-muted fs-5">L'enseignant n'a pas encore créé de tâches pour ce projet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Ressources du projet -->
                <div class="tab-pane fade" id="ressources">
                    <div class="row">
                        <!-- Fichiers -->
                        <div class="col-lg-8">
                            <div class="content-card">
                                <div class="content-card-header">
                                    <h4><i class="fas fa-folder-open"></i>Fichiers et Documents</h4>
                                </div>
                                <div class="content-card-body">
                                    <?php if (!empty($fichiers)): ?>
                                        <?php foreach (['image' => 'Images', 'video' => 'Vidéos', 'document' => 'Documents'] as $type => $label): ?>
                                            <?php if (!empty($fichiers_par_type[$type])): ?>
                                            <div class="mb-4">
                                                <h5 class="text-primary mb-3">
                                                    <i class="fas fa-<?= $type == 'image' ? 'images' : ($type == 'video' ? 'video' : 'file-alt') ?> text-warning me-2"></i>
                                                    <?= $label ?> (<?= count($fichiers_par_type[$type]) ?>)
                                                </h5>
                                                <?php foreach ($fichiers_par_type[$type] as $fichier): ?>
                                                <div class="file-item">
                                                    <div class="file-info">
                                                        <div class="file-icon <?= $type ?>">
                                                            <i class="fas fa-<?= $type == 'image' ? 'image' : ($type == 'video' ? 'video' : 'file-alt') ?>"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($fichier['nom_original'] ?? 'Fichier sans nom') ?></div>
                                                            <div class="text-muted small">
                                                                <?= formatBytes($fichier['taille_fichier'] ?? 0) ?> - 
                                                                Ajouté le <?= date('d/m/Y', strtotime($fichier['date_upload'] ?? 'now')) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <a href="<?= htmlspecialchars($fichier['chemin_fichier'] ?? '#') ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       target="_blank">
                                                        <i class="fas fa-download"></i> Télécharger
                                                    </a>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-folder-open fa-4x mb-4" style="color: #FF9800;"></i>
                                            <h4 class="text-muted mb-3">Aucun fichier disponible</h4>
                                            <p class="text-muted">L'enseignant n'a pas encore ajouté de fichiers à ce projet.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Liens -->
                        <div class="col-lg-4">
                            <div class="content-card">
                                <div class="content-card-header">
                                    <h4><i class="fas fa-link"></i>Liens Utiles</h4>
                                </div>
                                <div class="content-card-body">
                                    <?php if (!empty($liens)): ?>
                                        <?php foreach ($liens as $lien): ?>
                                        <div class="mb-3 p-3 bg-light rounded">
                                            <div class="mb-2">
                                                <a href="<?= htmlspecialchars($lien['url'] ?? '#') ?>" 
                                                   target="_blank" 
                                                   class="text-decoration-none fw-bold">
                                                    <i class="fas fa-external-link-alt text-primary me-2"></i>
                                                    <?= htmlspecialchars($lien['description'] ?? 'Lien sans description') ?>
                                                </a>
                                            </div>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($lien['url'] ?? '') ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Ajouté le <?= date('d/m/Y', strtotime($lien['date_ajout'] ?? 'now')) ?>
                                            </small>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-link fa-3x mb-3" style="color: #FF9800;"></i>
                                            <p class="text-muted mb-0">Aucun lien disponible</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mes livrables (Étudiant uniquement) -->
                <?php if ($role == 'etudiant'): ?>
                <div class="tab-pane fade" id="mes-livrables">
                    <div class="content-card">
                        <div class="content-card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4><i class="fas fa-upload"></i>Mes Livrables (<?= count($mes_livrables) ?>)</h4>
                                <button class="btn btn-primary btn-sm" onclick="ouvrirModalSoumission()">
                                    <i class="fas fa-plus me-1"></i>Nouveau Livrable
                                </button>
                            </div>
                        </div>
                        <div class="content-card-body">
                            <?php if (!empty($mes_livrables)): ?>
                                <?php foreach ($mes_livrables as $livrable): ?>
                                <div class="livrable-card status-<?= $livrable['statut'] ?? 'soumis' ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <h5 class="mb-2 fw-bold">
                                                <i class="fas fa-file-upload text-primary me-2"></i>
                                                <?= htmlspecialchars($livrable['tache_titre'] ?? 'Tâche inconnue') ?>
                                            </h5>
                                            <?php if (!empty($livrable['commentaire'])): ?>
                                            <p class="text-muted mb-2">
                                                <strong>Commentaire :</strong> <?= nl2br(htmlspecialchars($livrable['commentaire'])) ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge <?= 
                                                ($livrable['statut'] ?? 'soumis') == 'valide' ? 'bg-success' : 
                                                (($livrable['statut'] ?? 'soumis') == 'refuse' ? 'bg-danger' : 
                                                (($livrable['statut'] ?? 'soumis') == 'remplace' ? 'bg-secondary' : 'bg-warning'))
                                            ?>">
                                                <i class="fas fa-<?= 
                                                    ($livrable['statut'] ?? 'soumis') == 'valide' ? 'check' : 
                                                    (($livrable['statut'] ?? 'soumis') == 'refuse' ? 'times' : 
                                                    (($livrable['statut'] ?? 'soumis') == 'remplace' ? 'history' : 'clock'))
                                                ?>"></i>
                                                <?= ucfirst($livrable['statut'] ?? 'Soumis') ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <?php if (!empty($livrable['fichier_nom'])): ?>
                                            <div class="submission-info">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-file text-primary me-2"></i>
                                                    <div>
                                                        <strong><?= htmlspecialchars($livrable['fichier_nom']) ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?= formatBytes($livrable['fichier_taille'] ?? 0) ?> - 
                                                            Soumis le <?= date('d/m/Y H:i', strtotime($livrable['date_soumission'] ?? 'now')) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php else: ?>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Soumis le : <?= date('d/m/Y H:i', strtotime($livrable['date_soumission'] ?? 'now')) ?>
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <div class="submission-actions">
                                                <?php if (!empty($livrable['fichier_chemin']) && file_exists($livrable['fichier_chemin'])): ?>
                                                <a href="<?= htmlspecialchars($livrable['fichier_chemin']) ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   target="_blank">
                                                    <i class="fas fa-download me-1"></i>Télécharger
                                                </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($livrable['statut'] != 'valide' && $livrable['statut'] != 'remplace'): ?>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="supprimerLivrable(<?= $livrable['id'] ?>, '<?= htmlspecialchars($livrable['tache_titre']) ?>')">
                                                    <i class="fas fa-trash me-1"></i>Supprimer
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-upload fa-5x mb-4" style="color: #FF9800;"></i>
                                    <h3 class="text-muted mb-3">Aucun livrable soumis</h3>
                                    <p class="text-muted fs-5">Vous n'avez pas encore soumis de livrables pour ce projet.</p>
                                    <p class="text-muted">Consultez l'onglet "Tâches" pour soumettre vos premiers livrables.</p>
                                    <button class="btn btn-primary" onclick="ouvrirModalSoumission()">
                                        <i class="fas fa-upload me-2"></i>Soumettre mon Premier Livrable
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal pour soumettre un livrable -->
    <?php if ($role == 'etudiant'): ?>
    <div class="modal fade" id="livrableModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-upload me-2"></i>Soumettre un Livrable
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="livrableForm">
                    <div class="modal-body">
                        <input type="hidden" name="tache_id" id="modal_tache_id">
                        <input type="hidden" name="allow_resubmit" id="modal_allow_resubmit" value="0">
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-tasks"></i>Tâche Sélectionnée
                            </label>
                            <div class="bg-light p-3 rounded">
                                <strong id="modal_tache_titre">Sélectionnez une tâche</strong>
                            </div>
                        </div>

                        <div class="mb-4" id="resubmit_warning" style="display: none;">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Attention :</strong> Vous avez déjà soumis un livrable pour cette tâche. 
                                Cette nouvelle soumission remplacera la précédente.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="fichier_livrable" class="form-label">
                                <i class="fas fa-file"></i>Fichier à Soumettre
                            </label>
                            <div class="file-upload-area" onclick="document.getElementById('fichier_livrable').click()">
                                <i class="fas fa-cloud-upload-alt fa-3x mb-3" style="color: #009688;"></i>
                                <h5>Cliquez pour sélectionner un fichier</h5>
                                <p class="text-muted mb-0">ou glissez-déposez votre fichier ici</p>
                                <input type="file" class="d-none" name="fichier_livrable" id="fichier_livrable" 
                                       accept=".pdf,.doc,.docx,.txt,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.ppt,.pptx,.xls,.xlsx"
                                       onchange="afficherFichierSelectionne(this)">
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle text-warning me-1"></i>
                                Formats acceptés : PDF, DOC, DOCX, TXT, ZIP, RAR, 7Z, JPG, PNG, GIF, PPT, XLS (Max: 50MB)
                            </div>
                            <div id="fichier_info" class="mt-2" style="display: none;">
                                <div class="bg-success text-white p-2 rounded">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <span id="fichier_nom"></span>
                                    <span class="float-end" id="fichier_taille"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="commentaire" class="form-label">
                                <i class="fas fa-comment"></i>Commentaire (Optionnel)
                            </label>
                            <textarea class="form-control" name="commentaire" id="commentaire" rows="4" 
                                      placeholder="Ajoutez un commentaire sur votre livrable, des explications sur votre travail, des difficultés rencontrées, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="submit" name="soumettre_livrable" class="btn btn-primary" id="btn_soumettre">
                            <i class="fas fa-upload me-1"></i>Soumettre le Livrable
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmer la Suppression
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer ce livrable ?</p>
                    <div class="bg-light p-3 rounded">
                        <strong>Tâche :</strong> <span id="delete_tache_titre"></span>
                    </div>
                    <div class="mt-3">
                        <small class="text-danger">
                            <i class="fas fa-warning me-1"></i>
                            Cette action est irréversible. Le fichier sera définitivement supprimé.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="livrable_id" id="delete_livrable_id">
                        <button type="submit" name="supprimer_livrable" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Supprimer Définitivement
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Graphique de progression
        new Chart(document.getElementById('progressChart'), {
            type: 'doughnut',
            data: {
                labels: ['À faire', 'En cours', 'Terminé'],
                datasets: [{
                    data: [<?= $stats_taches['a_faire'] ?>, <?= $stats_taches['en_cours'] ?>, <?= $stats_taches['termine'] ?>],
                    backgroundColor: ['#f44336', '#FF9800', '#4CAF50'],
                    borderWidth: 3,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    }
                }
            }
        });

        <?php if ($role == 'etudiant'): ?>
        // Variables globales pour les tâches
        const taches = <?= json_encode($taches) ?>;

        function soumettreLivrable(tacheId, tacheTitre, isResubmit = false) {
            document.getElementById('modal_tache_id').value = tacheId;
            document.getElementById('modal_tache_titre').textContent = tacheTitre;
            document.getElementById('modal_allow_resubmit').value = isResubmit ? '1' : '0';
            
            // Afficher/masquer l'avertissement de re-soumission
            const warningDiv = document.getElementById('resubmit_warning');
            if (isResubmit) {
                warningDiv.style.display = 'block';
                document.getElementById('btn_soumettre').innerHTML = '<i class="fas fa-redo me-1"></i>Re-soumettre le Livrable';
            } else {
                warningDiv.style.display = 'none';
                document.getElementById('btn_soumettre').innerHTML = '<i class="fas fa-upload me-1"></i>Soumettre le Livrable';
            }
            
            // Réinitialiser le formulaire
            document.getElementById('livrableForm').reset();
            document.getElementById('fichier_info').style.display = 'none';
            
            new bootstrap.Modal(document.getElementById('livrableModal')).show();
        }

        function ouvrirModalSoumission() {
            if (taches.length === 0) {
                alert('Aucune tâche disponible pour ce projet.');
                return;
            }
            
            // Sélectionner la première tâche par défaut
            const premiereTache = taches[0];
            soumettreLivrable(premiereTache.id, premiereTache.titre);
        }

        function supprimerLivrable(livrableId, tacheTitre) {
            document.getElementById('delete_livrable_id').value = livrableId;
            document.getElementById('delete_tache_titre').textContent = tacheTitre;
            new bootstrap.Modal(document.getElementById('confirmDeleteModal')).show();
        }

        function afficherFichierSelectionne(input) {
            const fichierInfo = document.getElementById('fichier_info');
            const fichierNom = document.getElementById('fichier_nom');
            const fichierTaille = document.getElementById('fichier_taille');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                fichierNom.textContent = file.name;
                fichierTaille.textContent = formatBytes(file.size);
                fichierInfo.style.display = 'block';
                
                // Vérifier la taille du fichier
                const maxSize = 50 * 1024 * 1024; // 50MB
                if (file.size > maxSize) {
                    alert('Le fichier est trop volumineux. Taille maximum autorisée : 50MB');
                    input.value = '';
                    fichierInfo.style.display = 'none';
                    return;
                }
            } else {
                fichierInfo.style.display = 'none';
            }
        }

        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        // Gestion du drag & drop
        const uploadArea = document.querySelector('.file-upload-area');
        const fileInput = document.getElementById('fichier_livrable');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            uploadArea.classList.add('dragover');
        }

        function unhighlight(e) {
            uploadArea.classList.remove('dragover');
        }

        uploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fileInput.files = files;
                afficherFichierSelectionne(fileInput);
            }
        }
        <?php endif; ?>

        // Activation des onglets
        var triggerTabList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="tab"]'))
        triggerTabList.forEach(function (triggerEl) {
            var tabTrigger = new bootstrap.Tab(triggerEl)
            triggerEl.addEventListener('click', function (event) {
                event.preventDefault()
                tabTrigger.show()
            })
        })

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>