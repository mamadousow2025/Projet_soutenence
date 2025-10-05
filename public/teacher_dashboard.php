<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérification : utilisateur connecté et rôle enseignant (role_id = 2)
if (!isLoggedIn() || $_SESSION['role_id'] != 2) {
    header('Location: ../public/login.php');
    exit();
}

$teacher_name = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);
$teacher_id = $_SESSION['user_id'];

// Traitement du formulaire de création de module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_module'])) {
    $titre = $_POST['titre'];
    $cours_id = $_POST['cours_id'];
    $ordre = $_POST['ordre'];
    
    // Validation des données
    if (!empty($titre) && !empty($cours_id)) {
        $stmt = $pdo->prepare("INSERT INTO modules (titre, cours_id, ordre) VALUES (?, ?, ?)");
        if ($stmt->execute([$titre, $cours_id, $ordre])) {
            $success_message = "Module créé avec succès!";
        } else {
            $error_message = "Erreur lors de la création du module.";
        }
    } else {
        $error_message = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Traitement de l'ajout de contenu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_content'])) {
    $module_id = $_POST['module_id'];
    $titre = $_POST['titre'];
    $type = $_POST['type'];
    $ordre = $_POST['ordre'];
    
    // Vérifier si le module appartient à l'enseignant
    $check_stmt = $pdo->prepare("
        SELECT m.id 
        FROM modules m 
        JOIN cours c ON m.cours_id = c.id 
        WHERE m.id = ? AND c.enseignant_id = ?
    ");
    $check_stmt->execute([$module_id, $teacher_id]);
    
    if ($check_stmt->fetch()) {
        // Gérer l'upload de fichier si nécessaire
        $contenu = '';
        
        if ($type === 'fichier' && isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/modules/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['fichier']['name']);
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['fichier']['tmp_name'], $target_path)) {
                $contenu = $file_name;
            } else {
                $error_message = "Erreur lors du téléchargement du fichier.";
            }
        } else if ($type === 'video' && isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/videos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Vérifier le type de fichier
            $allowed_types = ['video/mp4', 'video/mov', 'video/avi', 'video/wmv', 'video/webm'];
            $file_type = $_FILES['video']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                $error_message = "Type de fichier non autorisé. Formats acceptés: MP4, MOV, AVI, WMV, WEBM.";
            } else {
                $file_name = time() . '_' . basename($_FILES['video']['name']);
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['video']['tmp_name'], $target_path)) {
                    $contenu = $file_name;
                } else {
                    $error_message = "Erreur lors du téléchargement de la vidéo.";
                }
            }
        } else if ($type === 'lien') {
            $contenu = $_POST['lien_url'];
            
            // Vérifier si c'est une URL YouTube
            if (preg_match('/(youtube\.com|youtu\.be)/', $contenu)) {
                // Extraire l'ID de la vidéo YouTube
                if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $contenu, $matches)) {
                    $youtube_id = $matches[1];
                    $contenu = "youtube:" . $youtube_id;
                }
            }
        } else if ($type === 'texte') {
            $contenu = $_POST['texte_contenu'];
        }
        
        if (!isset($error_message)) {
            $stmt = $pdo->prepare("
                INSERT INTO module_contenus (module_id, titre, type, contenu, ordre) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$module_id, $titre, $type, $contenu, $ordre])) {
                $success_message = "Contenu ajouté avec succès!";
            } else {
                $error_message = "Erreur lors de l'ajout du contenu.";
            }
        }
    } else {
        $error_message = "Module non trouvé ou non autorisé.";
    }
}

