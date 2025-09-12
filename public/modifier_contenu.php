<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérification : utilisateur connecté et rôle enseignant (role_id = 2)
if (!isLoggedIn() || $_SESSION['role_id'] != 2) {
    header('Location: ../public/login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Vérifier si l'ID du contenu est passé en paramètre
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: teacher_dashboard.php');
    exit();
}

$contenu_id = intval($_GET['id']);

// Récupérer les informations du contenu
$stmt = $pdo->prepare("
    SELECT mc.*, m.cours_id, c.enseignant_id 
    FROM module_contenus mc
    JOIN modules m ON mc.module_id = m.id
    JOIN cours c ON m.cours_id = c.id
    WHERE mc.id = ? AND c.enseignant_id = ?
");
$stmt->execute([$contenu_id, $teacher_id]);
$contenu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contenu) {
    header('Location: teacher_dashboard.php');
    exit();
}

// Récupérer les modules de l'enseignant pour le formulaire
$modules_stmt = $pdo->prepare("
    SELECT m.*, c.titre as course_titre 
    FROM modules m 
    JOIN cours c ON m.cours_id = c.id 
    WHERE c.enseignant_id = ?
    ORDER BY c.titre, m.ordre
");
$modules_stmt->execute([$teacher_id]);
$modules = $modules_stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_contenu'])) {
    $titre = $_POST['titre'];
    $module_id = $_POST['module_id'];
    $ordre = $_POST['ordre'];
    $description = $_POST['description'] ?? '';
    
    // Validation des données
    if (!empty($titre) && !empty($module_id)) {
        // Vérifier que le nouveau module appartient bien à l'enseignant
        $check_stmt = $pdo->prepare("
            SELECT m.id 
            FROM modules m 
            JOIN cours c ON m.cours_id = c.id 
            WHERE m.id = ? AND c.enseignant_id = ?
        ");
        $check_stmt->execute([$module_id, $teacher_id]);
        
        if ($check_stmt->fetch()) {
            $update_data = [
                'titre' => $titre,
                'module_id' => $module_id,
                'ordre' => $ordre,
                'description' => $description,
                'id' => $contenu_id
            ];
            
            // Gérer l'upload de fichier si nécessaire
            if ($contenu['type'] === 'fichier' && isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/modules/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Supprimer l'ancien fichier
                if (!empty($contenu['contenu']) && file_exists($upload_dir . $contenu['contenu'])) {
                    unlink($upload_dir . $contenu['contenu']);
                }
                
                $file_name = time() . '_' . basename($_FILES['fichier']['name']);
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['fichier']['tmp_name'], $target_path)) {
                    $update_data['contenu'] = $file_name;
                } else {
                    $error_message = "Erreur lors du téléchargement du fichier.";
                }
            } else if ($contenu['type'] === 'video' && isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
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
                    // Supprimer l'ancienne vidéo
                    if (!empty($contenu['contenu']) && file_exists($upload_dir . $contenu['contenu'])) {
                        unlink($upload_dir . $contenu['contenu']);
                    }
                    
                    $file_name = time() . '_' . basename($_FILES['video']['name']);
                    $target_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['video']['tmp_name'], $target_path)) {
                        $update_data['contenu'] = $file_name;
                    } else {
                        $error_message = "Erreur lors du téléchargement de la vidéo.";
                    }
                }
            } else if ($contenu['type'] === 'lien') {
                $update_data['contenu'] = $_POST['lien_url'];
                
                // Vérifier si c'est une URL YouTube
                if (preg_match('/(youtube\.com|youtu\.be)/', $update_data['contenu'])) {
                    // Extraire l'ID de la vidéo YouTube
                    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $update_data['contenu'], $matches)) {
                        $youtube_id = $matches[1];
                        $update_data['contenu'] = "youtube:" . $youtube_id;
                    }
                }
            } else if ($contenu['type'] === 'texte') {
                $update_data['contenu'] = $_POST['texte_contenu'];
            }
            
            if (!isset($error_message)) {
                $sql = "UPDATE module_contenus SET titre = ?, module_id = ?, ordre = ?, description = ?";
                $params = [$titre, $module_id, $ordre, $description];
                
                if (isset($update_data['contenu'])) {
                    $sql .= ", contenu = ?";
                    $params[] = $update_data['contenu'];
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $contenu_id;
                
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute($params)) {
                    $success_message = "Contenu modifié avec succès!";
                    // Recharger les données du contenu
                    $stmt = $pdo->prepare("SELECT * FROM module_contenus WHERE id = ?");
                    $stmt->execute([$contenu_id]);
                    $contenu = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error_message = "Erreur lors de la modification du contenu.";
                }
            }
        } else {
            $error_message = "Module non autorisé.";
        }
    } else {
        $error_message = "Veuillez remplir tous les champs obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Modifier le contenu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i data-feather="edit" class="inline-block mr-2"></i>
                    Modifier le contenu
                </h1>
                <a href="teacher_dashboard.php" class="text-primary-600 hover:text-primary-800">
                    <i data-feather="arrow-left" class="inline-block mr-1"></i> Retour
                </a>
            </div>

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

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="titre" class="block text-sm font-medium text-gray-700 mb-1">Titre *</label>
                    <input type="text" id="titre" name="titre" required 
                           value="<?= htmlspecialchars($contenu['titre']) ?>"
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label for="module_id" class="block text-sm font-medium text-gray-700 mb-1">Module *</label>
                    <select id="module_id" name="module_id" required 
                            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Sélectionner un module</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?= $module['id'] ?>" 
                                <?= $module['id'] == $contenu['module_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($module['course_titre'] . ' - ' . $module['titre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="ordre" class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                    <input type="number" id="ordre" name="ordre" 
                           value="<?= htmlspecialchars($contenu['ordre']) ?>"
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="3"
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"><?= htmlspecialchars($contenu['description'] ?? '') ?></textarea>
                </div>

                <!-- Champs spécifiques au type de contenu -->
                <?php if ($contenu['type'] === 'fichier'): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fichier actuel</label>
                        <p class="text-sm text-gray-600 mb-2">
                            <?= htmlspecialchars($contenu['contenu']) ?>
                            <?php if (!empty($contenu['contenu'])): ?>
                                <a href="../uploads/modules/<?= htmlspecialchars($contenu['contenu']) ?>" 
                                   class="text-primary-600 hover:text-primary-800 ml-2" target="_blank">
                                    <i data-feather="download" class="inline-block w-4 h-4"></i> Télécharger
                                </a>
                            <?php endif; ?>
                        </p>
                        <label for="fichier" class="block text-sm font-medium text-gray-700 mb-1">Nouveau fichier (laisser vide pour conserver l'actuel)</label>
                        <input type="file" id="fichier" name="fichier" 
                               class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                <?php elseif ($contenu['type'] === 'video'): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vidéo actuelle</label>
                        <p class="text-sm text-gray-600 mb-2">
                            <?= htmlspecialchars($contenu['contenu']) ?>
                            <?php if (!empty($contenu['contenu'])): ?>
                                <a href="../uploads/videos/<?= htmlspecialchars($contenu['contenu']) ?>" 
                                   class="text-primary-600 hover:text-primary-800 ml-2" target="_blank">
                                    <i data-feather="download" class="inline-block w-4 h-4"></i> Télécharger
                                </a>
                            <?php endif; ?>
                        </p>
                        <label for="video" class="block text-sm font-medium text-gray-700 mb-1">Nouvelle vidéo (laisser vide pour conserver l'actuelle)</label>
                        <input type="file" id="video" name="video" accept="video/*"
                               class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                <?php elseif ($contenu['type'] === 'lien'): ?>
                    <div>
                        <label for="lien_url" class="block text-sm font-medium text-gray-700 mb-1">URL *</label>
                        <?php 
                        $url_value = $contenu['contenu'];
                        if (strpos($url_value, 'youtube:') === 0) {
                            $youtube_id = substr($url_value, 8);
                            $url_value = "https://youtube.com/watch?v=" . $youtube_id;
                        }
                        ?>
                        <input type="url" id="lien_url" name="lien_url" required 
                               value="<?= htmlspecialchars($url_value) ?>"
                               class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                               placeholder="https://example.com">
                    </div>
                <?php elseif ($contenu['type'] === 'texte'): ?>
                    <div>
                        <label for="texte_contenu" class="block text-sm font-medium text-gray-700 mb-1">Contenu texte *</label>
                        <textarea id="texte_contenu" name="texte_contenu" rows="10" required
                               class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"><?= htmlspecialchars($contenu['contenu']) ?></textarea>
                    </div>
                <?php endif; ?>

                <div class="flex justify-end space-x-4 pt-4">
                    <a href="teacher_dashboard.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Annuler
                    </a>
                    <button type="submit" name="modifier_contenu" 
                            class="px-6 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        Modifier le contenu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>