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

// Vérifier les permissions (seuls enseignants et admins peuvent valider)
if ($role != 'enseignant' && $role != 'admin') {
    header('Location: projet.php');
    exit();
}

// Récupérer l'ID du projet
$projet_id = $_GET['id'] ?? null;
if (!$projet_id) {
    header('Location: projet.php');
    exit();
}

// Vérifier si le projet existe et si l'utilisateur a le droit de le valider
$stmt = $pdo->prepare("
    SELECT p.*, u.nom as enseignant_nom, u.prenom as enseignant_prenom, f.nom as filiere_nom
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

// Vérifier les permissions spécifiques
if ($role == 'enseignant' && $projet['enseignant_id'] != $user_id) {
    header('Location: projet.php');
    exit();
}

// Traitement de la validation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['valider_projet'])) {
        // Correction des erreurs: vérifier l'existence des clés avant utilisation
        $statut_validation = $_POST['statut_validation'] ?? 'en_attente';
        $commentaire_validation = $_POST['commentaire_validation'] ?? '';
        $note_globale = $_POST['note_globale'] ?? null;
        
        try {
            // Démarrer une transaction
            $pdo->beginTransaction();
            
            // Mettre à jour le statut de validation du projet
            $stmt = $pdo->prepare("
                UPDATE projets 
                SET statut_validation = ?, commentaire_validation = ?, note_globale = ?, 
                    date_validation = NOW(), validateur_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$statut_validation, $commentaire_validation, $note_globale, $user_id, $projet_id]);
            
            // Si le projet est validé, mettre à jour le statut général
            if ($statut_validation == 'valide') {
                $stmt = $pdo->prepare("UPDATE projets SET statut = 'termine' WHERE id = ?");
                $stmt->execute([$projet_id]);
            }
            
            // Traitement des validations individuelles des tâches
            if (isset($_POST['taches_validations'])) {
                foreach ($_POST['taches_validations'] as $tache_id => $validation_data) {
                    $tache_statut = $validation_data['statut'] ?? 'en_attente';
                    $tache_commentaire = $validation_data['commentaire'] ?? '';
                    $tache_note = $validation_data['note'] ?? null;
                    
                    // Vérifier si une validation existe déjà
                    $stmt = $pdo->prepare("
                        SELECT id FROM validations_taches 
                        WHERE tache_id = ? AND validateur_id = ?
                    ");
                    $stmt->execute([$tache_id, $user_id]);
                    $validation_existante = $stmt->fetch();
                    
                    if ($validation_existante) {
                        // Mettre à jour la validation existante
                        $stmt = $pdo->prepare("
                            UPDATE validations_taches 
                            SET statut_validation = ?, commentaire = ?, note = ?, date_validation = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$tache_statut, $tache_commentaire, $tache_note, $validation_existante['id']]);
                    } else {
                        // Créer une nouvelle validation
                        $stmt = $pdo->prepare("
                            INSERT INTO validations_taches (tache_id, validateur_id, statut_validation, commentaire, note, date_validation)
                            VALUES (?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$tache_id, $user_id, $tache_statut, $tache_commentaire, $tache_note]);
                    }
                }
            }
            
            // Traitement des validations des livrables
            if (isset($_POST['livrables_validations'])) {
                foreach ($_POST['livrables_validations'] as $livrable_id => $validation_data) {
                    $livrable_statut = $validation_data['statut'] ?? 'en_attente';
                    $livrable_commentaire = $validation_data['commentaire'] ?? '';
                    $livrable_note = $validation_data['note'] ?? null;
                    
                    // Vérifier si une validation existe déjà
                    $stmt = $pdo->prepare("
                        SELECT id FROM validations_livrables 
                        WHERE livrable_id = ? AND validateur_id = ?
                    ");
                    $stmt->execute([$livrable_id, $user_id]);
                    $validation_existante = $stmt->fetch();
                    
                    if ($validation_existante) {
                        // Mettre à jour la validation existante
                        $stmt = $pdo->prepare("
                            UPDATE validations_livrables 
                            SET statut_validation = ?, commentaire = ?, note = ?, date_validation = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$livrable_statut, $livrable_commentaire, $livrable_note, $validation_existante['id']]);
                    } else {
                        // Créer une nouvelle validation
                        $stmt = $pdo->prepare("
                            INSERT INTO validations_livrables (livrable_id, validateur_id, statut_validation, commentaire, note, date_validation)
                            VALUES (?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$livrable_id, $user_id, $livrable_statut, $livrable_commentaire, $livrable_note]);
                    }
                }
            }
            
            $pdo->commit();
            $success_message = "Validation du projet enregistrée avec succès!";
            
            // Recharger les données du projet
            $stmt = $pdo->prepare("
                SELECT p.*, u.nom as enseignant_nom, u.prenom as enseignant_prenom, f.nom as filiere_nom,
                       v.nom as validateur_nom, v.prenom as validateur_prenom
                FROM projets p
                JOIN users u ON p.enseignant_id = u.id
                LEFT JOIN filieres f ON p.filiere_id = f.id
                LEFT JOIN users v ON p.validateur_id = v.id
                WHERE p.id = ?
            ");
            $stmt->execute([$projet_id]);
            $projet = $stmt->fetch();
            
        } catch (Exception $e) {
            $pdo->rollback();
            $error_message = "Erreur lors de la validation du projet: " . $e->getMessage();
        }
    }
}

