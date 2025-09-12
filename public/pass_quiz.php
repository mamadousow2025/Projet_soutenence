<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole(1); // étudiant

$student_id = $_SESSION['user_id'];

// Récupérer l'id du quiz
if(!isset($_GET['id'])) {
    die("Quiz introuvable.");
}
$quiz_id = (int)$_GET['id'];

// Vérifier que le quiz existe et est disponible
$stmt = $pdo->prepare("
    SELECT q.*, c.titre AS cours_titre
    FROM quizz q
    JOIN cours c ON q.course_id = c.id
    WHERE q.id = ? AND q.date_limite >= NOW()
");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$quiz) {
    die("Quiz introuvable ou expiré.");
}

// Récupérer les questions avec leurs réponses depuis la table 'reponses'
$stmt = $pdo->prepare("
    SELECT q.id AS question_id, q.question_text, q.type, r.id AS reponse_id, r.reponse_text
    FROM questions q
    LEFT JOIN reponses r ON q.id = r.question_id
    WHERE q.quizz_id = ?
    ORDER BY q.id ASC, r.id ASC
");
$stmt->execute([$quiz_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les données par question
$questions = [];
foreach($rows as $row) {
    $qid = $row['question_id'];
    if(!isset($questions[$qid])){
        $questions[$qid] = [
            'question_text' => $row['question_text'],
            'type' => $row['type'],
            'reponses' => []
        ];
    }
    if($row['reponse_id']){
        $questions[$qid]['reponses'][] = $row['reponse_text'];
    }
}

// Soumettre les réponses
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['reponse']) && is_array($_POST['reponse'])){
        foreach($_POST['reponse'] as $question_id => $value){
            if(is_array($value)){
                $value = implode(';', $value); // pour association multiple
            }
            $stmt = $pdo->prepare("
                INSERT INTO reponses_etudiants (etudiant_id, quiz_id, question_id, reponse)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE reponse = VALUES(reponse)
            ");
            $stmt->execute([$student_id, $quiz_id, $question_id, $value]);
        }
        echo "<script>alert('Quiz soumis avec succès !'); window.location.href='quiz_pass.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Passer le Quiz - <?php echo htmlspecialchars($quiz['titre']); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* Variables et réinitialisation */
    :root {
        --primary: #009688;
        --secondary: #FF9800;
        --primary-light: rgba(0, 150, 136, 0.1);
        --secondary-light: rgba(255, 152, 0, 0.1);
        --primary-dark: #00766c;
        --secondary-dark: #f57c00;
        --light: #f8f9fa;
        --dark: #212529;
        --gray: #6c757d;
        --light-gray: #e9ecef;
        --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        --hover-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        color: var(--dark);
        line-height: 1.6;
        min-height: 100vh;
    }
    
    /* Header */
    header {
        background: linear-gradient(120deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 1.2rem 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }
    
    header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transform: rotate(45deg);
        animation: shimmer 8s infinite linear;
    }
    
    @keyframes shimmer {
        0% { transform: translateX(-100%) rotate(45deg); }
        100% { transform: translateX(100%) rotate(45deg); }
    }
    
    .header-container {
        width: 90%;
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        z-index: 1;
    }
    
    .logo {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        font-weight: 600;
        font-size: 1.4rem;
    }
    
    .logo i {
        font-size: 1.6rem;
        color: var(--secondary);
    }
    
    .back-btn {
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.1);
    }
    
    .back-btn:hover {
        color: white;
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }
    
    /* Main content */
    .main-container {
        width: 90%;
        max-width: 800px;
        margin: 2rem auto;
        padding-bottom: 3rem;
    }
    
    .quiz-info {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--card-shadow);
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--gray);
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        background: var(--primary-light);
        border-radius: 50px;
    }
    
    .info-item i {
        color: var(--primary);
    }
    
    /* Question cards */
    .question-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--card-shadow);
        position: relative;
        overflow: hidden;
        border-left: 4px solid var(--primary);
    }
    
    .question-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--primary-light), var(--secondary-light));
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 0;
    }
    
    .question-card:hover::before {
        opacity: 0.05;
    }
    
    .question-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.5rem;
        position: relative;
        z-index: 1;
    }
    
    .question-number {
        background: var(--primary);
        color: white;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        flex-shrink: 0;
    }
    
    .question-text {
        font-weight: 500;
        font-size: 1.1rem;
        color: var(--dark);
        line-height: 1.5;
    }
    
    /* Answer options */
    .answer-options {
        position: relative;
        z-index: 1;
    }
    
    .option-label {
        display: flex;
        align-items: center;
        padding: 1rem;
        margin-bottom: 0.8rem;
        border: 1px solid var(--light-gray);
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .option-label:hover {
        border-color: var(--primary);
        background: var(--primary-light);
        transform: translateX(5px);
    }
    
    .option-label input[type="radio"] {
        margin-right: 1rem;
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    
    .option-text {
        flex: 1;
    }
    
    /* Text inputs */
    .text-input {
        width: 100%;
        padding: 1rem;
        border: 1px solid var(--light-gray);
        border-radius: 12px;
        font-family: 'Poppins', sans-serif;
        font-size: 1rem;
        transition: all 0.2s ease;
        margin-bottom: 0.8rem;
    }
    
    .text-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0, 150, 136, 0.2);
    }
    
    .association-item {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        gap: 1rem;
    }
    
    .association-key {
        flex: 1;
        background: var(--primary-light);
        padding: 0.8rem;
        border-radius: 8px;
        font-weight: 500;
    }
    
    .association-input {
        flex: 2;
    }
    
    /* Submit button */
    .submit-btn {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.8rem;
        width: 100%;
        padding: 1.2rem;
        background: linear-gradient(120deg, var(--primary), var(--secondary));
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .submit-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: 0.5s;
    }
    
    .submit-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 150, 136, 0.3);
    }
    
    .submit-btn:hover::before {
        left: 100%;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .header-container {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .quiz-info {
            flex-direction: column;
            gap: 0.8rem;
        }
        
        .question-header {
            flex-direction: column;
            gap: 0.8rem;
        }
        
        .association-item {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .association-key, .association-input {
            width: 100%;
        }
    }
    
    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .question-card {
        animation: fadeIn 0.5s ease-out forwards;
        opacity: 0;
    }
    
    .question-card:nth-child(1) { animation-delay: 0.1s; }
    .question-card:nth-child(2) { animation-delay: 0.2s; }
    .question-card:nth-child(3) { animation-delay: 0.3s; }
    .question-card:nth-child(4) { animation-delay: 0.4s; }
    .question-card:nth-child(5) { animation-delay: 0.5s; }
    .question-card:nth-child(6) { animation-delay: 0.6s; }
</style>
</head>
<body>
<header>
    <div class="header-container">
        <div class="logo">
            <i class="fas fa-book-reader"></i>
            <span><?php echo htmlspecialchars($quiz['titre']); ?></span>
        </div>
        <a href="quiz_pass.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</header>

<div class="main-container">
    <div class="quiz-info">
        <div class="info-item">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Cours : <?php echo htmlspecialchars($quiz['cours_titre']); ?></span>
        </div>
        <div class="info-item">
            <i class="fas fa-clock"></i>
            <span>Date limite : <?php echo date('d/m/Y H:i', strtotime($quiz['date_limite'])); ?></span>
        </div>
        <div class="info-item">
            <i class="fas fa-star"></i>
            <span>Points : <?php echo $quiz['points']; ?></span>
        </div>
    </div>

    <form method="POST">
        <?php $i = 1; foreach($questions as $qid => $q): ?>
        <div class="question-card">
            <div class="question-header">
                <div class="question-number"><?php echo $i++; ?></div>
                <div class="question-text"><?php echo htmlspecialchars($q['question_text']); ?></div>
            </div>

            <div class="answer-options">
                <?php if(!empty($q['reponses'])): ?>
                    <?php if($q['type'] == 'qcm' || $q['type'] == 'vrai_faux'): ?>
                        <?php foreach($q['reponses'] as $rep): ?>
                            <label class="option-label">
                                <input type="radio" name="reponse[<?php echo $qid; ?>]" value="<?php echo htmlspecialchars($rep); ?>">
                                <span class="option-text"><?php echo htmlspecialchars($rep); ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php elseif($q['type'] == 'association'): ?>
                        <p class="info-item" style="margin-bottom: 1.5rem;">
                            <i class="fas fa-link"></i>
                            <span>Associez les éléments correspondants</span>
                        </p>
                        <?php foreach($q['reponses'] as $rep): ?>
                            <div class="association-item">
                                <div class="association-key"><?php echo htmlspecialchars($rep); ?></div>
                                <div class="association-input">
                                    <input type="text" name="reponse[<?php echo $qid; ?>][]" class="text-input" placeholder="Votre réponse">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif($q['type'] == 'texte_libre' || $q['type'] == 'texte_a_trous'): ?>
                        <textarea name="reponse[<?php echo $qid; ?>]" rows="4" class="text-input" placeholder="Saisissez votre réponse ici..."></textarea>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <button type="submit" class="submit-btn">
            <i class="fas fa-paper-plane"></i>
            Soumettre le Quiz
        </button>
    </form>
</div>

</body>
</html>