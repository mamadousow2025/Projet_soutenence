<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier que l'utilisateur est un enseignant
if (!isLoggedIn() || $_SESSION['role_id'] != 2) {
    header('Location: ../public/login.php');
    exit();
}

$enseignant_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Récupérer les cours enseignés par cet enseignant
$cours_enseignes = [];
$stmt = $pdo->prepare("SELECT c.id, c.titre, f.nom as filiere_nom 
                       FROM cours c 
                       JOIN filieres f ON c.filiere_id = f.id 
                       WHERE c.enseignant_id = ? 
                       ORDER BY c.titre");
$stmt->execute([$enseignant_id]);
$cours_enseignes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $course_id = $_POST['course_id'];
    $date_limite = $_POST['date_limite'];
    $points = isset($_POST['points']) ? (int)$_POST['points'] : 0;
    $type_quiz = isset($_POST['type_quiz']) ? $_POST['type_quiz'] : 'qcm'; // Valeur par défaut
    $duree = isset($_POST['duree']) ? (int)$_POST['duree'] : 30; // Valeur par défaut

    // Validation
    if (empty($titre) || empty($course_id) || empty($date_limite) || empty($type_quiz)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            // Insertion d'un quiz
            $stmt = $pdo->prepare("INSERT INTO quizz 
                (titre, description, course_id, date_limite, points, enseignant_id, type_quiz, duree) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titre, $description, $course_id, $date_limite, $points, $enseignant_id, $type_quiz, $duree]);
            $quiz_id = $pdo->lastInsertId();
            
            // Redirection vers l'ajout de questions selon le type de quiz
            if ($quiz_id) {
                header("Location: add_questions.php?quiz_id=$quiz_id&type=$type_quiz");
                exit();
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de la création: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Créer un Quiz - Espace Enseignant</title>

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
    
    .animated-tab {
        transition: all 0.3s ease;
        border-radius: 8px;
        padding: 12px 24px;
        font-weight: 500;
    }
    
    .animated-tab.active {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(0, 150, 136, 0.3);
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
</style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen">

<header class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <div class="bg-blue-100 p-2 rounded-lg">
                <i class="fas fa-chalkboard-teacher text-blue-600 text-xl"></i>
            </div>
            <h1 class="text-xl font-bold text-gray-800">Espace Enseignant</h1>
        </div>
        <nav class="flex items-center space-x-6">
            <a href="teacher_dashboard.php" class="text-gray-600 hover:text-blue-500 transition-colors flex items-center">
                <i class="fas fa-tachometer-alt mr-2"></i> Tableau de bord
            </a>
            <a href="../public/logout.php" class="text-gray-600 hover:text-blue-500 transition-colors flex items-center">
                <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
            </a>
        </nav>
    </div>
</header>

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <div class="card p-8 mb-8">
        <div class="flex items-center mb-6">
            <div class="bg-blue-100 p-3 rounded-lg mr-4">
                <i class="fas fa-question-circle text-blue-600 text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 section-title">Créer un nouveau Quiz</h2>
        </div>
        
        <p class="text-gray-600 mb-8">Remplissez le formulaire ci-dessous pour créer une nouvelle évaluation quiz.</p>

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

        <!-- Formulaire -->
        <form id="creation-form" method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="titre" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-heading text-blue-500 mr-2 text-sm"></i> Titre <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input type="text" id="titre" name="titre" required 
                           class="form-input w-full"
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
                            <option value="<?php echo $cours['id']; ?>">
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
                          placeholder="Décrivez le quiz..."></textarea>
            </div>

            <!-- Nouvelle section: Type de quiz -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-poll text-blue-500 mr-2 text-sm"></i> Type de Quiz <span class="text-red-500 ml-1">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <label class="cursor-pointer">
                        <input type="radio" name="type_quiz" value="qcm" class="quiz-type-input" checked required>
                        <div class="quiz-type-card relative bg-white p-5 rounded-lg shadow-md text-center">
                            <div class="checkmark"><i class="fas fa-check text-xs"></i></div>
                            <div class="text-3xl text-blue-500 mb-3"><i class="fas fa-list-ol"></i></div>
                            <h3 class="font-semibold mb-2">Questions à Choix Multiples</h3>
                            <p class="text-sm text-gray-600 mb-3">Plusieurs réponses possibles, une seule correcte</p>
                            <span class="badge badge-mcq">QCM</span>
                        </div>
                    </label>
                    
                    <label class="cursor-pointer">
                        <input type="radio" name="type_quiz" value="vrai_faux" class="quiz-type-input">
                        <div class="quiz-type-card relative bg-white p-5 rounded-lg shadow-md text-center">
                            <div class="checkmark"><i class="fas fa-check text-xs"></i></div>
                            <div class="text-3xl text-green-500 mb-3"><i class="fas fa-check-circle"></i></div>
                            <h3 class="font-semibold mb-2">Vrai ou Faux</h3>
                            <p class="text-sm text-gray-600 mb-3">Questions avec seulement deux options</p>
                            <span class="badge badge-tf">Vrai/Faux</span>
                        </div>
                    </label>
                    
                    <label class="cursor-pointer">
                        <input type="radio" name="type_quiz" value="texte_libre" class="quiz-type-input">
                        <div class="quiz-type-card relative bg-white p-5 rounded-lg shadow-md text-center">
                            <div class="checkmark"><i class="fas fa-check text-xs"></i></div>
                            <div class="text-3xl text-purple-500 mb-3"><i class="fas fa-align-left"></i></div>
                            <h3 class="font-semibold mb-2">Réponse Libre</h3>
                            <p class="text-sm text-gray-600 mb-3">L'étudiant rédige sa réponse</p>
                            <span class="badge badge-essay">Essai</span>
                        </div>
                    </label>
                    
                    <label class="cursor-pointer">
                        <input type="radio" name="type_quiz" value="association" class="quiz-type-input">
                        <div class="quiz-type-card relative bg-white p-5 rounded-lg shadow-md text-center">
                            <div class="checkmark"><i class="fas fa-check text-xs"></i></div>
                            <div class="text-3xl text-teal-500 mb-3"><i class="fas fa-project-diagram"></i></div>
                            <h3 class="font-semibold mb-2">Questions d'Association</h3>
                            <p class="text-sm text-gray-600 mb-3">Relier les éléments des deux colonnes</p>
                            <span class="badge badge-matching">Association</span>
                        </div>
                    </label>
                    
                    <label class="cursor-pointer">
                        <input type="radio" name="type_quiz" value="texte_a_trous" class="quiz-type-input">
                        <div class="quiz-type-card relative bg-white p-5 rounded-lg shadow-md text-center">
                            <div class="checkmark"><i class="fas fa-check text-xs"></i></div>
                            <div class="text-3xl text-orange-500 mb-3"><i class="fas fa-tasks"></i></div>
                            <h3 class="font-semibold mb-2">Texte à Trou</h3>
                            <p class="text-sm text-gray-600 mb-3">Compléter les parties manquantes d'un texte</p>
                            <span class="badge badge-fill">Remplissage</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="date_limite" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-calendar-alt text-blue-500 mr-2 text-sm"></i> Date limite <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input type="datetime-local" id="date_limite" name="date_limite" required
                           class="form-input w-full">
                </div>

                <div>
                    <label for="points" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-star text-blue-500 mr-2 text-sm"></i> Points <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input type="number" id="points" name="points" min="1" max="100" value="20" required
                           class="form-input w-full">
                </div>
            </div>
            
            <div>
                <label for="duree" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-clock text-blue-500 mr-2 text-sm"></i> Durée (en minutes)
                </label>
                <input type="number" id="duree" name="duree" min="1" max="240" value="30"
                       class="form-input w-full" placeholder="Durée du quiz en minutes">
            </div>

            <div class="pt-6 border-t border-gray-100">
                <button type="submit" class="btn-primary text-white w-full md:w-auto flex items-center justify-center">
                    <i class="fas fa-arrow-right mr-2"></i> Continuer vers les questions
                </button>
            </div>
        </form>
    </div>
    
    <!-- Section des quiz existants -->
    <div class="card p-8">
        <div class="flex items-center mb-6">
            <div class="bg-blue-100 p-3 rounded-lg mr-4">
                <i class="fas fa-list text-blue-600 text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 section-title">Mes Quiz Existants</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cours</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date limite</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    // Récupérer les quiz existants
                    $stmt = $pdo->prepare("SELECT q.*, c.titre as cours_titre 
                                           FROM quizz q 
                                           JOIN cours c ON q.course_id = c.id 
                                           WHERE q.enseignant_id = ? 
                                           ORDER BY q.created_at DESC");
                    $stmt->execute([$enseignant_id]);
                    $quiz_existants = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($quiz_existants) > 0):
                        foreach ($quiz_existants as $quiz): 
                            $date_limite = new DateTime($quiz['date_limite']);
                            $badge_class = '';
                            switch($quiz['type_quiz']) {
                                case 'qcm': $badge_class = 'badge-mcq'; break;
                                case 'vrai_faux': $badge_class = 'badge-tf'; break;
                                case 'texte_libre': $badge_class = 'badge-essay'; break;
                                case 'association': $badge_class = 'badge-matching'; break;
                                case 'texte_a_trous': $badge_class = 'badge-fill'; break;
                                default: $badge_class = 'badge-mcq';
                            }
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($quiz['titre']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php 
                                switch($quiz['type_quiz']) {
                                    case 'qcm': echo 'QCM'; break;
                                    case 'vrai_faux': echo 'V/F'; break;
                                    case 'texte_libre': echo 'Essai'; break;
                                    case 'association': echo 'Association'; break;
                                    case 'texte_a_trous': echo 'Trous'; break;
                                    default: echo 'QCM';
                                }
                                ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($quiz['cours_titre']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $date_limite->format('d/m/Y H:i'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $quiz['points']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="edit_quiz.php?id=<?php echo $quiz['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-edit"></i></a>
                            <a href="add_questions.php?quiz_id=<?php echo $quiz['id']; ?>&type=<?php echo $quiz['type_quiz']; ?>" class="text-green-600 hover:text-green-900 mr-3" title="Ajouter des questions"><i class="fas fa-plus-circle"></i></a>
                            <a href="delete_quiz.php?id=<?php echo $quiz['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce quiz?');"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-2"></i> Aucun quiz créé pour le moment
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Min datetime
    const now = new Date();
    const minDateTime = now.toISOString().slice(0,16);
    document.getElementById('date_limite').min = minDateTime;
    
    // Sélection automatique du premier type de quiz
    document.querySelector('input[name="type_quiz"]').checked = true;
    document.querySelector('input[name="type_quiz"]').closest('label').querySelector('.quiz-type-card').classList.add('selected');
});

// Gestion de la sélection des types de quiz
document.querySelectorAll('input[name="type_quiz"]').forEach(input => {
    input.addEventListener('change', function() {
        document.querySelectorAll('.quiz-type-card').forEach(card => {
            card.classList.remove('selected');
        });
        this.closest('label').querySelector('.quiz-type-card').classList.add('selected');
    });
});
</script>

</body>
</html>