<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier que l'utilisateur est un enseignant (role_id = 2) ou l'admin (role_id = 3)
if (!isLoggedIn() || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3)) {
    header('Location: ../public/login.php');
    exit();
}

// Déterminer le lien du tableau de bord selon le rôle
if ($_SESSION['role_id'] == 3) { // Admin
    $dashboard_link = 'admin_dashboard.php';
} else { // Enseignant
    $dashboard_link = 'teacher_dashboard.php';
}

$enseignant_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Vérifier si l'ID du quiz est fourni
if (!isset($_GET['id'])) {
    header('Location: create_quiz.php');
    exit();
}

$quiz_id = $_GET['id'];

// Récupérer les données du quiz - modification pour admin
if ($_SESSION['role_id'] == 3) {
    // Admin peut modifier tous les quizzes
    $stmt = $pdo->prepare("SELECT q.*, c.titre as cours_titre 
                           FROM quizz q 
                           JOIN cours c ON q.course_id = c.id 
                           WHERE q.id = ?");
    $stmt->execute([$quiz_id]);
} else {
    // Enseignant ne peut modifier que ses propres quizzes
    $stmt = $pdo->prepare("SELECT q.*, c.titre as cours_titre 
                           FROM quizz q 
                           JOIN cours c ON q.course_id = c.id 
                           WHERE q.id = ? AND q.enseignant_id = ?");
    $stmt->execute([$quiz_id, $enseignant_id]);
}

$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier si le quiz existe
if (!$quiz) {
    header('Location: create_quiz.php');
    exit();
}

// Récupérer les cours - modification pour admin
if ($_SESSION['role_id'] == 3) {
    // Admin peut voir tous les cours
    $stmt = $pdo->prepare("SELECT c.id, c.titre, f.nom as filiere_nom 
                           FROM cours c 
                           JOIN filieres f ON c.filiere_id = f.id 
                           ORDER BY c.titre");
    $stmt->execute();
} else {
    // Enseignant ne voit que ses cours
    $stmt = $pdo->prepare("SELECT c.id, c.titre, f.nom as filiere_nom 
                           FROM cours c 
                           JOIN filieres f ON c.filiere_id = f.id 
                           WHERE c.enseignant_id = ? 
                           ORDER BY c.titre");
    $stmt->execute([$enseignant_id]);
}

$cours_enseignes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $course_id = $_POST['course_id'];
    $date_limite = $_POST['date_limite'];
    $points = isset($_POST['points']) ? (int)$_POST['points'] : 0;
    $type_quiz = isset($_POST['type_quiz']) ? $_POST['type_quiz'] : 'qcm';
    $duree = isset($_POST['duree']) ? (int)$_POST['duree'] : 30;

    // Validation
    if (empty($titre) || empty($course_id) || empty($date_limite) || empty($type_quiz)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            // Mise à jour du quiz - modification pour admin
            if ($_SESSION['role_id'] == 3) {
                // Admin peut modifier n'importe quel quiz
                $stmt = $pdo->prepare("UPDATE quizz 
                                      SET titre = ?, description = ?, course_id = ?, 
                                          date_limite = ?, points = ?, type_quiz = ?, duree = ?
                                      WHERE id = ?");
                $stmt->execute([$titre, $description, $course_id, $date_limite, $points, $type_quiz, $duree, $quiz_id]);
            } else {
                // Enseignant ne peut modifier que ses quizzes
                $stmt = $pdo->prepare("UPDATE quizz 
                                      SET titre = ?, description = ?, course_id = ?, 
                                          date_limite = ?, points = ?, type_quiz = ?, duree = ?
                                      WHERE id = ? AND enseignant_id = ?");
                $stmt->execute([$titre, $description, $course_id, $date_limite, $points, $type_quiz, $duree, $quiz_id, $enseignant_id]);
            }
            
            $message = "Quiz modifié avec succès!";
            
            // Recharger les données du quiz
            if ($_SESSION['role_id'] == 3) {
                $stmt = $pdo->prepare("SELECT q.*, c.titre as cours_titre 
                                       FROM quizz q 
                                       JOIN cours c ON q.course_id = c.id 
                                       WHERE q.id = ?");
                $stmt->execute([$quiz_id]);
            } else {
                $stmt = $pdo->prepare("SELECT q.*, c.titre as cours_titre 
                                       FROM quizz q 
                                       JOIN cours c ON q.course_id = c.id 
                                       WHERE q.id = ? AND q.enseignant_id = ?");
                $stmt->execute([$quiz_id, $enseignant_id]);
            }
            
            $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $error = "Erreur lors de la modification: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modifier le Quiz - <?php echo $_SESSION['role_id'] == 3 ? 'Espace Admin' : 'Espace Enseignant'; ?></title>

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    }
    
    :root {
        --primary-color: #009688;
        --secondary-color: #FF9800;
        --primary-light: rgba(0, 150, 136, 0.1);
        --secondary-light: rgba(255, 152, 0, 0.1);
        --teal-color: #0D9488;
        --orange-color: #F59E0B;
        --red-color: #EF4444;
    }
    
    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.15);
    }
    
    .form-input {
        transition: all 0.3s;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 16px;
    }
    
    .form-input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(0, 150, 136, 0.2);
        outline: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        transition: all 0.3s;
        border-radius: 8px;
        padding: 12px 24px;
        font-weight: 600;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #00766c 0%, #e65100 100%);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 150, 136, 0.4);
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
    
    .btn-red {
        background: linear-gradient(135deg, var(--red-color) 0%, #F87171 100%);
        transition: all 0.3s;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
    }
    
    .btn-red:hover {
        background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.4);
    }
    
    .section-title {
        position: relative;
        padding-left: 16px;
    }
    
    .section-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        height: 24px;
        width: 4px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        border-radius: 2px;
    }
    
    .quiz-type-card {
        border: 2px solid transparent;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .quiz-type-card:hover, .quiz-type-card.selected {
        border-color: var(--primary-color);
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -10px rgba(0, 150, 136, 0.2);
    }
    
    .quiz-type-card.selected .checkmark {
        display: block;
    }
    
    .checkmark {
        display: none;
        position: absolute;
        top: -10px;
        right: -10px;
        background: var(--primary-color);
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        text-align: center;
        line-height: 24px;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .badge-mcq {
        background-color: var(--primary-light);
        color: var(--primary-color);
    }
    
    .badge-tf {
        background-color: #e3f2fd;
        color: #1976d2;
    }
    
    .badge-essay {
        background-color: #f3e5f5;
        color: #7b1fa2;
    }
    
    .badge-matching {
        background-color: #e8f5e9;
        color: #388e3c;
    }
    
    .badge-fill {
        background-color: #fff3e0;
        color: #f57c00;
    }
    
    .quiz-type-input {
        position: absolute;
        opacity: 0;
    }
    
    .quiz-type-input:checked + .quiz-type-card {
        border-color: var(--primary-color);
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -10px rgba(0, 150, 136, 0.2);
    }
    
    .quiz-type-input:checked + .quiz-type-card .checkmark {
        display: block;
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
    
    .role-indicator {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 10;
    }
</style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen">

<header class="bg-white shadow-sm relative">
    <div class="container mx-auto px-4 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <div class="bg-blue-100 p-2 rounded-lg">
                <i class="fas fa-chalkboard-teacher text-blue-600 text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800">Espace <?php echo $_SESSION['role_id'] == 3 ? 'Administrateur' : 'Enseignant'; ?></h1>
                <p class="text-sm text-gray-600">Modification du quiz</p>
            </div>
        </div>
        
        <!-- Indicateur de rôle -->
        <?php if ($_SESSION['role_id'] == 3): ?>
        <div class="role-indicator">
           
     
            </span>
        </div>
        <?php endif; ?>
        
        <nav class="flex items-center space-x-4">
    <a href="<?= $dashboard_link ?>" class="btn-teal text-white transition-colors flex items-center">
        <i class="fas fa-tachometer-alt mr-2"></i> Tableau de bord
    </a>
    <a href="quiz_devoir.php" class="btn-orange text-white transition-colors flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Retour aux quiz
    </a>
    <a href="../public/logout.php" class="btn-red text-white transition-colors flex items-center">
        <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
    </a>
</nav>
    </div>
</header>

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <div class="card p-8 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <div class="bg-blue-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-edit text-blue-600 text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 section-title">Modifier le Quiz</h2>
                    <p class="text-gray-600 mt-1">ID du quiz: #<?php echo $quiz_id; ?></p>
                </div>
            </div>
            
            <!-- Indicateur de propriété pour l'admin -->
            <?php if ($_SESSION['role_id'] == 3 && isset($quiz['enseignant_id'])): ?>
            <div class="bg-purple-100 border border-purple-200 rounded-lg px-4 py-2">
                <p class="text-purple-800 text-sm">
                    <i class="fas fa-user-shield mr-1"></i>
                    Quiz créé par l'enseignant ID: <?php echo $quiz['enseignant_id']; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <p class="text-gray-600 mb-8">Modifiez les informations du quiz ci-dessous.</p>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg flex items-center">
                <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                <span class="text-green-700"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
                <span class="text-red-700"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <!-- Informations sur le quiz actuel -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-blue-800 mb-2 flex items-center">
                <i class="fas fa-info-circle mr-2"></i> Informations actuelles du quiz
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-medium text-blue-700">Titre actuel:</span>
                    <span class="text-blue-900"><?php echo htmlspecialchars($quiz['titre']); ?></span>
                </div>
                <div>
                    <span class="font-medium text-blue-700">Cours associé:</span>
                    <span class="text-blue-900"><?php echo htmlspecialchars($quiz['cours_titre']); ?></span>
                </div>
                <div>
                    <span class="font-medium text-blue-700">Type de quiz:</span>
                    <span class="text-blue-900">
                        <?php 
                        switch($quiz['type_quiz']) {
                            case 'qcm': echo 'QCM'; break;
                            case 'vrai_faux': echo 'Vrai/Faux'; break;
                            case 'texte_libre': echo 'Réponse Libre'; break;
                            case 'association': echo 'Association'; break;
                            case 'texte_a_trous': echo 'Texte à Trou'; break;
                            default: echo ucfirst($quiz['type_quiz']);
                        }
                        ?>
                    </span>
                </div>
                <div>
                    <span class="font-medium text-blue-700">Date limite:</span>
                    <span class="text-blue-900"><?php echo date('d/m/Y H:i', strtotime($quiz['date_limite'])); ?></span>
                </div>
            </div>
        </div>

        <!-- Formulaire -->
        <form id="edit-form" method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="titre" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-heading text-blue-500 mr-2 text-sm"></i> Titre <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input type="text" id="titre" name="titre" required 
                           class="form-input w-full"
                           value="<?php echo htmlspecialchars($quiz['titre']); ?>"
                           placeholder="Ex: Quiz sur les fonctions...">
                </div>

                <div>
                    <label for="course_id" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-book text-blue-500 mr-2 text-sm"></i> Cours associé <span class="text-red-500 ml-1">*</span>
                    </label>
                    <select id="course_id" name="course_id" required
                            class="form-input w-full">
                        <option value="">Sélectionnez un cours</option>
                        <?php foreach ($cours_enseignes as $cours): ?>
                            <option value="<?php echo $cours['id']; ?>" 
                                <?php echo $cours['id'] == $quiz['course_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cours['titre']) . ' - ' . htmlspecialchars($cours['filiere_nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-align-left text-blue-500 mr-2 text-sm"></i> Description
                </label>
                <textarea id="description" name="description" rows="3"
                          class="form-input w-full"
                          placeholder="Décrivez le quiz..."><?php echo htmlspecialchars($quiz['description']); ?></textarea>
            </div>

            <!-- Section: Type de quiz -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-poll text-blue-500 mr-2 text-sm"></i> Type de Quiz <span class="text-red-500 ml-1">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php
                    $types_quiz = [
                        'qcm' => ['icon' => 'fas fa-list-ol', 'color' => 'blue', 'label' => 'QCM'],
                        'vrai_faux' => ['icon' => 'fas fa-check-circle', 'color' => 'green', 'label' => 'V/F'],
                        'texte_libre' => ['icon' => 'fas fa-align-left', 'color' => 'purple', 'label' => 'Essai'],
                        'association' => ['icon' => 'fas fa-project-diagram', 'color' => 'teal', 'label' => 'Association'],
                        'texte_a_trous' => ['icon' => 'fas fa-tasks', 'color' => 'orange', 'label' => 'Trous']
                    ];
                    
                    foreach ($types_quiz as $type => $details):
                        $is_selected = $quiz['type_quiz'] == $type;
                        $badge_class = 'badge-' . ($type == 'qcm' ? 'mcq' : ($type == 'vrai_faux' ? 'tf' : ($type == 'texte_libre' ? 'essay' : ($type == 'association' ? 'matching' : 'fill'))));
                    ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="type_quiz" value="<?php echo $type; ?>" 
                               class="quiz-type-input" <?php echo $is_selected ? 'checked' : ''; ?> required>
                        <div class="quiz-type-card relative bg-white p-5 rounded-lg shadow-md text-center <?php echo $is_selected ? 'selected' : ''; ?>">
                            <div class="checkmark"><i class="fas fa-check text-xs"></i></div>
                            <div class="text-3xl text-<?php echo $details['color']; ?>-500 mb-3">
                                <i class="<?php echo $details['icon']; ?>"></i>
                            </div>
                            <h3 class="font-semibold mb-2">
                                <?php 
                                switch($type) {
                                    case 'qcm': echo 'Questions à Choix Multiples'; break;
                                    case 'vrai_faux': echo 'Vrai ou Faux'; break;
                                    case 'texte_libre': echo 'Réponse Libre'; break;
                                    case 'association': echo 'Questions d\'Association'; break;
                                    case 'texte_a_trous': echo 'Texte à Trou'; break;
                                }
                                ?>
                            </h3>
                            <p class="text-sm text-gray-600 mb-3">
                                <?php 
                                switch($type) {
                                    case 'qcm': echo 'Plusieurs réponses possibles, une seule correcte'; break;
                                    case 'vrai_faux': echo 'Questions avec seulement deux options'; break;
                                    case 'texte_libre': echo 'L\'étudiant rédige sa réponse'; break;
                                    case 'association': echo 'Relier les éléments des deux colonnes'; break;
                                    case 'texte_a_trous': echo 'Compléter les parties manquantes d\'un texte'; break;
                                }
                                ?>
                            </p>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $details['label']; ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="date_limite" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-calendar-alt text-blue-500 mr-2 text-sm"></i> Date limite <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input type="datetime-local" id="date_limite" name="date_limite" required
                           class="form-input w-full"
                           value="<?php echo date('Y-m-d\TH:i', strtotime($quiz['date_limite'])); ?>">
                </div>

                <div>
                    <label for="points" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-star text-blue-500 mr-2 text-sm"></i> Points <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input type="number" id="points" name="points" min="1" max="100" required
                           class="form-input w-full"
                           value="<?php echo $quiz['points']; ?>">
                </div>
            </div>
            
            <div>
                <label for="duree" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-clock text-blue-500 mr-2 text-sm"></i> Durée (en minutes)
                </label>
                <input type="number" id="duree" name="duree" min="1" max="240"
                       class="form-input w-full" 
                       value="<?php echo $quiz['duree'] ? $quiz['duree'] : 30; ?>"
                       placeholder="Durée du quiz en minutes">
            </div>

            <div class="pt-6 border-t border-gray-100 flex space-x-4">
                <button type="submit" class="btn-primary text-white flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> Enregistrer les modifications
                </button>
                <a href="add_questions.php?quiz_id=<?php echo $quiz_id; ?>&type=<?php echo $quiz['type_quiz']; ?>" 
                   class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition-colors flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i> Gérer les questions
                </a>
                <a href="create_quiz.php" 
                   class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors flex items-center">
                    <i class="fas fa-times mr-2"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Min datetime
    const now = new Date();
    const minDateTime = now.toISOString().slice(0,16);
    document.getElementById('date_limite').min = minDateTime;
    
    // Gestion de la sélection des types de quiz
    document.querySelectorAll('input[name="type_quiz"]').forEach(input => {
        input.addEventListener('change', function() {
            document.querySelectorAll('.quiz-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            this.closest('label').querySelector('.quiz-type-card').classList.add('selected');
        });
    });
    
    // Afficher un message de confirmation si des modifications sont détectées
    const form = document.getElementById('edit-form');
    const initialFormData = new FormData(form);
    
    form.addEventListener('submit', function(e) {
        const currentFormData = new FormData(form);
        let hasChanges = false;
        
        for (let [key, value] of initialFormData.entries()) {
            if (currentFormData.get(key) !== value) {
                hasChanges = true;
                break;
            }
        }
        
        if (!hasChanges) {
            e.preventDefault();
            alert('Aucune modification détectée.');
        }
    });
});
</script>

</body>
</html>