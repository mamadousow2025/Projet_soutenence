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

// Vérifier les permissions (seuls enseignants et admins peuvent supprimer)
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

// Vérifier si le projet existe et si l'utilisateur a le droit de le supprimer
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

// Fonction pour supprimer récursivement un dossier et son contenu
function supprimerDossier($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            supprimerDossier($path);
        } else {
            unlink($path);
        }
    }
    return rmdir($dir);
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmer_suppression'])) {
    try {
        // Démarrer une transaction
        $pdo->beginTransaction();
        
        // Récupérer tous les fichiers associés au projet
        $stmt = $pdo->prepare("SELECT * FROM projet_fichiers WHERE projet_id = ?");
        $stmt->execute([$projet_id]);
        $fichiers = $stmt->fetchAll();
        
        // Supprimer les fichiers physiques
        foreach ($fichiers as $fichier) {
            if (file_exists($fichier['chemin_fichier'])) {
                unlink($fichier['chemin_fichier']);
            }
        }
        
        // Supprimer le dossier du projet s'il existe
        $upload_dir = '../uploads/projets/' . $projet_id;
        if (is_dir($upload_dir)) {
            supprimerDossier($upload_dir);
        }
        
        // Supprimer les enregistrements de la base de données dans l'ordre correct
        // 1. Supprimer les livrables
        $stmt = $pdo->prepare("DELETE FROM livrables WHERE tache_id IN (SELECT id FROM taches WHERE projet_id = ?)");
        $stmt->execute([$projet_id]);
        
        // 2. Supprimer les tâches
        $stmt = $pdo->prepare("DELETE FROM taches WHERE projet_id = ?");
        $stmt->execute([$projet_id]);
        
        // 3. Supprimer les fichiers
        $stmt = $pdo->prepare("DELETE FROM projet_fichiers WHERE projet_id = ?");
        $stmt->execute([$projet_id]);
        
        // 4. Supprimer les liens
        $stmt = $pdo->prepare("DELETE FROM projet_liens WHERE projet_id = ?");
        $stmt->execute([$projet_id]);
        
        // 5. Supprimer les associations projet-étudiants
        $stmt = $pdo->prepare("DELETE FROM projet_etudiants WHERE projet_id = ?");
        $stmt->execute([$projet_id]);
        
        // 6. Supprimer le projet lui-même
        $stmt = $pdo->prepare("DELETE FROM projets WHERE id = ?");
        $stmt->execute([$projet_id]);
        
        $pdo->commit();
        
        // Rediriger avec message de succès
        $_SESSION['success_message'] = "Le projet '" . htmlspecialchars($projet['titre']) . "' a été supprimé avec succès, ainsi que tous ses fichiers et données associées.";
        header('Location: projet.php');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollback();
        $error_message = "Erreur lors de la suppression du projet: " . $e->getMessage();
    }
}

// Récupérer les statistiques du projet pour l'affichage
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT t.id) as nb_taches,
        COUNT(DISTINCT pf.id) as nb_fichiers,
        COUNT(DISTINCT pl.id) as nb_liens,
        COUNT(DISTINCT pe.etudiant_id) as nb_etudiants
    FROM projets p
    LEFT JOIN taches t ON p.id = t.projet_id
    LEFT JOIN projet_fichiers pf ON p.id = pf.projet_id
    LEFT JOIN projet_liens pl ON p.id = pl.projet_id
    LEFT JOIN projet_etudiants pe ON p.id = pe.projet_id
    WHERE p.id = ?
");
$stmt->execute([$projet_id]);
$stats_projet = $stmt->fetch();

// Récupérer les tâches du projet
$stmt = $pdo->prepare("SELECT * FROM taches WHERE projet_id = ? ORDER BY date_creation ASC");
$stmt->execute([$projet_id]);
$taches = $stmt->fetchAll();

// Récupérer les fichiers du projet
$stmt = $pdo->prepare("SELECT * FROM projet_fichiers WHERE projet_id = ? ORDER BY date_upload ASC");
$stmt->execute([$projet_id]);
$fichiers = $stmt->fetchAll();