// Récupérer les tâches du projet avec leurs validations
$stmt = $pdo->prepare("
    SELECT t.*, vt.statut_validation, vt.commentaire as validation_commentaire, 
           vt.note as validation_note, vt.date_validation
    FROM taches t
    LEFT JOIN validations_taches vt ON t.id = vt.tache_id AND vt.validateur_id = ?
    WHERE t.projet_id = ?
    ORDER BY t.date_creation ASC
");
$stmt->execute([$user_id, $projet_id]);
$taches = $stmt->fetchAll();

// Récupérer les livrables avec leurs validations
$stmt = $pdo->prepare("
    SELECT l.*, t.titre as tache_titre, u.nom as etudiant_nom, u.prenom as etudiant_prenom,
           vl.statut_validation, vl.commentaire as validation_commentaire, 
           vl.note as validation_note, vl.date_validation
    FROM livrables l
    JOIN taches t ON l.tache_id = t.id
    JOIN users u ON l.etudiant_id = u.id
    LEFT JOIN validations_livrables vl ON l.id = vl.livrable_id AND vl.validateur_id = ?
    WHERE t.projet_id = ?
    ORDER BY l.date_soumission DESC
");
$stmt->execute([$user_id, $projet_id]);
$livrables = $stmt->fetchAll();

// Récupérer les étudiants assignés au projet
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.nom, u.prenom, u.email
    FROM users u
    JOIN projet_etudiants pe ON u.id = pe.etudiant_id
    WHERE pe.projet_id = ?
    ORDER BY u.nom, u.prenom
");
$stmt->execute([$projet_id]);
$etudiants_assignes = $stmt->fetchAll();

// Calculer les statistiques de validation
$stats_validation = [
    'taches_validees' => 0,
    'taches_refusees' => 0,
    'taches_en_attente' => 0,
    'livrables_valides' => 0,
    'livrables_refuses' => 0,
    'livrables_en_attente' => 0
];

foreach ($taches as $tache) {
    switch ($tache['statut_validation'] ?? 'en_attente') {
        case 'valide':
            $stats_validation['taches_validees']++;
            break;
        case 'refuse':
            $stats_validation['taches_refusees']++;
            break;
        default:
            $stats_validation['taches_en_attente']++;
            break;
    }
}

foreach ($livrables as $livrable) {
    switch ($livrable['statut_validation'] ?? 'en_attente') {
        case 'valide':
            $stats_validation['livrables_valides']++;
            break;
        case 'refuse':
            $stats_validation['livrables_refuses']++;
            break;
        default:
            $stats_validation['livrables_en_attente']++;
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation du Projet - <?= htmlspecialchars($projet['titre']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #009688;
            --secondary-color: #ff9800;
            --success-color: #4caf50;
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --info-color: #2196f3;
            --dark-color: #263238;
            --light-color: #eceff1;
            --gradient-primary: linear-gradient(135deg, #009688 0%, #00695c 100%);
            --gradient-secondary: linear-gradient(135deg, #ff9800 0%, #e65100 100%);
            --shadow-primary: 0 8px 32px rgba(0, 150, 136, 0.3);
            --shadow-secondary: 0 8px 32px rgba(255, 152, 0, 0.3);
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #e0f2f1 0%, #fff3e0 100%);
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-color);
            min-height: 100vh;
            line-height: 1.6;
        }

        .main-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-primary);
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
            opacity: 0.3;
        }

        .main-header .container {
            position: relative;
            z-index: 2;
        }

        .main-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .main-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .content-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 150, 136, 0.1);
            transition: var(--transition);
        }

        .content-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
        }

        .content-card-header {
            background: var(--gradient-primary);
            color: white;
            padding: 1.5rem 2rem;
            position: relative;
        }

        .content-card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-secondary);
        }

        .content-card-header h4 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .content-card-header i {
            color: rgba(255, 255, 255, 0.8);
            margin-right: 1rem;
            font-size: 1.5rem;
        }

        .content-card-body {
            padding: 2rem;
        }

        .project-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 6px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .project-info::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(0, 150, 136, 0.1) 0%, transparent 70%);
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border: 2px solid transparent;
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
            background: var(--gradient-primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-primary);
        }

        .stat-card .icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            font-size: 1.8rem;
            color: white;
            position: relative;
        }

        .stat-card.success .icon {
            background: var(--gradient-primary);
        }

        .stat-card.danger .icon {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }

        .stat-card.warning .icon {
            background: var(--gradient-secondary);
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card .label {
            font-size: 0.9rem;
            color: #546e7a;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .validation-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 6px solid var(--secondary-color);
            position: relative;
        }

        .validation-item {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 2px solid #e0e0e0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .validation-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-secondary);
        }

        .validation-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .validation-header {
            background: var(--gradient-primary);
            color: white;
            padding: 1rem 1.5rem;
            margin: -1.5rem -1.5rem 1.5rem -1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: var(--transition);
            background: white;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 150, 136, 0.25);
            outline: none;
        }

        .form-label {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
        }

        .form-label i {
            color: var(--primary-color);
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
            transition: var(--transition);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-success {
            background: var(--gradient-primary);
            box-shadow: var(--shadow-primary);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0, 150, 136, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            box-shadow: 0 8px 32px rgba(244, 67, 54, 0.3);
        }

        .btn-warning {
            background: var(--gradient-secondary);
            color: white;
            box-shadow: var(--shadow-secondary);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            box-shadow: 0 8px 32px rgba(108, 117, 125, 0.3);
        }

        .alert {
            border-radius: var(--border-radius);
            border: none;
            padding: 1.5rem;
            margin-bottom: 2rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 6px;
        }

        .alert-success {
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
            color: #2e7d32;
        }

        .alert-success::before {
            background: var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #c62828;
        }

        .alert-danger::before {
            background: var(--danger-color);
        }

        .badge {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .status-valide {
            background: var(--gradient-primary);
            color: white;
        }

        .status-refuse {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
        }

        .status-en-attente {
            background: var(--gradient-secondary);
            color: white;
        }

        .status-revision {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
        }

        .livrable-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 6px solid var(--primary-color);
            transition: var(--transition);
        }

        .livrable-item:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .validation-status {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 3px solid var(--primary-color);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .validation-status::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 150, 136, 0.05) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .nav-tabs {
            border: none;
            margin-bottom: 2rem;
            background: white;
            border-radius: var(--border-radius);
            padding: 0.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .nav-tabs::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .nav-tabs .nav-link {
            background: transparent;
            border: none;
            color: #546e7a;
            margin: 0 0.25rem;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .nav-tabs .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .nav-tabs .nav-link.active::before {
            left: 0;
        }

        .nav-tabs .nav-link.active {
            color: white;
            box-shadow: var(--shadow-primary);
        }

        .nav-tabs .nav-link i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .card-body {
            padding: 2rem;
        }

        .progress {
            height: 8px;
            border-radius: 10px;
            background: #e0e0e0;
            overflow: hidden;
        }

        .progress-bar {
            background: var(--gradient-primary);
            transition: width 0.6s ease;
        }

        .floating-action {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        .floating-action .btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e0e0e0;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .main-header h1 {
                font-size: 2rem;
            }
            
            .content-card-body {
                padding: 1.5rem;
            }
            
            .stat-card {
                margin-bottom: 1rem;
            }
            
            .nav-tabs .nav-link {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .note-input {
            max-width: 120px;
        }

        .tooltip-custom {
            position: relative;
        }

        .tooltip-custom:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            white-space: nowrap;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="container-fluid">
        <!-- Header -->
        <div class="main-header animate-fade-in">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1>
                            <i class="fas fa-check-circle" style="color: rgba(255, 255, 255, 0.8); margin-right: 1rem;"></i>
                            Validation du Projet
                        </h1>
                        <p class="mb-0">
                            <i class="fas fa-project-diagram" style="color: rgba(255, 255, 255, 0.8); margin-right: 0.5rem;"></i>
                            <?= htmlspecialchars($projet['titre']) ?>
                        </p>
                    </div>
                    <div class="col-lg-4 text-end">
                        <a href="projet.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour aux Projets
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- Messages -->
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success animate-fade-in">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Succès :</strong> <?= $success_message ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger animate-fade-in">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Erreur :</strong> <?= $error_message ?>
            </div>
            <?php endif; ?>

            <!-- Informations du projet -->
            <div class="content-card animate-fade-in">
                <div class="content-card-header">
                    <h4><i class="fas fa-info-circle"></i>Informations du Projet</h4>
                </div>
                <div class="content-card-body">
                    <div class="project-info">
                        <div class="row">
                            <div class="col-lg-8">
                                <h3 class="mb-3" style="color: var(--primary-color);">
                                    <i class="fas fa-project-diagram me-2"></i>
                                    <?= htmlspecialchars($projet['titre']) ?>
                                </h3>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-cog me-2" style="color: var(--primary-color);"></i>Type:</strong>
                                        <?php 
                                        $types = [
                                            'cahier_charge' => 'Cahier des charges',
                                            'sujet_pratique' => 'Sujet pratique',
                                            'creation' => 'Création'
                                        ];
                                        echo $types[$projet['type_projet']] ?? 'Inconnu';
                                        ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-graduation-cap me-2" style="color: var(--primary-color);"></i>Filière:</strong>
                                        <?= htmlspecialchars($projet['filiere_nom'] ?? 'Non spécifiée') ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-user-tie me-2" style="color: var(--primary-color);"></i>Enseignant:</strong>
                                        <?= htmlspecialchars(($projet['enseignant_prenom'] ?? '') . ' ' . ($projet['enseignant_nom'] ?? '')) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-calendar me-2" style="color: var(--primary-color);"></i>Créé le:</strong>
                                        <?= date('d/m/Y à H:i', strtotime($projet['date_creation'])) ?>
                                    </div>
                                </div>
                                <?php if ($projet['date_limite']): ?>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-clock me-2" style="color: var(--primary-color);"></i>Date limite:</strong>
                                        <?= date('d/m/Y', strtotime($projet['date_limite'])) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-hourglass-half me-2" style="color: var(--primary-color);"></i>Statut:</strong>
                                        <span class="badge <?= 
                                            $projet['statut'] == 'actif' ? 'status-valide' : 
                                            ($projet['statut'] == 'termine' ? 'bg-secondary' : 'status-en-attente')
                                        ?>">
                                            <?= ucfirst($projet['statut']) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-lg-4">
                                <div class="validation-status">
                                    <h5 class="mb-3" style="color: var(--primary-color);">Statut de Validation</h5>
                                    <?php if ($projet['statut_validation']): ?>
                                        <span class="badge status-<?= $projet['statut_validation'] ?> fs-6 px-3 py-2">
                                            <i class="fas fa-<?= 
                                                $projet['statut_validation'] == 'valide' ? 'check' : 
                                                ($projet['statut_validation'] == 'refuse' ? 'times' : 
                                                ($projet['statut_validation'] == 'revision' ? 'edit' : 'clock'))
                                            ?>"></i>
                                            <?= ucfirst(str_replace('_', ' ', $projet['statut_validation'])) ?>
                                        </span>
                                        <?php if ($projet['note_globale']): ?>
                                        <div class="mt-3">
                                            <strong>Note globale: </strong>
                                            <span class="fs-4" style="color: var(--primary-color);"><?= $projet['note_globale'] ?>/20</span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($projet['date_validation']): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                Validé le <?= date('d/m/Y à H:i', strtotime($projet['date_validation'])) ?>
                                                <?php if (isset($projet['validateur_nom']) && $projet['validateur_nom']): ?>
                                                    par <?= htmlspecialchars(($projet['validateur_prenom'] ?? '') . ' ' . $projet['validateur_nom']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge status-en-attente fs-6 px-3 py-2">
                                            <i class="fas fa-clock"></i>
                                            En Attente de Validation
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques de validation -->
                    <div class="row">
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="stat-card success">
                                <div class="icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="number"><?= $stats_validation['taches_validees'] ?></div>
                                <div class="label">Tâches Validées</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="stat-card danger">
                                <div class="icon">
                                    <i class="fas fa-times"></i>
                                </div>
                                <div class="number"><?= $stats_validation['taches_refusees'] ?></div>
                                <div class="label">Tâches Refusées</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="stat-card warning">
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="number"><?= $stats_validation['taches_en_attente'] ?></div>
                                <div class="label">Tâches En Attente</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="stat-card success">
                                <div class="icon">
                                    <i class="fas fa-file-check"></i>
                                </div>
                                <div class="number"><?= $stats_validation['livrables_valides'] ?></div>
                                <div class="label">Livrables Validés</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="stat-card danger">
                                <div class="icon">
                                    <i class="fas fa-file-times"></i>
                                </div>
                                <div class="number"><?= $stats_validation['livrables_refuses'] ?></div>
                                <div class="label">Livrables Refusés</div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="stat-card warning">
                                <div class="icon">
                                    <i class="fas fa-file-clock"></i>
                                </div>
                                <div class="number"><?= $stats_validation['livrables_en_attente'] ?></div>
                                <div class="label">Livrables En Attente</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <ul class="nav nav-tabs animate-fade-in">
                <li class="nav-item">
                    <a class="nav-link active" href="#validation-globale" data-bs-toggle="tab">
                        <i class="fas fa-check-circle"></i>Validation Globale
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#validation-taches" data-bs-toggle="tab">
                        <i class="fas fa-tasks"></i>Validation des Tâches
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#validation-livrables" data-bs-toggle="tab">
                        <i class="fas fa-file-upload"></i>Validation des Livrables
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#etudiants-assignes" data-bs-toggle="tab">
                        <i class="fas fa-users"></i>Étudiants Assignés
                    </a>
                </li>
            </ul>

            <!-- Contenu des onglets -->
            <div class="tab-content">
                <!-- Validation globale -->
                <div class="tab-pane fade show active" id="validation-globale">
                    <div class="content-card animate-fade-in">
                        <div class="content-card-header">
                            <h4><i class="fas fa-check-circle"></i>Validation Globale du Projet</h4>
                        </div>
                        <div class="content-card-body">
                            <form method="POST" id="validationForm">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-4">
                                            <label for="statut_validation" class="form-label">
                                                <i class="fas fa-clipboard-check"></i>Statut de Validation
                                            </label>
                                            <select class="form-select" id="statut_validation" name="statut_validation" required>
                                                <option value="en_attente" <?= ($projet['statut_validation'] ?? 'en_attente') == 'en_attente' ? 'selected' : '' ?>>
                                                    ⏳ En Attente
                                                </option>
                                                <option value="valide" <?= ($projet['statut_validation'] ?? '') == 'valide' ? 'selected' : '' ?>>
                                                    ✅ Validé
                                                </option>
                                                <option value="refuse" <?= ($projet['statut_validation'] ?? '') == 'refuse' ? 'selected' : '' ?>>
                                                    ❌ Refusé
                                                </option>
                                                <option value="revision" <?= ($projet['statut_validation'] ?? '') == 'revision' ? 'selected' : '' ?>>
                                                    🔄 À Réviser
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-4">
                                            <label for="note_globale" class="form-label">
                                                <i class="fas fa-star"></i>Note Globale (/20)
                                            </label>
                                            <input type="number" class="form-control" id="note_globale" name="note_globale" 
                                                   min="0" max="20" step="0.5" 
                                                   value="<?= $projet['note_globale'] ?? '' ?>"
                                                   placeholder="Note sur 20">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-4">
                                            <label class="form-label">
                                                <i class="fas fa-info-circle"></i>Informations
                                            </label>
                                            <div class="bg-light p-3 rounded">
                                                <small class="text-muted">
                                                    <strong>Total tâches:</strong> <?= count($taches) ?><br>
                                                    <strong>Total livrables:</strong> <?= count($livrables) ?><br>
                                                    <strong>Étudiants assignés:</strong> <?= count($etudiants_assignes) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="commentaire_validation" class="form-label">
                                        <i class="fas fa-comment"></i>Commentaire de Validation
                                    </label>
                                    <textarea class="form-control" id="commentaire_validation" name="commentaire_validation" 
                                              rows="5" placeholder="Rédigez vos commentaires, observations et recommandations..."><?= htmlspecialchars($projet['commentaire_validation'] ?? '') ?></textarea>
                                </div>

                                <?php if (isset($projet['commentaire_validation']) && $projet['commentaire_validation']): ?>
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-comment-dots me-2"></i>Commentaire Précédent:</h6>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($projet['commentaire_validation'])) ?></p>
                                </div>
                                <?php endif; ?>

                                <div class="text-center">
                                    <button type="submit" name="valider_projet" class="btn btn-success btn-lg px-5">
                                        <i class="fas fa-save me-2"></i>Enregistrer la Validation Globale
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Validation des tâches -->
                <div class="tab-pane fade" id="validation-taches">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-tasks"></i>Validation Détaillée des Tâches</h4>
                        </div>
                        <div class="content-card-body">
                            <?php if (!empty($taches)): ?>
                            <form method="POST">
                                <input type="hidden" name="valider_projet" value="1">
                                <input type="hidden" name="statut_validation" value="<?= $projet['statut_validation'] ?? 'en_attente' ?>">
                                <input type="hidden" name="commentaire_validation" value="<?= htmlspecialchars($projet['commentaire_validation'] ?? '') ?>">
                                <input type="hidden" name="note_globale" value="<?= $projet['note_globale'] ?? '' ?>">

                                <?php foreach ($taches as $index => $tache): ?>
                                <div class="validation-item">
                                    <div class="validation-header">
                                        <div>
                                            <i class="fas fa-clipboard-list me-2"></i>
                                            Tâche #<?= $index + 1 ?>: <?= htmlspecialchars($tache['titre']) ?>
                                        </div>
                                        <?php if (isset($tache['statut_validation']) && $tache['statut_validation']): ?>
                                        <span class="badge status-<?= $tache['statut_validation'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $tache['statut_validation'])) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <strong>Description:</strong>
                                        <p class="text-muted mt-2"><?= nl2br(htmlspecialchars($tache['description'])) ?></p>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="form-label">
                                                <i class="fas fa-clipboard-check"></i>Statut
                                            </label>
                                            <select class="form-select" name="taches_validations[<?= $tache['id'] ?>][statut]">
                                                <option value="en_attente" <?= ($tache['statut_validation'] ?? 'en_attente') == 'en_attente' ? 'selected' : '' ?>>
                                                    ⏳ En Attente
                                                </option>
                                                <option value="valide" <?= ($tache['statut_validation'] ?? '') == 'valide' ? 'selected' : '' ?>>
                                                    ✅ Validé
                                                </option>
                                                <option value="refuse" <?= ($tache['statut_validation'] ?? '') == 'refuse' ? 'selected' : '' ?>>
                                                    ❌ Refusé
                                                </option>
                                                <option value="revision" <?= ($tache['statut_validation'] ?? '') == 'revision' ? 'selected' : '' ?>>
                                                    🔄 À Réviser
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">
                                                <i class="fas fa-star"></i>Note (/20)
                                            </label>
                                            <input type="number" class="form-control note-input" 
                                                   name="taches_validations[<?= $tache['id'] ?>][note]"
                                                   min="0" max="20" step="0.5" 
                                                   value="<?= $tache['validation_note'] ?? '' ?>"
                                                   placeholder="Note">
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label">
                                                <i class="fas fa-comment"></i>Commentaire
                                            </label>
                                            <textarea class="form-control" rows="3"
                                                      name="taches_validations[<?= $tache['id'] ?>][commentaire]"
                                                      placeholder="Commentaires sur cette tâche..."><?= htmlspecialchars($tache['validation_commentaire'] ?? '') ?></textarea>
                                        </div>
                                    </div>

                                    <?php if (isset($tache['date_validation']) && $tache['date_validation']): ?>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Dernière validation: <?= date('d/m/Y à H:i', strtotime($tache['date_validation'])) ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-success btn-lg px-5">
                                        <i class="fas fa-save me-2"></i>Enregistrer les Validations des Tâches
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-tasks fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted mb-2">Aucune tâche définie</h4>
                                <p class="text-muted">Ce projet ne contient pas encore de tâches à valider.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Validation des livrables -->
                <div class="tab-pane fade" id="validation-livrables">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-file-upload"></i>Validation des Livrables Soumis</h4>
                        </div>
                        <div class="content-card-body">
                            <?php if (!empty($livrables)): ?>
                            <form method="POST">
                                <input type="hidden" name="valider_projet" value="1">
                                <input type="hidden" name="statut_validation" value="<?= $projet['statut_validation'] ?? 'en_attente' ?>">
                                <input type="hidden" name="commentaire_validation" value="<?= htmlspecialchars($projet['commentaire_validation'] ?? '') ?>">
                                <input type="hidden" name="note_globale" value="<?= $projet['note_globale'] ?? '' ?>">

                                <?php foreach ($livrables as $livrable): ?>
                                <div class="livrable-item">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <h6 class="mb-2" style="color: var(--primary-color);">
                                                <i class="fas fa-file-alt me-2"></i>
                                                <?= htmlspecialchars($livrable['tache_titre']) ?>
                                            </h6>
                                            <p class="mb-2">
                                                <strong>Étudiant:</strong> 
                                                <?= htmlspecialchars($livrable['etudiant_prenom'] . ' ' . $livrable['etudiant_nom']) ?>
                                            </p>
                                            <p class="mb-2">
                                                <strong>Fichier:</strong> 
                                                <a href="<?= htmlspecialchars($livrable['chemin_fichier']) ?>" target="_blank" style="color: var(--primary-color);">
                                                    <?= htmlspecialchars($livrable['nom_fichier']) ?>
                                                </a>
                                            </p>
                                            <p class="mb-2">
                                                <strong>Soumis le:</strong> 
                                                <?= date('d/m/Y à H:i', strtotime($livrable['date_soumission'])) ?>
                                            </p>
                                            <?php if (isset($livrable['commentaire']) && $livrable['commentaire']): ?>
                                            <p class="mb-0">
                                                <strong>Commentaire étudiant:</strong><br>
                                                <em class="text-muted"><?= nl2br(htmlspecialchars($livrable['commentaire'])) ?></em>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label class="form-label">
                                                        <i class="fas fa-clipboard-check"></i>Statut
                                                    </label>
                                                    <select class="form-select" name="livrables_validations[<?= $livrable['id'] ?>][statut]">
                                                        <option value="en_attente" <?= ($livrable['statut_validation'] ?? 'en_attente') == 'en_attente' ? 'selected' : '' ?>>
                                                            ⏳ En Attente
                                                        </option>
                                                        <option value="valide" <?= ($livrable['statut_validation'] ?? '') == 'valide' ? 'selected' : '' ?>>
                                                            ✅ Validé
                                                        </option>
                                                        <option value="refuse" <?= ($livrable['statut_validation'] ?? '') == 'refuse' ? 'selected' : '' ?>>
                                                            ❌ Refusé
                                                        </option>
                                                        <option value="revision" <?= ($livrable['statut_validation'] ?? '') == 'revision' ? 'selected' : '' ?>>
                                                            🔄 À Réviser
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">
                                                        <i class="fas fa-star"></i>Note (/20)
                                                    </label>
                                                    <input type="number" class="form-control" 
                                                           name="livrables_validations[<?= $livrable['id'] ?>][note]"
                                                           min="0" max="20" step="0.5" 
                                                           value="<?= $livrable['validation_note'] ?? '' ?>"
                                                           placeholder="Note">
                                                </div>
                                                <div class="col-md-5">
                                                    <?php if (isset($livrable['statut_validation']) && $livrable['statut_validation']): ?>
                                                    <div class="mb-2">
                                                        <span class="badge status-<?= $livrable['statut_validation'] ?>">
                                                            <?= ucfirst(str_replace('_', ' ', $livrable['statut_validation'])) ?>
                                                        </span>
                                                        <?php if (isset($livrable['validation_note']) && $livrable['validation_note']): ?>
                                                        <span class="badge bg-info ms-2">
                                                            <?= $livrable['validation_note'] ?>/20
                                                        </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <label class="form-label">
                                                    <i class="fas fa-comment"></i>Commentaire de validation
                                                </label>
                                                <textarea class="form-control" rows="3"
                                                          name="livrables_validations[<?= $livrable['id'] ?>][commentaire]"
                                                          placeholder="Commentaires sur ce livrable..."><?= htmlspecialchars($livrable['validation_commentaire'] ?? '') ?></textarea>
                                            </div>
                                            <?php if (isset($livrable['date_validation']) && $livrable['date_validation']): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Validé le: <?= date('d/m/Y à H:i', strtotime($livrable['date_validation'])) ?>
                                                </small>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-success btn-lg px-5">
                                        <i class="fas fa-save me-2"></i>Enregistrer les Validations des Livrables
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-file-upload fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted mb-2">Aucun livrable soumis</h4>
                                <p class="text-muted">Les étudiants n'ont pas encore soumis de livrables pour ce projet.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Étudiants assignés -->
                <div class="tab-pane fade" id="etudiants-assignes">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-users"></i>Étudiants Assignés au Projet</h4>
                        </div>
                        <div class="content-card-body">
                            <?php if (!empty($etudiants_assignes)): ?>
                            <div class="row">
                                <?php foreach ($etudiants_assignes as $etudiant): ?>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card" style="border: 2px solid var(--primary-color);">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="fas fa-user-graduate fa-3x" style="color: var(--primary-color);"></i>
                                            </div>
                                            <h5 class="card-title" style="color: var(--primary-color);">
                                                <?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?>
                                            </h5>
                                            <p class="card-text">
                                                <i class="fas fa-envelope text-muted me-2"></i>
                                                <?= htmlspecialchars($etudiant['email']) ?>
                                            </p>
                                            <div class="btn-group">
                                                <a href="mailto:<?= htmlspecialchars($etudiant['email']) ?>" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-envelope me-1"></i>Contacter
                                                </a>
                                                <a href="profil_etudiant.php?id=<?= $etudiant['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-user me-1"></i>Profil
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted mb-2">Aucun étudiant assigné</h4>
                                <p class="text-muted">Ce projet n'a pas encore d'étudiants assignés spécifiquement.</p>
                                <p class="text-muted">Les étudiants de la filière <strong><?= htmlspecialchars($projet['filiere_nom'] ?? 'Non spécifiée') ?></strong> peuvent accéder à ce projet.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="floating-action">
        <button class="btn btn-success" onclick="scrollToTop()" data-tooltip="Retour en haut">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activation des onglets avec animation
        var triggerTabList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="tab"]'))
        triggerTabList.forEach(function (triggerEl) {
            var tabTrigger = new bootstrap.Tab(triggerEl)
            triggerEl.addEventListener('click', function (event) {
                event.preventDefault()
                showLoading()
                setTimeout(() => {
                    tabTrigger.show()
                    hideLoading()
                }, 300)
            })
        })

        // Loading overlay functions
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('active')
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active')
        }

        // Scroll to top function
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            })
        }

        // Confirmation avant soumission des validations
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    showLoading()
                    
                    const statutSelect = form.querySelector('select[name="statut_validation"]');
                    if (statutSelect && statutSelect.value === 'refuse') {
                        hideLoading()
                        if (!confirm('⚠️ Êtes-vous sûr de vouloir refuser ce projet ?\n\nCette action nécessitera une justification détaillée dans le commentaire.')) {
                            e.preventDefault();
                            return false;
                        }
                        showLoading()
                    }
                    
                    if (statutSelect && statutSelect.value === 'valide') {
                        hideLoading()
                        if (!confirm('✅ Êtes-vous sûr de vouloir valider définitivement ce projet ?\n\nCette action marquera le projet comme terminé.')) {
                            e.preventDefault();
                            return false;
                        }
                        showLoading()
                    }
                });
            });

            // Animation des cartes au scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in');
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.stat-card, .validation-item, .livrable-item').forEach(el => {
                observer.observe(el);
            });
        });

        // Auto-save des commentaires avec debounce
        let saveTimeout;
        function autoSaveComment(textarea) {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                const projectId = <?= $projet_id ?>;
                const commentType = textarea.name;
                const commentValue = textarea.value;
                
                // Indication visuelle de sauvegarde
                textarea.style.borderColor = '#ff9800';
                setTimeout(() => {
                    textarea.style.borderColor = '#009688';
                }, 1000);
                
                console.log('Auto-saving comment for project', projectId, commentType, commentValue);
            }, 2000);
        }

        // Calcul automatique de la moyenne des notes
        function calculateAverageGrade() {
            const noteInputs = document.querySelectorAll('input[type="number"][name*="[note]"]');
            let total = 0;
            let count = 0;
            
            noteInputs.forEach(input => {
                if (input.value && parseFloat(input.value) > 0) {
                    total += parseFloat(input.value);
                    count++;
                }
            });
            
            if (count > 0) {
                const average = (total / count).toFixed(1);
                const globalNoteInput = document.getElementById('note_globale');
                if (globalNoteInput && !globalNoteInput.value) {
                    globalNoteInput.value = average;
                    globalNoteInput.style.borderColor = '#ff9800';
                    setTimeout(() => {
                        globalNoteInput.style.borderColor = '#009688';
                    }, 1000);
                }
            }
        }

        // Écouter les changements sur les notes individuelles
        document.addEventListener('input', function(e) {
            if (e.target.type === 'number' && e.target.name.includes('[note]')) {
                calculateAverageGrade();
            }
            
            if (e.target.tagName === 'TEXTAREA') {
                autoSaveComment(e.target);
            }
        });

        // Progress bar animation
        function animateProgressBars() {
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + S pour sauvegarder
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const activeForm = document.querySelector('.tab-pane.active form');
                if (activeForm) {
                    activeForm.submit();
                }
            }
            
            // Échap pour fermer les modales
            if (e.key === 'Escape') {
                hideLoading();
            }
        });

        // Smooth scroll pour les liens internes
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Notification toast (optionnel)
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        // Initialisation
        window.addEventListener('load', function() {
            hideLoading();
            animateProgressBars();
        });
    </script>
</body>
</html>