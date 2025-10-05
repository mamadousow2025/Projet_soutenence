<?php 
session_start();

// Connexion à la base
require_once __DIR__ . '/../config/database.php';

// Authentification
require_once __DIR__ . '/../includes/auth.php';

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Déterminer quelle section afficher par défaut
$defaultSection = 'dashboard';
$sections = ['dashboard', 'users', 'courses', 'projects', 'quizzes', 'messagerie'];

if (isset($_GET['section']) && in_array($_GET['section'], $sections)) {
    $defaultSection = $_GET['section'];
}

// =================== RECHERCHE ===================
$searchTerm = '';
$searchResults = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    
    // Recherche dans les utilisateurs
    $searchStmt = $pdo->prepare("
        SELECT u.id, u.nom, u.prenom, u.email, r.role_name AS role, 'user' as type
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE CONCAT(u.nom, ' ', u.prenom) LIKE :search 
           OR u.nom LIKE :search 
           OR u.prenom LIKE :search 
           OR u.email LIKE :search
           OR r.role_name LIKE :search
        ORDER BY u.id DESC
    ");
    $searchTermLike = '%' . $searchTerm . '%';
    $searchStmt->bindParam(':search', $searchTermLike);
    $searchStmt->execute();
    $searchResults = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Connexion à la base de données (exemple avec PDO)
try {
    $pdo = new PDO("mysql:host=localhost;dbname=lms_isep;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// =================== TRAITEMENT DES MESSAGES ===================
// Traitement de l'envoi de message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $sujet = trim($_POST['sujet']);
    $contenu = trim($_POST['contenu']);
    $destinataire_id = (int)$_POST['destinataire_id'];
    $expediteur_id = 3; // ID de l'admin corrigé
    
    if (!empty($sujet) && !empty($contenu) && $destinataire_id > 0) {
        $stmt = $pdo->prepare("INSERT INTO messages (expediteur_id, destinataire_id, sujet, contenu, date_envoi, lu) VALUES (?, ?, ?, ?, NOW(), 0)");
        $stmt->execute([$expediteur_id, $destinataire_id, $sujet, $contenu]);
        $success_message = "Message envoyé avec succès !";
    } else {
        $error_message = "Veuillez remplir tous les champs.";
    }
}

// Marquer un message comme lu
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $message_id = (int)$_GET['mark_read'];
    $stmt = $pdo->prepare("UPDATE messages SET lu = 1 WHERE id = ? AND destinataire_id = 3");
    $stmt->execute([$message_id]);
}

// Récupérer les messages reçus pour l'administrateur
$messages_recus = $pdo->query("SELECT m.id, m.sujet, m.contenu, m.date_envoi, m.lu,
    u.nom AS expediteur_nom, u.prenom AS expediteur_prenom
    FROM messages m
    JOIN users u ON u.id = m.expediteur_id
    WHERE m.destinataire_id = 3
    ORDER BY m.date_envoi DESC");

// Récupérer les messages envoyés par l'administrateur
$messages_envoyes = $pdo->query("SELECT m.id, m.sujet, m.contenu, m.date_envoi,
    u.nom AS destinataire_nom, u.prenom AS destinataire_prenom
    FROM messages m
    JOIN users u ON u.id = m.destinataire_id
    WHERE m.expediteur_id = 3
    ORDER BY m.date_envoi DESC");

// Récupérer tous les utilisateurs pour la liste des destinataires
$destinataires = $pdo->query("SELECT id, nom, prenom, role_id FROM users WHERE id != 3");

// =================== STATISTIQUES GLOBALES ===================
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM cours")->fetchColumn();
$totalProjets = $pdo->query("SELECT COUNT(*) FROM projets")->fetchColumn();
$totalQuizzes = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();

// Comptage par rôles
$totalEtudiants = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'etudiant'")->fetchColumn();
$totalEnseignants = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'enseignant'")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'admin'")->fetchColumn();

// Statistiques mensuelles pour le graphique d'évolution
$monthlyUsers = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
    ORDER BY month
")->fetchAll(PDO::FETCH_ASSOC);

// Activités récentes - CORRIGÉ pour utiliser le bon nom de table
$recentActivities = $pdo->query("
    (SELECT 'user' as type, CONCAT('Nouvel utilisateur: ', nom, ' ', prenom) as description, created_at as date FROM users ORDER BY created_at DESC LIMIT 5)
    UNION 
    (SELECT 'course' as type, CONCAT('Nouveau cours: ', titre) as description, created_at as date FROM cours ORDER BY created_at DESC LIMIT 5)
    UNION
    (SELECT 'projet' as type, CONCAT('Projet soumis: ', titre) as description, date_creation as date FROM projets ORDER BY date_creation DESC LIMIT 5)
    ORDER BY date DESC LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// =================== LISTE UTILISATEURS ===================
$usersStmt = $pdo->query("
    SELECT u.id, u.nom, u.prenom, u.email, r.role_name AS role
    FROM users u
    JOIN roles r ON u.role_id = r.id
    ORDER BY u.id DESC
");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// =================== LISTE DES COURS ===================
$coursesStmt = $pdo->query("
    SELECT c.id, c.titre AS title, f.nom AS filiere, c.status
    FROM cours c
    JOIN filieres f ON c.filiere_id = f.id
    ORDER BY c.created_at DESC
");
$courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

// =================== LISTE DES COURS EN ATTENTE ===================
$pendingCoursesStmt = $pdo->query("
    SELECT c.id, c.titre AS title, f.nom AS filiere, c.status
    FROM cours c
    JOIN filieres f ON c.filiere_id = f.id
    WHERE c.status = 'pending'
    ORDER BY c.created_at DESC
");
$pendingCourses = $pendingCoursesStmt->fetchAll(PDO::FETCH_ASSOC);

// =================== LISTE DES PROJETS - CORRIGÉ ===================
$projetsStmt = $pdo->query("
    SELECT p.id, p.titre, CONCAT(u.nom, ' ', u.prenom) AS enseignant, p.statut, p.date_creation
    FROM projets p
    JOIN users u ON p.enseignant_id = u.id
    ORDER BY p.date_creation DESC
");
$projects = $projetsStmt->fetchAll(PDO::FETCH_ASSOC);

// =================== LISTE DES QUIZZES ===================
$quizzStmt = $pdo->query("
    SELECT q.id, q.titre AS title, c.titre AS course, 
           (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) AS questions_count,
           q.status
    FROM quizz q
    JOIN cours c ON q.course_id = c.id
    ORDER BY q.id DESC
");
$quizzes = $quizzStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Admin</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ========== RESET & BASE ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            min-height: 100vh;
        }

        /* ========== SIDEBAR ========== */
        .sidebar {
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #009688 0%, #00695c 100%);
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 25px 20px;
            box-shadow: 4px 0 20px rgba(0, 150, 136, 0.15);
            z-index: 1000;
        }
        
        .sidebar h2 {
            font-size: 24px;
            margin-bottom: 40px;
            text-align: center;
            font-weight: 700;
            letter-spacing: 1px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        .menu a {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.9);
            padding: 15px 18px;
            margin-bottom: 8px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .menu a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .menu a:hover::before {
            left: 100%;
        }
        
        .menu a:hover,
        .menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .menu a i {
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        
        .logout {
            text-align: center;
            margin-top: 20px;
        }
        
        .logout a {
            display: block;
            background: linear-gradient(135deg, #FF9800 0%, #f57c00 100%);
            padding: 15px;
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
        }
        
        .logout a:hover {
            background: linear-gradient(135deg, #f57c00 0%, #e65100 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
        }

        /* ========== MAIN CONTENT ========== */
        .main {
            margin-left: 280px;
            padding: 30px 40px;
            width: calc(100% - 280px);
            min-height: 100vh;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: rgba(255, 255, 255, 0.9);
            padding: 25px 30px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .header h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #009688 0%, #FF9800 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
        
        .header .user {
            font-size: 16px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 500;
        }

        /* ========== STATS CARDS ========== */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #009688 0%, #FF9800 100%);
        }
        
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .card i {
            font-size: 40px;
            background: linear-gradient(135deg, #009688 0%, #FF9800 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
        }
        
        .card h3 {
            font-size: 16px;
            color: #777;
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .card p {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .card .trend {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 14px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .card .trend.up {
            background: linear-gradient(135deg, #4caf50 0%, #8bc34a 100%);
            color: #fff;
        }
        
        .card .trend.down {
            background: linear-gradient(135deg, #f44336 0%, #e91e63 100%);
            color: #fff;
        }

        /* ========== SECTION TITLES ========== */
        h2.section-title {
            font-size: 28px;
            margin: 40px 0 25px;
            background: linear-gradient(135deg, #009688 0%, #FF9800 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            position: relative;
            padding-left: 20px;
        }
        
        h2.section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 40px;
            background: linear-gradient(180deg, #009688 0%, #FF9800 100%);
            border-radius: 3px;
        }

        /* ========== TABLES ========== */
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 16px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 50px;
            backdrop-filter: blur(10px);
        }
        
        th, td {
            padding: 18px 20px;
            text-align: left;
        }
        
        th {
            background: linear-gradient(135deg, #009688 0%, #00695c 100%);
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tr:nth-child(even) {
            background: rgba(0, 150, 136, 0.05);
        }
        
        tr:hover {
            background: rgba(255, 152, 0, 0.1);
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        /* ========== BUTTONS ========== */
        .button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 14px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        
        .add {
            background: linear-gradient(135deg, #FF9800 0%, #f57c00 100%);
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
        }
        
        .add:hover {
            background: linear-gradient(135deg, #f57c00 0%, #e65100 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
        }
        
        .edit {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        }
        
        .edit:hover {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            transform: translateY(-2px);
        }
        
        .success {
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        .success:hover {
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            transform: translateY(-2px);
        }
        
        .danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
        }
        
        .danger:hover {
            background: linear-gradient(135deg, #d32f2f 0%, #c62828 100%);
            transform: translateY(-2px);
        }

        /* ========== SEARCH BAR ========== */
        .search-container {
            margin-bottom: 40px;
            display: flex;
            justify-content: flex-end;
        }
        
        .search-form {
            display: flex;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        
        .search-input {
            flex: 1;
            padding: 16px 20px;
            border: none;
            font-size: 16px;
            outline: none;
            background: transparent;
            color: #333;
        }
        
        .search-input::placeholder {
            color: #999;
        }
        
        .search-button {
            padding: 16px 24px;
            background: linear-gradient(135deg, #009688 0%, #00695c 100%);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .search-button:hover {
            background: linear-gradient(135deg, #00695c 0%, #004d40 100%);
        }

        /* ========== SEARCH RESULTS ========== */
        .search-results {
            margin-top: 30px;
        }
        
        .search-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 3px solid #009688;
            padding-bottom: 10px;
            font-weight: 600;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #777;
            font-style: italic;
            font-size: 18px;
        }

        /* ========== CHARTS ========== */
        .chart-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 50px;
        }
        
        .chart-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .chart-title {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, #009688 0%, #FF9800 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .chart-actions {
            display: flex;
            gap: 10px;
        }
        
        .chart-action-btn {
            background: rgba(0, 150, 136, 0.1);
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #009688;
        }
        
        .chart-action-btn:hover {
            background: rgba(0, 150, 136, 0.2);
            transform: scale(1.05);
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
        }

        /* ========== ACTIVITIES ========== */
        .activities-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 50px;
            backdrop-filter: blur(10px);
        }
        
        .activity-list {
            margin-top: 25px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background: rgba(0, 150, 136, 0.05);
            border-radius: 12px;
            padding: 20px 15px;
        }
        
        .activity-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .activity-icon.user {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            color: #fff;
        }
        
        .activity-icon.course {
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            color: #fff;
        }
        
        .activity-icon.project {
            background: linear-gradient(135deg, #FF9800 0%, #f57c00 100%);
            color: #fff;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-content p {
            margin: 0;
            color: #555;
            font-weight: 500;
        }
        
        .activity-time {
            font-size: 14px;
            color: #999;
            margin-top: 5px;
        }

        /* ========== KPI CARDS ========== */
        .kpi-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }
        
        .kpi-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
        }
        
        .kpi-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .kpi-icon.success {
            background: linear-gradient(135deg, #4caf50 0%, #8bc34a 100%);
            color: #fff;
        }
        
        .kpi-icon.warning {
            background: linear-gradient(135deg, #FF9800 0%, #ffc107 100%);
            color: #fff;
        }
        
        .kpi-icon.info {
            background: linear-gradient(135deg, #2196f3 0%, #03a9f4 100%);
            color: #fff;
        }
        
        .kpi-content {
            flex: 1;
        }
        
        .kpi-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #009688 0%, #FF9800 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .kpi-label {
            font-size: 16px;
            color: #777;
            font-weight: 500;
        }

        /* ========== STATUS BADGES ========== */
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status.pending {
            background: linear-gradient(135deg, #FF9800 0%, #ffc107 100%);
            color: #fff;
        }
        
        .status.active {
            background: linear-gradient(135deg, #4caf50 0%, #8bc34a 100%);
            color: #fff;
        }
        
        .status.inactive {
            background: linear-gradient(135deg, #f44336 0%, #e91e63 100%);
            color: #fff;
        }

        /* ========== MESSAGERIE STYLES ========== */
        .messagerie-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .message-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(0, 150, 136, 0.2);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #009688;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0, 150, 136, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .message-list {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            max-height: 600px;
            overflow-y: auto;
        }
        
        .message-item {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 20px 0;
            transition: all 0.3s ease;
        }
        
        .message-item:last-child {
            border-bottom: none;
        }
        
        .message-item:hover {
            background: rgba(0, 150, 136, 0.05);
            border-radius: 12px;
            padding: 20px 15px;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .message-subject {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }
        
        .message-date {
            font-size: 14px;
            color: #999;
        }
        
        .message-sender {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .message-content {
            color: #555;
            line-height: 1.6;
        }
        
        .message-unread {
            background: rgba(255, 152, 0, 0.1);
            border-left: 4px solid #FF9800;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            color: #388e3c;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            color: #d32f2f;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        /* ========== FOOTER ========== */
        footer {
            margin-top: 60px;
            text-align: center;
            color: #777;
            font-size: 14px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            backdrop-filter: blur(10px);
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1200px) {
            .chart-container {
                grid-template-columns: 1fr;
            }
            .messagerie-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 992px) {
            .kpi-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
            }
            
            .main {
                margin-left: 250px;
                padding: 20px;
            }
            
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 600px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main {
                margin: 0;
                width: 100%;
                padding: 15px;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            .kpi-container {
                grid-template-columns: 1fr;
            }
        }

        /* ========== ANIMATIONS ========== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .section {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .card:nth-child(2) { animation-delay: 0.1s; }
        .card:nth-child(3) { animation-delay: 0.2s; }
        .card:nth-child(4) { animation-delay: 0.3s; }
        .card:nth-child(5) { animation-delay: 0.4s; }
        .card:nth-child(6) { animation-delay: 0.5s; }
        .card:nth-child(7) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div>
            <h2><i class="fa-solid fa-shield-halved"></i> Admin Panel</h2>
            <div class="menu">
                <a href="?section=dashboard" data-section="dashboard" class="<?= $defaultSection === 'dashboard' ? 'active' : '' ?>">
                    <i class="fa-solid fa-gauge"></i> Tableau de bord
                </a>
                <a href="?section=users" data-section="users" class="<?= $defaultSection === 'users' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> Utilisateurs
                </a>
                <a href="?section=courses" data-section="courses" class="<?= $defaultSection === 'courses' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book"></i> Cours
                </a>
                <a href="?section=projects" data-section="projects" class="<?= $defaultSection === 'projects' ? 'active' : '' ?>">
                    <i class="fa-solid fa-briefcase"></i> Projets disponible
                </a>
                <a href="?section=quizzes" data-section="quizzes" class="<?= $defaultSection === 'quizzes' ? 'active' : '' ?>">
                    <i class="fa-solid fa-circle-question"></i> Quizzes
                </a>
               <a href="/lms_isep/public/messagerie.php" target="_top" onclick="window.location.href='/lms_isep/public/messagerie.php'; return false;">
    <i class="fa-solid fa-envelope"></i> Messagerie
</a>
<a href="/lms_isep/public/projet.php" target="_top" onclick="window.location.href='/lms_isep/public/projet.php'; return false;">
    <i class="fa-solid fa-diagram-project"></i> Projets
</a>

<a href="/lms_isep/public/progression.php" target="_top" onclick="window.location.href='/lms_isep/public/progression.php'; return false;">
    <i class="fa-solid fa-chart-line"></i> Suivre  progression
</a>





            </div>
        </div>
        <div class="logout">
            <a href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
            </a>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main">
        <div class="header">
            <h1><i class="fa-solid fa-crown"></i> Bienvenue, Administrateur</h1>
            <div class="user">
                <i class="fa-solid fa-user-shield"></i> 
                <span>Admin Dashboard</span>
            </div>
        </div>

        <!-- BARRE DE RECHERCHE -->
        <div class="search-container">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Rechercher un utilisateur..." value="<?= htmlspecialchars($searchTerm) ?>">
                <button type="submit" class="search-button">
                    <i class="fa-solid fa-search"></i>
                </button>
            </form>
        </div>

        <!-- RÉSULTATS DE RECHERCHE -->
        <?php if (!empty($searchTerm)): ?>
        <div class="search-results">
            <h2 class="search-title">Résultats de recherche pour "<?= htmlspecialchars($searchTerm) ?>"</h2>
            
            <?php if (!empty($searchResults)): ?>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Nom complet</th><th>Email</th><th>Rôle</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($searchResults as $result): ?>
                            <tr>
                                <td><?= $result['id'] ?></td>
                                <td><?= htmlspecialchars($result['nom'].' '.$result['prenom']) ?></td>
                                <td><?= htmlspecialchars($result['email']) ?></td>
                                <td><?= $result['role'] ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?= $result['id'] ?>" class="button edit">
                                        <i class="fa fa-edit"></i> Modifier
                                    </a>
                                    <a href="delete_user.php?id=<?= $result['id'] ?>" class="button danger" onclick="return confirm('Confirmer la suppression ?')">
                                        <i class="fa fa-trash"></i> Supprimer
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-results">Aucun résultat trouvé pour votre recherche.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- SECTION DASHBOARD -->
        <div id="dashboard" class="section" style="<?= $defaultSection === 'dashboard' ? '' : 'display:none;' ?>">
            <div class="stats">
                <div class="card">
                    <i class="fa-solid fa-users"></i>
                    <h3>Total Utilisateurs</h3>
                    <p><?= $totalUsers ?></p>
                    <span class="trend up">+12%</span>
                </div>
                <div class="card">
                    <i class="fa-solid fa-user-graduate"></i>
                    <h3>Étudiants</h3>
                    <p><?= $totalEtudiants ?></p>
                    <span class="trend up">+8%</span>
                </div>
                <div class="card">
                    <i class="fa-solid fa-chalkboard-user"></i>
                    <h3>Enseignants</h3>
                    <p><?= $totalEnseignants ?></p>
                    <span class="trend up">+5%</span>
                </div>
                <div class="card">
                    <i class="fa-solid fa-user-shield"></i>
                    <h3>Administrateurs</h3>
                    <p><?= $totalAdmins ?></p>
                </div>
                <div class="card">
                    <i class="fa-solid fa-book"></i>
                    <h3>Cours</h3>
                    <p><?= $totalCourses ?></p>
                    <span class="trend up">+15%</span>
                </div>
                <div class="card">
                    <i class="fa-solid fa-briefcase"></i>
                    <h3>Projets</h3>
                    <p><?= $totalProjets ?></p>
                    <span class="trend up">+22%</span>
                </div>
                <div class="card">
                    <i class="fa-solid fa-circle-question"></i>
                    <h3>Quizzes</h3>
                    <p><?= $totalQuizzes ?></p>
                    <span class="trend up">+18%</span>
                </div>
            </div>
            
            <!-- INDICATEURS DE PERFORMANCE -->
            <div class="kpi-container">
                <div class="kpi-card">
                    <div class="kpi-icon success">
                        <i class="fa-solid fa-check-circle fa-lg"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value">92%</div>
                        <div class="kpi-label">Taux de complétion</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon warning">
                        <i class="fa-solid fa-clock fa-lg"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value">18</div>
                        <div class="kpi-label">En attente de validation</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon info">
                        <i class="fa-solid fa-chart-line fa-lg"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value">74%</div>
                        <div class="kpi-label">Engagement des utilisateurs</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon success">
                        <i class="fa-solid fa-graduation-cap fa-lg"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value">86%</div>
                        <div class="kpi-label">Taux de réussite</div>
                    </div>
                </div>
            </div>

            <!-- GRAPHIQUES -->
            <div class="chart-container">
                <div class="chart-box">
                    <div class="chart-header">
                        <h3 class="chart-title">Répartition des utilisateurs</h3>
                        <div class="chart-actions">
                            <button class="chart-action-btn" onclick="downloadChart('userChart', 'users-repartition.png')">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="userChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-box">
                    <div class="chart-header">
                        <h3 class="chart-title">Évolution des inscriptions</h3>
                        <div class="chart-actions">
                            <button class="chart-action-btn" onclick="downloadChart('evolutionChart', 'user-evolution.png')">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="evolutionChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="chart-container">
                <div class="chart-box">
                    <div class="chart-header">
                        <h3 class="chart-title">Statistiques du système</h3>
                        <div class="chart-actions">
                            <button class="chart-action-btn" onclick="downloadChart('systemChart', 'system-stats.png')">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="systemChart"></canvas>
                    </div>
                </div>
                
                <!-- ACTIVITÉS RÉCENTES -->
                <div class="activities-container">
                    <div class="chart-header">
                        <h3 class="chart-title">Activités récentes</h3>
                        <div class="chart-actions">
                            <button class="chart-action-btn">
                                <i class="fas fa-sync"></i>
                            </button>
                        </div>
                    </div>
                    <div class="activity-list">
                        <?php foreach($recentActivities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?= $activity['type'] ?>">
                                    <?php if($activity['type'] == 'user'): ?>
                                        <i class="fa-solid fa-user-plus"></i>
                                    <?php elseif($activity['type'] == 'course'): ?>
                                        <i class="fa-solid fa-book"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-briefcase"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-content">
                                    <p><?= htmlspecialchars($activity['description']) ?></p>
                                    <div class="activity-time"><?= date('d/m/Y H:i', strtotime($activity['date'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION USERS -->
        <div id="users" class="section" style="<?= $defaultSection === 'users' ? '' : 'display:none;' ?>">
            <h2 class="section-title">Liste des utilisateurs</h2>
            <a href="add_users.php" class="button add" style="margin-bottom:25px;">
                <i class="fa fa-plus"></i> Ajouter un utilisateur
            </a>
            <table>
                <thead>
                    <tr><th>ID</th><th>Nom complet</th><th>Email</th><th>Rôle</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['nom'].' '.$user['prenom']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= $user['role'] ?></td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="button edit">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="delete_user.php?id=<?= $user['id'] ?>" class="button danger" onclick="return confirm('Confirmer la suppression ?')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- SECTION COURSES -->
        <div id="courses" class="section" style="<?= $defaultSection === 'courses' ? '' : 'display:none;' ?>">
            <h2 class="section-title">Tous les cours</h2>
            <a href="cours.php" class="button add" style="margin-bottom:25px;">
                <i class="fa fa-plus"></i> Ajouter un cours
            </a>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Filière</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($courses as $course): ?>
                        <tr>
                            <td><?= $course['id'] ?></td>
                            <td><?= htmlspecialchars($course['title']) ?></td>
                            <td><?= $course['filiere'] ?></td>
                            <td>
                                <span class="status <?= strtolower($course['status']) ?>">
                                    <?= ucfirst($course['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="modifier_cours.php?id=<?= $course['id'] ?>" class="button edit">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="supprimer_cours.php?id=<?= $course['id'] ?>" class="button danger" 
   onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce cours ? Cette action supprimera également tous les modules, leçons et quizzes associés. Cette action est irréversible.')">
    <i class="fa fa-trash"></i>
</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2 class="section-title">Cours en attente de validation</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Filière</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pendingCourses as $course): ?>
                        <tr>
                            <td><?= $course['id'] ?></td>
                            <td><?= htmlspecialchars($course['title']) ?></td>
                            <td><?= $course['filiere'] ?></td>
                            <td>
                                <span class="status <?= strtolower($course['status']) ?>">
                                    <?= ucfirst($course['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="validate_course.php?id=<?= $course['id'] ?>" class="button success">
                                    <i class="fa fa-check"></i>
                                </a>
                                <a href="delete_course.php?id=<?= $course['id'] ?>" class="button danger" onclick="return confirm('Confirmer la suppression ?')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- SECTION PROJECTS -->
        <div id="projects" class="section" style="<?= $defaultSection === 'projects' ? '' : 'display:none;' ?>">
            <h2 class="section-title">Projets soumis</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Enseignant</th>
                        <th>Status</th>
                        <th>Soumis le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($projects as $project): ?>
                        <tr>
                            <td><?= $project['id'] ?></td>
                            <td><?= htmlspecialchars($project['titre']) ?></td>
                            <td><?= htmlspecialchars($project['enseignant']) ?></td>
                            <td>
                                <span class="status <?= strtolower($project['statut']) ?>">
                                    <?= ucfirst($project['statut']) ?>
                                </span>
                            </td>
                            <td><?= $project['date_creation'] ?></td>
                            <td>
                                <a href="modifier_projet.php?id=<?= $project['id'] ?>" class="button edit">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="validate_projet.php?id=<?= $project['id'] ?>" class="button success">
                                    <i class="fa fa-check"></i>
                                </a>
                                <a href="supprimer_projet.php?id=<?= $project['id'] ?>" class="button danger" onclick="return confirm('Confirmer la suppression ?')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- SECTION QUIZZES -->
        <div id="quizzes" class="section" style="<?= $defaultSection === 'quizzes' ? '' : 'display:none;' ?>">
            <h2 class="section-title">Quizzes</h2>
           
             
            </a>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Cours associé</th>
                        <th>Nombre de questions</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($quizzes)): ?>
                        <?php foreach($quizzes as $quiz): ?>
                            <tr>
                                <td><?= $quiz['id'] ?></td>
                                <td><?= htmlspecialchars($quiz['title']) ?></td>
                                <td><?= htmlspecialchars($quiz['course']) ?></td>
                                <td><?= $quiz['questions_count'] ?></td>
                                <td>
                                    <span class="status <?= strtolower($quiz['status']) ?>">
                                        <?= ucfirst($quiz['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_quiz.php?id=<?= $quiz['id'] ?>" class="button edit" title="Modifier">
    <i class="fa fa-edit"></i>
</a>
                                    <a href="delete_quiz.php?id=<?= $quiz['id'] ?>" class="button danger" title="Supprimer"
   onclick="return confirm('Voulez-vous vraiment supprimer ce quiz ?');">
    <i class="fa fa-trash"></i>
</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">Aucun quiz disponible pour le moment.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- SECTION MESSAGERIE -->
        <div id="messagerie" class="section" style="<?= $defaultSection === 'messagerie' ? '' : 'display:none;' ?>">
            <h2 class="section-title">Messagerie Administrative</h2>
            
            <!-- Affichage des messages de succès/erreur -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <div class="messagerie-container">
                <!-- FORMULAIRE D'ENVOI DE MESSAGE -->
                <div class="message-form">
                    <h3 class="chart-title" style="margin-bottom: 25px;">
                        <i class="fa-solid fa-paper-plane"></i> Envoyer un message
                    </h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="send_message">
                        
                        <div class="form-group">
                            <label for="destinataire_id">
                                <i class="fa-solid fa-user"></i> Destinataire
                            </label>
                            <select name="destinataire_id" id="destinataire_id" required>
                                <option value="">Sélectionner un destinataire</option>
                                <?php foreach($destinataires as $destinataire): ?>
                                    <option value="<?= $destinataire['id'] ?>">
                                        <?= htmlspecialchars($destinataire['nom'] . ' ' . $destinataire['prenom']) ?>
                                        <?php if($destinataire['role_id'] == 2): ?>
                                            (Enseignant)
                                        <?php elseif($destinataire['role_id'] == 3): ?>
                                            (Étudiant)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="sujet">
                                <i class="fa-solid fa-tag"></i> Sujet
                            </label>
                            <input type="text" name="sujet" id="sujet" placeholder="Objet du message" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="contenu">
                                <i class="fa-solid fa-message"></i> Message
                            </label>
                            <textarea name="contenu" id="contenu" placeholder="Tapez votre message ici..." required></textarea>
                        </div>
                        
                        <button type="submit" class="button add" style="width: 100%; justify-content: center;">
                            <i class="fa-solid fa-paper-plane"></i> Envoyer le message
                        </button>
                    </form>
                </div>

                <!-- LISTE DES MESSAGES REÇUS -->
                <div class="message-list">
                    <h3 class="chart-title" style="margin-bottom: 25px;">
                        <i class="fa-solid fa-inbox"></i> Messages reçus
                    </h3>
                    
                    <?php if ($messages_recus->rowCount() > 0): ?>
                        <?php while($message = $messages_recus->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="message-item <?= !$message['lu'] ? 'message-unread' : '' ?>">
                                <div class="message-header">
                                    <div class="message-subject">
                                        <?php if (!$message['lu']): ?>
                                            <i class="fa-solid fa-circle" style="color: #FF9800; font-size: 8px; margin-right: 8px;"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($message['sujet']) ?>
                                    </div>
                                    <div class="message-date">
                                        <?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?>
                                    </div>
                                </div>
                                <div class="message-sender">
                                    <i class="fa-solid fa-user"></i> 
                                    De: <?= htmlspecialchars($message['expediteur_nom'] . ' ' . $message['expediteur_prenom']) ?>
                                </div>
                                <div class="message-content">
                                    <?= nl2br(htmlspecialchars($message['contenu'])) ?>
                                </div>
                                <?php if (!$message['lu']): ?>
                                    <div style="margin-top: 10px;">
                                        <a href="?section=messagerie&mark_read=<?= $message['id'] ?>" class="button edit" style="font-size: 12px; padding: 6px 12px;">
                                            <i class="fa-solid fa-check"></i> Marquer comme lu
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-results">
                            <i class="fa-solid fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                            <p>Aucun message reçu pour le moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- MESSAGES ENVOYÉS -->
            <div class="message-list" style="margin-top: 30px;">
                <h3 class="chart-title" style="margin-bottom: 25px;">
                    <i class="fa-solid fa-paper-plane"></i> Messages envoyés
                </h3>
                
                <?php if ($messages_envoyes->rowCount() > 0): ?>
                    <?php while($message = $messages_envoyes->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="message-item">
                            <div class="message-header">
                                <div class="message-subject">
                                    <?= htmlspecialchars($message['sujet']) ?>
                                </div>
                                <div class="message-date">
                                    <?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?>
                                </div>
                            </div>
                            <div class="message-sender">
                                <i class="fa-solid fa-user"></i> 
                                À: <?= htmlspecialchars($message['destinataire_nom'] . ' ' . $message['destinataire_prenom']) ?>
                            </div>
                            <div class="message-content">
                                <?= nl2br(htmlspecialchars($message['contenu'])) ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fa-solid fa-paper-plane" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                        <p>Aucun message envoyé pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <footer>
            <p>© 2025 Admin Dashboard - Système E-leraning de ISEP Aboulaye Ly Thies</p>
        </footer>
    </div>

    <!-- SCRIPT NAVIGATION -->
    <script>
        document.querySelectorAll(".menu a").forEach(link => {
            link.addEventListener("click", function(e) {
                e.preventDefault();
                // désactiver tous
                document.querySelectorAll(".menu a").forEach(l => l.classList.remove("active"));
                document.querySelectorAll(".section").forEach(sec => sec.style.display = "none");
                // activer celui-ci
                this.classList.add("active");
                const target = this.getAttribute("data-section");
                document.getElementById(target).style.display = "block";
                
                // Mettre à jour l'URL avec le paramètre de section
                const url = new URL(window.location);
                url.searchParams.set('section', target);
                window.history.pushState({}, '', url);
            });
        });

        // Configuration des couleurs pour les graphiques
        const primaryColor = '#009688';
        const secondaryColor = '#FF9800';
        const gradientPrimary = 'rgba(0, 150, 136, 0.8)';
        const gradientSecondary = 'rgba(255, 152, 0, 0.8)';

        // Graphique de répartition des utilisateurs
        const userChart = new Chart(
            document.getElementById('userChart'),
            {
                type: 'doughnut',
                data: {
                    labels: ['Étudiants', 'Enseignants', 'Administrateurs'],
                    datasets: [{
                        data: [<?= $totalEtudiants ?>, <?= $totalEnseignants ?>, <?= $totalAdmins ?>],
                        backgroundColor: [
                            gradientPrimary,
                            gradientSecondary,
                            'rgba(156, 39, 176, 0.8)'
                        ],
                        borderColor: [
                            primaryColor,
                            secondaryColor,
                            '#9c27b0'
                        ],
                        borderWidth: 3,
                        hoverOffset: 20
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 14,
                                    family: "'Inter', sans-serif",
                                    weight: '500'
                                },
                                padding: 25,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: primaryColor,
                            borderWidth: 2,
                            cornerRadius: 10,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '65%',
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 1500
                    }
                }
            }
        );

        // Graphique d'évolution des inscriptions
        const evolutionChart = new Chart(
            document.getElementById('evolutionChart'),
            {
                type: 'line',
                data: {
                    labels: [<?php 
                        $months = [];
                        foreach($monthlyUsers as $data) {
                            $months[] = "'" . date('M Y', strtotime($data['month'] . '-01')) . "'";
                        }
                        echo implode(', ', $months);
                    ?>],
                    datasets: [{
                        label: 'Nouveaux utilisateurs',
                        data: [<?php 
                            $counts = [];
                            foreach($monthlyUsers as $data) {
                                $counts[] = $data['count'];
                            }
                            echo implode(', ', $counts);
                        ?>],
                        backgroundColor: 'rgba(0, 150, 136, 0.1)',
                        borderColor: primaryColor,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: primaryColor,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: secondaryColor,
                        pointHoverBorderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: primaryColor,
                            borderWidth: 2,
                            cornerRadius: 10,
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#666',
                                font: {
                                    family: "'Inter', sans-serif"
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#666',
                                font: {
                                    family: "'Inter', sans-serif"
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    }
                }
            }
        );

        // Graphique des statistiques système
        const systemChart = new Chart(
            document.getElementById('systemChart'),
            {
                type: 'bar',
                data: {
                    labels: ['Cours', 'Projets', 'Quizzes'],
                    datasets: [{
                        label: 'Nombre total',
                        data: [<?= $totalCourses ?>, <?= $totalProjets ?>, <?= $totalQuizzes ?>],
                        backgroundColor: [
                            gradientPrimary,
                            'rgba(156, 39, 176, 0.8)',
                            gradientSecondary
                        ],
                        borderColor: [
                            primaryColor,
                            '#9c27b0',
                            secondaryColor
                        ],
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                        barPercentage: 0.7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: primaryColor,
                            borderWidth: 2,
                            cornerRadius: 10,
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.raw}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#666',
                                font: {
                                    family: "'Inter', sans-serif"
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#666',
                                font: {
                                    family: "'Inter', sans-serif",
                                    weight: '500'
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1500,
                        easing: 'easeOutBounce'
                    }
                }
            }
        );

        // Fonction pour télécharger le graphique
        function downloadChart(chartId, filename) {
            const chartCanvas = document.getElementById(chartId);
            const link = document.createElement('a');
            link.href = chartCanvas.toDataURL('image/png');
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Animation d'entrée pour les cartes
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observer les éléments animés
        document.querySelectorAll('.card, .kpi-card, .chart-box').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s ease-out';
            observer.observe(el);
        });
    </script>
</body>
</html>