// Récupérer les liens du projet
$stmt = $pdo->prepare("SELECT * FROM projet_liens WHERE projet_id = ? ORDER BY date_ajout ASC");
$stmt->execute([$projet_id]);
$liens = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer le Projet - <?= htmlspecialchars($projet['titre']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c3e50;
            min-height: 100vh;
        }

        .main-header {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
            padding: 25px 0;
            margin-bottom: 40px;
            box-shadow: 0 4px 20px rgba(244, 67, 54, 0.3);
        }

        .main-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .content-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .content-card-header {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
            padding: 25px 30px;
        }

        .content-card-header h4 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .content-card-header i {
            color: #FFE0B2;
            margin-right: 15px;
            font-size: 26px;
        }

        .content-card-body {
            padding: 30px;
        }

        .warning-box {
            background: linear-gradient(135deg, #fff3e0 0%, #ffcc02 100%);
            border: 2px solid #ff9800;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
        }

        .warning-box h3 {
            color: #e65100;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .warning-box p {
            color: #bf360c;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 0;
        }

        .danger-zone {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            border: 3px solid #f44336;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .danger-zone h4 {
            color: #c62828;
            font-weight: 800;
            margin-bottom: 20px;
            text-align: center;
        }

        .project-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid #009688;
        }

        .stat-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .stat-item .icon {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
            font-size: 24px;
        }

        .stat-item .number {
            font-size: 36px;
            font-weight: 800;
            color: #f44336;
            margin-bottom: 5px;
        }

        .stat-item .label {
            font-size: 14px;
            color: #546e7a;
            font-weight: 600;
            text-transform: uppercase;
        }

        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 12px 25px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 14px;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-outline-danger {
            border: 2px solid #f44336;
            color: #f44336;
            background: transparent;
        }

        .btn-outline-danger:hover {
            background: #f44336;
            color: white;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 20px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-danger {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .items-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
        }

        .item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #f44336;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .item:last-child {
            margin-bottom: 0;
        }

        .confirmation-section {
            background: linear-gradient(135deg, #ffebee 0%, #f8bbd9 100%);
            border: 3px dashed #f44336;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-top: 30px;
        }

        .confirmation-section h5 {
            color: #c62828;
            font-weight: 800;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .form-check-input:checked {
            background-color: #f44336;
            border-color: #f44336;
        }

        .form-check-label {
            font-weight: 600;
            color: #c62828;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="main-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1>
                            <i class="fas fa-trash-alt" style="color: #FFE0B2; margin-right: 20px;"></i>
                            Suppression Définitive du Projet
                        </h1>
                        <p class="mb-0 fs-5">
                            <i class="fas fa-exclamation-triangle" style="color: #FFE0B2; margin-right: 10px;"></i>
                            Action irréversible - Toutes les données seront perdues
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
            <!-- Messages d'erreur -->
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Erreur :</strong> <?= $error_message ?>
            </div>
            <?php endif; ?>

            <!-- Avertissement principal -->
            <div class="warning-box">
                <h3>
                    <i class="fas fa-exclamation-triangle me-3"></i>
                    ATTENTION - SUPPRESSION DÉFINITIVE
                </h3>
                <p>
                    Cette action supprimera définitivement le projet et TOUTES ses données associées.<br>
                    Cette opération est <strong>IRRÉVERSIBLE</strong> et ne peut pas être annulée.
                </p>
            </div>

            <!-- Informations du projet -->
            <div class="content-card">
                <div class="content-card-header">
                    <h4><i class="fas fa-info-circle"></i>Informations du Projet à Supprimer</h4>
                </div>
                <div class="content-card-body">
                    <div class="project-info">
                        <div class="row">
                            <div class="col-lg-8">
                                <h3 class="text-primary mb-3">
                                    <i class="fas fa-project-diagram text-warning me-2"></i>
                                    <?= htmlspecialchars($projet['titre']) ?>
                                </h3>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-cog text-warning me-2"></i>Type:</strong>
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
                                        <strong><i class="fas fa-graduation-cap text-warning me-2"></i>Filière:</strong>
                                        <?= htmlspecialchars($projet['filiere_nom'] ?? 'Non spécifiée') ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-user-tie text-warning me-2"></i>Enseignant:</strong>
                                        <?= htmlspecialchars(($projet['enseignant_prenom'] ?? '') . ' ' . ($projet['enseignant_nom'] ?? '')) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-calendar text-warning me-2"></i>Créé le:</strong>
                                        <?= date('d/m/Y à H:i', strtotime($projet['date_creation'])) ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <strong><i class="fas fa-align-left text-warning me-2"></i>Description:</strong>
                                    <p class="mt-2 text-muted"><?= nl2br(htmlspecialchars($projet['description'])) ?></p>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="text-center">
                                    <span class="badge <?= 
                                        $projet['statut'] == 'actif' ? 'bg-success' : 
                                        ($projet['statut'] == 'termine' ? 'bg-secondary' : 'bg-warning')
                                    ?> fs-6 px-3 py-2">
                                        <i class="fas fa-<?= 
                                            $projet['statut'] == 'actif' ? 'play' : 
                                            ($projet['statut'] == 'termine' ? 'check' : 'pause')
                                        ?>"></i>
                                        <?= ucfirst($projet['statut']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques du projet -->
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-item">
                                <div class="icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="number"><?= $stats_projet['nb_taches'] ?></div>
                                <div class="label">Tâches</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-item">
                                <div class="icon">
                                    <i class="fas fa-files"></i>
                                </div>
                                <div class="number"><?= $stats_projet['nb_fichiers'] ?></div>
                                <div class="label">Fichiers</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-item">
                                <div class="icon">
                                    <i class="fas fa-links"></i>
                                </div>
                                <div class="number"><?= $stats_projet['nb_liens'] ?></div>
                                <div class="label">Liens</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-item">
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="number"><?= $stats_projet['nb_etudiants'] ?></div>
                                <div class="label">Étudiants</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détails des éléments qui seront supprimés -->
            <div class="content-card">
                <div class="content-card-header">
                    <h4><i class="fas fa-list"></i>Éléments qui seront Supprimés Définitivement</h4>
                </div>
                <div class="content-card-body">
                    <div class="row">
                        <!-- Tâches -->
                        <div class="col-lg-6">
                            <h5 class="text-danger mb-3">
                                <i class="fas fa-tasks me-2"></i>
                                Tâches (<?= count($taches) ?>)
                            </h5>
                            <?php if (!empty($taches)): ?>
                            <div class="items-list">
                                <?php foreach ($taches as $tache): ?>
                                <div class="item">
                                    <div>
                                        <strong><?= htmlspecialchars($tache['titre']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars(substr($tache['description'], 0, 50)) ?>...</small>
                                    </div>
                                    <span class="badge bg-<?= $tache['priorite'] == 'haute' ? 'danger' : ($tache['priorite'] == 'moyenne' ? 'warning' : 'secondary') ?>">
                                        <?= ucfirst($tache['priorite']) ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">Aucune tâche à supprimer</p>
                            <?php endif; ?>
                        </div>

                        <!-- Fichiers -->
                        <div class="col-lg-6">
                            <h5 class="text-danger mb-3">
                                <i class="fas fa-files me-2"></i>
                                Fichiers (<?= count($fichiers) ?>)
                            </h5>
                            <?php if (!empty($fichiers)): ?>
                            <div class="items-list">
                                <?php foreach ($fichiers as $fichier): ?>
                                <div class="item">
                                    <div>
                                        <i class="fas fa-<?= $fichier['type_fichier'] == 'image' ? 'image' : ($fichier['type_fichier'] == 'video' ? 'video' : 'file-alt') ?> me-2"></i>
                                        <strong><?= htmlspecialchars($fichier['nom_original']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= round($fichier['taille_fichier'] / 1024 / 1024, 2) ?> MB</small>
                                    </div>
                                    <span class="badge bg-info"><?= ucfirst($fichier['type_fichier']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">Aucun fichier à supprimer</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Liens -->
                    <?php if (!empty($liens)): ?>
                    <div class="mt-4">
                        <h5 class="text-danger mb-3">
                            <i class="fas fa-links me-2"></i>
                            Liens Externes (<?= count($liens) ?>)
                        </h5>
                        <div class="items-list">
                            <?php foreach ($liens as $lien): ?>
                            <div class="item">
                                <div>
                                    <strong><a href="<?= htmlspecialchars($lien['url']) ?>" target="_blank" class="text-primary"><?= htmlspecialchars($lien['url']) ?></a></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($lien['description']) ?></small>
                                </div>
                                <i class="fas fa-external-link-alt text-muted"></i>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Zone de danger - Confirmation -->
            <div class="danger-zone">
                <h4>
                    <i class="fas fa-skull-crossbones me-3"></i>
                    ZONE DE DANGER - SUPPRESSION DÉFINITIVE
                </h4>
                
                <div class="confirmation-section">
                    <h5>
                        <i class="fas fa-hand-paper me-2"></i>
                        Confirmation Requise
                    </h5>
                    
                    <form method="POST" id="deleteForm">
                        <div class="mb-4">
                            <div class="form-check d-flex justify-content-center align-items-center">
                                <input class="form-check-input me-3" type="checkbox" id="confirmDelete" required 
                                       style="transform: scale(1.5);">
                                <label class="form-check-label" for="confirmDelete">
                                    Je comprends que cette action est <strong>IRRÉVERSIBLE</strong> et supprimera définitivement le projet "<?= htmlspecialchars($projet['titre']) ?>" ainsi que toutes ses données associées (tâches, fichiers, liens, etc.)
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check d-flex justify-content-center align-items-center">
                                <input class="form-check-input me-3" type="checkbox" id="confirmBackup" required 
                                       style="transform: scale(1.5);">
                                <label class="form-check-label" for="confirmBackup">
                                    J'ai effectué une sauvegarde si nécessaire et je souhaite procéder à la suppression
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-center gap-3">
                            <button type="submit" name="confirmer_suppression" class="btn btn-danger btn-lg px-5" 
                                    id="deleteButton" disabled>
                                <i class="fas fa-trash-alt me-2"></i>
                                SUPPRIMER DÉFINITIVEMENT LE PROJET
                            </button>
                            <a href="projet.php" class="btn btn-secondary btn-lg px-5">
                                <i class="fas fa-times me-2"></i>
                                Annuler et Retourner
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion de l'activation du bouton de suppression
        document.addEventListener('DOMContentLoaded', function() {
            const confirmDelete = document.getElementById('confirmDelete');
            const confirmBackup = document.getElementById('confirmBackup');
            const deleteButton = document.getElementById('deleteButton');
            const deleteForm = document.getElementById('deleteForm');
            
            function checkConfirmations() {
                if (confirmDelete.checked && confirmBackup.checked) {
                    deleteButton.disabled = false;
                    deleteButton.classList.remove('btn-outline-danger');
                    deleteButton.classList.add('btn-danger');
                } else {
                    deleteButton.disabled = true;
                    deleteButton.classList.remove('btn-danger');
                    deleteButton.classList.add('btn-outline-danger');
                }
            }
            
            confirmDelete.addEventListener('change', checkConfirmations);
            confirmBackup.addEventListener('change', checkConfirmations);
            
            // Confirmation finale avant soumission
            deleteForm.addEventListener('submit', function(e) {
                if (!confirm('DERNIÈRE CONFIRMATION\n\nÊtes-vous absolument certain de vouloir supprimer définitivement le projet "<?= addslashes($projet['titre']) ?>" ?\n\nCette action est IRRÉVERSIBLE et supprimera :\n- Le projet lui-même\n- Toutes les tâches (<?= $stats_projet['nb_taches'] ?>)\n- Tous les fichiers (<?= $stats_projet['nb_fichiers'] ?>)\n- Tous les liens (<?= $stats_projet['nb_liens'] ?>)\n- Toutes les associations étudiants\n\nTapez "SUPPRIMER" pour confirmer :')) {
                    e.preventDefault();
                    return false;
                }
                
                const finalConfirm = prompt('Pour confirmer définitivement, tapez exactement "SUPPRIMER" (en majuscules) :');
                if (finalConfirm !== 'SUPPRIMER') {
                    e.preventDefault();
                    alert('Suppression annulée. Le texte de confirmation ne correspond pas.');
                    return false;
                }
                
                // Désactiver le bouton pour éviter les doubles clics
                deleteButton.disabled = true;
                deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Suppression en cours...';
                
                return true;
            });
        });
    </script>
</body>
</html>