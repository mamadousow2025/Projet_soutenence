<?php
session_start();
require_once '../config/database.php';

// Vérifier connexion et rôle enseignant
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit;
}

$enseignant_id = $_SESSION['user_id'];
$errors = [];
$success = "";

// Récupérer la filière assignée à l'enseignant
$stmtFiliere = $pdo->prepare("SELECT f.id, f.nom FROM users u JOIN filieres f ON u.filiere_id = f.id WHERE u.id = ?");
$stmtFiliere->execute([$enseignant_id]);
$enseignantFiliere = $stmtFiliere->fetch(PDO::FETCH_ASSOC);

if (!$enseignantFiliere) {
    die("Votre filière n'est pas assignée. Contactez l'administrateur.");
}

// Traitement ajout cours
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cours'])) {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $filiere_id = $enseignantFiliere['id'];
    $imagePath = null;
    $videoPath = null;
    $pdfPath = null;

    // Validation titre
    if (empty($titre)) $errors[] = "Le titre est obligatoire.";

    // On impose que l'enseignant ne puisse pas ajouter vidéo ET pdf en même temps
    $hasVideo = isset($_FILES['video_cours']) && $_FILES['video_cours']['error'] === UPLOAD_ERR_OK;
    $hasPDF = isset($_FILES['pdf_cours']) && $_FILES['pdf_cours']['error'] === UPLOAD_ERR_OK;

    if ($hasVideo && $hasPDF) {
        $errors[] = "Vous ne pouvez pas ajouter une vidéo et un PDF en même temps.";
    }

    // Upload image couverture
    if (isset($_FILES['image_couverture']) && $_FILES['image_couverture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_couverture']['tmp_name'];
        $fileName = basename($_FILES['image_couverture']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExt, $allowedExts)) {
            $errors[] = "Image : formats autorisés jpg, jpeg, png, gif uniquement.";
        } else {
            $newFileName = uniqid('img_') . '.' . $fileExt;
            $destPath = __DIR__ . '/uploads/' . $newFileName;
            
            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $errors[] = "Erreur lors de l'upload de l'image.";
            } else {
                $imagePath = 'uploads/' . $newFileName;
            }
        }
    }

    // Upload vidéo
    if ($hasVideo && empty($errors)) {
        $fileTmpPath = $_FILES['video_cours']['tmp_name'];
        $fileName = basename($_FILES['video_cours']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedVideoExts = ['mp4', 'webm', 'ogg'];

        if (!in_array($fileExt, $allowedVideoExts)) {
            $errors[] = "Vidéo : formats autorisés mp4, webm, ogg uniquement.";
        } else {
            $newFileName = uniqid('vid_') . '.' . $fileExt;
            $destPath = __DIR__ . '/uploads/' . $newFileName;
            
            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $errors[] = "Erreur lors de l'upload de la vidéo.";
            } else {
                $videoPath = 'uploads/' . $newFileName;
            }
        }
    }

    // Upload PDF
    if ($hasPDF && empty($errors)) {
        $fileTmpPath = $_FILES['pdf_cours']['tmp_name'];
        $fileName = basename($_FILES['pdf_cours']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExt !== 'pdf') {
            $errors[] = "PDF : format autorisé uniquement PDF.";
        } else {
            $newFileName = uniqid('pdf_') . '.' . $fileExt;
            $destPath = __DIR__ . '/uploads/' . $newFileName;
            
            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $errors[] = "Erreur lors de l'upload du PDF.";
            } else {
                $pdfPath = 'uploads/' . $newFileName;
            }
        }
    }

    // Insertion dans la base
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO cours (enseignant_id, titre, description, filiere_id, image_couverture, video_cours, pdf_cours, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$enseignant_id, $titre, $description, $filiere_id, $imagePath, $videoPath, $pdfPath]);
        $success = "Cours ajouté avec succès !";
        $_POST = []; // reset form
    }
}

