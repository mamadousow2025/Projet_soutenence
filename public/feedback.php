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

// Si aucun ID de projet n'est fourni, afficher la liste des projets avec feedback
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Récupérer les projets selon le rôle
    if ($role == 'enseignant') {
        $stmt = $pdo->prepare("
            SELECT p.*, f.nom as filiere_nom,
                   COUNT(DISTINCT l.id) as total_livrables,
                   COUNT(DISTINCT CASE WHEN l.statut = 'soumis' THEN l.id END) as en_attente
            FROM projets p
            LEFT JOIN filieres f ON p.filiere_id = f.id
            LEFT JOIN taches t ON p.id = t.projet_id
            LEFT JOIN livrables l ON t.id = l.tache_id AND l.statut != 'remplace'
            WHERE p.enseignant_id = ?
            GROUP BY p.id
            ORDER BY p.date_creation DESC
        ");
        $stmt->execute([$user_id]);
    } else {
        // Pour les étudiants
        $stmt = $pdo->prepare("
            SELECT u.filiere_id 
            FROM users u 
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $user_info = $stmt->fetch();
        
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.*, f.nom as filiere_nom, u.nom as enseignant_nom, u.prenom as enseignant_prenom,
                   COUNT(DISTINCT l.id) as mes_livrables
            FROM projets p
            LEFT JOIN filieres f ON p.filiere_id = f.id
            LEFT JOIN users u ON p.enseignant_id = u.id
            LEFT JOIN taches t ON p.id = t.projet_id
            LEFT JOIN livrables l ON t.id = l.tache_id AND l.etudiant_id = ? AND l.statut != 'remplace'
            WHERE (p.filiere_id = ? OR EXISTS (
                SELECT 1 FROM projet_etudiants pe WHERE pe.projet_id = p.id AND pe.etudiant_id = ?
            ))
            GROUP BY p.id
            ORDER BY p.date_creation DESC
        ");
        $stmt->execute([$user_id, $user_info['filiere_id'] ?? 0, $user_id]);
    }
    
    $projets = $stmt->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Correction & Feedback - LMS ISEP</title>
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
                background: linear-gradient(135deg, #009688 0%, #00695c 100%);
                color: white;
                padding: 30px 0;
                margin-bottom: 30px;
                box-shadow: 0 4px 20px rgba(0, 150, 136, 0.3);
            }

            .project-card {
                background: white;
                border-radius: 15px;
                padding: 25px;
                margin-bottom: 20px;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
                border-left: 5px solid #009688;
            }

            .project-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
            }

            .btn-primary {
                background: linear-gradient(135deg, #009688 0%, #00695c 100%);
                border: none;
                border-radius: 10px;
                padding: 10px 20px;
                font-weight: 600;
                transition: all 0.3s ease;
            }

            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(0, 150, 136, 0.3);
            }

            .stat-badge {
                display: inline-block;
                padding: 5px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                margin-right: 10px;
            }

            .stat-badge.warning {
                background: #fff3cd;
                color: #856404;
                border: 1px solid #ffeaa7;
            }

            .stat-badge.info {
                background: #d1ecf1;
                color: #0c5460;
                border: 1px solid #bee5eb;
            }
        </style>
    </head>
    <body>
        <div class="main-header">
            <div class="container">
                <h1 class="display-4 fw-bold mb-0">
                    <i class="fas fa-comments me-3"></i>
                    Correction & Feedback
                </h1>
                <p class="lead mb-0">
                    <?php if ($role == 'enseignant'): ?>
                        Gérez les corrections et envoyez des feedbacks à vos étudiants
                    <?php else: ?>
                        Consultez vos livrables et les corrections de vos enseignants
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="container">
            <?php if (!empty($projets)): ?>
                <div class="row">
                    <?php foreach ($projets as $projet): ?>
                    <div class="col-lg-6 col-xl-4">
                        <div class="project-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="fw-bold text-primary mb-0">
                                    <?= htmlspecialchars($projet['titre'] ?? 'Projet sans titre') ?>
                                </h5>
                                <span class="badge bg-secondary">
                                    <?= htmlspecialchars($projet['filiere_nom'] ?? 'N/A') ?>
                                </span>
                            </div>
                            
                            <p class="text-muted mb-3">
                                <?= htmlspecialchars(substr($projet['description'] ?? 'Aucune description', 0, 100)) ?>
                                <?= strlen($projet['description'] ?? '') > 100 ? '...' : '' ?>
                            </p>
                            
                            <?php if ($role == 'enseignant'): ?>
                                <div class="mb-3">
                                    <?php if ($projet['total_livrables'] > 0): ?>
                                        <span class="stat-badge info">
                                            <i class="fas fa-file-upload me-1"></i>
                                            <?= $projet['total_livrables'] ?> livrable(s)
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($projet['en_attente'] > 0): ?>
                                        <span class="stat-badge warning">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= $projet['en_attente'] ?> en attente
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-user-tie me-1"></i>
                                        <?= htmlspecialchars(($projet['enseignant_prenom'] ?? '') . ' ' . ($projet['enseignant_nom'] ?? '')) ?>
                                    </small>
                                    
                                    <?php if ($projet['mes_livrables'] > 0): ?>
                                        <br>
                                        <span class="stat-badge info">
                                            <i class="fas fa-upload me-1"></i>
                                            <?= $projet['mes_livrables'] ?> livrable(s) soumis
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?= date('d/m/Y', strtotime($projet['date_creation'] ?? 'now')) ?>
                                </small>
                                
                                <a href="feedback.php?id=<?= $projet['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-arrow-right me-1"></i>
                                    <?= $role == 'enseignant' ? 'Corriger' : 'Voir mes livrables' ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-5x mb-4" style="color: #FF9800;"></i>
                    <h3 class="text-muted mb-3">Aucun projet disponible</h3>
                    <p class="text-muted fs-5">
                        <?php if ($role == 'enseignant'): ?>
                            Vous n'avez pas encore créé de projets.
                        <?php else: ?>
                            Aucun projet ne vous a été assigné pour le moment.
                        <?php endif; ?>
                    </p>
                    <a href="projet.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        <?= $role == 'enseignant' ? 'Créer un Projet' : 'Voir tous les Projets' ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
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
    header('Location: feedback.php');
    exit();
}

