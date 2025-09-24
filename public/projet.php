<?php
session_start();
require_once '../config/database.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Attribution du rôle en fonction de l'ID utilisateur - CORRIGÉ
// Définir le rôle selon role_id
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

// Les utilisateurs restent sur leur interface respective
// Aucune redirection n'est nécessaire ici si chaque page est réservée à un rôle

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Redirection vers l'interface appropriée selon le rôle
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page === 'projet.php') {
    switch ($role) {
        case 'etudiant':
            // L'étudiant reste sur cette page pour accéder aux projets
            break;
        case 'enseignant':
            // L'enseignant reste sur cette page pour créer des projets
            break;
        case 'admin':
            // L'admin reste sur cette page pour voir tous les projets
            break;
        default:
            // Rôle inconnu, redirection vers login
            header('Location: login.php');
            exit();
    }
}

// Fonction pour gérer l'upload de fichiers
function handleFileUpload($file, $project_id, $type) {
    global $pdo;
    
    $upload_dir = '../uploads/projets/' . $project_id . '/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_types = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
        'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt', 'ppt', 'pptx', 'xls', 'xlsx']
    ];
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types[$type])) {
        throw new Exception("Type de fichier non autorisé pour " . $type);
    }
    
    // Vérifier la taille du fichier (50MB max)
    if ($file['size'] > 50 * 1024 * 1024) {
        throw new Exception("Le fichier est trop volumineux (max 50MB)");
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Enregistrer en base de données
        $stmt = $pdo->prepare("
            INSERT INTO projet_fichiers (projet_id, nom_original, nom_fichier, chemin_fichier, type_fichier, taille_fichier, date_upload) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $project_id,
            $file['name'],
            $filename,
            $filepath,
            $type,
            $file['size']
        ]);
        
        return $filename;
    } else {
        throw new Exception("Erreur lors de l'upload du fichier");
    }
}

// Traitement de la création de projet
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['creer_projet'])) {
    $titre = $_POST['titre'];
    $type_projet = $_POST['type_projet'];
    $description = $_POST['description'];
    $objectifs = $_POST['objectifs'] ?? '';
    $criteres_evaluation = $_POST['criteres_evaluation'] ?? '';
    $ressources_necessaires = $_POST['ressources_necessaires'] ?? '';
    $competences_developpees = $_POST['competences_developpees'] ?? '';
    $date_limite = $_POST['date_limite'] ?: null;
    $filiere_id = $_POST['filiere_id'] ?? null;
    
    if ($role == 'admin') {
        $enseignant_id = $_POST['enseignant_id'];
    } else {
        $enseignant_id = $user_id;
    }
    
    try {
        // Démarrer une transaction
        $pdo->beginTransaction();
        
        // Créer le projet
        $stmt = $pdo->prepare("
            INSERT INTO projets (titre, type_projet, description, objectifs, criteres_evaluation, ressources_necessaires, competences_developpees, date_limite, enseignant_id, filiere_id, statut, date_creation) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'actif', NOW())
        ");
        $stmt->execute([$titre, $type_projet, $description, $objectifs, $criteres_evaluation, $ressources_necessaires, $competences_developpees, $date_limite, $enseignant_id, $filiere_id]);
        
        $project_id = $pdo->lastInsertId();
        
        // Traiter les tâches définies par l'enseignant
        if (isset($_POST['taches_titres']) && !empty($_POST['taches_titres'])) {
            $taches_titres = $_POST['taches_titres'];
            $taches_descriptions = $_POST['taches_descriptions'] ?? [];
            $taches_priorites = $_POST['taches_priorites'] ?? [];
            $taches_dates_limites = $_POST['taches_dates_limites'] ?? [];
            
            foreach ($taches_titres as $index => $titre_tache) {
                if (!empty($titre_tache)) {
                    $description_tache = $taches_descriptions[$index] ?? '';
                    $priorite_tache = $taches_priorites[$index] ?? 'moyenne';
                    $date_limite_tache = !empty($taches_dates_limites[$index]) ? $taches_dates_limites[$index] : null;
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO taches (projet_id, titre, description, priorite, date_limite, statut, date_creation) 
                        VALUES (?, ?, ?, ?, ?, 'a_faire', NOW())
                    ");
                    $stmt->execute([$project_id, $titre_tache, $description_tache, $priorite_tache, $date_limite_tache]);
                }
            }
        }
        
        // Traiter les uploads de fichiers
        $file_types = ['images', 'videos', 'documents'];
        foreach ($file_types as $file_type) {
            if (isset($_FILES[$file_type]) && !empty($_FILES[$file_type]['name'][0])) {
                $files = $_FILES[$file_type];
                $file_count = count($files['name']);
                
                for ($i = 0; $i < $file_count; $i++) {
                    if ($files['error'][$i] == UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $files['name'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'size' => $files['size'][$i],
                            'error' => $files['error'][$i]
                        ];
                        
                        $type = rtrim($file_type, 's'); // Enlever le 's' final
                        handleFileUpload($file, $project_id, $type);
                    }
                }
            }
        }
        
        // Traiter les liens
        if (isset($_POST['liens']) && !empty($_POST['liens'])) {
            $liens = $_POST['liens'];
            $descriptions_liens = $_POST['descriptions_liens'] ?? [];
            
            foreach ($liens as $index => $lien) {
                if (!empty($lien)) {
                    $description_lien = $descriptions_liens[$index] ?? '';
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO projet_liens (projet_id, url, description, date_ajout) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$project_id, $lien, $description_lien]);
                }
            }
        }
        
        $pdo->commit();
        $success_message = "Projet créé avec succès avec toutes les tâches, fichiers et liens associés!";
        
    } catch (Exception $e) {
        $pdo->rollback();
        $error_message = "Erreur lors de la création du projet: " . $e->getMessage();
    }
}