// Récupérer les cours de l'enseignant
$stmtCours = $pdo->prepare("
    SELECT c.*, f.nom AS filiere_nom 
    FROM cours c 
    JOIN filieres f ON c.filiere_id = f.id 
    WHERE c.enseignant_id = ? 
    ORDER BY c.created_at DESC
");
$stmtCours->execute([$enseignant_id]);
$cours = $stmtCours->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Enseignant - Mes Cours</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Variables et réinitialisation */
        :root {
            --primary-color: #009688; /* Teal */
            --secondary-color: #FF9800; /* Orange */
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

        /* Messages */
        .messages {
            margin-bottom: 25px;
            max-width: 900px;
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

        /* Form Styles */
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            overflow: hidden;
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

        .card-header h3 {
            font-size: 1.4rem;
        }

        .card-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 150, 136, 0.2);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .file-input {
            padding: 8px 0;
        }

        .help-text {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 5px;
            font-style: italic;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            gap: 8px;
        }

        .btn:hover {
            background-color: #00897B;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn i {
            font-size: 1.1rem;
        }

        /* Course Grid */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .course-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border-top: 4px solid var(--primary-color);
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .course-media {
            height: 180px;
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .course-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .course-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .media-placeholder {
            color: var(--text-light);
            text-align: center;
            padding: 20px;
        }

        .media-placeholder i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
            color: #b0bec5;
        }

        .course-content {
            padding: 20px;
        }

        .course-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--primary-color);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .course-description {
            color: var(--text-light);
            margin-bottom: 15px;
            font-size: 0.95rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .course-filiere {
            font-size: 0.85rem;
            color: var(--text-light);
            background-color: #f1f5f9;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .course-actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .btn-edit {
            background-color: var(--primary-color);
        }

        .btn-delete {
            background-color: var(--secondary-color);
        }

        .btn-delete:hover {
            background-color: #F57C00;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow: auto;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(to right, var(--primary-color), #26A69A);
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modal-header h3 {
            font-size: 1.3rem;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: white;
            transition: var(--transition);
        }

        .close-modal:hover {
            color: #ffeb3b;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-body iframe {
            width: 100%;
            height: 70vh;
            border: none;
        }

        /* Badge for new courses */
        .new-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--secondary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        /* Responsive */
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
            
            .courses-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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
            
            .course-actions {
                flex-direction: column;
                gap: 8px;
            }
        }

        @media (max-width: 576px) {
            .sidebar-nav {
                flex-direction: column;
                gap: 5px;
            }
            
            .courses-grid {
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
                <h1><i class="fas fa-chalkboard-teacher"></i> Espace Enseignant</h1>
            </div>
            
            <nav class="sidebar-nav">
                <a href="teacher_dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de bord</span>
                </a>
                <a href="#form-add" class="nav-item active">
                    <i class="fas fa-plus-circle"></i>
                    <span>Ajouter un cours</span>
                </a>
                <a href="#cours-list" class="nav-item">
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
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres</span>
                </a>
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
            <div class="header">
                <h2><i class="fas fa-book-open"></i> Gestion des Cours</h2>
                <div class="breadcrumb">
                    <a href="teacher_dashboard.php">Tableau de bord</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Mes cours</span>
                </div>
            </div>

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

            <!-- Formulaire d'ajout de cours -->
            <section id="form-add" class="card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Ajouter un nouveau cours</h3>
                </div>
                
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="add_cours" value="1">
                        
                        <div class="form-group">
                            <label for="titre">Titre du cours <span style="color: var(--warning-color)">*</span></label>
                            <input type="text" id="titre" name="titre" required 
                                   value="<?= isset($_POST['titre']) ? htmlspecialchars($_POST['titre']) : '' ?>" 
                                   placeholder="Ex: Introduction à la Programmation" 
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="5" 
                                      placeholder="Une brève description du cours" 
                                      class="form-control"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <p><strong>Filière :</strong> <?= htmlspecialchars($enseignantFiliere['nom']) ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label for="image_couverture">Image de couverture (jpg, png, gif)</label>
                            <input type="file" id="image_couverture" name="image_couverture" 
                                   accept=".jpg,.jpeg,.png,.gif" class="file-input">
                            <p class="help-text">Facultatif - Formats autorisés: jpg, jpeg, png, gif</p>
                        </div>
                        
                        <p class="help-text" style="margin-bottom: 15px;">
                            <i class="fas fa-info-circle"></i> Vous pouvez soit ajouter une vidéo <b>ou</b> un document PDF. Ne mettez pas les deux en même temps.
                        </p>
                        
                        <div class="form-group">
                            <label for="video_cours">Vidéo du cours (mp4, webm, ogg)</label>
                            <input type="file" id="video_cours" name="video_cours" 
                                   accept="video/mp4,video/webm,video/ogg" class="file-input">
                            <p class="help-text">Facultatif - Formats autorisés: mp4, webm, ogg</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="pdf_cours">Document PDF (téléchargeable)</label>
                            <input type="file" id="pdf_cours" name="pdf_cours" 
                                   accept=".pdf" class="file-input">
                            <p class="help-text">Facultatif - Format autorisé: PDF uniquement</p>
                        </div>
                        
                        <button type="submit" class="btn">
                            <i class="fas fa-plus-circle"></i>
                            Ajouter le cours
                        </button>
                    </form>
                </div>
            </section>

            <!-- Liste des cours -->
            <section id="cours-list">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-book"></i>
                        <h3>Mes cours (<?= count($cours) ?>)</h3>
                    </div>
                    
                    <div class="card-body">
                        <?php if (count($cours) > 0): ?>
                            <div class="courses-grid">
                                <?php foreach ($cours as $c): 
                                    $isNew = (strtotime($c['created_at']) > strtotime('-3 days'));
                                ?>
                                    <div class="course-card">
                                        <?php if ($isNew): ?>
                                            <div class="new-badge">Nouveau</div>
                                        <?php endif; ?>
                                        
                                        <div class="course-media">
                                            <?php if ($c['image_couverture']): ?>
                                                <img src="<?= htmlspecialchars($c['image_couverture']) ?>" alt="Image couverture <?= htmlspecialchars($c['titre']) ?>">
                                            <?php elseif ($c['video_cours']): ?>
                                                <video controls preload="metadata">
                                                    <source src="<?= htmlspecialchars($c['video_cours']) ?>" type="video/mp4">
                                                    Votre navigateur ne supporte pas la vidéo.
                                                </video>
                                            <?php elseif ($c['pdf_cours']): ?>
                                                <div class="media-placeholder">
                                                    <i class="far fa-file-pdf"></i>
                                                    <span>Document PDF</span>
                                                    <button onclick="openPdfModal('<?= htmlspecialchars($c['pdf_cours']) ?>')" class="btn btn-sm" style="margin-top: 10px;">
                                                        Visualiser
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <div class="media-placeholder">
                                                    <i class="far fa-file-alt"></i>
                                                    <span>Aucun média</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="course-content">
                                            <h3 class="course-title"><?= htmlspecialchars($c['titre']) ?></h3>
                                            <p class="course-description"><?= htmlspecialchars($c['description']) ?></p>
                                            
                                            <div class="course-meta">
                                                <span class="course-filiere"><?= htmlspecialchars($c['filiere_nom']) ?></span>
                                                
                                                <div class="course-actions">
                                                    <a href="modifier_cours.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="supprimer_cours.php?id=<?= $c['id'] ?>" 
                                                       onclick="return confirm('Voulez-vous vraiment supprimer ce cours ?');" 
                                                       class="btn btn-sm btn-delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px 20px; color: var(--text-light);">
                                <i class="fas fa-book-open" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                                <p style="font-size: 1.2rem;">Aucun cours ajouté pour le moment.</p>
                                <p>Commencez par ajouter votre premier cours en utilisant le formulaire ci-dessus.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal pour visualiser les PDF -->
    <div id="pdfModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Visualisation du document PDF</h3>
                <button class="close-modal" onclick="closePdfModal()">&times;</button>
            </div>
            <div class="modal-body">
                <iframe id="pdfFrame" src=""></iframe>
            </div>
        </div>
    </div>

    <script>
        function openPdfModal(src) {
            document.getElementById('pdfFrame').src = src;
            document.getElementById('pdfModal').style.display = 'flex';
        }

        function closePdfModal() {
            document.getElementById('pdfFrame').src = '';
            document.getElementById('pdfModal').style.display = 'none';
        }

        // Fermer la modal en cliquant à l'extérieur
        document.getElementById('pdfModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePdfModal();
            }
        });

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