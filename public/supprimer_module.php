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

// Vérifier si l'ID du module est passé en paramètre
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: teacher_dashboard.php');
    exit();
}

$module_id = intval($_GET['id']);

// Récupérer les informations du module
$stmt = $pdo->prepare("
    SELECT m.*, c.titre as course_titre 
    FROM modules m 
    JOIN cours c ON m.cours_id = c.id 
    WHERE m.id = ? AND c.enseignant_id = ?
");
$stmt->execute([$module_id, $teacher_id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$module) {
    header('Location: teacher_dashboard.php');
    exit();
}

// Récupérer les contenus du module pour affichage
$contenus_stmt = $pdo->prepare("SELECT * FROM module_contenus WHERE module_id = ?");
$contenus_stmt->execute([$module_id]);
$contenus = $contenus_stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_suppression'])) {
    try {
        $pdo->beginTransaction();
        
        // Supprimer les fichiers associés aux contenus
        foreach ($contenus as $contenu) {
            if (($contenu['type'] === 'fichier' || $contenu['type'] === 'video') && !empty($contenu['contenu'])) {
                $upload_dir = $contenu['type'] === 'fichier' ? '../uploads/modules/' : '../uploads/videos/';
                $file_path = $upload_dir . $contenu['contenu'];
                
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }
        
        // Supprimer les contenus du module
        $stmt = $pdo->prepare("DELETE FROM module_contenus WHERE module_id = ?");
        $stmt->execute([$module_id]);
        
        // Supprimer le module
        $stmt = $pdo->prepare("DELETE FROM modules WHERE id = ?");
        $stmt->execute([$module_id]);
        
        $pdo->commit();
        
        $_SESSION['success_message'] = "Module et ses contenus supprimés avec succès!";
        header('Location: teacher_dashboard.php');
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Erreur lors de la suppression du module: " . $e->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler'])) {
    header('Location: teacher_dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Supprimer le module</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i data-feather="trash-2" class="inline-block mr-2"></i>
                    Supprimer le module
                </h1>
                <a href="teacher_dashboard.php" class="text-blue-600 hover:text-blue-800">
                    <i data-feather="arrow-left" class="inline-block mr-1"></i> Retour
                </a>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i data-feather="alert-triangle" class="text-red-500 mr-3 mt-1"></i>
                    <div>
                        <h3 class="font-semibold text-red-800">Attention</h3>
                        <p class="text-red-700 mt-1">
                            Êtes-vous sûr de vouloir supprimer le module "<strong><?= htmlspecialchars($module['titre']) ?></strong>" ?
                            Cette action supprimera également tous les contenus associés et est irréversible.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h4 class="font-medium text-gray-800 mb-3">Détails du module</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Titre:</p>
                        <p class="font-medium"><?= htmlspecialchars($module['titre']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Cours associé:</p>
                        <p class="font-medium"><?= htmlspecialchars($module['course_titre']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Description:</p>
                        <p class="font-medium"><?= !empty($module['description']) ? htmlspecialchars($module['description']) : 'Aucune description' ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Ordre:</p>
                        <p class="font-medium"><?= htmlspecialchars($module['ordre']) ?></p>
                    </div>
                </div>
            </div>

            <?php if (!empty($contenus)): ?>
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h4 class="font-medium text-gray-800 mb-3">Contenus associés (<?= count($contenus) ?>)</h4>
                <div class="space-y-2">
                    <?php foreach ($contenus as $contenu): ?>
                    <div class="flex items-center justify-between bg-white p-3 rounded border">
                        <div class="flex items-center">
                            <?php if ($contenu['type'] === 'fichier'): ?>
                                <i data-feather="file" class="text-blue-500 mr-2"></i>
                            <?php elseif ($contenu['type'] === 'video'): ?>
                                <i data-feather="video" class="text-red-500 mr-2"></i>
                            <?php elseif ($contenu['type'] === 'texte'): ?>
                                <i data-feather="file-text" class="text-green-500 mr-2"></i>
                            <?php else: ?>
                                <i data-feather="file" class="text-gray-500 mr-2"></i>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($contenu['titre']) ?></span>
                        </div>
                        <span class="text-xs bg-gray-200 px-2 py-1 rounded">
                            <?= htmlspecialchars($contenu['type']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" class="flex justify-end space-x-4">
                <button type="submit" name="annuler" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit" name="confirmer_suppression" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Confirmer la suppression
                </button>
            </form>
        </div>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>