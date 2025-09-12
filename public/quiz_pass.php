<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier que l'utilisateur est connecté et est un étudiant
requireRole(1); // 1 = rôle étudiant
$student_id = $_SESSION['user_id'];
$student_name = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);

// Récupérer la filière de l'étudiant
$stmt = $pdo->prepare("SELECT filiere_id FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$etudiant) {
    die("Filière introuvable pour cet étudiant.");
}

$filiere_id = $etudiant['filiere_id'];

// Récupérer les quiz disponibles pour la filière
$now = date('Y-m-d H:i:s');
$stmt = $pdo->prepare("
    SELECT q.id, q.titre, q.type_quiz, q.points, q.date_limite, c.titre AS cours_titre
    FROM quizz q
    JOIN cours c ON q.course_id = c.id
    WHERE c.filiere_id = ? 
      AND q.date_limite >= ?
    ORDER BY q.date_limite ASC
");
$stmt->execute([$filiere_id, $now]);
$quizs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quiz Disponibles - Espace Étudiant</title>
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
        display: flex;
        flex-direction: column;
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
    
    nav a {
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
    
    nav a:hover {
        color: white;
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }
    
    /* Main content */
    .main-container {
        width: 90%;
        max-width: 1200px;
        margin: 2rem auto;
        flex: 1;
    }
    
    .page-title {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        margin-bottom: 2rem;
        color: var(--dark);
        font-weight: 600;
        font-size: 1.8rem;
        position: relative;
        padding-bottom: 0.5rem;
    }
    
    .page-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 2px;
    }
    
    .page-title i {
        color: var(--primary);
        background: var(--primary-light);
        padding: 0.8rem;
        border-radius: 12px;
    }
    
    /* Quiz grid */
    .quiz-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.8rem;
        margin-bottom: 3rem;
    }
    
    /* Quiz card */
    .quiz-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
        border-top: 4px solid var(--primary);
    }
    
    .quiz-card::before {
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
    
    .quiz-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--hover-shadow);
    }
    
    .quiz-card:hover::before {
        opacity: 1;
    }
    
    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--light-gray);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        position: relative;
        z-index: 1;
    }
    
    .quiz-title {
        font-weight: 600;
        font-size: 1.2rem;
        color: var(--dark);
        margin-bottom: 0.5rem;
    }
    
    .quiz-type {
        display: inline-flex;
        align-items: center;
        padding: 0.4rem 0.8rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 500;
        gap: 0.4rem;
    }
    
    .type-mcq { background-color: rgba(0, 150, 136, 0.15); color: var(--primary-dark); }
    .type-tf { background-color: rgba(255, 152, 0, 0.15); color: var(--secondary-dark); }
    .type-essay { background-color: rgba(0, 150, 136, 0.2); color: var(--primary-dark); }
    .type-matching { background-color: rgba(255, 152, 0, 0.2); color: var(--secondary-dark); }
    .type-fill { background-color: rgba(0, 150, 136, 0.25); color: var(--primary-dark); }
    
    .card-body {
        padding: 1.5rem;
        flex-grow: 1;
        position: relative;
        z-index: 1;
    }
    
    .quiz-info {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
        margin-bottom: 1.5rem;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        color: var(--gray);
        font-size: 0.9rem;
    }
    
    .info-item i {
        width: 20px;
        color: var(--primary);
    }
    
    .deadline-warning {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.8rem;
        background: rgba(255, 152, 0, 0.1);
        border-radius: 8px;
        margin-top: 1rem;
        font-size: 0.85rem;
        color: var(--secondary-dark);
        border-left: 3px solid var(--secondary);
    }
    
    .card-footer {
        padding: 0 1.5rem 1.5rem;
        position: relative;
        z-index: 1;
    }
    
    .quiz-btn {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.6rem;
        width: 100%;
        padding: 0.9rem;
        background: linear-gradient(120deg, var(--primary), var(--secondary));
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .quiz-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: 0.5s;
    }
    
    .quiz-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 150, 136, 0.3);
    }
    
    .quiz-btn:hover::before {
        left: 100%;
    }
    
    /* No quiz message */
    .no-quiz {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        margin: 2rem 0;
        position: relative;
        overflow: hidden;
    }
    
    .no-quiz::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }
    
    .no-quiz i {
        font-size: 4rem;
        color: var(--light-gray);
        margin-bottom: 1.5rem;
    }
    
    .no-quiz p {
        font-size: 1.2rem;
        color: var(--gray);
        margin-bottom: 2rem;
    }
    
    /* Footer */
    footer {
        background: white;
        padding: 1.5rem 0;
        text-align: center;
        color: var(--gray);
        margin-top: auto;
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
        position: relative;
    }
    
    footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }
    
    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .quiz-card {
        animation: fadeIn 0.5s ease-out forwards;
        opacity: 0;
    }
    
    .quiz-card:nth-child(1) { animation-delay: 0.1s; }
    .quiz-card:nth-child(2) { animation-delay: 0.2s; }
    .quiz-card:nth-child(3) { animation-delay: 0.3s; }
    .quiz-card:nth-child(4) { animation-delay: 0.4s; }
    .quiz-card:nth-child(5) { animation-delay: 0.5s; }
    .quiz-card:nth-child(6) { animation-delay: 0.6s; }
    
    /* Progress bar for time left */
    .time-progress {
        height: 5px;
        background: var(--light-gray);
        border-radius: 3px;
        margin-top: 0.8rem;
        overflow: hidden;
    }
    
    .time-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 3px;
        transition: width 0.5s ease;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .quiz-grid {
            grid-template-columns: 1fr;
        }
        
        .header-container {
            flex-direction: column;
            gap: 1rem;
        }
        
        .page-title {
            font-size: 1.5rem;
        }
    }
