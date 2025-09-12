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

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_suppression'])) {
    try {
        // Supprimer le fichier associé si nécessaire
        if (($contenu['type'] === 'fichier' || $contenu['type'] === 'video') && !empty($contenu['contenu'])) {
            $upload_dir = $contenu['type'] === 'fichier' ? '../uploads/modules/' : '../uploads/videos/';
            $file_path = $upload_dir . $contenu['contenu'];
            
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Supprimer le contenu de la base de données
        $stmt = $pdo->prepare("DELETE FROM module_contenus WHERE id = ?");
        $stmt->execute([$contenu_id]);
        
        $_SESSION['success_message'] = "Contenu supprimé avec succès!";
        header('Location: teacher_dashboard.php');
        exit();
        
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la suppression du contenu: " . $e->getMessage();
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
    <title>Supprimer le contenu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i data-feather="trash-2" class="inline-block mr-2"></i>
                    Supprimer le contenu
                </h1>
                <a href="teacher_dashboard.php" class="text-primary-600 hover:text-primary-800">
                    <i data-feather="arrow-left" class="inline-block mr-1"></i> Retour
                </a>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>

            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i data-feather="alert-triangle" class="text-red-500 mr-3 mt-1"></i>
                    <div>
                        <h3 class="font-semibold text-red-800">Attention</h3>
                        <p class="text-red-700 mt-1">
                            Êtes-vous sûr de vouloir supprimer le contenu "<strong><?= htmlspecialchars($contenu['titre']) ?></strong>" ?
                            Cette action est irréversible.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h4 class="font-medium text-gray-800 mb-2">Détails du contenu</h4>
                <div class="space-y-2 text-sm text-gray-600">
                    <p><span class="font-medium">Type:</span> 
                        <?php 
                        switch($contenu['type']) {
                            case 'fichier': echo 'Fichier'; break;
                            case 'video': echo 'Vidéo'; break;
                            case 'lien': echo 'Lien'; break;
                            case 'texte': echo 'Texte'; break;
                            default: echo $contenu['type'];
                        }
                        ?>
                    </p>
                    <p><span class="font-medium">Ordre:</span> <?= htmlspecialchars($contenu['ordre']) ?></p>
                    <p><span class="font-medium">Créé le:</span> <?= date('d/m/Y à H:i', strtotime($contenu['created_at'])) ?></p>
                </div>
            </div>

            <form method="POST" class="flex justify-end space-x-4">
                <a href="teacher_dashboard.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit" name="annuler" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Retour
                </button>
                <button type="submit" name="confirmer_suppression" 
                        class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
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