// Récupérer les cours de l'enseignant pour le formulaire
$courses_stmt = $pdo->prepare("SELECT id, titre FROM cours WHERE enseignant_id = ? AND status = 'active'");
$courses_stmt->execute([$teacher_id]);
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les modules existants avec leurs cours
$modules_stmt = $pdo->prepare("
    SELECT m.*, c.titre as course_titre 
    FROM modules m 
    JOIN cours c ON m.cours_id = c.id 
    WHERE c.enseignant_id = ?
    ORDER BY m.ordre ASC, m.created_at DESC
");
$modules_stmt->execute([$teacher_id]);
$modules = $modules_stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les contenus si un module est sélectionné
$module_contenus = [];
if (isset($_GET['view_module']) && is_numeric($_GET['view_module'])) {
    $module_id = $_GET['view_module'];
    
    // Vérifier que le module appartient à l'enseignant
    $check_stmt = $pdo->prepare("
        SELECT m.id 
        FROM modules m 
        JOIN cours c ON m.cours_id = c.id 
        WHERE m.id = ? AND c.enseignant_id = ?
    ");
    $check_stmt->execute([$module_id, $teacher_id]);
    
    if ($check_stmt->fetch()) {
        $selected_module_id = $module_id;
        
        // Récupérer les contenus du module
        $contenus_stmt = $pdo->prepare("
            SELECT * FROM module_contenus 
            WHERE module_id = ? 
            ORDER BY ordre ASC, created_at ASC
        ");
        $contenus_stmt->execute([$module_id]);
        $module_contenus = $contenus_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Étudiants inscrits
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT inscriptions.student_id)
    FROM inscriptions
    JOIN cours ON inscriptions.course_id = cours.id
    WHERE cours.enseignant_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$students_count = $stmt->fetchColumn() ?: 0;

// Cours actifs
$stmt = $pdo->prepare("SELECT COUNT(*) FROM cours WHERE enseignant_id = ? AND status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$courses_count = $stmt->fetchColumn() ?: 0;

// Quiz publiés
$stmt = $pdo->prepare("SELECT COUNT(*) FROM quizz WHERE enseignant_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$quizzes_count = $stmt->fetchColumn() ?: 0;

// Modules créés
$stmt = $pdo->prepare("SELECT COUNT(*) FROM modules m JOIN cours c ON m.cours_id = c.id WHERE c.enseignant_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$modules_count = $stmt->fetchColumn() ?: 0;

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Tableau de Bord Enseignant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#009688',
                        accent: '#FF9800',
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gray-100 flex min-h-screen">

    <!-- Menu latéral -->
    <aside class="w-64 bg-primary text-white flex flex-col">
        <div class="p-6 text-2xl font-bold border-b border-white/20 flex items-center gap-2">
            <i data-feather="book" class="stroke-[2px]"></i>
            <span>Espace Enseignant</span>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="http://localhost/lms_isep/public/cours.php" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="book-open"></i> Gestion des cours
            </a>
            <a href="#modules" class="flex items-center gap-3 p-3 rounded bg-accent transition">
                <i data-feather="folder-plus"></i> Gestion des modules
            </a>
            <a href="#contenus" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="file-plus"></i> Ajout de contenus
            </a>
            <a href="quiz_devoir.php" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition-colors duration-200">
    <i data-feather="help-circle"></i>
    <span>Quiz & Devoir</span>
</a>
<script>
    feather.replace(); // Pour afficher l'icône correctement
</script>

           
            <a href="feedback.php" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="edit-3"></i> Correction & Feedback
            </a>
            <a href="progression.php" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="trending-up"></i> Suivi des progrès
            </a>
           
            
           <a href="cours_direct.php" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
    <i data-feather="tv"></i>  Cours en direct
</a>

           
           <a href="projet.php" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
    <i data-feather="layers"></i> Projets
</a>

            <a href="messagerie.php" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="send"></i> Messagerie
            </a>
        </nav>
        <div class="p-4 border-t border-white/20">
            <a href="../public/logout.php" 
               class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition">
                <i data-feather="log-out"></i> Déconnexion
            </a>
        </div>
    </aside>

    <!-- Contenu principal -->
    <main class="flex-1 p-8 overflow-auto">
        <!-- Barre supérieure -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-primary">Bienvenue, <span class="text-accent"><?= htmlspecialchars($_SESSION['prenom']) ?></span></h1>
            <div class="flex items-center gap-3">
                <span class="text-gray-600">Enseignant connecté</span>
                <div class="w-10 h-10 rounded-full bg-accent flex items-center justify-center text-white font-bold text-lg">
                    <?= strtoupper(substr($_SESSION['prenom'], 0, 1)) ?>
                </div>
            </div>
        </div>

        <!-- Cartes statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Étudiants inscrits -->
            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition cursor-default">
                <div class="flex items-center gap-4">
                    <div class="bg-primary p-3 rounded-full text-white">
                        <i data-feather="users"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold"><?= $students_count ?></h2>
                        <p class="text-gray-500">Étudiants inscrits</p>
                    </div>
                </div>
            </div>

            <!-- Cours actifs -->
            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition cursor-default">
                <div class="flex items-center gap-4">
                    <div class="bg-accent p-3 rounded-full text-white">
                        <i data-feather="layers"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold"><?= $courses_count ?></h2>
                        <p class="text-gray-500">Cours actifs</p>
                    </div>
                </div>
            </div>

            <!-- Quiz publiés -->
            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition cursor-default">
                <div class="flex items-center gap-4">
                    <div class="bg-primary p-3 rounded-full text-white">
                        <i data-feather="file-text"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold"><?= $quizzes_count ?></h2>
                        <p class="text-gray-500">Quiz publiés</p>
                    </div>
                </div>
            </div>
            
            <!-- Modules créés -->
            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition cursor-default">
                <div class="flex items-center gap-4">
                    <div class="bg-accent p-3 rounded-full text-white">
                        <i data-feather="folder"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold"><?= $modules_count ?></h2>
                        <p class="text-gray-500">Modules créés</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section création de modules -->
        <section id="modules" class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-2xl font-bold text-primary mb-6 flex items-center gap-2">
                <i data-feather="folder-plus" class="stroke-[2px]"></i>
                Créer un nouveau module
            </h2>
            
            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= $success_message ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="titre" class="block text-sm font-medium text-gray-700 mb-1">Titre du module *</label>
                    <input type="text" id="titre" name="titre" required 
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-accent">
                </div>
                
                <div>
                    <label for="cours_id" class="block text-sm font-medium text-gray-700 mb-1">Cours associé *</label>
                    <select id="cours_id" name="cours_id" required 
                            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-accent">
                        <option value="">Sélectionner un cours</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['titre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="ordre" class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                    <input type="number" id="ordre" name="ordre" value="0" min="0"
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-accent">
                </div>
                
                <div class="md:col-span-2">
                    <button type="submit" name="create_module" 
                            class="bg-accent hover:bg-orange-600 text-white font-medium py-2 px-6 rounded-md transition">
                        Créer le module
                    </button>
                </div>
            </form>
        </section>
        
        <!-- Liste des modules existants -->
        <section class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-2xl font-bold text-primary mb-6 flex items-center gap-2">
                <i data-feather="folder" class="stroke-[2px]"></i>
                Mes modules
            </h2>
            
            <?php if (count($modules) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cours associé</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de création</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($modules as $module): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($module['titre']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($module['course_titre']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($module['ordre']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= date('d/m/Y', strtotime($module['created_at'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="?view_module=<?= $module['id'] ?>#contenus" class="text-accent hover:text-orange-600 mr-3">
                                            <i data-feather="plus" class="w-4 h-4 inline"></i> Ajouter contenu
                                        </a>
                                        <a href="#" class="text-red-600 hover:text-red-900">
                                            <i data-feather="trash" class="w-4 h-4 inline"></i> Supprimer
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i data-feather="folder" class="w-12 h-12 mx-auto mb-4"></i>
                    <p>Vous n'avez pas encore créé de modules.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Section ajout de contenu -->
        <?php if (isset($selected_module_id)): ?>
        <section id="contenus" class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-2xl font-bold text-primary mb-6 flex items-center gap-2">
                <i data-feather="file-plus" class="stroke-[2px]"></i>
                Ajouter du contenu au module
            </h2>
            
            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= $success_message ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <input type="hidden" name="module_id" value="<?= $selected_module_id ?>">
                
                <div>
                    <label for="titre_contenu" class="block text-sm font-medium text-gray-700 mb-1">Titre du contenu *</label>
                    <input type="text" id="titre_contenu" name="titre" required 
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-accent">
                </div>
                
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type de contenu *</label>
                    <select id="type" name="type" required 
                            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-accent">
                        <option value="">Sélectionner un type</option>
                        <option value="fichier">Fichier (PDF, Word, etc.)</option>
                        <option value="video">Vidéo (MP4, MOV, etc.)</option>
                        <option value="lien">Lien (URL)</option>
                        <option value="texte">Texte</option>
                    </select>
                </div>
                
                <div id="fichier_field" class="hidden md:col-span-2">
                    <label for="fichier" class="block text-sm font-medium text-gray-700 mb-1">Fichier *</label>
                    <input type="file" id="fichier" name="fichier" 
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-accent">
                    <p class="text-xs text-gray-500 mt-1">Formats acceptés: PDF, Word, Excel, PowerPoint, images</p>
                </div>
                
                <div id="video_field" class="hidden md:col-span-2">
                    <label for="video" class="block text-sm font-medium text-gray-700 mb-1">Vidéo *</label>
                    <input type="file" id="video" name="video" accept="video/*"
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-accent">
                    <p class="text-xs text-gray-500 mt-1">Formats acceptés: MP4, MOV, AVI, WMV, WEBM (max 100MB)</p>
                </div>
                
                <div id="lien_field" class="hidden md:col-span-2">
                    <label for="lien_url" class="block text-sm font-medium text-gray-700 mb-1">URL *</label>
                    <input type="url" id="lien_url" name="lien_url" 
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-accent"
                           placeholder="https://example.com ou https://youtube.com/...">
                    <p class="text-xs text-gray-500 mt-1">Les vidéos YouTube seront intégrées automatiquement</p>
                </div>
                
                <div id="texte_field" class="hidden md:col-span-2">
                    <label for="texte_contenu" class="block text-sm font-medium text-gray-700 mb-1">Contenu texte *</label>
                    <textarea id="texte_contenu" name="texte_contenu" rows="5"
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-accent"></textarea>
                </div>
                
                <div>
                    <label for="ordre_contenu" class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                    <input type="number" id="ordre_contenu" name="ordre" value="0" min="0"
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-accent">
                </div>
                
                <div class="md:col-span-2">
                    <button type="submit" name="add_content" 
                            class="bg-accent hover:bg-orange-600 text-white font-medium py-2 px-6 rounded-md transition">
                        Ajouter le contenu
                    </button>
                </div>
            </form>
            
            <!-- Liste des contenus existants -->
            <div class="mt-8">
                <h3 class="text-xl font-bold text-primary mb-4">Contenus existants</h3>
                
                <?php if (count($module_contenus) > 0): ?>
                    <div class="grid grid-cols-1 gap-6">
                        <?php foreach ($module_contenus as $contenu): ?>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex justify-between items-start mb-3">
                                    <h4 class="text-lg font-semibold"><?= htmlspecialchars($contenu['titre']) ?></h4>
                                    <span class="bg-primary text-white text-xs px-2 py-1 rounded">
                                        <?php 
                                        switch($contenu['type']) {
                                            case 'fichier': echo 'Fichier'; break;
                                            case 'video': echo 'Vidéo'; break;
                                            case 'lien': echo 'Lien'; break;
                                            case 'texte': echo 'Texte'; break;
                                            default: echo $contenu['type'];
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <?php if ($contenu['type'] === 'fichier'): ?>
                                        <div class="flex items-center gap-2">
                                            <i data-feather="file" class="w-5 h-5 text-gray-500"></i>
                                            <span class="text-sm text-gray-600">Fichier: <?= $contenu['contenu'] ?></span>
                                            <a href="../uploads/modules/<?= $contenu['contenu'] ?>" 
                                               class="ml-3 text-accent hover:underline text-sm" target="_blank">
                                                <i data-feather="download" class="w-4 h-4 inline"></i> Télécharger
                                            </a>
                                        </div>
                                    
                                    <?php elseif ($contenu['type'] === 'video'): ?>
                                        <div class="mb-2">
                                            <span class="text-sm text-gray-600">Vidéo: <?= $contenu['contenu'] ?></span>
                                            <a href="../uploads/videos/<?= $contenu['contenu'] ?>" 
                                               class="ml-3 text-accent hover:underline text-sm" target="_blank">
                                                <i data-feather="download" class="w-4 h-4 inline"></i> Télécharger
                                            </a>
                                        </div>
                                        <video controls class="w-full max-w-lg rounded-lg">
                                            <source src="../uploads/videos/<?= $contenu['contenu'] ?>" type="video/mp4">
                                            Votre navigateur ne supporte pas la lecture de vidéos.
                                        </video>
                                    
                                    <?php elseif ($contenu['type'] === 'lien'): ?>
                                        <?php 
                                        // Vérifier si c'est une vidéo YouTube
                                        if (strpos($contenu['contenu'], 'youtube:') === 0) {
                                            $youtube_id = substr($contenu['contenu'], 8);
                                            ?>
                                            <div class="mb-2">
                                                <span class="text-sm text-gray-600">Vidéo YouTube</span>
                                                <a href="https://youtube.com/watch?v=<?= $youtube_id ?>" 
                                                   class="ml-3 text-accent hover:underline text-sm" target="_blank">
                                                    <i data-feather="external-link" class="w-4 h-4 inline"></i> Voir sur YouTube
                                                </a>
                                            </div>
                                            <div class="w-full max-w-lg">
                                                <iframe class="w-full h-48 rounded-lg" 
                                                        src="https://www.youtube.com/embed/<?= $youtube_id ?>" 
                                                        frameborder="0" 
                                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                        allowfullscreen>
                                                </iframe>
                                            </div>
                                        <?php } else { ?>
                                            <div class="flex items-center gap-2">
                                                <i data-feather="link" class="w-5 h-5 text-gray-500"></i>
                                                <a href="<?= $contenu['contenu'] ?>" 
                                                   class="text-accent hover:underline break-all" target="_blank">
                                                    <?= $contenu['contenu'] ?>
                                                </a>
                                            </div>
                                        <?php } ?>
                                    
                                    <?php else: ?>
                                        <div class="bg-white p-3 rounded border">
                                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($contenu['contenu'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500">Ordre: <?= htmlspecialchars($contenu['ordre']) ?></span>
                                    <div class="flex gap-2">
                                        <a href="#" class="text-accent hover:text-orange-600 text-sm">
                                            <i data-feather="edit" class="w-4 h-4 inline"></i> Modifier
                                        </a>
                                        <a href="#" class="text-red-600 hover:text-red-900 text-sm">
                                            <i data-feather="trash" class="w-4 h-4 inline"></i> Supprimer
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i data-feather="file" class="w-12 h-12 mx-auto mb-4"></i>
                        <p>Aucun contenu n'a été ajouté à ce module.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Section raccourcis -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <?php
            $features = [
                ["Gestion des cours", "Créer, modifier et organiser vos cours.", "book-open"],
                ["Gestion des modules", "Organiser le contenu de vos cours.", "folder-plus"],
                ["Ajout de contenus multimédias", "Importer vidéos, audios et documents.", "file-plus"],
                ["Quiz", "Concevoir et publier des quiz.", "help-circle"],
                ["Devoirs", "Attribuer, corriger et noter les devoirs.", "clipboard"],
                ["Feedback", "Donner un retour aux étudiants.", "edit-3"],
                ["Suivi des progrès", "Analyser les performances.", "trending-up"],
                ["Forums", "Gérer les discussions.", "message-circle"],
                ["Sessions live", "Planifier vos visioconférences.", "video"],
                ["Partage d'écran", "Partager documents et écran.", "tv"],
                ["Chat & Sondages", "Interagir en temps réel.", "activity"],
                ["Enregistrements", "Revoir les sessions passées.", "film"],
                ["Messagerie", "Communiquer avec les étudiants.", "send"],
            ];

            foreach ($features as $f) {
                echo '
                <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition cursor-pointer">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="bg-accent p-3 rounded-full text-white">
                            <i data-feather="'.$f[2].'"></i>
                        </div>
                        <h3 class="text-lg font-semibold">'.$f[0].'</h3>
                    </div>
                    <p class="text-gray-500">'.$f[1].'</p>
                </div>';
            }
            ?>
        </div>
    </main>

    <script>
        feather.replace();
        
        // Afficher/masquer les champs en fonction du type de contenu sélectionné
        document.getElementById('type').addEventListener('change', function() {
            const type = this.value;
            
            // Masquer tous les champs
            document.getElementById('fichier_field').classList.add('hidden');
            document.getElementById('video_field').classList.add('hidden');
            document.getElementById('lien_field').classList.add('hidden');
            document.getElementById('texte_field').classList.add('hidden');
            
            // Afficher le champ correspondant au type sélectionné
            if (type === 'fichier') {
                document.getElementById('fichier_field').classList.remove('hidden');
            } else if (type === 'video') {
                document.getElementById('video_field').classList.remove('hidden');
            } else if (type === 'lien') {
                document.getElementById('lien_field').classList.remove('hidden');
            } else if (type === 'texte') {
                document.getElementById('texte_field').classList.remove('hidden');
            }
        });

        // Validation de la taille des fichiers vidéo
        document.getElementById('video').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = 100 * 1024 * 1024; // 100MB
                if (file.size > maxSize) {
                    alert('La taille du fichier ne doit pas dépasser 100MB.');
                    this.value = '';
                }
            }
        });
    </script>
</body>
</html>