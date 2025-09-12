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
$question_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$type_quiz = isset($_GET['type']) ? $_GET['type'] : '';

// Vérifier que la question appartient à un quiz de l'enseignant
// Vérifier que la question appartient à un quiz de l'enseignant
// Vérifier que la question appartient à un quiz de l'enseignant
$stmt = $pdo->prepare("
    SELECT q.*, z.titre AS quiz_titre
    FROM questions q
    JOIN quizz z ON q.quizz_id = z.id
    WHERE q.id = ? AND z.enseignant_id = ?
");
$stmt->execute([$question_id, $enseignant_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);




if (!$question) {
    header("Location: add_questions.php?quiz_id=$quiz_id&type=$type_quiz");
    exit();
}

// Récupérer les réponses existantes
$stmt = $pdo->prepare("SELECT * FROM reponses WHERE question_id = ?");
$stmt->execute([$question_id]);
$reponses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$error = '';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = trim($_POST['question_text']);
    $points = isset($_POST['points']) ? (int)$_POST['points'] : 1;
    
    // Validation basique
    if (empty($question_text)) {
        $error = "Le texte de la question est obligatoire.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Mettre à jour la question
            $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, points = ? WHERE id = ?");
            $stmt->execute([$question_text, $points, $question_id]);
            
            // Supprimer les anciennes réponses
            $stmt = $pdo->prepare("DELETE FROM reponses WHERE question_id = ?");
            $stmt->execute([$question_id]);
            
            // Ajouter les nouvelles réponses selon le type de quiz
            if ($type_quiz === 'qcm' || $type_quiz === 'vrai_faux') {
                $reponses = $_POST['reponses'];
                $correct_index = (int)$_POST['correct_answer'];
                
                foreach ($reponses as $index => $reponse_text) {
                    if (!empty($reponse_text)) {
                        $is_correct = ($index === $correct_index) ? 1 : 0;
                        $stmt = $pdo->prepare("INSERT INTO reponses 
                            (question_id, reponse_text, is_correct) 
                            VALUES (?, ?, ?)");
                        $stmt->execute([$question_id, $reponse_text, $is_correct]);
                    }
                }
            } elseif ($type_quiz === 'texte_libre') {
                // Pour les questions à réponse libre
                $reponse_model = trim($_POST['reponse_model']);
                if (!empty($reponse_model)) {
                    $stmt = $pdo->prepare("INSERT INTO reponses 
                        (question_id, reponse_text, is_correct) 
                        VALUES (?, ?, ?)");
                    $stmt->execute([$question_id, $reponse_model, 1]);
                }
            } elseif ($type_quiz === 'association') {
                $elements_gauche = $_POST['elements_gauche'];
                $elements_droite = $_POST['elements_droite'];
                
                foreach ($elements_gauche as $index => $gauche) {
                    if (!empty($gauche) && !empty($elements_droite[$index])) {
                        $stmt = $pdo->prepare("INSERT INTO reponses 
                            (question_id, reponse_text, matching_text) 
                            VALUES (?, ?, ?)");
                        $stmt->execute([$question_id, $gauche, $elements_droite[$index]]);
                    }
                }
            } elseif ($type_quiz === 'texte_a_trous') {
                $texte_complet = trim($_POST['texte_complet']);
                $mots_manquants = $_POST['mots_manquants'];
                
                if (!empty($texte_complet)) {
                    $stmt = $pdo->prepare("INSERT INTO reponses 
                        (question_id, reponse_text, is_correct) 
                        VALUES (?, ?, ?)");
                    $stmt->execute([$question_id, $texte_complet, 1]);
                    
                    // Stocker les mots manquants
                    foreach ($mots_manquants as $mot) {
                        if (!empty($mot)) {
                            $stmt = $pdo->prepare("INSERT INTO reponses 
                                (question_id, reponse_text, is_correct) 
                                VALUES (?, ?, ?)");
                            $stmt->execute([$question_id, $mot, 0]);
                        }
                    }
                }
            }
            
            $pdo->commit();
            $message = "Question modifiée avec succès!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la modification: " . $e->getMessage();
        }
    }
}

// Récupérer à nouveau les données après modification
$stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->execute([$question_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM reponses WHERE question_id = ?");
$stmt->execute([$question_id]);
$reponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modifier une Question - Espace Enseignant</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    }
    
    :root {
        --primary-color: #009688;
        --secondary-color: #FF9800;
    }
    
    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
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
            <a href="add_questions.php?quiz_id=<?php echo $quiz_id; ?>&type=<?php echo $type_quiz; ?>" 
               class="text-gray-600 hover:text-blue-500 transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Retour
            </a>
            <a href="../public/logout.php" class="text-gray-600 hover:text-blue-500 transition-colors flex items-center">
                <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
            </a>
        </nav>
    </div>
</header>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="card p-8">
        <div class="flex items-center mb-6">
            <div class="bg-blue-100 p-3 rounded-lg mr-4">
                <i class="fas fa-edit text-blue-600 text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Modifier la Question</h2>
        </div>
        
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

        <!-- Formulaire de modification -->
        <form method="POST" class="space-y-6">
            <div>
                <label for="question_text" class="block text-sm font-medium text-gray-700 mb-2">
                    Question <span class="text-red-500">*</span>
                </label>
                <textarea id="question_text" name="question_text" rows="3" required
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Entrez votre question ici..."><?php echo htmlspecialchars($question['question_text']); ?></textarea>
            </div>
            
            <div>
                <label for="points" class="block text-sm font-medium text-gray-700 mb-2">
                    Points pour cette question
                </label>
                <input type="number" id="points" name="points" min="1" max="10" value="<?php echo $question['points']; ?>"
                    class="p-2 border border-gray-300 rounded-lg">
            </div>
            
            <!-- Contenu spécifique au type de question -->
            <?php if ($type_quiz === 'qcm'): ?>
                <div id="qcm-section">
                    <h3 class="text-lg font-semibold mb-4">Options de réponse (QCM)</h3>
                    <div class="space-y-3">
                        <?php 
                        $correct_index = 0;
                        foreach ($reponses as $index => $reponse): 
                            if ($reponse['is_correct'] == 1) $correct_index = $index;
                        ?>
                            <div class="flex items-center">
                                <input type="radio" name="correct_answer" value="<?php echo $index; ?>" 
                                    <?php echo $reponse['is_correct'] == 1 ? 'checked' : ''; ?> class="mr-3">
                                <input type="text" name="reponses[]" value="<?php echo htmlspecialchars($reponse['reponse_text']); ?>"
                                    class="flex-1 p-2 border border-gray-300 rounded-lg" 
                                    placeholder="Option de réponse">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="add-option" class="mt-3 text-blue-500 hover:text-blue-700">
                        <i class="fas fa-plus-circle mr-1"></i> Ajouter une option
                    </button>
                </div>
                
            <?php elseif ($type_quiz === 'vrai_faux'): ?>
                <div id="vf-section">
                    <h3 class="text-lg font-semibold mb-4">Réponse correcte</h3>
                    <div class="flex space-x-4">
                        <label class="flex items-center">
                            <input type="radio" name="correct_answer" value="0" 
                                <?php echo ($reponses[0]['is_correct'] ?? 0) == 1 ? 'checked' : ''; ?> class="mr-2">
                            <span>Vrai</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="correct_answer" value="1" 
                                <?php echo ($reponses[1]['is_correct'] ?? 0) == 1 ? 'checked' : ''; ?> class="mr-2">
                            <span>Faux</span>
                        </label>
                    </div>
                    <!-- Champs cachés pour les options Vrai/Faux -->
                    <input type="hidden" name="reponses[]" value="Vrai">
                    <input type="hidden" name="reponses[]" value="Faux">
                </div>
                
            <?php elseif ($type_quiz === 'texte_libre'): ?>
                <div id="texte-libre-section">
                    <h3 class="text-lg font-semibold mb-4">Réponse modèle (optionnel)</h3>
                    <textarea name="reponse_model" rows="3"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Vous pouvez fournir une réponse modèle pour aider à la correction..."><?php 
                        echo !empty($reponses) ? htmlspecialchars($reponses[0]['reponse_text']) : ''; 
                    ?></textarea>
                </div>
                
            <?php elseif ($type_quiz === 'association'): ?>
                <div id="association-section">
                    <h3 class="text-lg font-semibold mb-4">Éléments à associer</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium mb-2">Colonne de gauche</h4>
                            <div class="space-y-2">
                                <?php foreach ($reponses as $index => $reponse): ?>
                                    <input type="text" name="elements_gauche[]" 
                                        value="<?php echo htmlspecialchars($reponse['reponse_text']); ?>"
                                        class="w-full p-2 border border-gray-300 rounded-lg" 
                                        placeholder="Élément">
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium mb-2">Colonne de droite</h4>
                            <div class="space-y-2">
                                <?php foreach ($reponses as $index => $reponse): ?>
                                    <input type="text" name="elements_droite[]" 
                                        value="<?php echo htmlspecialchars($reponse['matching_text']); ?>"
                                        class="w-full p-2 border border-gray-300 rounded-lg" 
                                        placeholder="Élément associé">
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="add-association" class="mt-3 text-blue-500 hover:text-blue-700">
                        <i class="fas fa-plus-circle mr-1"></i> Ajouter une paire
                    </button>
                </div>
                
            <?php elseif ($type_quiz === 'texte_a_trous'): ?>
                <div id="texte-trous-section">
                    <h3 class="text-lg font-semibold mb-4">Texte complet avec mots manquants</h3>
                    <?php 
                    $texte_complet = '';
                    $mots_manquants = [];
                    
                    foreach ($reponses as $reponse) {
                        if ($reponse['is_correct'] == 1) {
                            $texte_complet = $reponse['reponse_text'];
                        } else {
                            $mots_manquants[] = $reponse['reponse_text'];
                        }
                    }
                    ?>
                    <textarea name="texte_complet" rows="4" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Entrez le texte complet avec les mots manquants marqués entre crochets [...]. Ex: La [capital] de la France est Paris."><?php 
                        echo htmlspecialchars($texte_complet); 
                    ?></textarea>
                    
                    <div class="mt-4">
                        <h4 class="font-medium mb-2">Mots manquants</h4>
                        <div id="mots-manquants-container" class="space-y-2">
                            <?php foreach ($mots_manquants as $index => $mot): ?>
                                <div class="flex items-center">
                                    <span class="w-32">Mot manquant <?php echo $index + 1; ?>:</span>
                                    <input type="text" name="mots_manquants[]" value="<?php echo htmlspecialchars($mot); ?>" 
                                        class="flex-1 p-2 border border-gray-300 rounded-lg">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="flex space-x-4 pt-4">
                <button type="submit" class="btn-primary text-white px-6 py-2">
                    <i class="fas fa-save mr-2"></i> Enregistrer les modifications
                </button>
                <a href="add_questions.php?quiz_id=<?php echo $quiz_id; ?>&type=<?php echo $type_quiz; ?>" 
                   class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400">
                    <i class="fas fa-times mr-2"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Script pour gérer l'ajout dynamique d'options (QCM)
document.addEventListener('DOMContentLoaded', function() {
    const addOptionBtn = document.getElementById('add-option');
    if (addOptionBtn) {
        addOptionBtn.addEventListener('click', function() {
            const optionsContainer = this.previousElementSibling;
            const optionCount = optionsContainer.children.length;
            const newOption = document.createElement('div');
            newOption.className = 'flex items-center mt-2';
            newOption.innerHTML = `
                <input type="radio" name="correct_answer" value="${optionCount}" class="mr-3">
                <input type="text" name="reponses[]" class="flex-1 p-2 border border-gray-300 rounded-lg" placeholder="Nouvelle option">
                <button type="button" class="ml-2 text-red-500 remove-option">
                    <i class="fas fa-times"></i>
                </button>
            `;
            optionsContainer.appendChild(newOption);
            
            // Ajouter l'événement pour supprimer l'option
            newOption.querySelector('.remove-option').addEventListener('click', function() {
                newOption.remove();
            });
        });
    }
    
    // Script pour l'ajout de paires d'association
    const addAssociationBtn = document.getElementById('add-association');
    if (addAssociationBtn) {
        addAssociationBtn.addEventListener('click', function() {
            const leftContainer = document.querySelector('input[name="elements_gauche[]"]').parentNode;
            const rightContainer = document.querySelector('input[name="elements_droite[]"]').parentNode;
            
            const newLeft = document.createElement('input');
            newLeft.type = 'text';
            newLeft.name = 'elements_gauche[]';
            newLeft.className = 'w-full p-2 border border-gray-300 rounded-lg mt-2';
            newLeft.placeholder = 'Nouvel élément';
            
            const newRight = document.createElement('input');
            newRight.type = 'text';
            newRight.name = 'elements_droite[]';
            newRight.className = 'w-full p-2 border border-gray-300 rounded-lg mt-2';
            newRight.placeholder = 'Élément associé';
            
            leftContainer.appendChild(newLeft);
            rightContainer.appendChild(newRight);
        });
    }
    
    // Script pour extraire les mots manquants du texte à trous
    const texteComplet = document.querySelector('textarea[name="texte_complet"]');
    if (texteComplet) {
        texteComplet.addEventListener('blur', function() {
            const text = this.value;
            const motsManquants = text.match(/\[(.*?)\]/g);
            const container = document.getElementById('mots-manquants-container');
            
            if (container) {
                container.innerHTML = '';
                
                if (motsManquants) {
                    motsManquants.forEach((mot, index) => {
                        // Enlever les crochets
                        const motPropre = mot.replace('[', '').replace(']', '');
                        
                        const div = document.createElement('div');
                        div.className = 'flex items-center';
                        div.innerHTML = `
                            <span class="w-32">Mot manquant ${index + 1}:</span>
                            <input type="text" name="mots_manquants[]" value="${motPropre}" 
                                class="flex-1 p-2 border border-gray-300 rounded-lg">
                        `;
                        container.appendChild(div);
                    });
                }
            }
        });
    }
});
</script>

</body>
</html>