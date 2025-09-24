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

// Vérifier les permissions (seuls les enseignants et admins peuvent modifier)
if ($role !== 'enseignant' && $role !== 'admin') {
    header('Location: projet.php');
    exit();
}

// Récupérer l'ID du projet à modifier
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: projet.php');
    exit();
}

$projet_id = $_GET['id'];

// Vérifier que l'enseignant peut modifier ce projet (ou si c'est un admin)
if ($role === 'enseignant') {
    $stmt = $pdo->prepare("SELECT * FROM projets WHERE id = ? AND enseignant_id = ?");
    $stmt->execute([$projet_id, $user_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM projets WHERE id = ?");
    $stmt->execute([$projet_id]);
}

$projet = $stmt->fetch();

if (!$projet) {
    header('Location: projet.php');
    exit();
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

// Traitement de la modification du projet
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modifier_projet'])) {
    $titre = $_POST['titre'];
    $type_projet = $_POST['type_projet'];
    $description = $_POST['description'];
    $objectifs = $_POST['objectifs'] ?? '';
    $criteres_evaluation = $_POST['criteres_evaluation'] ?? '';
    $ressources_necessaires = $_POST['ressources_necessaires'] ?? '';
    $competences_developpees = $_POST['competences_developpees'] ?? '';
    $date_limite = $_POST['date_limite'] ?: null;
    $filiere_id = $_POST['filiere_id'] ?? null;
    $statut = $_POST['statut'] ?? $projet['statut'];
    
    if ($role == 'admin') {
        $enseignant_id = $_POST['enseignant_id'];
    } else {
        $enseignant_id = $projet['enseignant_id'];
    }
    
    try {
        // Démarrer une transaction
        $pdo->beginTransaction();
        
        // Modifier le projet
        $stmt = $pdo->prepare("
            UPDATE projets SET 
                titre = ?, 
                type_projet = ?, 
                description = ?, 
                objectifs = ?, 
                criteres_evaluation = ?, 
                ressources_necessaires = ?, 
                competences_developpees = ?, 
                date_limite = ?, 
                enseignant_id = ?, 
                filiere_id = ?, 
                statut = ?,
                date_modification = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $titre, $type_projet, $description, $objectifs, $criteres_evaluation, 
            $ressources_necessaires, $competences_developpees, $date_limite, 
            $enseignant_id, $filiere_id, $statut, $projet_id
        ]);
        
        // Traiter les nouveaux uploads de fichiers
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
                        handleFileUpload($file, $projet_id, $type);
                    }
                }
            }
        }
        
        // Traiter les nouveaux liens
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
                    $stmt->execute([$projet_id, $lien, $description_lien]);
                }
            }
        }
        
        // Supprimer les fichiers sélectionnés
        if (isset($_POST['supprimer_fichiers']) && !empty($_POST['supprimer_fichiers'])) {
            foreach ($_POST['supprimer_fichiers'] as $fichier_id) {
                // Récupérer le chemin du fichier
                $stmt = $pdo->prepare("SELECT chemin_fichier FROM projet_fichiers WHERE id = ? AND projet_id = ?");
                $stmt->execute([$fichier_id, $projet_id]);
                $fichier = $stmt->fetch();
                
                if ($fichier) {
                    // Supprimer le fichier physique
                    if (file_exists($fichier['chemin_fichier'])) {
                        unlink($fichier['chemin_fichier']);
                    }
                    
                    // Supprimer de la base de données
                    $stmt = $pdo->prepare("DELETE FROM projet_fichiers WHERE id = ? AND projet_id = ?");
                    $stmt->execute([$fichier_id, $projet_id]);
                }
            }
        }
        
        // Supprimer les liens sélectionnés
        if (isset($_POST['supprimer_liens']) && !empty($_POST['supprimer_liens'])) {
            foreach ($_POST['supprimer_liens'] as $lien_id) {
                $stmt = $pdo->prepare("DELETE FROM projet_liens WHERE id = ? AND projet_id = ?");
                $stmt->execute([$lien_id, $projet_id]);
            }
        }
        
        $pdo->commit();
        $success_message = "Projet modifié avec succès!";
        
        // Recharger les données du projet
        if ($role === 'enseignant') {
            $stmt = $pdo->prepare("SELECT * FROM projets WHERE id = ? AND enseignant_id = ?");
            $stmt->execute([$projet_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM projets WHERE id = ?");
            $stmt->execute([$projet_id]);
        }
        $projet = $stmt->fetch();
        
    } catch (Exception $e) {
        $pdo->rollback();
        $error_message = "Erreur lors de la modification du projet: " . $e->getMessage();
    }
}