// Récupérer les données selon le rôle
if ($role == 'etudiant') {
    // Récupérer la filière de l'étudiant
    $stmt = $pdo->prepare("SELECT filiere_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch();
    $etudiant_filiere_id = $user_info['filiere_id'] ?? null;
    
    // Statistiques pour l'étudiant
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT p.id) as nb_projets,
            COUNT(t.id) as nb_taches,
            COUNT(l.id) as nb_livrables
        FROM projets p
        LEFT JOIN projet_etudiants pe ON p.id = pe.projet_id
        LEFT JOIN taches t ON p.id = t.projet_id
        LEFT JOIN livrables l ON t.id = l.tache_id AND l.etudiant_id = ?
        WHERE (pe.etudiant_id = ? OR p.filiere_id = ?)
    ");
    $stmt->execute([$user_id, $user_id, $etudiant_filiere_id]);
    $stats = $stmt->fetch();

    // Projets assignés à l'étudiant + projets de sa filière
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.*, u.nom as enseignant_nom, u.prenom as enseignant_prenom, f.nom as filiere_nom
        FROM projets p
        JOIN users u ON p.enseignant_id = u.id
        LEFT JOIN filieres f ON p.filiere_id = f.id
        LEFT JOIN projet_etudiants pe ON p.id = pe.projet_id
        WHERE (pe.etudiant_id = ? OR p.filiere_id = ?)
        ORDER BY p.date_creation DESC
    ");
    $stmt->execute([$user_id, $etudiant_filiere_id]);
    $projets = $stmt->fetchAll();

} elseif ($role == 'enseignant') {
    // Statistiques pour l'enseignant
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT p.id) as nb_projets,
            COUNT(DISTINCT pe.etudiant_id) as nb_etudiants,
            COUNT(t.id) as nb_taches
        FROM projets p
        LEFT JOIN projet_etudiants pe ON p.id = pe.projet_id
        LEFT JOIN taches t ON p.id = t.projet_id
        WHERE p.enseignant_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();

    // Projets créés par l'enseignant
    $stmt = $pdo->prepare("
        SELECT p.*, COUNT(pe.etudiant_id) as nb_etudiants, f.nom as filiere_nom
        FROM projets p
        LEFT JOIN projet_etudiants pe ON p.id = pe.projet_id
        LEFT JOIN filieres f ON p.filiere_id = f.id
        WHERE p.enseignant_id = ?
        GROUP BY p.id
        ORDER BY p.date_creation DESC
    ");
    $stmt->execute([$user_id]);
    $projets = $stmt->fetchAll();

} elseif ($role == 'admin') {
    // Statistiques pour l'admin
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT p.id) as nb_projets,
            COUNT(DISTINCT u.id) as nb_etudiants,
            COUNT(DISTINCT ens.id) as nb_enseignants
        FROM projets p
        LEFT JOIN users u ON u.role_id = 1
        LEFT JOIN users ens ON ens.role_id = 2
    ");
    $stmt->execute();
    $stats = $stmt->fetch();

    // Tous les projets
    $stmt = $pdo->prepare("
        SELECT p.*, u.nom as enseignant_nom, u.prenom as enseignant_prenom,
               COUNT(pe.etudiant_id) as nb_etudiants, f.nom as filiere_nom
        FROM projets p
        JOIN users u ON p.enseignant_id = u.id
        LEFT JOIN projet_etudiants pe ON p.id = pe.projet_id
        LEFT JOIN filieres f ON p.filiere_id = f.id
        GROUP BY p.id
        ORDER BY p.date_creation DESC
    ");
    $stmt->execute();
    $projets = $stmt->fetchAll();
}

// Initialiser les variables si elles ne sont pas définies
if (!isset($stats)) {
    $stats = [
        'nb_projets' => 0,
        'nb_taches' => 0,
        'nb_livrables' => 0,
        'nb_etudiants' => 0,
        'nb_enseignants' => 0
    ];
}

if (!isset($projets)) {
    $projets = [];
}

// Récupérer les filières pour le formulaire
$stmt = $pdo->prepare("SELECT * FROM filieres ORDER BY nom");
$stmt->execute();
$filieres = $stmt->fetchAll();

// Données pour les graphiques
$current_month = date('Y-m');
$last_6_months = [];
for ($i = 5; $i >= 0; $i--) {
    $last_6_months[] = date('Y-m', strtotime("-$i months"));
}

