<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérification connexion et rôle étudiant
if (!isLoggedIn() || $_SESSION['role_id'] != 1) {
    header('Location: ../public/login.php');
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);

// Vérifier si l'ID du cours est passé en paramètre
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$course_id = intval($_GET['id']);

// Récupérer les détails du cours
$stmt = $pdo->prepare("SELECT c.*, u.prenom, u.nom, f.nom as filiere_nom 
                      FROM cours c 
                      JOIN users u ON c.enseignant_id = u.id 
                      JOIN filieres f ON c.filiere_id = f.id 
                      WHERE c.id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: dashboard.php');
    exit();
}

// Récupérer les modules du cours
$modules = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE cours_id = ? ORDER BY ordre");
    $stmt->execute([$course_id]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pour chaque module, récupérer son contenu depuis la table module_contenus
    foreach ($modules as $key => $module) {
        $stmt = $pdo->prepare("SELECT * FROM module_contenus WHERE module_id = ? ORDER BY ordre");
        $stmt->execute([$module['id']]);
        $modules[$key]['contenus'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des modules: " . $e->getMessage());
}

// Récupérer la progression de l'étudiant
$progression = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM progression WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$student_id, $course_id]);
    $progression = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Table progression non trouvée: " . $e->getMessage());
}

// Récupérer les ressources du cours
$ressources = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM ressources WHERE course_id = ? ORDER BY created_at DESC");
    $stmt->execute([$course_id]);
    $ressources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Table ressources non trouvée: " . $e->getMessage());
}