// Récupérer les fichiers existants du projet
$stmt = $pdo->prepare("SELECT * FROM projet_fichiers WHERE projet_id = ? ORDER BY date_upload DESC");
$stmt->execute([$projet_id]);
$fichiers_existants = $stmt->fetchAll();

// Récupérer les liens existants du projet
$stmt = $pdo->prepare("SELECT * FROM projet_liens WHERE projet_id = ? ORDER BY date_ajout DESC");
$stmt->execute([$projet_id]);
$liens_existants = $stmt->fetchAll();

// Récupérer les filières pour le formulaire
$stmt = $pdo->prepare("SELECT * FROM filieres ORDER BY nom");
$stmt->execute();
$filieres = $stmt->fetchAll();

// Récupérer les enseignants pour les admins
if ($role == 'admin') {
    $stmt = $pdo->prepare("SELECT id, nom, prenom FROM users WHERE role_id = 2");
    $stmt->execute();
    $enseignants = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Projet - <?= htmlspecialchars($projet['titre']) ?></title>
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

        .btn-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #607d8b 0%, #455a64 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(96, 125, 139, 0.3);
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

        .existing-files {
            background: #fff3e0;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 5px solid #FF9800;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #eee;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
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

        .nav-buttons {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
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
                        <h1>
                            <i class="fas fa-edit" style="color: #FF9800; margin-right: 20px;"></i>
                            Modification du Projet
                        </h1>
                        <p class="subtitle mb-0" style="font-size: 18px; opacity: 0.9;">
                            <i class="fas fa-project-diagram" style="color: #FF9800; margin-right: 10px;"></i>
                            <?= htmlspecialchars($projet['titre']) ?>
                        </p>
                    </div>
                    <div class="col-lg-4 text-end">
                        <div class="nav-buttons d-inline-block">
                            <a href="projet.php" class="btn btn-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Retour aux Projets
                            </a>
                            <a href="projet_details.php?id=<?= $projet_id ?>" class="btn btn-warning">
                                <i class="fas fa-eye me-2"></i>Voir Détails
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

            <!-- Formulaire de modification -->
            <div class="content-card">
                <div class="content-card-header">
                    <h4><i class="fas fa-edit"></i>Formulaire de Modification du Projet</h4>
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
                                           value="<?= htmlspecialchars($projet['titre']) ?>"
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
                                        <option value="cahier_charge" <?= $projet['type_projet'] == 'cahier_charge' ? 'selected' : '' ?>>📋 Cahier des charges</option>
                                        <option value="sujet_pratique" <?= $projet['type_projet'] == 'sujet_pratique' ? 'selected' : '' ?>>🛠️ Sujet pratique</option>
                                        <option value="creation" <?= $projet['type_projet'] == 'creation' ? 'selected' : '' ?>>🎨 Création</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-4">
                                    <label for="filiere_id" class="form-label">
                                        <i class="fas fa-graduation-cap"></i>Filière Cible
                                    </label>
                                    <select class="form-select" id="filiere_id" name="filiere_id" required>
                                        <option value="">Sélectionnez une filière</option>
                                        <?php foreach ($filieres as $filiere): ?>
                                        <option value="<?= $filiere['id'] ?>" <?= $projet['filiere_id'] == $filiere['id'] ? 'selected' : '' ?>>
                                            🎓 <?= htmlspecialchars($filiere['nom']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-4">
                                    <label for="date_limite" class="form-label">
                                        <i class="fas fa-calendar-times"></i>Date Limite de Rendu
                                    </label>
                                    <input type="date" class="form-control" id="date_limite" name="date_limite" 
                                           value="<?= $projet['date_limite'] ? date('Y-m-d', strtotime($projet['date_limite'])) : '' ?>"
                                           min="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-4">
                                    <label for="statut" class="form-label">
                                        <i class="fas fa-info-circle"></i>Statut du Projet
                                    </label>
                                    <select class="form-select" id="statut" name="statut" required>
                                        <option value="actif" <?= $projet['statut'] == 'actif' ? 'selected' : '' ?>>✅ Actif</option>
                                        <option value="suspendu" <?= $projet['statut'] == 'suspendu' ? 'selected' : '' ?>>⏸️ Suspendu</option>
                                        <option value="termine" <?= $projet['statut'] == 'termine' ? 'selected' : '' ?>>🏁 Terminé</option>
                                        <option value="archive" <?= $projet['statut'] == 'archive' ? 'selected' : '' ?>>📦 Archivé</option>
                                    </select>
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
                            </div>

                            <div class="description-section">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left"></i>Description Générale
                                </label>
                                <textarea class="form-control" id="description" name="description" rows="4" required 
                                          placeholder="Décrivez brièvement le projet, son contexte et ses enjeux..."><?= htmlspecialchars($projet['description']) ?></textarea>
                            </div>

                            <div class="description-section">
                                <label for="objectifs" class="form-label">
                                    <i class="fas fa-bullseye"></i>Objectifs Pédagogiques
                                </label>
                                <textarea class="form-control" id="objectifs" name="objectifs" rows="3" 
                                          placeholder="• Objectif 1: Développer les compétences en...&#10;• Objectif 2: Maîtriser les concepts de..."><?= htmlspecialchars($projet['objectifs']) ?></textarea>
                            </div>

                            <div class="description-section">
                                <label for="criteres_evaluation" class="form-label">
                                    <i class="fas fa-check-square"></i>Critères d'Évaluation
                                </label>
                                <textarea class="form-control" id="criteres_evaluation" name="criteres_evaluation" rows="3" 
                                          placeholder="• Qualité technique (30%)&#10;• Respect des délais (20%)"><?= htmlspecialchars($projet['criteres_evaluation']) ?></textarea>
                            </div>

                            <div class="description-section">
                                <label for="ressources_necessaires" class="form-label">
                                    <i class="fas fa-tools"></i>Ressources Nécessaires
                                </label>
                                <textarea class="form-control" id="ressources_necessaires" name="ressources_necessaires" rows="3" 
                                          placeholder="• Logiciels requis: ...&#10;• Matériel nécessaire: ..."><?= htmlspecialchars($projet['ressources_necessaires']) ?></textarea>
                            </div>

                            <div class="description-section">
                                <label for="competences_developpees" class="form-label">
                                    <i class="fas fa-graduation-cap"></i>Compétences Développées
                                </label>
                                <textarea class="form-control" id="competences_developpees" name="competences_developpees" rows="3" 
                                          placeholder="• Compétences techniques: ...&#10;• Compétences transversales: ..."><?= htmlspecialchars($projet['competences_developpees']) ?></textarea>
                            </div>
                        </div>

                        <!-- Fichiers existants -->
                        <?php if (!empty($fichiers_existants)): ?>
                        <div class="existing-files">
                            <div class="text-center mb-4">
                                <h5 class="text-warning fw-bold">
                                    <i class="fas fa-folder-open me-2"></i>
                                    Fichiers Existants du Projet
                                </h5>
                                <p class="text-muted mb-0">Cochez les fichiers que vous souhaitez supprimer</p>
                            </div>

                            <?php foreach ($fichiers_existants as $fichier): ?>
                            <div class="file-item">
                                <div class="file-info">
                                    <div class="file-icon <?= $fichier['type_fichier'] ?>">
                                        <i class="fas fa-<?= 
                                            $fichier['type_fichier'] == 'image' ? 'image' : 
                                            ($fichier['type_fichier'] == 'video' ? 'video' : 'file-alt') 
                                        ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($fichier['nom_original']) ?></div>
                                        <div class="text-muted small">
                                            <?= number_format($fichier['taille_fichier'] / 1024 / 1024, 2) ?> MB - 
                                            Ajouté le <?= date('d/m/Y à H:i', strtotime($fichier['date_upload'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="supprimer_fichiers[]" 
                                           value="<?= $fichier['id'] ?>" id="fichier_<?= $fichier['id'] ?>">
                                    <label class="form-check-label text-danger" for="fichier_<?= $fichier['id'] ?>">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Section Upload de nouveaux fichiers -->
                        <div class="file-upload-section">
                            <div class="file-upload-header">
                                <h5><i class="fas fa-cloud-upload-alt me-2"></i>Ajouter de Nouvelles Ressources</h5>
                                <p class="text-muted mb-0">Ajoutez des images, vidéos et documents supplémentaires</p>
                            </div>

                            <!-- Upload Images -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-images"></i>Nouvelles Images (JPG, PNG, GIF, SVG)
                                </label>
                                <div class="file-upload-area">
                                    <i class="fas fa-image fa-3x text-success mb-3"></i>
                                    <p class="mb-2"><strong>Sélectionnez de nouvelles images</strong></p>
                                    <input type="file" class="form-control" name="images[]" multiple accept="image/*">
                                </div>
                            </div>

                            <!-- Upload Vidéos -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-video"></i>Nouvelles Vidéos (MP4, AVI, MOV, WebM)
                                </label>
                                <div class="file-upload-area">
                                    <i class="fas fa-video fa-3x text-warning mb-3"></i>
                                    <p class="mb-2"><strong>Sélectionnez de nouvelles vidéos</strong></p>
                                    <input type="file" class="form-control" name="videos[]" multiple accept="video/*">
                                </div>
                            </div>

                            <!-- Upload Documents -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-file-alt"></i>Nouveaux Documents (PDF, DOC, DOCX, TXT, PPT, XLS)
                                </label>
                                <div class="file-upload-area">
                                    <i class="fas fa-file-alt fa-3x text-info mb-3"></i>
                                    <p class="mb-2"><strong>Sélectionnez de nouveaux documents</strong></p>
                                    <input type="file" class="form-control" name="documents[]" multiple 
                                           accept=".pdf,.doc,.docx,.txt,.rtf,.odt,.ppt,.pptx,.xls,.xlsx">
                                </div>
                            </div>
                        </div>

                        <!-- Liens existants -->
                        <?php if (!empty($liens_existants)): ?>
                        <div class="existing-files">
                            <div class="text-center mb-4">
                                <h5 class="text-info fw-bold">
                                    <i class="fas fa-link me-2"></i>
                                    Liens Existants du Projet
                                </h5>
                                <p class="text-muted mb-0">Cochez les liens que vous souhaitez supprimer</p>
                            </div>

                            <?php foreach ($liens_existants as $lien): ?>
                            <div class="file-item">
                                <div class="file-info">
                                    <div class="file-icon" style="background: #2196F3;">
                                        <i class="fas fa-link"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">
                                            <a href="<?= htmlspecialchars($lien['url']) ?>" target="_blank" class="text-primary">
                                                <?= htmlspecialchars($lien['description'] ?: $lien['url']) ?>
                                            </a>
                                        </div>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars($lien['url']) ?> - 
                                            Ajouté le <?= date('d/m/Y à H:i', strtotime($lien['date_ajout'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="supprimer_liens[]" 
                                           value="<?= $lien['id'] ?>" id="lien_<?= $lien['id'] ?>">
                                    <label class="form-check-label text-danger" for="lien_<?= $lien['id'] ?>">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Section Nouveaux Liens -->
                        <div class="link-section">
                            <div class="text-center mb-4">
                                <h5 class="text-primary fw-bold">
                                    <i class="fas fa-link text-warning me-2"></i>
                                    Ajouter de Nouveaux Liens
                                </h5>
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
                        
                        <?php if ($role == 'admin'): ?>
                        <div class="mb-4">
                            <label for="enseignant_id" class="form-label">
                                <i class="fas fa-user-tie"></i>Enseignant Responsable
                            </label>
                            <select class="form-select" id="enseignant_id" name="enseignant_id" required>
                                <option value="">Sélectionnez un enseignant</option>
                                <?php foreach ($enseignants as $enseignant): ?>
                                <option value="<?= $enseignant['id'] ?>" <?= $projet['enseignant_id'] == $enseignant['id'] ? 'selected' : '' ?>>
                                    👨‍🏫 <?= htmlspecialchars($enseignant['prenom'] . ' ' . $enseignant['nom']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-5">
                            <button type="submit" name="modifier_projet" class="btn btn-primary btn-lg px-5 py-3 me-3">
                                <i class="fas fa-save me-2"></i>Enregistrer les Modifications
                            </button>
                            <a href="projet.php" class="btn btn-secondary btn-lg px-5 py-3">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        // Confirmation avant suppression
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const fichiersASupprimer = document.querySelectorAll('input[name="supprimer_fichiers[]"]:checked');
                const liensASupprimer = document.querySelectorAll('input[name="supprimer_liens[]"]:checked');
                
                if (fichiersASupprimer.length > 0 || liensASupprimer.length > 0) {
                    const message = `Vous êtes sur le point de supprimer définitivement :\n` +
                                  `- ${fichiersASupprimer.length} fichier(s)\n` +
                                  `- ${liensASupprimer.length} lien(s)\n\n` +
                                  `Cette action est irréversible. Continuer ?`;
                    
                    if (!confirm(message)) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>