// Statistiques mensuelles pour graphiques
$monthly_stats = [];
foreach ($last_6_months as $month) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM projets 
        WHERE DATE_FORMAT(date_creation, '%Y-%m') = ?
    ");
    $stmt->execute([$month]);
    $monthly_stats[] = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Projets - Dashboard <?= ucfirst($role) ?></title>
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

        /* Header principal professionnel */
        .main-header {
            background: linear-gradient(135deg, #009688 0%, #00695c 100%);
            color: white;
            padding: 25px 0;
            margin-bottom: 40px;
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

        .main-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .main-header .subtitle {
            margin: 8px 0 0 0;
            font-size: 18px;
            opacity: 0.9;
            font-weight: 300;
        }

        /* Section utilisateur améliorée */
        .user-section {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .role-badge {
            background: linear-gradient(135deg, #FF9800 0%, #f57c00 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 15px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.2);
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 500;
        }

        .btn-logout {
            background: linear-gradient(135deg, #FF9800 0%, #f57c00 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Cartes de statistiques professionnelles */
        .stats-section {
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 150, 136, 0.1);
            position: relative;
            overflow: hidden;
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
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            font-size: 28px;
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.3);
        }

        .stat-card .number {
            font-size: 42px;
            font-weight: 800;
            color: #009688;
            margin-bottom: 8px;
            line-height: 1;
        }

        .stat-card .label {
            font-size: 16px;
            color: #546e7a;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Section graphiques */
        .charts-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .chart-header {
            background: linear-gradient(135deg, #009688 0%, #00695c 100%);
            color: white;
            padding: 20px 25px;
            margin: -30px -30px 30px -30px;
            border-radius: 15px 15px 0 0;
        }

        .chart-header h4 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }

        .chart-header i {
            color: #FF9800;
            margin-right: 12px;
            font-size: 24px;
        }

        /* Navigation professionnelle */
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

        /* Cartes de contenu */
        .content-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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

        /* Tableaux professionnels */
        .table {
            margin-bottom: 0;
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg, #37474f 0%, #263238 100%);
            border: none;
            color: white;
            font-weight: 700;
            padding: 20px 15px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table thead th i {
            color: #FF9800;
            margin-right: 8px;
            font-size: 16px;
        }

        .table tbody td {
            padding: 18px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Boutons professionnels */
        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 14px;
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

        .btn-info {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        /* Badges améliorés */
        .badge {
            font-size: 13px;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bg-primary {
            background: linear-gradient(135deg, #009688 0%, #00695c 100%) !important;
        }

        .bg-warning {
            background: linear-gradient(135deg, #FF9800 0%, #f57c00 100%) !important;
        }

        /* Formulaires professionnels */
        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
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

        /* Messages d'alerte */
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

        /* Kanban amélioré */
        .kanban-column {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .kanban-column h6 {
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kanban-column h6 i {
            color: #FF9800;
            margin-right: 10px;
            font-size: 20px;
        }

        .kanban-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .status-a-faire {
            border-left-color: #f44336;
        }

        .status-en-cours {
            border-left-color: #FF9800;
        }

        .status-termine {
            border-left-color: #4CAF50;
        }

        /* Indicateurs de performance */
        .performance-indicator {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .performance-indicator .value {
            font-size: 36px;
            font-weight: 800;
            color: #009688;
            margin-bottom: 5px;
        }

        .performance-indicator .metric {
            font-size: 14px;
            color: #546e7a;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Styles pour les uploads de fichiers */
        .file-upload-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 2px dashed #009688;
        }

        .file-upload-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .file-upload-header h5 {
            color: #009688;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background: white;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .file-upload-area:hover {
            border-color: #009688;
            background: #f0f8f7;
        }

        .file-upload-area.dragover {
            border-color: #FF9800;
            background: #fff8e1;
        }

        .file-list {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .file-item:last-child {
            border-bottom: none;
        }

        .file-info {
            display: flex;
            align-items: center;
        }

        .file-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 18px;
        }

        .file-icon.image { background: #4CAF50; }
        .file-icon.video { background: #FF9800; }
        .file-icon.document { background: #2196F3; }

        .link-section {
            background: #e3f2fd;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 5px solid #2196F3;
        }

        .link-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }

        .description-sections {
            background: #f3e5f5;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 5px solid #9C27B0;
        }

        .description-section {
            margin-bottom: 20px;
        }

        .description-section:last-child {
            margin-bottom: 0;
        }

        /* Styles pour la section des tâches */
        .tasks-section {
            background: #fff3e0;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 5px solid #FF9800;
        }

        .task-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .task-item:last-child {
            margin-bottom: 0;
        }

        .task-header {
            background: linear-gradient(135deg, #FF9800 0%, #f57c00 100%);
            color: white;
            padding: 10px 15px;
            margin: -20px -20px 20px -20px;
            border-radius: 10px 10px 0 0;
            font-weight: bold;
        }

        .priority-badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 15px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .priority-haute { background: #ffebee; color: #c62828; }
        .priority-moyenne { background: #fff8e1; color: #f57c00; }
        .priority-basse { background: #e8f5e8; color: #2e7d32; }

        /* Responsive */
        @media (max-width: 768px) {
            .main-header {
                text-align: center;
            }
            
            .user-section {
                text-align: center;
                margin-top: 20px;
            }
            
            .stat-card {
                margin-bottom: 20px;
            }
            
            .charts-section {
                padding: 20px;
            }
        }

        /* Suppression de tous les effets de survol */
        * {
            transition: none !important;
        }

        .table tbody tr:hover {
            background-color: transparent;
        }

        .btn:hover {
            transform: none;
        }

        .nav-link:hover {
            background-color: inherit;
            color: inherit;
        }

        .stat-card:hover {
            transform: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header principal professionnel -->
        <div class="main-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1>
                            <i class="fas fa-<?= 
                                $role == 'admin' ? 'cogs' : 
                                ($role == 'enseignant' ? 'chalkboard-teacher' : 'laptop-code') 
                            ?>" style="color: #FF9800; margin-right: 20px;"></i>
                            <?php if ($role == 'etudiant'): ?>
                                Dashboard Étudiant - Gestion des Projets
                            <?php elseif ($role == 'enseignant'): ?>
                                Dashboard Enseignant - Création & Suivi
                            <?php else: ?>
                                Dashboard Administrateur - Contrôle Global
                            <?php endif; ?>
                        </h1>
                        <p class="subtitle">
                            <i class="fas fa-info-circle" style="color: #FF9800; margin-right: 10px;"></i>
                            <?php if ($role == 'etudiant'): ?>
                                Interface complète pour consulter et gérer vos projets académiques
                            <?php elseif ($role == 'enseignant'): ?>
                                Plateforme de création de projets et suivi des étudiants
                            <?php else: ?>
                                Supervision complète de l'écosystème des projets étudiants
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-lg-4">
                        <div class="user-section text-end">
                            <div class="role-badge">
                                <i class="fas fa-<?= 
                                    $role == 'admin' ? 'crown' : 
                                    ($role == 'enseignant' ? 'chalkboard-teacher' : 'user-graduate') 
                                ?>"></i>
                                <?= strtoupper($role) ?> - ID: <?= $user_id ?>
                            </div>
                            <div class="user-info">
                                <i class="fas fa-user" style="color: #FF9800; margin-right: 10px;"></i>
                                <?= ($_SESSION['prenom'] ?? 'Utilisateur') . ' ' . ($_SESSION['nom'] ?? '') ?>
                            </div>
                            <a href="logout.php" class="btn-logout">
                                <i class="fas fa-sign-out-alt" style="margin-right: 10px;"></i>
                                Déconnexion Sécurisée
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- Messages -->
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <strong>Succès :</strong> <?= $success_message ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Erreur :</strong> <?= $error_message ?>
            </div>
            <?php endif; ?>

            <!-- Section statistiques avancées -->
            <div class="stats-section">
                <div class="row">
                    <?php if ($role == 'etudiant'): ?>
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="number"><?= $stats['nb_projets'] ?? 0 ?></div>
                                <div class="label">Projets Disponibles</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="number"><?= $stats['nb_taches'] ?? 0 ?></div>
                                <div class="label">Tâches Assignées</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="icon">
                                    <i class="fas fa-file-upload"></i>
                                </div>
                                <div class="number"><?= $stats['nb_livrables'] ?? 0 ?></div>
                                <div class="label">Livrables Soumis</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="number"><?= round((($stats['nb_livrables'] ?? 0) / max(($stats['nb_taches'] ?? 1), 1)) * 100, 1) ?>%</div>
                                <div class="label">Taux de Réussite</div>
                            </div>
                        </div>
                    <?php elseif ($role == 'enseignant'): ?>
                        <div class="col-xl-4 col-md-6">
                            <div class="stat-card">
                                <div class="icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="number"><?= $stats['nb_projets'] ?? 0 ?></div>
                                <div class="label">Projets Créés</div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="stat-card">
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="number"><?= $stats['nb_etudiants'] ?? 0 ?></div>
                                <div class="label">Étudiants Encadrés</div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="stat-card">
                                <div class="icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="number"><?= $stats['nb_taches'] ?? 0 ?></div>
                                <div class="label">Tâches Supervisées</div>
                            </div>
                        </div>
                    <?php elseif ($role == 'admin'): ?>
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="number"><?= $stats['nb_projets'] ?? 0 ?></div>
                                <div class="label">Total Projets</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="icon">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div class="number"><?= $stats['nb_etudiants'] ?? 0 ?></div>
                                <div class="label">Étudiants Actifs</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="number"><?= $stats['nb_enseignants'] ?? 0 ?></div>
                                <div class="label">Enseignants</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div class="icon">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <div class="number"><?= round((($stats['nb_projets'] ?? 0) / max(($stats['nb_enseignants'] ?? 1), 1)), 1) ?></div>
                                <div class="label">Projets/Enseignant</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section graphiques professionnels -->
            <div class="charts-section">
                <div class="chart-header">
                    <h4><i class="fas fa-chart-area"></i>Analyse des Performances - 6 Derniers Mois</h4>
                </div>
                <div class="row">
                    <div class="col-lg-8">
                        <canvas id="monthlyChart" height="100"></canvas>
                    </div>
                    <div class="col-lg-4">
                        <div class="row">
                            <div class="col-12">
                                <div class="performance-indicator">
                                    <div class="value"><?= array_sum($monthly_stats) ?></div>
                                    <div class="metric">Total Projets (6 mois)</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="performance-indicator">
                                    <div class="value"><?= round(array_sum($monthly_stats) / 6, 1) ?></div>
                                    <div class="metric">Moyenne Mensuelle</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <canvas id="statusChart" height="150"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation professionnelle -->
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#liste-projets" data-bs-toggle="tab">
                        <i class="fas fa-list"></i>
                        <?php if ($role == 'etudiant'): ?>
                            Mes Projets Disponibles
                        <?php elseif ($role == 'enseignant'): ?>
                            Mes Projets Créés
                        <?php else: ?>
                            Tous les Projets
                        <?php endif; ?>
                    </a>
                </li>
                <?php if ($role == 'etudiant'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#suivi-projets" data-bs-toggle="tab">
                        <i class="fas fa-chart-bar"></i>Suivi & Progression
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($role == 'enseignant' || $role == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#creer-projet" data-bs-toggle="tab">
                        <i class="fas fa-plus-circle"></i>Créer un Projet
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#analytics" data-bs-toggle="tab">
                        <i class="fas fa-analytics"></i>Analyses Avancées
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Contenu des onglets -->
            <div class="tab-content">
                <!-- Liste des projets -->
                <div class="tab-pane fade show active" id="liste-projets">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4>
                                <i class="fas fa-project-diagram"></i>
                                <?php if ($role == 'etudiant'): ?>
                                    Projets Disponibles - Vue Complète
                                <?php elseif ($role == 'enseignant'): ?>
                                    Vos Projets - Gestion & Suivi
                                <?php else: ?>
                                    Vue Globale - Tous les Projets du Système
                                <?php endif; ?>
                            </h4>
                        </div>
                        <div class="content-card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-tag"></i>Titre du Projet</th>
                                            <th><i class="fas fa-cog"></i>Type</th>
                                            <th><i class="fas fa-graduation-cap"></i>Filière</th>
                                            <?php if ($role != 'enseignant'): ?>
                                            <th><i class="fas fa-user-tie"></i>Enseignant</th>
                                            <?php endif; ?>
                                            <th><i class="fas fa-calendar-plus"></i>Date Création</th>
                                            <th><i class="fas fa-calendar-times"></i>Date Limite</th>
                                            <th><i class="fas fa-info-circle"></i>Statut</th>
                                            <th><i class="fas fa-tools"></i>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($projets)): ?>
                                            <?php foreach ($projets as $projet): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong class="text-primary"><?= htmlspecialchars($projet['titre'] ?? 'Titre non défini') ?></strong>
                                                        <br><small class="text-muted"><?= substr(htmlspecialchars($projet['description'] ?? 'Description non disponible'), 0, 60) ?>...</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php 
                                                        $types = [
                                                            'cahier_charge' => 'Cahier des charges',
                                                            'sujet_pratique' => 'Sujet pratique',
                                                            'creation' => 'Création'
                                                        ];
                                                        echo $types[$projet['type_projet'] ?? 'inconnu'] ?? 'Inconnu';
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?= htmlspecialchars($projet['filiere_nom'] ?? 'Non spécifiée') ?>
                                                    </span>
                                                </td>
                                                <?php if ($role != 'enseignant'): ?>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-user-circle text-muted me-2"></i>
                                                        <?= htmlspecialchars(($projet['enseignant_prenom'] ?? '') . ' ' . ($projet['enseignant_nom'] ?? '')) ?>
                                                    </div>
                                                </td>
                                                <?php endif; ?>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-calendar text-muted me-2"></i>
                                                        <?= date('d/m/Y', strtotime($projet['date_creation'] ?? 'now')) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (isset($projet['date_limite']) && $projet['date_limite']): ?>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-clock text-muted me-2"></i>
                                                            <?= date('d/m/Y', strtotime($projet['date_limite'])) ?>
                                                        </div>
                                                        <?php 
                                                        $jours_restants = ceil((strtotime($projet['date_limite']) - time()) / (60*60*24));
                                                        if ($jours_restants < 0): ?>
                                                            <small class="text-danger fw-bold">
                                                                <i class="fas fa-exclamation-triangle"></i> Expiré
                                                            </small>
                                                        <?php elseif ($jours_restants <= 7): ?>
                                                            <small class="text-warning fw-bold">
                                                                <i class="fas fa-hourglass-half"></i> <?= $jours_restants ?> jour(s)
                                                            </small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">
                                                            <i class="fas fa-infinity"></i> Non définie
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= 
                                                        ($projet['statut'] ?? 'actif') == 'actif' ? 'bg-success' : 
                                                        (($projet['statut'] ?? 'actif') == 'termine' ? 'bg-secondary' : 'bg-warning')
                                                    ?>">
                                                        <i class="fas fa-<?= 
                                                            ($projet['statut'] ?? 'actif') == 'actif' ? 'play' : 
                                                            (($projet['statut'] ?? 'actif') == 'termine' ? 'check' : 'pause')
                                                        ?>"></i>
                                                        <?= ucfirst($projet['statut'] ?? 'Actif') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if ($role == 'etudiant'): ?>
                                                            <a href="projet_details.php?id=<?= $projet['id'] ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-play"></i> Accéder
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="projet_details.php?id=<?= $projet['id'] ?>" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i> Voir
                                                            </a>
                                                            <?php if ($role == 'enseignant' || $role == 'admin'): ?>
                                                            <a href="modifier_projet.php?id=<?= $projet['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i> Modifier
                                                            </a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="<?= $role == 'enseignant' ? '7' : '8' ?>" class="text-center py-5">
                                                    <div class="text-center">
                                                        <i class="fas fa-inbox fa-4x text-muted mb-3" style="color: #FF9800 !important;"></i>
                                                        <h4 class="text-muted mb-2">
                                                            <?php if ($role == 'etudiant'): ?>
                                                                Aucun projet disponible actuellement
                                                            <?php elseif ($role == 'enseignant'): ?>
                                                                Vous n'avez pas encore créé de projets
                                                            <?php else: ?>
                                                                Aucun projet dans le système
                                                            <?php endif; ?>
                                                        </h4>
                                                        <p class="text-muted">
                                                            <?php if ($role == 'etudiant'): ?>
                                                                Les nouveaux projets apparaîtront ici dès leur publication
                                                            <?php elseif ($role == 'enseignant'): ?>
                                                                Utilisez l'onglet "Créer un Projet" pour commencer
                                                            <?php else: ?>
                                                                Encouragez les enseignants à créer des projets
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Suivi des projets (Étudiant) -->
                <?php if ($role == 'etudiant'): ?>
                <div class="tab-pane fade" id="suivi-projets">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-chart-bar"></i>Suivi Détaillé & Progression des Projets</h4>
                        </div>
                        <div class="content-card-body">
                            <?php if (!empty($projets)): ?>
                                <?php foreach ($projets as $projet): 
                                    // Récupérer les tâches pour ce projet
                                    $stmt = $pdo->prepare("
                                        SELECT * FROM taches 
                                        WHERE projet_id = ? 
                                        ORDER BY priorite DESC, date_creation ASC
                                    ");
                                    $stmt->execute([$projet['id']]);
                                    $taches = $stmt->fetchAll();

                                    // Compter les tâches par statut
                                    $stats_taches = [
                                        'a_faire' => 0,
                                        'en_cours' => 0,
                                        'termine' => 0
                                    ];
                                    foreach ($taches as $tache) {
                                        $stats_taches[$tache['statut'] ?? 'a_faire']++;
                                    }
                                ?>
                                <div class="mb-5">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <h5 class="text-primary mb-1 fw-bold"><?= htmlspecialchars($projet['titre'] ?? 'Projet sans titre') ?></h5>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-graduation-cap text-warning me-2"></i>
                                                <?= htmlspecialchars($projet['filiere_nom'] ?? 'Non spécifiée') ?> | 
                                                <i class="fas fa-user-tie text-warning me-2 ms-3"></i>
                                                <?= htmlspecialchars(($projet['enseignant_prenom'] ?? '') . ' ' . ($projet['enseignant_nom'] ?? '')) ?>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary fs-6 px-3 py-2">
                                                <i class="fas fa-tasks me-1"></i>
                                                <?= array_sum($stats_taches) ?> tâches au total
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-lg-8">
                                            <div class="bg-white p-3 rounded">
                                                <canvas id="chart-<?= $projet['id'] ?>" height="80"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="row">
                                                <div class="col-4 col-lg-12 mb-lg-3">
                                                    <div class="performance-indicator">
                                                        <i class="fas fa-clock fa-2x mb-2" style="color: #FF9800;"></i>
                                                        <div class="value text-danger"><?= $stats_taches['a_faire'] ?></div>
                                                        <div class="metric">À Faire</div>
                                                    </div>
                                                </div>
                                                <div class="col-4 col-lg-12 mb-lg-3">
                                                    <div class="performance-indicator">
                                                        <i class="fas fa-spinner fa-2x mb-2" style="color: #FF9800;"></i>
                                                        <div class="value" style="color: #FF9800;"><?= $stats_taches['en_cours'] ?></div>
                                                        <div class="metric">En Cours</div>
                                                    </div>
                                                </div>
                                                <div class="col-4 col-lg-12">
                                                    <div class="performance-indicator">
                                                        <i class="fas fa-check-circle fa-2x mb-2" style="color: #FF9800;"></i>
                                                        <div class="value text-success"><?= $stats_taches['termine'] ?></div>
                                                        <div class="metric">Terminé</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kanban amélioré -->
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="kanban-column">
                                                <h6 class="text-danger" style="border-color: #f44336;">
                                                    <i class="fas fa-clock"></i>À Faire
                                                </h6>
                                                <?php foreach ($taches as $tache): 
                                                    if (($tache['statut'] ?? 'a_faire') == 'a_faire'): ?>
                                                    <div class="kanban-card status-a-faire">
                                                        <h6 class="fw-bold text-dark"><?= htmlspecialchars($tache['titre'] ?? 'Tâche sans titre') ?></h6>
                                                        <p class="small text-muted mb-3"><?= substr(htmlspecialchars($tache['description'] ?? ''), 0, 100) ?>...</p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="badge <?= 
                                                                ($tache['priorite'] ?? 'basse') == 'haute' ? 'bg-danger' : 
                                                                (($tache['priorite'] ?? 'basse') == 'moyenne' ? 'bg-warning' : 'bg-secondary')
                                                            ?>">
                                                                <i class="fas fa-flag"></i> <?= ucfirst($tache['priorite'] ?? 'Basse') ?>
                                                            </span>
                                                            <button class="btn btn-sm btn-primary" 
                                                                    onclick="changerStatutTache(<?= $tache['id'] ?>, 'en_cours')">
                                                                <i class="fas fa-play"></i> Commencer
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <?php endif; 
                                                endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="kanban-column">
                                                <h6 style="color: #FF9800; border-color: #FF9800;">
                                                    <i class="fas fa-spinner"></i>En Cours
                                                </h6>
                                                <?php foreach ($taches as $tache): 
                                                    if (($tache['statut'] ?? 'a_faire') == 'en_cours'): ?>
                                                    <div class="kanban-card status-en-cours">
                                                        <h6 class="fw-bold text-dark"><?= htmlspecialchars($tache['titre'] ?? 'Tâche sans titre') ?></h6>
                                                        <p class="small text-muted mb-3"><?= substr(htmlspecialchars($tache['description'] ?? ''), 0, 100) ?>...</p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="badge <?= 
                                                                ($tache['priorite'] ?? 'basse') == 'haute' ? 'bg-danger' : 
                                                                (($tache['priorite'] ?? 'basse') == 'moyenne' ? 'bg-warning' : 'bg-secondary')
                                                            ?>">
                                                                <i class="fas fa-flag"></i> <?= ucfirst($tache['priorite'] ?? 'Basse') ?>
                                                            </span>
                                                            <div class="btn-group">
                                                                <button class="btn btn-sm btn-success" 
                                                                        onclick="changerStatutTache(<?= $tache['id'] ?>, 'termine')">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-warning" 
                                                                        onclick="soumettreLivrable(<?= $tache['id'] ?>)">
                                                                    <i class="fas fa-upload"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php endif; 
                                                endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="kanban-column">
                                                <h6 class="text-success" style="border-color: #4CAF50;">
                                                    <i class="fas fa-check-circle"></i>Terminé
                                                </h6>
                                                <?php foreach ($taches as $tache): 
                                                    if (($tache['statut'] ?? 'a_faire') == 'termine'): ?>
                                                    <div class="kanban-card status-termine">
                                                        <h6 class="fw-bold text-dark"><?= htmlspecialchars($tache['titre'] ?? 'Tâche sans titre') ?></h6>
                                                        <p class="small text-muted mb-3"><?= substr(htmlspecialchars($tache['description'] ?? ''), 0, 100) ?>...</p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check"></i> Terminé
                                                            </span>
                                                            <?php 
                                                            // Vérifier si un livrable a été soumis
                                                            $stmt = $pdo->prepare("SELECT * FROM livrables WHERE tache_id = ? AND etudiant_id = ?");
                                                            $stmt->execute([$tache['id'], $user_id]);
                                                            $livrable = $stmt->fetch();
                                                            ?>
                                                            <span class="badge <?= $livrable ? 'bg-info' : 'bg-secondary' ?>">
                                                                <i class="fas fa-<?= $livrable ? 'check' : 'times' ?>"></i>
                                                                <?= $livrable ? 'Livré' : 'Non livré' ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <?php endif; 
                                                endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr class="my-5">
                                <script>
                                    new Chart(document.getElementById('chart-<?= $projet['id'] ?>'), {
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
                                            plugins: {
                                                legend: {
                                                    position: 'bottom',
                                                    labels: {
                                                        padding: 20,
                                                        font: {
                                                            size: 14,
                                                            weight: 'bold'
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    });
                                </script>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-tasks fa-5x mb-4" style="color: #FF9800;"></i>
                                    <h3 class="text-muted mb-3">Aucun projet à suivre actuellement</h3>
                                    <p class="text-muted fs-5">Les projets assignés apparaîtront ici avec leur progression détaillée et leurs métriques de performance.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Créer un projet -->
                <?php if ($role == 'enseignant' || $role == 'admin'): ?>
                <div class="tab-pane fade" id="creer-projet">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-plus-circle"></i>Créer un Nouveau Projet Académique Complet</h4>
                        </div>
                        <div class="content-card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <!-- Informations de base -->
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-4">
                                            <label for="titre" class="form-label">
                                                <i class="fas fa-tag"></i>Titre du Projet
                                            </label>
                                            <input type="text" class="form-control" id="titre" name="titre" required 
                                                   placeholder="Entrez un titre descriptif et accrocheur">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-4">
                                            <label for="type_projet" class="form-label">
                                                <i class="fas fa-cog"></i>Type de Projet
                                            </label>
                                            <select class="form-select" id="type_projet" name="type_projet" required>
                                                <option value="">Sélectionnez le type de projet</option>
                                                <option value="cahier_charge">📋 Cahier des charges</option>
                                                <option value="sujet_pratique">🛠️ Sujet pratique</option>
                                                <option value="creation">🎨 Création</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-4">
                                            <label for="filiere_id" class="form-label">
                                                <i class="fas fa-graduation-cap"></i>Filière Cible
                                            </label>
                                            <select class="form-select" id="filiere_id" name="filiere_id" required>
                                                <option value="">Sélectionnez une filière</option>
                                                <?php foreach ($filieres as $filiere): ?>
                                                <option value="<?= $filiere['id'] ?>">
                                                    🎓 <?= htmlspecialchars($filiere['nom']) ?>
                                                    <?php if ($filiere['description']): ?>
                                                        - <?= htmlspecialchars($filiere['description']) ?>
                                                    <?php endif; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text mt-2">
                                                <i class="fas fa-info-circle text-warning me-1"></i>
                                                Le projet sera automatiquement visible par tous les étudiants de cette filière
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-4">
                                            <label for="date_limite" class="form-label">
                                                <i class="fas fa-calendar-times"></i>Date Limite de Rendu
                                            </label>
                                            <input type="date" class="form-control" id="date_limite" name="date_limite" 
                                                   min="<?= date('Y-m-d') ?>">
                                            <div class="form-text mt-2">
                                                <i class="fas fa-clock text-warning me-1"></i>
                                                Laissez vide pour un projet sans date limite
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sections de description détaillée -->
                                <div class="description-sections">
                                    <div class="text-center mb-4">
                                        <h5 class="text-primary fw-bold">
                                            <i class="fas fa-file-alt text-warning me-2"></i>
                                            Description Complète du Projet
                                        </h5>
                                        <p class="text-muted">Structurez votre projet avec des sections détaillées pour une meilleure compréhension</p>
                                    </div>

                                    <div class="description-section">
                                        <label for="description" class="form-label">
                                            <i class="fas fa-align-left"></i>Description Générale
                                        </label>
                                        <textarea class="form-control" id="description" name="description" rows="4" required 
                                                  placeholder="Décrivez brièvement le projet, son contexte et ses enjeux..."></textarea>
                                    </div>

                                    <div class="description-section">
                                        <label for="objectifs" class="form-label">
                                            <i class="fas fa-bullseye"></i>Objectifs Pédagogiques
                                        </label>
                                        <textarea class="form-control" id="objectifs" name="objectifs" rows="3" 
                                                  placeholder="• Objectif 1: Développer les compétences en...&#10;• Objectif 2: Maîtriser les concepts de...&#10;• Objectif 3: Acquérir une expérience pratique en..."></textarea>
                                    </div>

                                    <div class="description-section">
                                        <label for="criteres_evaluation" class="form-label">
                                            <i class="fas fa-check-square"></i>Critères d'Évaluation
                                        </label>
                                        <textarea class="form-control" id="criteres_evaluation" name="criteres_evaluation" rows="3" 
                                                  placeholder="• Qualité technique (30%)&#10;• Respect des délais (20%)&#10;• Innovation et créativité (25%)&#10;• Présentation et documentation (25%)"></textarea>
                                    </div>

                                    <div class="description-section">
                                        <label for="ressources_necessaires" class="form-label">
                                            <i class="fas fa-tools"></i>Ressources Nécessaires
                                        </label>
                                        <textarea class="form-control" id="ressources_necessaires" name="ressources_necessaires" rows="3" 
                                                  placeholder="• Logiciels requis: ...&#10;• Matériel nécessaire: ...&#10;• Accès aux laboratoires: ...&#10;• Bibliographie recommandée: ..."></textarea>
                                    </div>

                                    <div class="description-section">
                                        <label for="competences_developpees" class="form-label">
                                            <i class="fas fa-graduation-cap"></i>Compétences Développées
                                        </label>
                                        <textarea class="form-control" id="competences_developpees" name="competences_developpees" rows="3" 
                                                  placeholder="• Compétences techniques: ...&#10;• Compétences transversales: ...&#10;• Soft skills: travail en équipe, gestion de projet...&#10;• Compétences métier: ..."></textarea>
                                    </div>
                                </div>

                                <!-- Section Définition des Tâches -->
                                <div class="tasks-section">
                                    <div class="text-center mb-4">
                                        <h5 class="text-primary fw-bold">
                                            <i class="fas fa-tasks text-warning me-2"></i>
                                            Définition des Tâches du Projet
                                        </h5>
                                        <p class="text-muted mb-0">Créez les tâches que les étudiants devront accomplir pour ce projet</p>
                                    </div>

                                    <div id="tasksContainer">
                                        <div class="task-item">
                                            <div class="task-header">
                                                <i class="fas fa-clipboard-list me-2"></i>
                                                Tâche #1
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <label class="form-label">
                                                        <i class="fas fa-tag"></i>Titre de la Tâche
                                                    </label>
                                                    <input type="text" class="form-control" name="taches_titres[]" 
                                                           placeholder="Ex: Analyse des besoins et spécifications">
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="form-label">
                                                        <i class="fas fa-flag"></i>Priorité
                                                    </label>
                                                    <select class="form-select" name="taches_priorites[]">
                                                        <option value="basse">🟢 Basse</option>
                                                        <option value="moyenne" selected>🟡 Moyenne</option>
                                                        <option value="haute">🔴 Haute</option>
                                                    </select>
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="form-label">
                                                        <i class="fas fa-calendar-alt"></i>Date Limite (Optionnelle)
                                                    </label>
                                                    <input type="date" class="form-control" name="taches_dates_limites[]" 
                                                           min="<?= date('Y-m-d') ?>">
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <label class="form-label">
                                                    <i class="fas fa-align-left"></i>Description Détaillée
                                                </label>
                                                <textarea class="form-control" name="taches_descriptions[]" rows="3" 
                                                          placeholder="Décrivez précisément ce que l'étudiant doit accomplir dans cette tâche..."></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center mt-4">
                                        <button type="button" class="btn btn-outline-primary me-3" onclick="ajouterTache()">
                                            <i class="fas fa-plus me-2"></i>Ajouter une Tâche
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" onclick="supprimerDerniereTache()">
                                            <i class="fas fa-trash me-2"></i>Supprimer la Dernière Tâche
                                        </button>
                                    </div>
                                </div>

                                <!-- Section Upload de Fichiers -->
                                <div class="file-upload-section">
                                    <div class="file-upload-header">
                                        <h5><i class="fas fa-cloud-upload-alt me-2"></i>Ressources Multimédias du Projet</h5>
                                        <p class="text-muted mb-0">Ajoutez des images, vidéos et documents pour enrichir votre projet</p>
                                    </div>

                                    <!-- Upload Images -->
                                    <div class="mb-4">
                                        <label class="form-label">
                                            <i class="fas fa-images"></i>Images (JPG, PNG, GIF, SVG)
                                        </label>
                                        <div class="file-upload-area" id="imageUploadArea">
                                            <i class="fas fa-image fa-3x text-success mb-3"></i>
                                            <p class="mb-2"><strong>Glissez-déposez vos images ici</strong></p>
                                            <p class="text-muted mb-3">ou cliquez pour parcourir (Max: 10MB par fichier)</p>
                                            <input type="file" class="form-control" name="images[]" id="images" multiple 
                                                   accept="image/*" style="display: none;">
                                            <button type="button" class="btn btn-outline-success" onclick="document.getElementById('images').click()">
                                                <i class="fas fa-plus me-2"></i>Sélectionner Images
                                            </button>
                                        </div>
                                        <div id="imageList" class="file-list" style="display: none;"></div>
                                    </div>

                                    <!-- Upload Vidéos -->
                                    <div class="mb-4">
                                        <label class="form-label">
                                            <i class="fas fa-video"></i>Vidéos (MP4, AVI, MOV, WebM)
                                        </label>
                                        <div class="file-upload-area" id="videoUploadArea">
                                            <i class="fas fa-video fa-3x text-warning mb-3"></i>
                                            <p class="mb-2"><strong>Glissez-déposez vos vidéos ici</strong></p>
                                            <p class="text-muted mb-3">ou cliquez pour parcourir (Max: 50MB par fichier)</p>
                                            <input type="file" class="form-control" name="videos[]" id="videos" multiple 
                                                   accept="video/*" style="display: none;">
                                            <button type="button" class="btn btn-outline-warning" onclick="document.getElementById('videos').click()">
                                                <i class="fas fa-plus me-2"></i>Sélectionner Vidéos
                                            </button>
                                        </div>
                                        <div id="videoList" class="file-list" style="display: none;"></div>
                                    </div>

                                    <!-- Upload Documents -->
                                    <div class="mb-4">
                                        <label class="form-label">
                                            <i class="fas fa-file-alt"></i>Documents (PDF, DOC, DOCX, TXT, PPT, XLS)
                                        </label>
                                        <div class="file-upload-area" id="documentUploadArea">
                                            <i class="fas fa-file-alt fa-3x text-info mb-3"></i>
                                            <p class="mb-2"><strong>Glissez-déposez vos documents ici</strong></p>
                                            <p class="text-muted mb-3">ou cliquez pour parcourir (Max: 20MB par fichier)</p>
                                            <input type="file" class="form-control" name="documents[]" id="documents" multiple 
                                                   accept=".pdf,.doc,.docx,.txt,.rtf,.odt,.ppt,.pptx,.xls,.xlsx" style="display: none;">
                                            <button type="button" class="btn btn-outline-info" onclick="document.getElementById('documents').click()">
                                                <i class="fas fa-plus me-2"></i>Sélectionner Documents
                                            </button>
                                        </div>
                                        <div id="documentList" class="file-list" style="display: none;"></div>
                                    </div>
                                </div>

                                <!-- Section Liens -->
                                <div class="link-section">
                                    <div class="text-center mb-4">
                                        <h5 class="text-primary fw-bold">
                                            <i class="fas fa-link text-warning me-2"></i>
                                            Liens et Ressources Externes
                                        </h5>
                                        <p class="text-muted mb-0">Ajoutez des liens vers des ressources en ligne, tutoriels, ou références</p>
                                    </div>

                                    <div id="linksContainer">
                                        <div class="link-item">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <label class="form-label">
                                                        <i class="fas fa-globe"></i>URL du Lien
                                                    </label>
                                                    <input type="url" class="form-control" name="liens[]" 
                                                           placeholder="https://exemple.com/ressource">
                                                </div>
                                                <div class="col-lg-6">
                                                    <label class="form-label">
                                                        <i class="fas fa-comment"></i>Description du Lien
                                                    </label>
                                                    <input type="text" class="form-control" name="descriptions_liens[]" 
                                                           placeholder="Tutoriel vidéo, documentation officielle, etc.">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center mt-3">
                                        <button type="button" class="btn btn-outline-primary" onclick="ajouterLien()">
                                            <i class="fas fa-plus me-2"></i>Ajouter un Autre Lien
                                        </button>
                                    </div>
                                </div>
                                
                                <?php if ($role == 'admin'): 
                                    $stmt = $pdo->prepare("SELECT id, nom, prenom FROM users WHERE role_id = 2");
                                    $stmt->execute();
                                    $enseignants = $stmt->fetchAll();
                                ?>
                                <div class="mb-4">
                                    <label for="enseignant_id" class="form-label">
                                        <i class="fas fa-user-tie"></i>Enseignant Responsable
                                    </label>
                                    <select class="form-select" id="enseignant_id" name="enseignant_id" required>
                                        <option value="">Sélectionnez un enseignant</option>
                                        <?php foreach ($enseignants as $enseignant): ?>
                                        <option value="<?= $enseignant['id'] ?>">
                                            👨‍🏫 <?= htmlspecialchars(($enseignant['prenom'] ?? '') . ' ' . ($enseignant['nom'] ?? '')) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php else: ?>
                                <input type="hidden" name="enseignant_id" value="<?= $user_id ?>">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Information :</strong> Vous serez automatiquement assigné comme enseignant responsable de ce projet.
                                </div>
                                <?php endif; ?>
                                
                                <div class="text-center mt-5">
                                    <button type="submit" name="creer_projet" class="btn btn-primary btn-lg px-5 py-3">
                                        <i class="fas fa-rocket me-2"></i>Créer le Projet Complet avec Tâches
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Analyses avancées -->
                <div class="tab-pane fade" id="analytics">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-analytics"></i>Analyses Avancées & Métriques de Performance</h4>
                        </div>
                        <div class="content-card-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="bg-light p-4 rounded mb-4">
                                        <h5 class="text-primary mb-3">
                                            <i class="fas fa-chart-pie text-warning me-2"></i>
                                            Répartition par Type de Projet
                                        </h5>
                                        <canvas id="typeChart" height="200"></canvas>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="bg-light p-4 rounded mb-4">
                                        <h5 class="text-primary mb-3">
                                            <i class="fas fa-chart-line text-warning me-2"></i>
                                            Évolution Mensuelle
                                        </h5>
                                        <canvas id="evolutionChart" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-lg-3">
                                    <div class="performance-indicator">
                                        <i class="fas fa-trophy fa-2x mb-3" style="color: #FF9800;"></i>
                                        <div class="value"><?= $stats['nb_projets'] ?? 0 ?></div>
                                        <div class="metric">Projets Actifs</div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="performance-indicator">
                                        <i class="fas fa-users fa-2x mb-3" style="color: #FF9800;"></i>
                                        <div class="value"><?= $role == 'admin' ? ($stats['nb_etudiants'] ?? 0) : ($role == 'enseignant' ? ($stats['nb_etudiants'] ?? 0) : ($stats['nb_projets'] ?? 0)) ?></div>
                                        <div class="metric"><?= $role == 'admin' ? 'Étudiants' : ($role == 'enseignant' ? 'Étudiants' : 'Participations') ?></div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="performance-indicator">
                                        <i class="fas fa-calendar fa-2x mb-3" style="color: #FF9800;"></i>
                                        <div class="value"><?= date('m') ?></div>
                                        <div class="metric">Mois Actuel</div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="performance-indicator">
                                        <i class="fas fa-percentage fa-2x mb-3" style="color: #FF9800;"></i>
                                        <div class="value">85%</div>
                                        <div class="metric">Taux de Réussite</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Graphique mensuel principal
        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(function($month) { return date('M Y', strtotime($month)); }, $last_6_months)) ?>,
                datasets: [{
                    label: 'Projets créés',
                    data: <?= json_encode($monthly_stats) ?>,
                    borderColor: '#009688',
                    backgroundColor: 'rgba(0, 150, 136, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#FF9800',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 150, 136, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 150, 136, 0.1)'
                        }
                    }
                }
            }
        });

        // Graphique de statut
        new Chart(document.getElementById('statusChart'), {
            type: 'pie',
            data: {
                labels: ['Actifs', 'Terminés', 'En attente'],
                datasets: [{
                    data: [<?= $stats['nb_projets'] ?? 0 ?>, 5, 2],
                    backgroundColor: ['#009688', '#4CAF50', '#FF9800'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
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

        <?php if ($role == 'enseignant' || $role == 'admin'): ?>
        // Graphique par type de projet
        new Chart(document.getElementById('typeChart'), {
            type: 'bar',
            data: {
                labels: ['Cahier des charges', 'Sujet pratique', 'Création'],
                datasets: [{
                    label: 'Nombre de projets',
                    data: [3, 5, 2],
                    backgroundColor: ['#009688', '#FF9800', '#4CAF50'],
                    borderWidth: 0,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique d'évolution
        new Chart(document.getElementById('evolutionChart'), {
            type: 'area',
            data: {
                labels: <?= json_encode(array_map(function($month) { return date('M', strtotime($month)); }, $last_6_months)) ?>,
                datasets: [{
                    label: 'Projets',
                    data: <?= json_encode($monthly_stats) ?>,
                    borderColor: '#FF9800',
                    backgroundColor: 'rgba(255, 152, 0, 0.2)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>

        function changerStatutTache(tacheId, nouveauStatut) {
            if (confirm('Êtes-vous sûr de vouloir changer le statut de cette tâche ?')) {
                fetch('changer_statut_tache.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tache_id: tacheId,
                        nouveau_statut: nouveauStatut
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                });
            }
        }

        function soumettreLivrable(tacheId) {
            const fichier = prompt('Veuillez entrer le nom du fichier livrable:');
            if (fichier) {
                const commentaire = prompt('Commentaire (optionnel):');
                
                fetch('soumettre_livrable.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tache_id: tacheId,
                        fichier_nom: fichier,
                        commentaire: commentaire
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Livrable soumis avec succès!');
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                });
            }
        }

        // Fonctions pour la gestion des uploads de fichiers
        function setupFileUpload(inputId, listId, iconClass) {
            const input = document.getElementById(inputId);
            const list = document.getElementById(listId);
            
            input.addEventListener('change', function() {
                displayFiles(this.files, list, iconClass);
            });
        }

        function displayFiles(files, listElement, iconClass) {
            listElement.innerHTML = '';
            if (files.length > 0) {
                listElement.style.display = 'block';
                Array.from(files).forEach((file, index) => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';
                    fileItem.innerHTML = `
                        <div class="file-info">
                            <div class="file-icon ${iconClass}">
                                <i class="fas fa-${iconClass === 'image' ? 'image' : iconClass === 'video' ? 'video' : 'file-alt'}"></i>
                            </div>
                            <div>
                                <div class="fw-bold">${file.name}</div>
                                <div class="text-muted small">${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile(this, '${inputId}', ${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    listElement.appendChild(fileItem);
                });
            } else {
                listElement.style.display = 'none';
            }
        }

        function removeFile(button, inputId, index) {
            // Cette fonction nécessiterait une implémentation plus complexe pour vraiment supprimer le fichier
            // Pour l'instant, on cache juste l'élément
            button.closest('.file-item').remove();
        }

        // Fonction pour ajouter des liens
        function ajouterLien() {
            const container = document.getElementById('linksContainer');
            const newLink = document.createElement('div');
            newLink.className = 'link-item';
            newLink.innerHTML = `
                <div class="row">
                    <div class="col-lg-5">
                        <label class="form-label">
                            <i class="fas fa-globe"></i>URL du Lien
                        </label>
                        <input type="url" class="form-control" name="liens[]" 
                               placeholder="https://exemple.com/ressource">
                    </div>
                    <div class="col-lg-5">
                        <label class="form-label">
                            <i class="fas fa-comment"></i>Description du Lien
                        </label>
                        <input type="text" class="form-control" name="descriptions_liens[]" 
                               placeholder="Tutoriel vidéo, documentation officielle, etc.">
                    </div>
                    <div class="col-lg-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger" onclick="supprimerLien(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newLink);
        }

        function supprimerLien(button) {
            button.closest('.link-item').remove();
        }

        // Variables globales pour les tâches
        let taskCounter = 1;

        // Fonction pour ajouter une tâche
        function ajouterTache() {
            taskCounter++;
            const container = document.getElementById('tasksContainer');
            const newTask = document.createElement('div');
            newTask.className = 'task-item';
            newTask.innerHTML = `
                <div class="task-header">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Tâche #${taskCounter}
                    <button type="button" class="btn btn-sm btn-outline-light float-end" onclick="supprimerTache(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <label class="form-label">
                            <i class="fas fa-tag"></i>Titre de la Tâche
                        </label>
                        <input type="text" class="form-control" name="taches_titres[]" 
                               placeholder="Ex: Développement de l'interface utilisateur">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">
                            <i class="fas fa-flag"></i>Priorité
                        </label>
                        <select class="form-select" name="taches_priorites[]">
                            <option value="basse">🟢 Basse</option>
                            <option value="moyenne" selected>🟡 Moyenne</option>
                            <option value="haute">🔴 Haute</option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt"></i>Date Limite (Optionnelle)
                        </label>
                        <input type="date" class="form-control" name="taches_dates_limites[]" 
                               min="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">
                        <i class="fas fa-align-left"></i>Description Détaillée
                    </label>
                    <textarea class="form-control" name="taches_descriptions[]" rows="3" 
                              placeholder="Décrivez précisément ce que l'étudiant doit accomplir dans cette tâche..."></textarea>
                </div>
            `;
            container.appendChild(newTask);
        }

        // Fonction pour supprimer une tâche spécifique
        function supprimerTache(button) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
                button.closest('.task-item').remove();
                // Recalculer les numéros des tâches
                recalculerNumerosTaches();
            }
        }

        // Fonction pour supprimer la dernière tâche
        function supprimerDerniereTache() {
            const container = document.getElementById('tasksContainer');
            const tasks = container.querySelectorAll('.task-item');
            if (tasks.length > 1) {
                if (confirm('Êtes-vous sûr de vouloir supprimer la dernière tâche ?')) {
                    tasks[tasks.length - 1].remove();
                    taskCounter--;
                }
            } else {
                alert('Vous devez conserver au moins une tâche pour le projet.');
            }
        }

        // Fonction pour recalculer les numéros des tâches
        function recalculerNumerosTaches() {
            const container = document.getElementById('tasksContainer');
            const tasks = container.querySelectorAll('.task-item');
            tasks.forEach((task, index) => {
                const header = task.querySelector('.task-header');
                const taskNumber = index + 1;
                header.innerHTML = `
                    <i class="fas fa-clipboard-list me-2"></i>
                    Tâche #${taskNumber}
                    ${taskNumber > 1 ? '<button type="button" class="btn btn-sm btn-outline-light float-end" onclick="supprimerTache(this)"><i class="fas fa-times"></i></button>' : ''}
                `;
            });
            taskCounter = tasks.length;
        }

        // Initialiser les uploads de fichiers
        document.addEventListener('DOMContentLoaded', function() {
            setupFileUpload('images', 'imageList', 'image');
            setupFileUpload('videos', 'videoList', 'video');
            setupFileUpload('documents', 'documentList', 'document');

            // Drag and drop functionality
            ['imageUploadArea', 'videoUploadArea', 'documentUploadArea'].forEach(areaId => {
                const area = document.getElementById(areaId);
                if (area) {
                    area.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        this.classList.add('dragover');
                    });

                    area.addEventListener('dragleave', function(e) {
                        e.preventDefault();
                        this.classList.remove('dragover');
                    });

                    area.addEventListener('drop', function(e) {
                        e.preventDefault();
                        this.classList.remove('dragover');
                        
                        const inputId = areaId.replace('UploadArea', 's');
                        const input = document.getElementById(inputId);
                        if (input) {
                            input.files = e.dataTransfer.files;
                            input.dispatchEvent(new Event('change'));
                        }
                    });
                }
            });
        });

        // Activation des onglets
        var triggerTabList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="tab"]'))
        triggerTabList.forEach(function (triggerEl) {
            var tabTrigger = new bootstrap.Tab(triggerEl)
            triggerEl.addEventListener('click', function (event) {
                event.preventDefault()
                tabTrigger.show()
            })
        })
    </script>
</body>
</html>