</style>
</head>
<body>
   
<header>
    <div class="header-container" style="display:flex; justify-content:space-between; align-items:center; padding:10px 20px;">
        <!-- Logo + Nom Étudiant -->
        <div class="logo" style="display:flex; align-items:center; gap:10px;">
            <i class="fas fa-user-graduate"></i>
            <span>Espace Étudiant - <?php echo $student_name; ?></span>
        </div>

        <!-- Navigation -->
        <nav style="display:flex; align-items:center; gap:15px;">
            <a href="etudiant_dashboard.php" style="display:flex; align-items:center;">
                <i class="fas fa-tachometer-alt" style="margin-right:5px;"></i> Tableau de bord
            </a>
            <a href="../public/logout.php" style="display:flex; align-items:center;">
                <i class="fas fa-sign-out-alt" style="margin-right:5px;"></i> Déconnexion
            </a>
        </nav>
    </div>
</header>


<div class="main-container">
    <h1 class="page-title">
        <i class="fas fa-tasks"></i>
        <span>Quiz Disponibles</span>
    </h1>

    <?php if(count($quizs) > 0): ?>
    <div class="quiz-grid">
        <?php foreach($quizs as $index => $quiz): 
            $badge_class = '';
            $type_text = '';
            switch($quiz['type_quiz']) {
                case 'qcm': 
                    $badge_class = 'type-mcq';
                    $type_text = 'QCM';
                    $type_icon = 'fa-list-ol';
                    break;
                case 'vrai_faux': 
                    $badge_class = 'type-tf';
                    $type_text = 'Vrai/Faux';
                    $type_icon = 'fa-check-double';
                    break;
                case 'texte_libre': 
                    $badge_class = 'type-essay';
                    $type_text = 'Essai';
                    $type_icon = 'fa-font';
                    break;
                case 'association': 
                    $badge_class = 'type-matching';
                    $type_text = 'Association';
                    $type_icon = 'fa-object-group';
                    break;
                case 'texte_a_trous': 
                    $badge_class = 'type-fill';
                    $type_text = 'Texte à trous';
                    $type_icon = 'fa-edit';
                    break;
                default: 
                    $badge_class = 'type-mcq';
                    $type_text = 'QCM';
                    $type_icon = 'fa-list-ol';
            }
            
            // Vérifier si la date limite est proche (moins de 24h)
            $date_limite = new DateTime($quiz['date_limite']);
            $now = new DateTime();
            $interval = $now->diff($date_limite);
            $hours_left = ($interval->days * 24) + $interval->h;
            $is_urgent = $hours_left < 24;
            
            // Calcul du pourcentage de temps restant (pour la barre de progression)
            $total_hours = 72; // On considère que le quiz est disponible pendant 72h max
            $time_percentage = min(100, ($hours_left / $total_hours) * 100);
        ?>
        <div class="quiz-card">
            <div class="card-header">
                <h3 class="quiz-title"><?php echo htmlspecialchars($quiz['titre']); ?></h3>
                <span class="quiz-type <?php echo $badge_class; ?>">
                    <i class="fas <?php echo $type_icon; ?>"></i>
                    <?php echo $type_text; ?>
                </span>
            </div>
            
            <div class="card-body">
                <div class="quiz-info">
                    <div class="info-item">
                        <i class="fas fa-book"></i>
                        <span>Cours : <?php echo htmlspecialchars($quiz['cours_titre']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-star"></i>
                        <span>Points : <?php echo $quiz['points']; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span>Date limite : <?php echo date('d/m/Y H:i', strtotime($quiz['date_limite'])); ?></span>
                    </div>
                </div>
                
                <!-- Barre de progression du temps restant -->
                <div class="time-progress">
                    <div class="time-progress-bar" style="width: <?php echo $time_percentage; ?>%"></div>
                </div>
                
                <?php if($is_urgent): ?>
                <div class="deadline-warning">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Délai approchant! Il reste <?php echo $hours_left; ?> heure(s)</span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="card-footer">
                <a href="pass_quiz.php?id=<?php echo $quiz['id']; ?>" class="quiz-btn">
                    <i class="fas fa-play-circle"></i>
                    Commencer le quiz
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="no-quiz">
            <i class="fas fa-inbox"></i>
            <p>Aucun quiz disponible pour le moment.</p>
            <p>Revenez plus tard pour découvrir de nouveaux défis!</p>
        </div>
    <?php endif; ?>
</div>

<footer>
    <div class="footer-container">
        &copy; <?php echo date('Y'); ?> Plateforme E-learning ISEP Thiès. Tous droits réservés.
    </div>
</footer>

</body>
</html>