// Récupérer les annonces du cours
$annonces = [];
try {
    // Essayer différentes colonnes possibles pour la liaison
    $possible_columns = ['course_id', 'cours_id', 'id_cours'];
    $annonce_course_column = null;
    
    foreach ($possible_columns as $col) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM annonces WHERE $col = ? ORDER BY created_at DESC LIMIT 3");
            $stmt->execute([$course_id]);
            $annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($annonces) > 0) {
                $annonce_course_column = $col;
                break;
            }
        } catch (PDOException $e) {
            continue;
        }
    }
    
    // Si on a trouvé des annonces mais pas les noms des enseignants, on les récupère
    if (count($annonces) > 0) {
        foreach ($annonces as $key => $annonce) {
            if (isset($annonce['enseignant_id'])) {
                $stmt = $pdo->prepare("SELECT prenom, nom FROM users WHERE id = ?");
                $stmt->execute([$annonce['enseignant_id']]);
                $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($teacher) {
                    $annonces[$key]['prenom'] = $teacher['prenom'];
                    $annonces[$key]['nom'] = $teacher['nom'];
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log("Erreur avec la table annonces: " . $e->getMessage());
}

// Marquer un module comme complété
if (isset($_POST['mark_complete']) && isset($_POST['module_id'])) {
    $module_id = intval($_POST['module_id']);
    
    // Vérifier si le module appartient bien à ce cours
    try {
        $stmt = $pdo->prepare("SELECT id FROM modules WHERE id = ? AND cours_id = ?");
        $stmt->execute([$module_id, $course_id]);
        
        if ($stmt->fetch()) {
            // Vérifier si la table modules_completed existe
            try {
                $stmt = $pdo->prepare("SELECT * FROM modules_completed WHERE student_id = ? AND module_id = ?");
                $stmt->execute([$student_id, $module_id]);
                
                if (!$stmt->fetch()) {
                    // Marquer le module comme complété
                    $stmt = $pdo->prepare("INSERT INTO modules_completed (student_id, module_id, completed_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$student_id, $module_id]);
                    
                    // Mettre à jour la progression
                    $modules_faits = $progression ? $progression['modules_faits'] + 1 : 1;
                    $modules_total = count($modules);
                    
                    if ($progression) {
                        $stmt = $pdo->prepare("UPDATE progression SET modules_faits = ? WHERE student_id = ? AND course_id = ?");
                        $stmt->execute([$modules_faits, $student_id, $course_id]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO progression (student_id, course_id, modules_total, modules_faits) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$student_id, $course_id, $modules_total, $modules_faits]);
                    }
                    
                    // Recharger la progression
                    $stmt = $pdo->prepare("SELECT * FROM progression WHERE student_id = ? AND course_id = ?");
                    $stmt->execute([$student_id, $course_id]);
                    $progression = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $success_message = "Module marqué comme complété!";
                }
            } catch (PDOException $e) {
                error_log("Table modules_completed non trouvée: " . $e->getMessage());
                $error_message = "Erreur lors de la mise à jour de la progression.";
            }
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification du module: " . $e->getMessage());
        $error_message = "Erreur lors de la mise à jour de la progression.";
    }
}

// Récupérer les modules complétés par l'étudiant
$completed_modules = [];
try {
    $stmt = $pdo->prepare("SELECT module_id FROM modules_completed WHERE student_id = ?");
    $stmt->execute([$student_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $completed_modules[] = $row['module_id'];
    }
} catch (PDOException $e) {
    error_log("Table modules_completed non trouvée: " . $e->getMessage());
}

// Calculer le pourcentage de progression
$progress_percent = 0;
if ($progression && $progression['modules_total'] > 0) {
    $progress_percent = round(($progression['modules_faits'] / $progression['modules_total']) * 100);
} elseif (count($modules) > 0) {
    $progress_percent = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($course['titre']) ?> - Détails du cours</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        },
                        accent: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        },
                        sidebar: '#0f766e',
                        sidebarHover: '#0d9488'
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        .module-content {
            display: none;
        }
        
        .module-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        .contenu-item {
            display: none;
        }
        
        .contenu-item.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .completed {
            border-left: 4px solid #10b981;
        }
        
        .progress-ring {
            transition: stroke-dashoffset 0.5s ease;
        }
        
        .course-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .contenu-nav {
            display: flex;
            overflow-x: auto;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
        }
        
        .contenu-nav-btn {
            padding: 0.5rem 1rem;
            margin-right: 0.5rem;
            background-color: #f3f4f6;
            border-radius: 0.375rem;
            white-space: nowrap;
            font-size: 0.875rem;
            cursor: pointer;
        }
        
        .contenu-nav-btn.active {
            background-color: #0d9488;
            color: white;
        }
        
        .video-container {
            position: relative;
            width: 100%;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            margin-bottom: 1rem;
        }
        
        .video-container video,
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 0.5rem;
        }
        
        .image-container {
            max-width: 100%;
            margin-bottom: 1rem;
        }
        
        .image-container img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .audio-player {
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .file-preview {
            background-color: #f8fafc;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>

<body class="flex min-h-screen bg-gray-50">

    <!-- SIDEBAR -->
    <nav class="w-64 bg-sidebar text-white flex flex-col fixed top-0 left-0 bottom-0 shadow-xl overflow-y-auto z-10">
        <div class="p-6 text-center border-b border-primary-700">
            <h1 class="text-2xl font-bold tracking-wide flex items-center justify-center gap-2">
                <i class="fas fa-graduation-cap"></i>
                <span>Espace Étudiant</span>
            </h1>
        </div>
        
        <div class="p-4 flex items-center gap-3 border-b border-primary-700 py-4">
            <div class="w-12 h-12 rounded-full bg-primary-500 flex items-center justify-center text-white font-bold text-lg">
                <?= strtoupper(substr($_SESSION['prenom'], 0, 1) . substr($_SESSION['nom'], 0, 1)) ?>
            </div>
            <div>
                <p class="font-medium"><?= $student_name ?></p>
                <p class="text-xs text-primary-200"><?= htmlspecialchars($course['filiere_nom']) ?></p>
            </div>
        </div>
        
        <div class="flex-grow py-4">
            <ul class="space-y-2 px-3">
                <li>
                    <a href="etudiant_dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-sidebarHover transition-all">
                        <i class="fas fa-home w-5 h-5"></i> 
                        <span>Tableau de bord</span>
                    </a>
                </li>
                <li>
                    <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-sidebarHover transition-all">
                        <i class="fas fa-book-open w-5 h-5"></i> 
                        <span>Mes cours</span>
                    </a>
                </li>
                <li>
                    <a href="quiz_devoirs.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-sidebarHover transition-all">
                        <i class="fas fa-tasks w-5 h-5"></i> 
                        <span>Quiz & Devoirs</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="p-4 border-t border-primary-700">
            <a href="../public/logout.php" class="flex items-center gap-3 px-4 py-3 bg-red-600 rounded-lg hover:bg-red-700 transition-all">
                <i class="fas fa-sign-out-alt w-5 h-5"></i> 
                <span>Déconnexion</span>
            </a>
        </div>
    </nav>

    <!-- CONTENU PRINCIPAL -->
    <div class="flex-1 ml-64 p-8 max-w-7xl mx-auto">
        <!-- En-tête avec informations du cours -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($course['titre']) ?></h1>
                    <p class="text-gray-600 mt-1">Par <?= htmlspecialchars($course['prenom'] . ' ' . $course['nom']) ?></p>
                </div>
                
                <div class="flex items-center">
                    <div class="relative w-16 h-16 mr-4">
                        <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="16" fill="none" stroke="#e6e6e6" stroke-width="2" />
                            <circle cx="18" cy="18" r="16" fill="none" stroke="#0d9488" stroke-width="2" 
                                    stroke-dasharray="100" stroke-dashoffset="<?= 100 - $progress_percent ?>" class="progress-ring" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-sm font-bold text-primary-700"><?= $progress_percent ?>%</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Progression</p>
                        <p class="font-semibold">
                            <?= $progression ? $progression['modules_faits'] : 0 ?> / 
                            <?= $progression ? $progression['modules_total'] : count($modules) ?> modules
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Afficher l'image du cours si elle existe -->
            <?php if (!empty($course['image'])): ?>
            <div class="mb-4">
                <img src="../uploads/cours/<?= htmlspecialchars($course['image']) ?>" alt="Image du cours" class="course-image">
            </div>
            <?php endif; ?>
            
            <p class="text-gray-700 mb-4"><?= nl2br(htmlspecialchars($course['description'] ?? '')) ?></p>
            
            <div class="flex flex-wrap gap-2">
                <span class="bg-primary-100 text-primary-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    <i class="fas fa-graduation-cap mr-1"></i> <?= htmlspecialchars($course['filiere_nom']) ?>
                </span>
                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    <i class="fas fa-clock mr-1"></i> Créé le <?= date('d/m/Y', strtotime($course['created_at'])) ?>
                </span>
                <?php if (isset($course['updated_at']) && $course['updated_at'] != $course['created_at']): ?>
                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    <i class="fas fa-sync-alt mr-1"></i> Mis à jour le <?= date('d/m/Y', strtotime($course['updated_at'])) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span><?= $success_message ?></span>
        </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span><?= $error_message ?></span>
        </div>
        <?php endif; ?>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Colonne principale avec les modules -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-list-ol text-primary-600 mr-2"></i>
                        Contenu du cours
                    </h2>
                    
                    <?php if (count($modules) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($modules as $index => $module): 
                                $is_completed = in_array($module['id'], $completed_modules);
                            ?>
                                <div class="border border-gray-200 rounded-lg p-4 <?= $is_completed ? 'completed' : '' ?>">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center mr-3">
                                                <span class="text-primary-700 font-semibold"><?= $index + 1 ?></span>
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($module['titre']) ?></h3>
                                                <p class="text-sm text-gray-600 mt-1"><?= nl2br(htmlspecialchars($module['description'] ?? '')) ?></p>
                                            </div>
                                        </div>
                                        
                                        <?php if ($is_completed): ?>
                                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full flex items-center">
                                                <i class="fas fa-check mr-1"></i> Terminé
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-4 flex justify-between items-center">
                                        <button type="button" class="view-module-btn text-primary-600 hover:text-primary-800 font-medium flex items-center"
                                                data-module-id="<?= $module['id'] ?>">
                                            <i class="fas fa-eye mr-2"></i> Voir le contenu
                                        </button>
                                        
                                        <?php if (!$is_completed): ?>
                                            <form method="POST" class="m-0">
                                                <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
                                                <button type="submit" name="mark_complete" 
                                                        class="bg-green-100 hover:bg-green-200 text-green-800 px-3 py-1 rounded-lg text-sm font-medium flex items-center">
                                                    <i class="fas fa-check-circle mr-1"></i> Marquer comme terminé
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Contenu du module (caché par défaut) -->
                                    <div id="module-<?= $module['id'] ?>" class="module-content mt-4 pt-4 border-t border-gray-200">
                                        <?php if (!empty($module['contenus'])): ?>
                                            <div class="contenu-nav mb-4">
                                                <?php foreach ($module['contenus'] as $contenuIndex => $contenu): ?>
                                                    <button type="button" class="contenu-nav-btn <?= $contenuIndex === 0 ? 'active' : '' ?>" 
                                                            data-target="contenu-<?= $module['id'] ?>-<?= $contenu['id'] ?>">
                                                        <?= htmlspecialchars($contenu['titre']) ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <?php foreach ($module['contenus'] as $contenuIndex => $contenu): ?>
                                                <div id="contenu-<?= $module['id'] ?>-<?= $contenu['id'] ?>" class="contenu-item <?= $contenuIndex === 0 ? 'active' : '' ?>">
                                                    <h4 class="font-medium text-gray-800 mb-2"><?= htmlspecialchars($contenu['titre']) ?></h4>
                                                    <?php if (!empty($contenu['description'])): ?>
                                                        <p class="text-gray-600 mb-4"><?= nl2br(htmlspecialchars($contenu['description'])) ?></p>
                                                    <?php endif; ?>
                                                    
                                                    <?php 
                                                    // Afficher le contenu en fonction du type
                                                    if ($contenu['type'] === 'fichier' && !empty($contenu['contenu'])):
                                                        $file_extension = pathinfo($contenu['contenu'], PATHINFO_EXTENSION);
                                                        $file_name = $contenu['contenu'];
                                                        $file_path = "../uploads/modules/" . $contenu['contenu'];
                                                    ?>
                                                        <div class="file-preview">
                                                            <div class="flex items-center mb-3">
                                                                <?php if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                                                    <i class="fas fa-image text-blue-500 text-2xl mr-3"></i>
                                                                    <div>
                                                                        <p class="font-medium">Image</p>
                                                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($file_name) ?></p>
                                                                    </div>
                                                                <?php elseif (in_array(strtolower($file_extension), ['mp4', 'mov', 'avi', 'wmv', 'webm'])): ?>
                                                                    <i class="fas fa-video text-red-500 text-2xl mr-3"></i>
                                                                    <div>
                                                                        <p class="font-medium">Vidéo</p>
                                                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($file_name) ?></p>
                                                                    </div>
                                                                <?php elseif (in_array(strtolower($file_extension), ['mp3', 'wav', 'ogg'])): ?>
                                                                    <i class="fas fa-music text-purple-500 text-2xl mr-3"></i>
                                                                    <div>
                                                                        <p class="font-medium">Audio</p>
                                                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($file_name) ?></p>
                                                                    </div>
                                                                <?php elseif (strtolower($file_extension) === 'pdf'): ?>
                                                                    <i class="fas fa-file-pdf text-red-500 text-2xl mr-3"></i>
                                                                    <div>
                                                                        <p class="font-medium">Document PDF</p>
                                                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($file_name) ?></p>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <i class="fas fa-file text-gray-500 text-2xl mr-3"></i>
                                                                    <div>
                                                                        <p class="font-medium">Fichier</p>
                                                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($file_name) ?></p>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <?php if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']) && file_exists($file_path)): ?>
                                                                <div class="image-container">
                                                                    <img src="<?= $file_path ?>" alt="<?= htmlspecialchars($contenu['titre']) ?>">
                                                                </div>
                                                            <?php elseif (in_array(strtolower($file_extension), ['mp4', 'mov', 'avi', 'wmv', 'webm']) && file_exists($file_path)): ?>
                                                                <div class="video-container">
                                                                    <video controls>
                                                                        <source src="<?= $file_path ?>" type="video/<?= $file_extension ?>">
                                                                        Votre navigateur ne supporte pas la lecture de vidéos.
                                                                    </video>
                                                                </div>
                                                            <?php elseif (in_array(strtolower($file_extension), ['mp3', 'wav', 'ogg']) && file_exists($file_path)): ?>
                                                                <div class="audio-player">
                                                                    <audio controls style="width: 100%">
                                                                        <source src="<?= $file_path ?>" type="audio/<?= $file_extension ?>">
                                                                        Votre navigateur ne supporte pas la lecture audio.
                                                                    </audio>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <a href="<?= $file_path ?>" 
                                                               class="inline-flex items-center mt-3 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors" 
                                                               download>
                                                                <i class="fas fa-download mr-2"></i> Télécharger le fichier
                                                            </a>
                                                        </div>
                                                    <?php elseif ($contenu['type'] === 'video' && !empty($contenu['contenu'])): ?>
                                                        <div class="video-container">
                                                            <video controls>
                                                                <source src="../uploads/videos/<?= htmlspecialchars($contenu['contenu']) ?>" type="video/mp4">
                                                                Votre navigateur ne supporte pas la lecture de vidéos.
                                                            </video>
                                                        </div>
                                                        <a href="../uploads/videos/<?= htmlspecialchars($contenu['contenu']) ?>" 
                                                           class="inline-flex items-center mt-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors" 
                                                           download>
                                                            <i class="fas fa-download mr-2"></i> Télécharger la vidéo
                                                        </a>
                                                    <?php elseif ($contenu['type'] === 'lien' && !empty($contenu['contenu'])): ?>
                                                        <?php
                                                        // Vérifier si c'est une vidéo YouTube
                                                        if (strpos($contenu['contenu'], 'youtube:') === 0) {
                                                            $youtube_id = substr($contenu['contenu'], 8);
                                                        ?>
                                                            <div class="video-container">
                                                                <iframe src="https://www.youtube.com/embed/<?= $youtube_id ?>" 
                                                                        frameborder="0" 
                                                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                                        allowfullscreen>
                                                                </iframe>
                                                            </div>
                                                            <a href="https://youtube.com/watch?v=<?= $youtube_id ?>" 
                                                               class="inline-flex items-center mt-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors" 
                                                               target="_blank">
                                                                <i class="fab fa-youtube mr-2"></i> Voir sur YouTube
                                                            </a>
                                                        <?php } else { ?>
                                                            <div class="bg-blue-50 p-4 rounded-lg mb-4">
                                                                <div class="flex items-center">
                                                                    <i class="fas fa-link text-blue-500 text-xl mr-3"></i>
                                                                    <div>
                                                                        <p class="font-medium">Lien externe</p>
                                                                        <a href="<?= htmlspecialchars($contenu['contenu']) ?>" 
                                                                           class="text-blue-600 hover:underline break-all" 
                                                                           target="_blank">
                                                                            <?= htmlspecialchars($contenu['contenu']) ?>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <a href="<?= htmlspecialchars($contenu['contenu']) ?>" 
                                                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors" 
                                                               target="_blank">
                                                                <i class="fas fa-external-link-alt mr-2"></i> Visiter le lien
                                                            </a>
                                                        <?php } ?>
                                                    <?php elseif ($contenu['type'] === 'texte' && !empty($contenu['contenu'])): ?>
                                                        <div class="prose max-w-none bg-gray-50 p-4 rounded-lg">
                                                            <?= nl2br(htmlspecialchars($contenu['contenu'])) ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <p class="text-gray-500">Aucun contenu disponible pour cette section.</p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-gray-500">Aucun contenu disponible pour ce module.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Aucun module disponible pour ce cours.</p>
                            <p class="text-sm text-gray-400 mt-2">Le professeur n'a pas encore ajouté de contenu à ce cours.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Colonne latérale avec informations supplémentaires -->
            <div>
                <!-- Annonces récentes -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-bullhorn text-accent-600 mr-2"></i>
                        Annonces récentes
                    </h2>
                    
                    <?php if (count($annonces) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($annonces as $annonce): ?>
                                <div class="border-l-4 border-accent-500 pl-4 py-1">
                                    <h3 class="font-medium text-gray-800"><?= htmlspecialchars($annonce['titre']) ?></h3>
                                    <p class="text-sm text-gray-600 mt-1"><?= nl2br(htmlspecialchars($annonce['contenu'] ?? '')) ?></p>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-xs text-gray-500">Par <?= htmlspecialchars(($annonce['prenom'] ?? '') . ' ' . ($annonce['nom'] ?? '')) ?></span>
                                        <span class="text-xs text-gray-500"><?= date('d/m/Y', strtotime($annonce['created_at'])) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-sm">Aucune annonce pour le moment.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Ressources supplémentaires -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-paperclip text-primary-600 mr-2"></i>
                        Ressources
                    </h2>
                    
                    <?php if (count($ressources) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($ressources as $ressource): 
                                $icon_class = "fa-file";
                                $extension = pathinfo($ressource['fichier'], PATHINFO_EXTENSION);
                                
                                switch(strtolower($extension)) {
                                    case 'pdf': $icon_class = "fa-file-pdf"; break;
                                    case 'doc': case 'docx': $icon_class = "fa-file-word"; break;
                                    case 'xls': case 'xlsx': $icon_class = "fa-file-excel"; break;
                                    case 'ppt': case 'pptx': $icon_class = "fa-file-powerpoint"; break;
                                    case 'zip': case 'rar': $icon_class = "fa-file-archive"; break;
                                    case 'jpg': case 'jpeg': case 'png': case 'gif': $icon_class = "fa-file-image"; break;
                                }
                            ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas <?= $icon_class ?> text-primary-600 mr-3"></i>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($ressource['titre']) ?></p>
                                            <p class="text-xs text-gray-500">Ajouté le <?= date('d/m/Y', strtotime($ressource['created_at'])) ?></p>
                                        </div>
                                    </div>
                                    <a href="../uploads/ressources/<?= htmlspecialchars($ressource['fichier']) ?>" 
                                       download class="text-gray-500 hover:text-primary-600">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-sm">Aucune ressource supplémentaire.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Actions rapides -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-bolt text-accent-600 mr-2"></i>
                        Actions rapides
                    </h2>
                    
                    <div class="space-y-3">
                        <a href="quiz_list.php?course_id=<?= $course_id ?>" 
                           class="flex items-center justify-between p-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-question-circle mr-3"></i>
                                <span>Quiz de ce cours</span>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        
                        <a href="devoirs.php?course_id=<?= $course_id ?>" 
                           class="flex items-center justify-between p-3 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-tasks mr-3"></i>
                                <span>Devoirs à rendre</span>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        
                        <a href="messagerie.php?recipient=<?= $course['enseignant_id'] ?>" 
                           class="flex items-center justify-between p-3 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-envelope mr-3"></i>
                                <span>Contacter l'enseignant</span>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript pour l'interactivité -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gérer l'affichage du contenu des modules
            const viewButtons = document.querySelectorAll('.view-module-btn');
            
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const moduleId = this.getAttribute('data-module-id');
                    const moduleContent = document.getElementById(`module-${moduleId}`);
                    
                    // Basculer l'affichage du contenu
                    if (moduleContent.classList.contains('active')) {
                        moduleContent.classList.remove('active');
                        this.innerHTML = '<i class="fas fa-eye mr-2"></i> Voir le contenu';
                    } else {
                        // Masquer tous les autres contenus de module
                        document.querySelectorAll('.module-content').forEach(content => {
                            content.classList.remove('active');
                        });
                        
                        // Réinitialiser tous les boutons
                        document.querySelectorAll('.view-module-btn').forEach(btn => {
                            btn.innerHTML = '<i class="fas fa-eye mr-2"></i> Voir le contenu';
                        });
                        
                        // Afficher le contenu sélectionné
                        moduleContent.classList.add('active');
                        this.innerHTML = '<i class="fas fa-times mr-2"></i> Masquer le contenu';
                    }
                });
            });
            
            // Gérer la navigation entre les contenus d'un module
            const contenuNavButtons = document.querySelectorAll('.contenu-nav-btn');
            
            contenuNavButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const parentContainer = this.closest('.module-content');
                    
                    // Mettre à jour les boutons de navigation
                    parentContainer.querySelectorAll('.contenu-nav-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Afficher le contenu cible
                    parentContainer.querySelectorAll('.contenu-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    document.getElementById(targetId).classList.add('active');
                });
            });
            
            // Animation des cercles de progression
            const progressRing = document.querySelector('.progress-ring');
            if (progressRing) {
                const radius = progressRing.getAttribute('r');
                const circumference = 2 * Math.PI * radius;
                
                progressRing.style.strokeDasharray = circumference;
                progressRing.style.strokeDashoffset = circumference;
                
                // Animer le cercle de progression
                setTimeout(() => {
                    const progress = <?= $progress_percent ?>;
                    const offset = circumference - (progress / 100) * circumference;
                    progressRing.style.strokeDashoffset = offset;
                }, 300);
            }
        });
    </script>
</body>
</html>