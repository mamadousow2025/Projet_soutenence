<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier que l'utilisateur est un enseignant (role_id = 2) ou l'admin (role_id = 3)
if (!isLoggedIn() || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3)) {
    header('Location: ../public/login.php');
    exit();
}

$enseignant_id = $_SESSION['user_id'];
$dashboard_link = ($_SESSION['role_id'] == 3) ? 'admin_dashboard.php' : 'teacher_dashboard.php';

// Vérifier si l'ID du quiz est fourni
if (!isset($_GET['id'])) {
    header('Location: create_quiz.php');
    exit();
}

$quiz_id = $_GET['id'];

// Vérifier si le quiz existe - modification pour admin
if ($_SESSION['role_id'] == 3) {
    // Admin peut voir tous les quizzes
    $stmt = $pdo->prepare("SELECT q.*, c.titre as cours_titre 
                           FROM quizz q 
                           JOIN cours c ON q.course_id = c.id 
                           WHERE q.id = ?");
    $stmt->execute([$quiz_id]);
} else {
    // Enseignant ne peut voir que ses propres quizzes
    $stmt = $pdo->prepare("SELECT q.*, c.titre as cours_titre 
                           FROM quizz q 
                           JOIN cours c ON q.course_id = c.id 
                           WHERE q.id = ? AND q.enseignant_id = ?");
    $stmt->execute([$quiz_id, $enseignant_id]);
}

$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    header('Location: create_quiz.php');
    exit();
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Commencer une transaction pour assurer l'intégrité des données
        $pdo->beginTransaction();
        
        // Supprimer d'abord les réponses des étudiants liées à ce quiz
        $stmt = $pdo->prepare("DELETE r FROM reponses_etudiants r 
                              JOIN questions q ON r.question_id = q.id 
                              WHERE q.quiz_id = ?");
        $stmt->execute([$quiz_id]);
        
        // Supprimer les options des questions
        $stmt = $pdo->prepare("DELETE o FROM options_questions o 
                              JOIN questions q ON o.question_id = q.id 
                              WHERE q.quiz_id = ?");
        $stmt->execute([$quiz_id]);
        
        // Supprimer les questions
        $stmt = $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?");
        $stmt->execute([$quiz_id]);
        
        // Supprimer le quiz - modification pour admin
        if ($_SESSION['role_id'] == 3) {
            // Admin peut supprimer n'importe quel quiz
            $stmt = $pdo->prepare("DELETE FROM quizz WHERE id = ?");
            $stmt->execute([$quiz_id]);
        } else {
            // Enseignant ne peut supprimer que ses quizzes
            $stmt = $pdo->prepare("DELETE FROM quizz WHERE id = ? AND enseignant_id = ?");
            $stmt->execute([$quiz_id, $enseignant_id]);
        }
        
        $pdo->commit();
        
        $_SESSION['success_message'] = "Le quiz a été supprimé avec succès!";
        header('Location: quiz_devoir.php');
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Erreur lors de la suppression: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supprimer le Quiz - <?php echo $_SESSION['role_id'] == 3 ? 'Espace Admin' : 'Espace Enseignant'; ?></title>

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    }
    
    :root {
        --teal-color: #0D9488;
        --orange-color: #F59E0B;
        --red-color: #EF4444;
    }
    
    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }
    
    .btn-teal {
        background: linear-gradient(135deg, var(--teal-color) 0%, #14B8A6 100%);
        transition: all 0.3s;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
    }
    
    .btn-teal:hover {
        background: linear-gradient(135deg, #0F766E 0%, #0D9488 100%);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(13, 148, 136, 0.4);
    }
    
    .btn-orange {
        background: linear-gradient(135deg, var(--orange-color) 0%, #FB923C 100%);
        transition: all 0.3s;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
    }
    
    .btn-orange:hover {
        background: linear-gradient(135deg, #D97706 0%, #EA580C 100%);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.4);
    }
    
    .btn-danger {
        background: linear-gradient(135deg, var(--red-color) 0%, #F87171 100%);
        transition: all 0.3s;
        border-radius: 8px;
        padding: 12px 24px;
        font-weight: 600;
    }
    
    .btn-danger:hover {
        background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.4);
    }
    
    .btn-secondary {
        background: #6b7280;
        transition: all 0.3s;
        border-radius: 8px;
        padding: 12px 24px;
        font-weight: 600;
    }
    
    .btn-secondary:hover {
        background: #4b5563;
        transform: translateY(-2px);
    }
    
    .admin-badge {
        background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
        color: white;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
</style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen">

<header class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <div class="bg-blue-100 p-2 rounded-lg">
                <i class="fas fa-chalkboard-teacher text-blue-600 text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800">Espace <?php echo $_SESSION['role_id'] == 3 ? 'Administrateur' : 'Enseignant'; ?></h1>
                <p class="text-sm text-gray-600">Suppression du quiz</p>
            </div>
        </div>
        
        <!-- Indicateur de rôle -->
        <?php if ($_SESSION['role_id'] == 3): ?>
        <div class="admin-badge px-3 py-1 rounded-full text-sm font-medium shadow-lg">
            <i class="fas fa-shield-alt mr-1"></i> Mode Administrateur
        </div>
        <?php endif; ?>
        
        <nav class="flex items-center space-x-4">
            <a href="<?= $dashboard_link ?>" class="btn-teal text-white transition-colors flex items-center">
                <i class="fas fa-tachometer-alt mr-2"></i> Tableau de bord
            </a>
            <a href="quiz_devoir.php" class="btn-orange text-white transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Retour aux quiz
            </a>
        </nav>
    </div>
</header>

<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="card p-8">
        <div class="text-center mb-6">
            <div class="bg-red-100 p-4 rounded-full inline-flex items-center justify-center mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Confirmer la suppression</h2>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
                <span class="text-red-700"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <!-- Indicateur de propriété pour l'admin -->
        <?php if ($_SESSION['role_id'] == 3 && isset($quiz['enseignant_id'])): ?>
        <div class="bg-purple-100 border border-purple-200 rounded-lg p-3 mb-4">
            <p class="text-purple-800 text-sm text-center">
                <i class="fas fa-user-shield mr-1"></i>
                Quiz créé par l'enseignant ID: <?php echo $quiz['enseignant_id']; ?>
            </p>
        </div>
        <?php endif; ?>

        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 rounded-lg">
            <div class="flex items-start">
                <i class="fas fa-exclamation-circle text-yellow-500 text-xl mr-3 mt-1"></i>
                <div>
                    <h3 class="font-semibold text-yellow-800">Attention!</h3>
                    <p class="text-yellow-700 mt-1">
                        Vous êtes sur le point de supprimer définitivement le quiz "<strong><?php echo htmlspecialchars($quiz['titre']); ?></strong>".
                        Cette action supprimera également toutes les questions associées et les réponses des étudiants.
                        <strong>Cette action est irréversible.</strong>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h4 class="font-semibold text-gray-800 mb-2">Détails du quiz à supprimer:</h4>
            <div class="space-y-2 text-sm text-gray-600">
                <div class="flex justify-between">
                    <span>Titre:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($quiz['titre']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Cours:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($quiz['cours_titre']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Type:</span>
                    <span class="font-medium">
                        <?php 
                        switch($quiz['type_quiz']) {
                            case 'qcm': echo 'QCM'; break;
                            case 'vrai_faux': echo 'Vrai/Faux'; break;
                            case 'texte_libre': echo 'Réponse libre'; break;
                            case 'association': echo 'Association'; break;
                            case 'texte_a_trous': echo 'Texte à trous'; break;
                            default: echo 'QCM';
                        }
                        ?>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Date limite:</span>
                    <span class="font-medium"><?php echo date('d/m/Y H:i', strtotime($quiz['date_limite'])); ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Points:</span>
                    <span class="font-medium"><?php echo $quiz['points']; ?></span>
                </div>
                <?php if ($_SESSION['role_id'] == 3): ?>
                <div class="flex justify-between">
                    <span>Enseignant:</span>
                    <span class="font-medium">ID <?php echo $quiz['enseignant_id']; ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" class="space-y-4">
            <div class="flex items-center mb-4">
                <input type="checkbox" id="confirm" name="confirm" required 
                       class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500">
                <label for="confirm" class="ml-2 text-sm font-medium text-gray-900">
                    Je confirme vouloir supprimer définitivement ce quiz
                </label>
            </div>

            <div class="flex space-x-4 pt-4">
                <button type="submit" class="btn-danger text-white flex-1 flex items-center justify-center">
                    <i class="fas fa-trash mr-2"></i> Supprimer définitivement
                </button>
                <a href="quiz_devoir.php" class="btn-secondary text-white flex-1 flex items-center justify-center text-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

</body>
</html>