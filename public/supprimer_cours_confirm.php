<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier les permissions
if (!isLoggedIn() || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3)) {
    header("Location: ../public/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];
$dashboard_link = ($role_id == 3) ? 'admin_dashboard.php' : 'teacher_dashboard.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: cours.php");
    exit;
}

$cours_id = intval($_GET['id']);

// Récupérer les infos du cours avec permissions
if ($role_id == 3) {
    $stmt = $pdo->prepare("SELECT c.*, u.nom as enseignant_nom, u.prenom as enseignant_prenom 
                           FROM cours c 
                           JOIN utilisateurs u ON c.enseignant_id = u.id 
                           WHERE c.id = ?");
    $stmt->execute([$cours_id]);
} else {
    $stmt = $pdo->prepare("SELECT c.*, u.nom as enseignant_nom, u.prenom as enseignant_prenom 
                           FROM cours c 
                           JOIN utilisateurs u ON c.enseignant_id = u.id 
                           WHERE c.id = ? AND c.enseignant_id = ?");
    $stmt->execute([$cours_id, $user_id]);
}

$cours = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cours) {
    header("Location: cours.php");
    exit;
}

// Compter les éléments associés
$stmtModules = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE cours_id = ?");
$stmtModules->execute([$cours_id]);
$nb_modules = $stmtModules->fetchColumn();

$stmtQuizzes = $pdo->prepare("SELECT COUNT(*) FROM quizz WHERE course_id = ?");
$stmtQuizzes->execute([$cours_id]);
$nb_quizzes = $stmtQuizzes->fetchColumn();

// Traitement de la suppression après confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    header("Location: supprimer_cours.php?id=$cours_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmer la suppression - <?php echo $role_id == 3 ? 'Admin' : 'Enseignant'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .btn-teal { background: linear-gradient(135deg, #0D9488 0%, #14B8A6 100%); }
        .btn-orange { background: linear-gradient(135deg, #F59E0B 0%, #FB923C 100%); }
        .btn-red { background: linear-gradient(135deg, #EF4444 0%, #F87171 100%); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <!-- En-tête -->
            <div class="text-center mb-6">
                <div class="bg-red-100 p-3 rounded-full inline-flex mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Confirmer la suppression</h1>
                <?php if ($role_id == 3): ?>
                <div class="bg-red-100 border border-red-200 rounded-lg p-2 mt-2 inline-block">
                    <span class="text-red-700 text-sm font-medium">
                        <i class="fas fa-shield-alt mr-1"></i> Mode Administrateur
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Avertissement -->
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                <div class="flex">
                    <i class="fas fa-exclamation-circle text-yellow-500 text-xl mr-3 mt-1"></i>
                    <div>
                        <h3 class="font-semibold text-yellow-800">Attention ! Action irréversible</h3>
                        <p class="text-yellow-700 mt-1 text-sm">
                            Vous êtes sur le point de supprimer définitivement le cours <strong>"<?= htmlspecialchars($cours['titre']) ?>"</strong>.
                            Cette action supprimera également tous les éléments associés.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Détails du cours -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h4 class="font-semibold text-gray-800 mb-3">Détails du cours à supprimer :</h4>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><span class="font-medium">Titre :</span> <?= htmlspecialchars($cours['titre']) ?></div>
                    <div><span class="font-medium">Filière :</span> <?= htmlspecialchars($cours['filiere_id']) ?></div>
                    <div><span class="font-medium">Modules :</span> <?= $nb_modules ?></div>
                    <div><span class="font-medium">Quizzes :</span> <?= $nb_quizzes ?></div>
                    <?php if ($role_id == 3): ?>
                    <div class="col-span-2">
                        <span class="font-medium">Enseignant :</span> 
                        <?= htmlspecialchars($cours['prenom'] . ' ' . $cours['nom']) ?> (ID: <?= $cours['enseignant_id'] ?>)
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Éléments qui seront supprimés -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <h4 class="font-semibold text-red-800 mb-2">
                    <i class="fas fa-trash mr-2"></i>Éléments qui seront supprimés :
                </h4>
                <ul class="text-red-700 text-sm space-y-1">
                    <li>• Le cours "<?= htmlspecialchars($cours['titre']) ?>"</li>
                    <li>• <?= $nb_modules ?> module(s) associé(s)</li>
                    <li>• <?= $nb_quizzes ?> quiz(s) associé(s)</li>
                    <li>• Toutes les leçons et questions liées</li>
                    <li>• Les fichiers uploadés (images, vidéos, PDF)</li>
                    <li>• L'historique de progression des étudiants</li>
                </ul>
            </div>

            <!-- Formulaire de confirmation -->
            <form method="POST">
                <div class="flex items-center mb-4 p-3 bg-gray-100 rounded-lg">
                    <input type="checkbox" id="confirm" name="confirm" required 
                           class="w-4 h-4 text-red-600 bg-white border-gray-300 rounded focus:ring-red-500">
                    <label for="confirm" class="ml-3 text-sm font-medium text-gray-900">
                        Je comprends que cette action est irréversible et je confirme la suppression
                    </label>
                </div>

                <div class="flex space-x-4">
                    <button type="submit" class="btn-red text-white px-6 py-3 rounded-lg font-semibold flex-1 flex items-center justify-center">
                        <i class="fas fa-trash mr-2"></i> Supprimer définitivement
                    </button>
                    <a href="cours.php" class="btn-teal text-white px-6 py-3 rounded-lg font-semibold flex-1 text-center">
                        <i class="fas fa-times mr-2"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>