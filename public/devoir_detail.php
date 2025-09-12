<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier que l'utilisateur est un étudiant
if (!isLoggedIn() || $_SESSION['role_id'] != 1) {
    header('Location: ../public/login.php');
    exit();
}

$etudiant_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

if ($quiz_id === 0) {
    die("ID de quiz non spécifié");
}

// Récupérer les informations du quiz
try {
    $stmt = $pdo->prepare("SELECT q.*, c.nom as cours_nom 
                          FROM quizz q 
                          JOIN cours c ON q.cours_id = c.id 
                          WHERE q.id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}

if (!$quiz) {
    header('Location: mes_cours.php');
    exit();
}

// Vérifier si l'étudiant est inscrit au cours
try {
    $stmt = $pdo->prepare("SELECT * FROM inscriptions WHERE cours_id = ? AND etudiant_id = ?");
    $stmt->execute([$quiz['cours_id'], $etudiant_id]);
    $inscription = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}

if (!$inscription) {
    header('Location: mes_cours.php');
    exit();
}

// Vérifier si le quiz est déjà complété
try {
    $stmt = $pdo->prepare("SELECT * FROM quiz_soumissions WHERE quiz_id = ? AND etudiant_id = ?");
    $stmt->execute([$quiz_id, $etudiant_id]);
    $soumission_existante = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}

// Récupérer les questions du quiz
try {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}

// Récupérer les réponses pour chaque question
foreach ($questions as $index => $question) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM reponses WHERE question_id = ?");
        $stmt->execute([$question['id']]);
        $reponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $questions[$index]['reponses'] = $reponses;
    } catch (PDOException $e) {
        die("Erreur de base de données: " . $e->getMessage());
    }
}

$message = '';
$error = '';

