<?php
session_start();
require_once '../config/database.php';

// Vérifier connexion et rôle (enseignant id=2 ou admin id=3)
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3)) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];
$errors = [];
$success = "";

// Vérifier si l'ID du cours à supprimer est présent
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $errors[] = "Aucun cours spécifié pour la suppression.";
    header("Location: cours.php");
    exit;
}

$cours_id = $_GET['id'];

// Récupérer les informations du cours
$stmtCours = $pdo->prepare("
    SELECT c.*, u.nom AS enseignant_nom, f.nom AS filiere_nom 
    FROM cours c 
    JOIN users u ON c.enseignant_id = u.id 
    JOIN filieres f ON c.filiere_id = f.id 
    WHERE c.id = ?
");
$stmtCours->execute([$cours_id]);
$cours = $stmtCours->fetch(PDO::FETCH_ASSOC);

if (!$cours) {
    $errors[] = "Le cours demandé n'existe pas.";
    header("Location: cours.php");
    exit;
}

// Vérifier les permissions : l'enseignant ne peut supprimer que ses propres cours
if ($role_id == 2 && $cours['enseignant_id'] != $user_id) {
    $errors[] = "Vous n'avez pas la permission de supprimer ce cours.";
    header("Location: cours.php");
    exit;
}

// Traitement de la suppression après confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete'])) {
        try {
            // Commencer une transaction pour s'assurer que tout se passe bien
            $pdo->beginTransaction();
            
            // 1. Vérifier s'il y a des modules associés à ce cours
            $stmtCheckModules = $pdo->prepare("SELECT COUNT(*) as module_count FROM modules WHERE cours_id = ?");
            $stmtCheckModules->execute([$cours_id]);
            $moduleCount = $stmtCheckModules->fetch(PDO::FETCH_ASSOC)['module_count'];
            
            // 2. Vérifier s'il y a d'autres dépendances (exercices, évaluations, etc.)
            $stmtCheckExercices = $pdo->prepare("SELECT COUNT(*) as exercice_count FROM exercices WHERE cours_id = ?");
            $stmtCheckExercices->execute([$cours_id]);
            $exerciceCount = $stmtCheckExercices->fetch(PDO::FETCH_ASSOC)['exercice_count'];
            
            // 3. Si des dépendances existent, proposer une suppression en cascade ou empêcher la suppression
            if ($moduleCount > 0 || $exerciceCount > 0) {
                // Option 1: Suppression en cascade (décommenter si vous voulez cette fonctionnalité)
                /*
                // Supprimer d'abord les exercices associés
                if ($exerciceCount > 0) {
                    $stmtDeleteExercices = $pdo->prepare("DELETE FROM exercices WHERE cours_id = ?");
                    $stmtDeleteExercices->execute([$cours_id]);
                }
                
                // Supprimer ensuite les modules associés
                if ($moduleCount > 0) {
                    $stmtDeleteModules = $pdo->prepare("DELETE FROM modules WHERE cours_id = ?");
                    $stmtDeleteModules->execute([$cours_id]);
                }
                */
                
                // Option 2: Empêcher la suppression et afficher un message d'erreur
                throw new Exception("Impossible de supprimer ce cours car il contient des modules ou exercices associés. Veuillez d'abord supprimer les éléments dépendants.");
            }
            
            // 4. Supprimer les fichiers associés s'ils existent
            $fichiers = [$cours['image_couverture'], $cours['video_cours'], $cours['pdf_cours']];
            
            foreach ($fichiers as $fichier) {
                if ($fichier && file_exists($fichier) && is_file($fichier)) {
                    unlink($fichier);
                }
            }
            
            // 5. Supprimer le cours de la base de données
            $stmtDelete = $pdo->prepare("DELETE FROM cours WHERE id = ?");
            $stmtDelete->execute([$cours_id]);
            
            $pdo->commit();
            
            $success = "Le cours a été supprimé avec succès !";
            
            // Redirection après suppression réussie
            header("Refresh: 2; URL=cours.php");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Une erreur est survenue lors de la suppression : " . $e->getMessage();
        }
    } elseif (isset($_POST['cancel_delete'])) {
        // Annulation de la suppression - redirection vers la page des cours
        header("Location: cours.php");
        exit;
    }
}

// Fonction pour déterminer le tableau de bord en fonction du rôle
function getDashboardUrl($role_id) {
    switch ($role_id) {
        case 1: // Étudiant
            return "student_dashboard.php";
        case 2: // Enseignant
            return "teacher_dashboard.php";
        case 3: // Admin
            return "admin_dashboard.php";
        default:
            return "dashboard.php";
    }
}