// Vérifier les permissions d'accès
if ($role == 'etudiant') {
    // Vérifier si l'étudiant a accès à ce projet
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
        header('Location: feedback.php');
        exit();
    }
} elseif ($role == 'enseignant') {
    // Vérifier que l'enseignant est propriétaire du projet
    if ($projet['enseignant_id'] != $user_id) {
        header('Location: feedback.php');
        exit();
    }
}

// Variables pour les messages
$success_message = '';
$error_message = '';

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
        SELECT l.*, t.titre as tache_titre, 
               f.note, f.commentaire_enseignant, f.date_correction,
               u.nom as enseignant_nom, u.prenom as enseignant_prenom
        FROM livrables l
        JOIN taches t ON l.tache_id = t.id
        LEFT JOIN feedback f ON l.id = f.livrable_id
        LEFT JOIN users u ON f.enseignant_id = u.id
        WHERE l.etudiant_id = ? AND t.projet_id = ?
        ORDER BY l.date_soumission DESC
    ");
    $stmt->execute([$user_id, $projet_id]);
    $mes_livrables = $stmt->fetchAll();
}



// Si c'est un enseignant, récupérer tous les livrables des étudiants
$tous_livrables = [];
if ($role == 'enseignant') {
    $stmt = $pdo->prepare("
        SELECT l.*, t.titre as tache_titre, t.description as tache_description,
               u.nom as etudiant_nom, u.prenom as etudiant_prenom, u.email as etudiant_email,
               f.note, f.commentaire_enseignant, f.date_correction, f.id as feedback_id
        FROM livrables l
        JOIN taches t ON l.tache_id = t.id
        JOIN users u ON l.etudiant_id = u.id
        LEFT JOIN feedbacks f ON l.id = f.livrable_id
        WHERE t.projet_id = ? AND l.statut != 'remplace'
        ORDER BY l.date_soumission DESC, u.nom ASC, u.prenom ASC
    ");
    $stmt->execute([$projet_id]);
    $tous_livrables = $stmt->fetchAll();
}

// Traitement de soumission de livrable (étudiant)
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
            SELECT l.*, t.titre as tache_titre, 
                   f.note, f.commentaire_enseignant, f.date_correction,
                   u.nom as enseignant_nom, u.prenom as enseignant_prenom
            FROM livrables l
            JOIN taches t ON l.tache_id = t.id
            LEFT JOIN feedback f ON l.id = f.livrable_id
            LEFT JOIN users u ON f.enseignant_id = u.id
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

// Traitement de correction/feedback par l'enseignant
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['corriger_livrable']) && $role == 'enseignant') {
    $livrable_id = (int)$_POST['livrable_id'];
    $note = !empty($_POST['note']) ? (float)$_POST['note'] : null;
    $commentaire_enseignant = trim($_POST['commentaire_enseignant'] ?? '');
    $statut_livrable = $_POST['statut_livrable'] ?? 'soumis';
    
    try {
        // Vérifier que le livrable appartient à un projet de cet enseignant
        $stmt = $pdo->prepare("
            SELECT l.*, t.projet_id 
            FROM livrables l
            JOIN taches t ON l.tache_id = t.id
            JOIN projets p ON t.projet_id = p.id
            WHERE l.id = ? AND p.enseignant_id = ?
        ");
        $stmt->execute([$livrable_id, $user_id]);
        $livrable = $stmt->fetch();
        
        if (!$livrable) {
            throw new Exception("Livrable non trouvé ou non autorisé");
        }
        
        // Vérifier si un feedback existe déjà
        $stmt = $pdo->prepare("SELECT id FROM feedback WHERE livrable_id = ?");
        $stmt->execute([$livrable_id]);
        $existing_feedback = $stmt->fetch();
        
        if ($existing_feedback) {
            // Mettre à jour le feedback existant
            $stmt = $pdo->prepare("
                UPDATE feedback 
                SET note = ?, commentaire_enseignant = ?, date_correction = NOW()
                WHERE livrable_id = ?
            ");
            $stmt->execute([$note, $commentaire_enseignant, $livrable_id]);
        } else {
            // Créer un nouveau feedback
            $stmt = $pdo->prepare("
                INSERT INTO feedback (livrable_id, enseignant_id, note, commentaire_enseignant, date_correction) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$livrable_id, $user_id, $note, $commentaire_enseignant]);
        }
        
        // Mettre à jour le statut du livrable
        $stmt = $pdo->prepare("UPDATE livrables SET statut = ? WHERE id = ?");
        $stmt->execute([$statut_livrable, $livrable_id]);
        
        $success_message = "Correction enregistrée avec succès ! L'étudiant sera notifié.";
        
        // Recharger les livrables
        $stmt = $pdo->prepare("
            SELECT l.*, t.titre as tache_titre, t.description as tache_description,
                   u.nom as etudiant_nom, u.prenom as etudiant_prenom, u.email as etudiant_email,
                   f.note, f.commentaire_enseignant, f.date_correction, f.id as feedback_id
            FROM livrables l
            JOIN taches t ON l.tache_id = t.id
            JOIN users u ON l.etudiant_id = u.id
            LEFT JOIN feedback f ON l.id = f.livrable_id
            WHERE t.projet_id = ? AND l.statut != 'remplace'
            ORDER BY l.date_soumission DESC, u.nom ASC, u.prenom ASC
        ");
        $stmt->execute([$projet_id]);
        $tous_livrables = $stmt->fetchAll();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Traitement d'envoi de message général par l'enseignant
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['envoyer_message']) && $role == 'enseignant') {
    $message_contenu = trim($_POST['message_contenu'] ?? '');
    $message_type = $_POST['message_type'] ?? 'info';
    
    try {
        if (empty($message_contenu)) {
            throw new Exception("Le message ne peut pas être vide");
        }
        
        // Insérer le message dans la base de données
        $stmt = $pdo->prepare("
            INSERT INTO messages_projet (projet_id, utilisateur_id, contenu, type_message, date_envoi) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$projet_id, $user_id, $message_contenu, $message_type]);
        
        $success_message = "Message envoyé avec succès à tous les étudiants du projet !";
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Récupérer les messages du projet
$messages_projet = [];
$stmt = $pdo->prepare("
    SELECT m.*, u.nom as enseignant_nom, u.prenom as enseignant_prenom
    FROM messages_projet m
    JOIN users u ON m.enseignant_id = u.id
    WHERE m.projet_id = ?
    ORDER BY m.date_envoi DESC
    LIMIT 10
");
$stmt->execute([$projet_id]);
$messages_projet = $stmt->fetchAll();

// Calculer les statistiques
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

// Statistiques pour l'enseignant
$stats_livrables = [];
if ($role == 'enseignant') {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_livrables,
            SUM(CASE WHEN l.statut = 'soumis' THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN l.statut = 'valide' THEN 1 ELSE 0 END) as valides,
            SUM(CASE WHEN l.statut = 'refuse' THEN 1 ELSE 0 END) as refuses,
            COUNT(DISTINCT l.etudiant_id) as etudiants_actifs
        FROM livrables l
        JOIN taches t ON l.tache_id = t.id
        WHERE t.projet_id = ? AND l.statut != 'remplace'
    ");
    $stmt->execute([$projet_id]);
    $stats_livrables = $stmt->fetch();
}

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
    <title>Feedback - <?= htmlspecialchars($projet['titre'] ?? 'Projet') ?> - LMS ISEP</title>
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

        .feedback-section {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            border-left: 4px solid #2196F3;
        }

        .message-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .message-card.type-info {
            border-left-color: #2196F3;
        }

        .message-card.type-warning {
            border-left-color: #FF9800;
        }

        .message-card.type-success {
            border-left-color: #4CAF50;
        }

        .message-card.type-danger {
            border-left-color: #f44336;
        }

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

        .note-input {
            width: 80px;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
        }

        .correction-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
            border: 1px solid #e0e0e0;
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
                            <i class="fas fa-comments" style="color: #FF9800; margin-right: 20px;"></i>
                            Feedback - <?= htmlspecialchars($projet['titre'] ?? 'Projet sans titre') ?>
                        </h1>
                        <div class="text-white-50 fs-5">
                            <?php if ($role == 'enseignant'): ?>
                                <i class="fas fa-chalkboard-teacher me-2"></i>Interface Enseignant - Correction et Suivi
                            <?php else: ?>
                                <i class="fas fa-user-graduate me-2"></i>Mes Livrables et Corrections
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-4 text-end">
                        <a href="feedback.php" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i>Retour à la Liste
                        </a>
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

            <!-- Statistiques -->
            <?php if ($role == 'enseignant'): ?>
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-file-upload"></i>
                        </div>
                        <div class="number"><?= $stats_livrables['total_livrables'] ?? 0 ?></div>
                        <div class="label">Total Livrables</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="number"><?= $stats_livrables['en_attente'] ?? 0 ?></div>
                        <div class="label">En Attente</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="number"><?= $stats_livrables['valides'] ?? 0 ?></div>
                        <div class="label">Validés</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="number"><?= $stats_livrables['etudiants_actifs'] ?? 0 ?></div>
                        <div class="label">Étudiants Actifs</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Navigation par onglets -->
            <ul class="nav nav-tabs">
                <?php if ($role == 'etudiant'): ?>
                <li class="nav-item">
                    <a class="nav-link active" href="#mes-livrables" data-bs-toggle="tab">
                        <i class="fas fa-upload"></i>Mes Livrables (<?= count($mes_livrables) ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#soumettre" data-bs-toggle="tab">
                        <i class="fas fa-plus"></i>Nouveau Livrable
                    </a>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link active" href="#tous-livrables" data-bs-toggle="tab">
                        <i class="fas fa-folder-open"></i>Tous les Livrables (<?= count($tous_livrables) ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#envoyer-message" data-bs-toggle="tab">
                        <i class="fas fa-bullhorn"></i>Envoyer un Message
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="#messages" data-bs-toggle="tab">
                        <i class="fas fa-comments"></i>Messages (<?= count($messages_projet) ?>)
                    </a>
                </li>
            </ul>

            <!-- Contenu des onglets -->
            <div class="tab-content">
                <!-- Mes livrables (Étudiant) -->
                <?php if ($role == 'etudiant'): ?>
                <div class="tab-pane fade show active" id="mes-livrables">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-upload"></i>Mes Livrables et Corrections</h4>
                        </div>
                        <div class="content-card-body">
                            <?php if (!empty($mes_livrables)): ?>
                                <?php foreach ($mes_livrables as $livrable): ?>
                                <div class="livrable-card status-<?= $livrable['statut'] ?? 'soumis' ?>">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h5 class="mb-2 fw-bold">
                                                <i class="fas fa-file-upload text-primary me-2"></i>
                                                <?= htmlspecialchars($livrable['tache_titre'] ?? 'Tâche inconnue') ?>
                                            </h5>
                                            
                                            <?php if (!empty($livrable['commentaire'])): ?>
                                            <p class="text-muted mb-2">
                                                <strong>Mon commentaire :</strong> <?= nl2br(htmlspecialchars($livrable['commentaire'])) ?>
                                            </p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($livrable['fichier_nom'])): ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-file text-primary me-2"></i>
                                                <strong><?= htmlspecialchars($livrable['fichier_nom']) ?></strong>
                                                <span class="text-muted ms-2">(<?= formatBytes($livrable['fichier_taille'] ?? 0) ?>)</span>
                                                <?php if (!empty($livrable['fichier_chemin']) && file_exists($livrable['fichier_chemin'])): ?>
                                                <a href="<?= htmlspecialchars($livrable['fichier_chemin']) ?>" 
                                                   class="btn btn-sm btn-outline-primary ms-2" 
                                                   target="_blank">
                                                    <i class="fas fa-download me-1"></i>Télécharger
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Soumis le : <?= date('d/m/Y H:i', strtotime($livrable['date_soumission'] ?? 'now')) ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <span class="badge <?= 
                                                ($livrable['statut'] ?? 'soumis') == 'valide' ? 'bg-success' : 
                                                (($livrable['statut'] ?? 'soumis') == 'refuse' ? 'bg-danger' : 'bg-warning')
                                            ?> mb-2">
                                                <i class="fas fa-<?= 
                                                    ($livrable['statut'] ?? 'soumis') == 'valide' ? 'check' : 
                                                    (($livrable['statut'] ?? 'soumis') == 'refuse' ? 'times' : 'clock')
                                                ?>"></i>
                                                <?= ucfirst($livrable['statut'] ?? 'Soumis') ?>
                                            </span>
                                            
                                            <?php if (!empty($livrable['note'])): ?>
                                            <div class="fs-4 fw-bold text-primary">
                                                <?= number_format($livrable['note'], 1) ?>/20
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Feedback de l'enseignant -->
                                    <?php if (!empty($livrable['commentaire_enseignant']) || !empty($livrable['note'])): ?>
                                    <div class="feedback-section">
                                        <h6 class="text-primary mb-2">
                                            <i class="fas fa-chalkboard-teacher me-2"></i>
                                            Correction de <?= htmlspecialchars(($livrable['enseignant_prenom'] ?? '') . ' ' . ($livrable['enseignant_nom'] ?? '')) ?>
                                        </h6>
                                        
                                        <?php if (!empty($livrable['commentaire_enseignant'])): ?>
                                        <p class="mb-2"><?= nl2br(htmlspecialchars($livrable['commentaire_enseignant'])) ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($livrable['date_correction'])): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-check me-1"></i>
                                            Corrigé le : <?= date('d/m/Y H:i', strtotime($livrable['date_correction'])) ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-upload fa-5x mb-4" style="color: #FF9800;"></i>
                                    <h3 class="text-muted mb-3">Aucun livrable soumis</h3>
                                    <p class="text-muted fs-5">Vous n'avez pas encore soumis de livrables pour ce projet.</p>
                                    <button class="btn btn-primary" onclick="$('.nav-link[href=\'#soumettre\']').tab('show')">
                                        <i class="fas fa-upload me-2"></i>Soumettre mon Premier Livrable
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Soumettre un livrable (Étudiant) -->
                <div class="tab-pane fade" id="soumettre">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-plus"></i>Soumettre un Nouveau Livrable</h4>
                        </div>
                        <div class="content-card-body">
                            <?php if (!empty($taches)): ?>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="tache_id" class="form-label">
                                                <i class="fas fa-tasks"></i>Sélectionner une Tâche
                                            </label>
                                            <select class="form-select" name="tache_id" id="tache_id" required>
                                                <option value="">-- Choisir une tâche --</option>
                                                <?php foreach ($taches as $tache): ?>
                                                <option value="<?= $tache['id'] ?>">
                                                    <?= htmlspecialchars($tache['titre'] ?? 'Tâche sans titre') ?>
                                                    <?php if (!empty($tache['date_limite'])): ?>
                                                    (Échéance: <?= date('d/m/Y', strtotime($tache['date_limite'])) ?>)
                                                    <?php endif; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="fichier_livrable" class="form-label">
                                                <i class="fas fa-file"></i>Fichier à Soumettre
                                            </label>
                                            <input type="file" class="form-control" name="fichier_livrable" id="fichier_livrable" 
                                                   accept=".pdf,.doc,.docx,.txt,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.ppt,.pptx,.xls,.xlsx">
                                            <div class="form-text">
                                                <i class="fas fa-info-circle text-warning me-1"></i>
                                                Formats acceptés : PDF, DOC, DOCX, TXT, ZIP, RAR, 7Z, JPG, PNG, GIF, PPT, XLS (Max: 50MB)
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="commentaire" class="form-label">
                                                <i class="fas fa-comment"></i>Commentaire (Optionnel)
                                            </label>
                                            <textarea class="form-control" name="commentaire" id="commentaire" rows="6" 
                                                      placeholder="Ajoutez un commentaire sur votre livrable, des explications sur votre travail, des difficultés rencontrées, etc."></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <button type="submit" name="soumettre_livrable" class="btn btn-primary btn-lg">
                                        <i class="fas fa-upload me-2"></i>Soumettre le Livrable
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-tasks fa-4x mb-4" style="color: #FF9800;"></i>
                                    <h4 class="text-muted mb-3">Aucune tâche disponible</h4>
                                    <p class="text-muted">L'enseignant n'a pas encore créé de tâches pour ce projet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tous les livrables (Enseignant) -->
                <?php if ($role == 'enseignant'): ?>
                <div class="tab-pane fade show active" id="tous-livrables">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-folder-open"></i>Tous les Livrables des Étudiants</h4>
                        </div>
                        <div class="content-card-body">
                            <?php if (!empty($tous_livrables)): ?>
                                <?php foreach ($tous_livrables as $livrable): ?>
                                <div class="livrable-card status-<?= $livrable['statut'] ?? 'soumis' ?>">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-user-graduate text-primary me-2"></i>
                                                <h5 class="mb-0 fw-bold">
                                                    <?= htmlspecialchars(($livrable['etudiant_prenom'] ?? '') . ' ' . ($livrable['etudiant_nom'] ?? '')) ?>
                                                </h5>
                                                <span class="badge bg-secondary ms-2"><?= htmlspecialchars($livrable['etudiant_email'] ?? '') ?></span>
                                            </div>
                                            
                                            <h6 class="text-primary mb-2">
                                                <i class="fas fa-tasks me-1"></i>
                                                <?= htmlspecialchars($livrable['tache_titre'] ?? 'Tâche inconnue') ?>
                                            </h6>
                                            
                                            <?php if (!empty($livrable['commentaire'])): ?>
                                            <p class="text-muted mb-2">
                                                <strong>Commentaire étudiant :</strong> <?= nl2br(htmlspecialchars($livrable['commentaire'])) ?>
                                            </p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($livrable['fichier_nom'])): ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-file text-primary me-2"></i>
                                                <strong><?= htmlspecialchars($livrable['fichier_nom']) ?></strong>
                                                <span class="text-muted ms-2">(<?= formatBytes($livrable['fichier_taille'] ?? 0) ?>)</span>
                                                <?php if (!empty($livrable['fichier_chemin']) && file_exists($livrable['fichier_chemin'])): ?>
                                                <a href="<?= htmlspecialchars($livrable['fichier_chemin']) ?>" 
                                                   class="btn btn-sm btn-outline-primary ms-2" 
                                                   target="_blank">
                                                    <i class="fas fa-download me-1"></i>Télécharger
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Soumis le : <?= date('d/m/Y H:i', strtotime($livrable['date_soumission'] ?? 'now')) ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <span class="badge <?= 
                                                ($livrable['statut'] ?? 'soumis') == 'valide' ? 'bg-success' : 
                                                (($livrable['statut'] ?? 'soumis') == 'refuse' ? 'bg-danger' : 'bg-warning')
                                            ?> mb-2">
                                                <i class="fas fa-<?= 
                                                    ($livrable['statut'] ?? 'soumis') == 'valide' ? 'check' : 
                                                    (($livrable['statut'] ?? 'soumis') == 'refuse' ? 'times' : 'clock')
                                                ?>"></i>
                                                <?= ucfirst($livrable['statut'] ?? 'Soumis') ?>
                                            </span>
                                            
                                            <?php if (!empty($livrable['note'])): ?>
                                            <div class="fs-4 fw-bold text-primary mb-2">
                                                <?= number_format($livrable['note'], 1) ?>/20
                                            </div>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-sm btn-warning" onclick="corriger(<?= $livrable['id'] ?>, '<?= htmlspecialchars($livrable['tache_titre']) ?>', '<?= htmlspecialchars(($livrable['etudiant_prenom'] ?? '') . ' ' . ($livrable['etudiant_nom'] ?? '')) ?>', <?= $livrable['note'] ?? 'null' ?>, '<?= htmlspecialchars($livrable['commentaire_enseignant'] ?? '') ?>', '<?= $livrable['statut'] ?? 'soumis' ?>')">
                                                <i class="fas fa-edit me-1"></i>Corriger
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Feedback existant -->
                                    <?php if (!empty($livrable['commentaire_enseignant']) || !empty($livrable['note'])): ?>
                                    <div class="feedback-section">
                                        <h6 class="text-primary mb-2">
                                            <i class="fas fa-chalkboard-teacher me-2"></i>
                                            Ma Correction
                                        </h6>
                                        
                                        <?php if (!empty($livrable['commentaire_enseignant'])): ?>
                                        <p class="mb-2"><?= nl2br(htmlspecialchars($livrable['commentaire_enseignant'])) ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($livrable['date_correction'])): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-check me-1"></i>
                                            Corrigé le : <?= date('d/m/Y H:i', strtotime($livrable['date_correction'])) ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-folder-open fa-5x mb-4" style="color: #FF9800;"></i>
                                    <h3 class="text-muted mb-3">Aucun livrable soumis</h3>
                                    <p class="text-muted fs-5">Les étudiants n'ont pas encore soumis de livrables pour ce projet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Envoyer un message (Enseignant) -->
                <div class="tab-pane fade" id="envoyer-message">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-bullhorn"></i>Envoyer un Message aux Étudiants</h4>
                        </div>
                        <div class="content-card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-4">
                                            <label for="message_contenu" class="form-label">
                                                <i class="fas fa-comment"></i>Contenu du Message
                                            </label>
                                            <textarea class="form-control" name="message_contenu" id="message_contenu" rows="6" 
                                                      placeholder="Rédigez votre message pour tous les étudiants du projet..." required></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-4">
                                            <label for="message_type" class="form-label">
                                                <i class="fas fa-tag"></i>Type de Message
                                            </label>
                                            <select class="form-select" name="message_type" id="message_type">
                                                <option value="info">Information</option>
                                                <option value="warning">Avertissement</option>
                                                <option value="success">Félicitations</option>
                                                <option value="danger">Important/Urgent</option>
                                            </select>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" name="envoyer_message" class="btn btn-primary">
                                                <i class="fas fa-paper-plane me-2"></i>Envoyer le Message
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            
                            <hr class="my-4">
                            
                            <div class="bg-light p-3 rounded">
                                <h6 class="text-primary mb-2">
                                    <i class="fas fa-info-circle me-2"></i>Conseils pour vos Messages
                                </h6>
                                <ul class="mb-0">
                                    <li><strong>Information :</strong> Pour des annonces générales, des rappels de dates importantes</li>
                                    <li><strong>Avertissement :</strong> Pour attirer l'attention sur des points importants</li>
                                    <li><strong>Félicitations :</strong> Pour encourager et féliciter les étudiants</li>
                                    <li><strong>Important/Urgent :</strong> Pour des messages critiques nécessitant une action immédiate</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Messages du projet -->
                <div class="tab-pane fade" id="messages">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-comments"></i>Messages du Projet</h4>
                        </div>
                        <div class="content-card-body">
                            <?php if (!empty($messages_projet)): ?>
                                <?php foreach ($messages_projet as $message): ?>
                                <div class="message-card type-<?= $message['type_message'] ?? 'info' ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-<?= 
                                                ($message['type_message'] ?? 'info') == 'info' ? 'info-circle text-primary' : 
                                                (($message['type_message'] ?? 'info') == 'warning' ? 'exclamation-triangle text-warning' : 
                                                (($message['type_message'] ?? 'info') == 'success' ? 'check-circle text-success' : 'exclamation-circle text-danger'))
                                            ?> me-2 fs-5"></i>
                                            <h6 class="mb-0 fw-bold">
                                                <?= htmlspecialchars(($message['enseignant_prenom'] ?? '') . ' ' . ($message['enseignant_nom'] ?? '')) ?>
                                            </h6>
                                        </div>
                                        <span class="badge <?= 
                                            ($message['type_message'] ?? 'info') == 'info' ? 'bg-primary' : 
                                            (($message['type_message'] ?? 'info') == 'warning' ? 'bg-warning' : 
                                            (($message['type_message'] ?? 'info') == 'success' ? 'bg-success' : 'bg-danger'))
                                        ?>">
                                            <?= ucfirst($message['type_message'] ?? 'Info') ?>
                                        </span>
                                    </div>
                                    <p class="mb-2"><?= nl2br(htmlspecialchars($message['contenu'] ?? '')) ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($message['date_envoi'] ?? 'now')) ?>
                                    </small>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-comments fa-4x mb-4" style="color: #FF9800;"></i>
                                    <h4 class="text-muted mb-3">Aucun message</h4>
                                    <p class="text-muted">Aucun message n'a encore été envoyé pour ce projet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de correction (Enseignant) -->
    <?php if ($role == 'enseignant'): ?>
    <div class="modal fade" id="correctionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Corriger le Livrable
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="correctionForm">
                    <div class="modal-body">
                        <input type="hidden" name="livrable_id" id="modal_livrable_id">
                        
                        <div class="mb-4">
                            <h6 class="text-primary">
                                <i class="fas fa-user-graduate me-2"></i>Étudiant
                            </h6>
                            <div class="bg-light p-3 rounded">
                                <strong id="modal_etudiant_nom">Nom de l'étudiant</strong>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="text-primary">
                                <i class="fas fa-tasks me-2"></i>Tâche
                            </h6>
                            <div class="bg-light p-3 rounded">
                                <strong id="modal_tache_titre">Titre de la tâche</strong>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label for="note" class="form-label">
                                        <i class="fas fa-star"></i>Note (/20)
                                    </label>
                                    <input type="number" class="form-control note-input" name="note" id="note" 
                                           min="0" max="20" step="0.5" placeholder="Note">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label for="statut_livrable" class="form-label">
                                        <i class="fas fa-flag"></i>Statut
                                    </label>
                                    <select class="form-select" name="statut_livrable" id="statut_livrable">
                                        <option value="soumis">En attente</option>
                                        <option value="valide">Validé</option>
                                        <option value="refuse">Refusé</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="commentaire_enseignant" class="form-label">
                                <i class="fas fa-comment"></i>Commentaire de Correction
                            </label>
                            <textarea class="form-control" name="commentaire_enseignant" id="commentaire_enseignant" rows="5" 
                                      placeholder="Ajoutez vos commentaires, conseils, points d'amélioration..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="submit" name="corriger_livrable" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Enregistrer la Correction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        <?php if ($role == 'enseignant'): ?>
        function corriger(livrableId, tacheTitre, etudiantNom, note, commentaire, statut) {
            document.getElementById('modal_livrable_id').value = livrableId;
            document.getElementById('modal_tache_titre').textContent = tacheTitre;
            document.getElementById('modal_etudiant_nom').textContent = etudiantNom;
            document.getElementById('note').value = note || '';
            document.getElementById('commentaire_enseignant').value = commentaire || '';
            document.getElementById('statut_livrable').value = statut || 'soumis';
            
            new bootstrap.Modal(document.getElementById('correctionModal')).show();
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