// Traitement de la soumission du quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$soumission_existante) {
    try {
        $score_total = 0;
        $points_obtenus = 0;
        
        // Enregistrer la soumission du quiz
        $stmt = $pdo->prepare("INSERT INTO quiz_soumissions (quiz_id, etudiant_id, date_soumission) VALUES (?, ?, NOW())");
        $stmt->execute([$quiz_id, $etudiant_id]);
        $soumission_id = $pdo->lastInsertId();
        
        // Traiter chaque réponse
        foreach ($questions as $question) {
            $points_question = $question['points'];
            $score_total += $points_question;
            
            if ($question['type_question'] === 'qcm' || $question['type_question'] === 'vrai_faux') {
                $reponse_etudiant = isset($_POST['question_'.$question['id']]) ? (int)$_POST['question_'.$question['id']] : null;
                
                if ($reponse_etudiant !== null) {
                    // Vérifier si la réponse est correcte
                    $stmt = $pdo->prepare("SELECT is_correct FROM reponses WHERE id = ? AND question_id = ?");
                    $stmt->execute([$reponse_etudiant, $question['id']]);
                    $reponse_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $est_correct = $reponse_data && $reponse_data['is_correct'] ? 1 : 0;
                    $points_question_obtenus = $est_correct ? $points_question : 0;
                    $points_obtenus += $points_question_obtenus;
                    
                    // Enregistrer la réponse
                    $stmt = $pdo->prepare("INSERT INTO reponses_etudiants 
                        (soumission_id, question_id, reponse_id, reponse_texte, points_obtenus) 
                        VALUES (?, ?, ?, NULL, ?)");
                    $stmt->execute([$soumission_id, $question['id'], $reponse_etudiant, $points_question_obtenus]);
                }
            } 
            elseif ($question['type_question'] === 'texte_libre') {
                $reponse_texte = isset($_POST['question_'.$question['id']]) ? trim($_POST['question_'.$question['id']]) : '';
                
                // Pour les réponses libres, on ne peut pas noter automatiquement
                $points_question_obtenus = 0;
                
                // Enregistrer la réponse
                $stmt = $pdo->prepare("INSERT INTO reponses_etudiants 
                    (soumission_id, question_id, reponse_id, reponse_texte, points_obtenus) 
                    VALUES (?, ?, NULL, ?, ?)");
                $stmt->execute([$soumission_id, $question['id'], $reponse_texte, $points_question_obtenus]);
            }
            elseif ($question['type_question'] === 'association') {
                // Traitement pour les questions d'association
                $points_question_obtenus = 0;
                $associations_correctes = 0;
                $total_associations = 0;
                
                foreach ($question['reponses'] as $reponse) {
                    $total_associations++;
                    $reponse_etudiant = isset($_POST['association_'.$reponse['id']]) ? (int)$_POST['association_'.$reponse['id']] : null;
                    
                    if ($reponse_etudiant !== null) {
                        // Vérifier si l'association est correcte
                        $stmt = $pdo->prepare("SELECT id FROM reponses WHERE id = ? AND matching_text = ?");
                        $stmt->execute([$reponse_etudiant, $reponse['matching_text']]);
                        $association_correcte = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($association_correcte) {
                            $associations_correctes++;
                        }
                        
                        // Enregistrer chaque association
                        $stmt = $pdo->prepare("INSERT INTO reponses_etudiants 
                            (soumission_id, question_id, reponse_id, reponse_texte, points_obtenus) 
                            VALUES (?, ?, ?, NULL, 0)");
                        $stmt->execute([$soumission_id, $question['id'], $reponse_etudiant]);
                    }
                }
                
                // Calculer les points pour les associations
                if ($total_associations > 0) {
                    $points_question_obtenus = ($associations_correctes / $total_associations) * $points_question;
                    $points_obtenus += $points_question_obtenus;
                    
                    // Mettre à jour le score pour cette question
                    $stmt = $pdo->prepare("UPDATE reponses_etudiants SET points_obtenus = ? 
                                          WHERE soumission_id = ? AND question_id = ?");
                    $stmt->execute([$points_question_obtenus, $soumission_id, $question['id']]);
                }
            }
            elseif ($question['type_question'] === 'texte_a_trous') {
                // Traitement pour les textes à trous
                $reponse_texte = isset($_POST['question_'.$question['id']]) ? trim($_POST['question_'.$question['id']]) : '';
                $points_question_obtenus = 0;
                
                // Enregistrer la réponse
                $stmt = $pdo->prepare("INSERT INTO reponses_etudiants 
                    (soumission_id, question_id, reponse_id, reponse_texte, points_obtenus) 
                    VALUES (?, ?, NULL, ?, ?)");
                $stmt->execute([$soumission_id, $question['id'], $reponse_texte, $points_question_obtenus]);
            }
        }
        
        // Mettre à jour le score total dans la soumission
        $score_pourcentage = $score_total > 0 ? ($points_obtenus / $score_total) * 100 : 0;
        $stmt = $pdo->prepare("UPDATE quiz_soumissions SET score = ?, points_obtenus = ?, points_total = ? WHERE id = ?");
        $stmt->execute([$score_pourcentage, $points_obtenus, $score_total, $soumission_id]);
        
        $message = "Quiz soumis avec succès! Votre score: " . round($score_pourcentage, 2) . "%";
        
        // Recharger la page pour afficher les résultats
        header("Location: devoir_detail.php?quiz_id=$quiz_id");
        exit();
        
    } catch (PDOException $e) {
        $error = "Erreur lors de la soumission: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($quiz['titre']); ?> - Espace Étudiant</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    }
    
    :root {
        --primary-color: #4F46E5;
        --secondary-color: #10B981;
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
        background: linear-gradient(135deg, #4338CA 0%, #0DA271 100%);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
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
</style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen">

<header class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <div class="bg-blue-100 p-2 rounded-lg">
                <i class="fas fa-user-graduate text-blue-600 text-xl"></i>
            </div>
            <h1 class="text-xl font-bold text-gray-800">Espace Étudiant</h1>
        </div>
        <nav class="flex items-center space-x-6">
            <a href="dashboard_etudiant.php" class="text-gray-600 hover:text-blue-500 transition-colors flex items-center">
                <i class="fas fa-tachometer-alt mr-2"></i> Tableau de bord
            </a>
            <a href="mes_cours.php" class="text-gray-600 hover:text-blue-500 transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Retour
            </a>
            <a href="../public/logout.php" class="text-gray-600 hover:text-blue-500 transition-colors flex items-center">
                <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
            </a>
        </nav>
    </div>
</header>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="card p-8 mb-8">
        <div class="flex items-center mb-6">
            <div class="bg-blue-100 p-3 rounded-lg mr-4">
                <i class="fas fa-task text-blue-600 text-2xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-800 section-title"><?php echo htmlspecialchars($quiz['titre']); ?></h2>
                <p class="text-gray-600">Cours: <?php echo htmlspecialchars($quiz['cours_nom']); ?></p>
                <p class="text-gray-600">Date limite: <?php echo date('d/m/Y', strtotime($quiz['date_limite'])); ?></p>
            </div>
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

        <?php if ($soumission_existante): ?>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-lg flex items-center">
                <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
                <span class="text-blue-700">Vous avez déjà complété ce quiz le <?php echo date('d/m/Y à H:i', strtotime($soumission_existante['date_soumission'])); ?>. Votre score: <?php echo round($soumission_existante['score'], 2); ?>%</span>
            </div>
        <?php endif; ?>

        <div class="prose max-w-none mb-6">
            <p><?php echo nl2br(htmlspecialchars($quiz['description'])); ?></p>
        </div>

        <?php if (!$soumission_existante): ?>
        <form method="POST" class="space-y-8">
        <?php endif; ?>
            
            <?php foreach ($questions as $index => $question): ?>
            <div class="border-l-4 border-blue-500 pl-4 py-2">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-lg font-semibold">Question #<?php echo $index + 1; ?></h3>
                    <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded">
                        <?php echo $question['points']; ?> point<?php echo $question['points'] > 1 ? 's' : ''; ?>
                    </span>
                </div>
                <p class="mb-4"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                
                <?php if ($question['type_question'] === 'qcm' || $question['type_question'] === 'vrai_faux'): ?>
                    <div class="space-y-2">
                        <?php foreach ($question['reponses'] as $reponse): ?>
                            <div class="flex items-center">
                                <input 
                                    type="radio" 
                                    id="reponse_<?php echo $reponse['id']; ?>" 
                                    name="question_<?php echo $question['id']; ?>" 
                                    value="<?php echo $reponse['id']; ?>" 
                                    class="mr-2" 
                                    <?php echo $soumission_existante ? 'disabled' : ''; ?>
                                    required
                                >
                                <label for="reponse_<?php echo $reponse['id']; ?>"><?php echo htmlspecialchars($reponse['reponse_text']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                
                <?php elseif ($question['type_question'] === 'texte_libre'): ?>
                    <textarea 
                        name="question_<?php echo $question['id']; ?>" 
                        rows="4" 
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Votre réponse..." 
                        <?php echo $soumission_existante ? 'disabled' : ''; ?>
                        required
                    ></textarea>
                
                <?php elseif ($question['type_question'] === 'association'): ?>
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div>
                            <h4 class="font-medium mb-2">Éléments</h4>
                            <ul class="space-y-2">
                                <?php foreach ($question['reponses'] as $reponse): ?>
                                    <li class="bg-gray-100 p-2 rounded"><?php echo htmlspecialchars($reponse['reponse_text']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-medium mb-2">Associations</h4>
                            <div class="space-y-2">
                                <?php 
                                // Mélanger les réponses pour l'association
                                $reponses_assoc = $question['reponses'];
                                shuffle($reponses_assoc);
                                ?>
                                <?php foreach ($question['reponses'] as $reponse): ?>
                                    <div class="flex items-center">
                                        <select 
                                            name="association_<?php echo $reponse['id']; ?>" 
                                            class="w-full p-2 border border-gray-300 rounded-lg"
                                            <?php echo $soumission_existante ? 'disabled' : ''; ?>
                                            required
                                        >
                                            <option value="">Choisir une association</option>
                                            <?php foreach ($reponses_assoc as $option): ?>
                                                <option value="<?php echo $option['id']; ?>"><?php echo htmlspecialchars($option['matching_text']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                
                <?php elseif ($question['type_question'] === 'texte_a_trous'): ?>
                    <textarea 
                        name="question_<?php echo $question['id']; ?>" 
                        rows="4" 
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Complétez le texte..." 
                        <?php echo $soumission_existante ? 'disabled' : ''; ?>
                        required
                    ></textarea>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <?php if (!$soumission_existante): ?>
            <div class="flex justify-end pt-4">
                <button type="submit" class="btn-primary text-white px-6 py-2">
                    <i class="fas fa-paper-plane mr-2"></i> Soumettre le quiz
                </button>
            </div>
            <?php endif; ?>
        
        <?php if (!$soumission_existante): ?>
        </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>