$dashboard_url = getDashboardUrl($role_id);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer un cours - Confirmation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reprise du style de votre fichier cours.php */
        :root {
            --primary-color: #009688;
            --secondary-color: #FF9800;
            --accent-color: #E91E63;
            --success-color: #4CAF50;
            --warning-color: #FF5722;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --text-color: #333;
            --text-light: #777;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: linear-gradient(to bottom, var(--primary-color), #00766C);
            color: white;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            box-shadow: var(--box-shadow);
            z-index: 100;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-header h1 {
            font-size: 1.5rem;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            text-decoration: none;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        .nav-item:hover, .nav-item.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .nav-item i {
            margin-right: 12px;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            padding: 10px 15px;
            border-radius: var(--border-radius);
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .logout-btn i {
            margin-right: 10px;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .content-container {
            max-width: 800px;
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
        }

        .header h2 {
            font-size: 2rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb i {
            margin: 0 8px;
            font-size: 0.8rem;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(to right, var(--primary-color), #26A69A);
            color: white;
        }

        .card-header h1 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 30px;
        }

        .messages {
            margin-bottom: 25px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .course-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            border-left: 4px solid var(--primary-color);
        }

        .course-info h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .info-value {
            color: var(--text-color);
            padding: 8px 12px;
            background-color: white;
            border-radius: var(--border-radius);
            border: 1px solid #e0e0e0;
        }

        .dependencies-info {
            background-color: #fff3e0;
            border: 2px solid var(--warning-color);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 25px;
        }

        .dependencies-info h4 {
            color: var(--warning-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dependencies-list {
            list-style-type: none;
            padding-left: 0;
        }

        .dependencies-list li {
            padding: 8px 0;
            border-bottom: 1px solid #ffe0b2;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dependencies-list li:last-child {
            border-bottom: none;
        }

        .warning-box {
            background-color: #fff3e0;
            border: 2px solid var(--warning-color);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }

        .warning-box i {
            font-size: 2rem;
            color: var(--warning-color);
            margin-bottom: 15px;
            display: block;
        }

        .warning-box h3 {
            color: var(--warning-color);
            margin-bottom: 10px;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            gap: 8px;
            min-width: 140px;
        }

        .btn-danger {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #e64a19;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #3d8b40;
            transform: translateY(-2px);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #00897B;
            transform: translateY(-2px);
        }

        .btn i {
            font-size: 1.1rem;
        }

        @media (max-width: 992px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 15px 0;
            }
            
            .sidebar-nav {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .nav-item {
                margin-bottom: 0;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header h2 {
                font-size: 1.7rem;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .sidebar-nav {
                flex-direction: column;
                gap: 5px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-chalkboard-teacher"></i> 
                    <?= $role_id == 3 ? 'Espace Admin' : 'Espace Enseignant' ?>
                </h1>
            </div>
            
            <nav class="sidebar-nav">
                <a href="<?= $dashboard_url ?>" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de bord</span>
                </a>
                <a href="cours.php" class="nav-item">
                    <i class="fas fa-book"></i>
                    <span>Mes cours</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendrier</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Étudiants</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Statistiques</span>
                </a>
                <?php if ($role_id == 3): ?>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Administration</span>
                </a>
                <?php endif; ?>
            </nav>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-container">
                <div class="header">
                    <h2><i class="fas fa-trash-alt"></i> Supprimer un cours</h2>
                    <div class="breadcrumb">
                        <a href="<?= $dashboard_url ?>">Tableau de bord</a>
                        <i class="fas fa-chevron-right"></i>
                        <a href="cours.php">Mes cours</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>Supprimer</span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h1><i class="fas fa-trash-alt"></i> Confirmation de suppression</h1>
                    </div>
                    
                    <div class="card-body">
                        <!-- Messages d'alerte -->
                        <div class="messages">
                            <?php foreach ($errors as $err): ?>
                                <div class="alert alert-error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?= htmlspecialchars($err) ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <?= htmlspecialchars($success) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($errors) && empty($success)): ?>
                            <!-- Informations du cours -->
                            <div class="course-info">
                                <h3><i class="fas fa-info-circle"></i> Cours à supprimer</h3>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Titre du cours</span>
                                        <span class="info-value"><?= htmlspecialchars($cours['titre']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Filière</span>
                                        <span class="info-value"><?= htmlspecialchars($cours['filiere_nom']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Enseignant</span>
                                        <span class="info-value"><?= htmlspecialchars($cours['enseignant_nom']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Date de création</span>
                                        <span class="info-value"><?= date('d/m/Y à H:i', strtotime($cours['created_at'])) ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Vérification des dépendances -->
                            <?php
                            // Vérifier les dépendances
                            $stmtCheckModules = $pdo->prepare("SELECT COUNT(*) as module_count FROM modules WHERE cours_id = ?");
                            $stmtCheckModules->execute([$cours_id]);
                            $moduleCount = $stmtCheckModules->fetch(PDO::FETCH_ASSOC)['module_count'];
                            
                            $stmtCheckExercices = $pdo->prepare("SELECT COUNT(*) as exercice_count FROM exercices WHERE cours_id = ?");
                            $stmtCheckExercices->execute([$cours_id]);
                            $exerciceCount = $stmtCheckExercices->fetch(PDO::FETCH_ASSOC)['exercice_count'];
                            ?>

                            <?php if ($moduleCount > 0 || $exerciceCount > 0): ?>
                                <div class="dependencies-info">
                                    <h4><i class="fas fa-exclamation-triangle"></i> Dépendances détectées</h4>
                                    <p>Ce cours contient des éléments associés qui doivent être traités :</p>
                                    <ul class="dependencies-list">
                                        <?php if ($moduleCount > 0): ?>
                                            <li><i class="fas fa-folder"></i> <?= $moduleCount ?> module(s) associé(s)</li>
                                        <?php endif; ?>
                                        <?php if ($exerciceCount > 0): ?>
                                            <li><i class="fas fa-tasks"></i> <?= $exerciceCount ?> exercice(s) associé(s)</li>
                                        <?php endif; ?>
                                    </ul>
                                    <p style="margin-top: 10px; font-style: italic;">
                                        <strong>Solution :</strong> Veuillez d'abord supprimer manuellement ces éléments ou contacter l'administrateur.
                                    </p>
                                </div>
                            <?php endif; ?>

                            <!-- Avertissement -->
                            <div class="warning-box">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h3>Attention ! Cette action est irréversible</h3>
                                <p>Vous êtes sur le point de supprimer définitivement ce cours. Cette action supprimera également tous les fichiers associés (image, vidéo, PDF).</p>
                                <p><strong>Êtes-vous sûr de vouloir continuer ?</strong></p>
                            </div>

                            <!-- Formulaire de confirmation -->
                            <form method="POST">
                                <div class="btn-group">
                                    <button type="submit" name="confirm_delete" class="btn btn-danger" 
                                        <?= ($moduleCount > 0 || $exerciceCount > 0) ? 'disabled' : '' ?>>
                                        <i class="fas fa-trash-alt"></i>
                                        <?= ($moduleCount > 0 || $exerciceCount > 0) ? 'Suppression bloquée' : 'Confirmer la suppression' ?>
                                    </button>
                                    <a href="cours.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i>
                                        Annuler
                                    </a>
                                    <a href="<?= $dashboard_url ?>" class="btn btn-primary">
                                        <i class="fas fa-tachometer-alt"></i>
                                        Tableau de bord
                                    </a>
                                </div>
                            </form>
                        <?php elseif ($success): ?>
                            <!-- Message de succès avec redirection -->
                            <div class="btn-group" style="margin-top: 20px;">
                                <a href="cours.php" class="btn btn-success">
                                    <i class="fas fa-arrow-left"></i>
                                    Retour aux cours
                                </a>
                                <a href="<?= $dashboard_url ?>" class="btn btn-primary">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Tableau de bord
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- En cas d'erreur -->
                            <div class="btn-group" style="margin-top: 20px;">
                                <a href="cours.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i>
                                    Retour aux cours
                                </a>
                                <a href="<?= $dashboard_url ?>" class="btn btn-primary">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Tableau de bord
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Confirmation supplémentaire pour éviter les suppressions accidentelles
        const confirmButton = document.querySelector('button[name="confirm_delete"]');
        if (confirmButton && !confirmButton.disabled) {
            confirmButton.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous ABSOLUMENT sûr de vouloir supprimer ce cours ? Cette action ne peut pas être annulée.')) {
                    e.preventDefault();
                }
            });
        }

        // Redirection automatique après suppression réussie
        <?php if ($success): ?>
            setTimeout(function() {
                window.location.href = 'cours.php';
            }, 2000);
        <?php endif; ?>

        // Gestion des ancres pour la navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId !== '#') {
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 20,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    </script>
</body>
</html>