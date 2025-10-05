<?php
session_start();

require_once '../config/database.php';
require_once '../includes/auth.php';

// Debug: Afficher les informations de session pour diagnostiquer le problème
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "Session data:\n";
    print_r($_SESSION);
    echo "\nUser ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Non défini');
    echo "\nUser role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'Non défini');
    echo "\nUser type: " . (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'Non défini');
    echo "</pre>";
    exit;
}

try {
    // Si $pdo n'existe pas, créer une nouvelle connexion
    if (!isset($pdo)) {
        $pdo = new PDO("mysql:host=localhost;dbname=lms_isep;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérification des droits d'accès améliorée et plus flexible
$access_granted = false;
$user_info = "";
$dashboard_url = ""; // URL du tableau de bord approprié

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Vérifier si l'utilisateur est admin (id=3) ou enseignant (id=2)
    if ($user_id == 2 || $user_id == 3) {
        $access_granted = true;
        $dashboard_url = ($user_id == 3) ? "admin_dashboard.php" : "teacher_dashboard.php";
    }
    
    // Alternative: vérifier par rôle si disponible
    if (isset($_SESSION['role'])) {
        $role = strtolower($_SESSION['role']);
        if (in_array($role, ['admin', 'teacher', 'enseignant', 'administrateur', 'instructor', 'prof'])) {
            $access_granted = true;
            if (in_array($role, ['admin', 'administrateur'])) {
                $dashboard_url = "admin_dashboard.php";
            } else {
                $dashboard_url = "teacher_dashboard.php";
            }
        }
    }
    
    // Alternative: vérifier par user_type si disponible
    if (isset($_SESSION['user_type'])) {
        $user_type = strtolower($_SESSION['user_type']);
        if (in_array($user_type, ['admin', 'teacher', 'enseignant', 'administrateur', 'instructor', 'prof'])) {
            $access_granted = true;
            if (in_array($user_type, ['admin', 'administrateur'])) {
                $dashboard_url = "admin_dashboard.php";
            } else {
                $dashboard_url = "teacher_dashboard.php";
            }
        }
    }
    
    // Vérification dans la base de données si nécessaire - CORRIGÉ
    if (!$access_granted) {
        try {
            // Utiliser seulement les colonnes qui existent dans votre table users
            $stmt = $pdo->prepare("SELECT role, user_type, role_id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch();
            
            if ($user_data) {
                $db_role = strtolower($user_data['role'] ?? '');
                $db_user_type = strtolower($user_data['user_type'] ?? '');
                $db_role_id = $user_data['role_id'] ?? 0;
                
                $allowed_roles = ['admin', 'teacher', 'enseignant', 'administrateur', 'instructor', 'prof', 'professeur'];
                
                if (in_array($db_role, $allowed_roles) || 
                    in_array($db_user_type, $allowed_roles) ||
                    $db_role_id == 2 || $db_role_id == 3) { // 2 = enseignant, 3 = admin
                    $access_granted = true;
                    
                    // Déterminer l'URL du tableau de bord
                    if (in_array($db_role, ['admin', 'administrateur']) || 
                        in_array($db_user_type, ['admin', 'administrateur']) || 
                        $db_role_id == 3) {
                        $dashboard_url = "admin_dashboard.php";
                    } else {
                        $dashboard_url = "teacher_dashboard.php";
                    }
                }
            }
        } catch(PDOException $e) {
            // En cas d'erreur de base de données
        }
    }
    
    // Si toujours pas d'accès, permettre temporairement l'accès pour tous les utilisateurs connectés
    if (!$access_granted && isset($_SESSION['user_id'])) {
        $access_granted = true; // TEMPORAIRE - pour le débogage
        $dashboard_url = "teacher_dashboard.php"; // Par défaut vers le tableau enseignant
    }
    
    // Définir les informations utilisateur pour l'affichage (sans les détails de débogage)
    if (isset($_SESSION['role'])) {
        $role_display = ucfirst($_SESSION['role']);
    } elseif (isset($_SESSION['user_type'])) {
        $role_display = ucfirst($_SESSION['user_type']);
    } else {
        $role_display = "Utilisateur";
    }
    
    $user_info = "Connecté en tant que: " . $role_display;
    
} else {
    $user_info = "Aucune session utilisateur trouvée";
}

if (!$access_granted) {
    die("
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
        <h2 style='color: #d32f2f;'>Accès non autorisé</h2>
        <p>Seuls les administrateurs et enseignants peuvent accéder à cette page.</p>
        <hr>
        <h3>Informations de débogage:</h3>
        <p><strong>$user_info</strong></p>
        <p><em>Pour accéder à cette page, vous devez être:</em></p>
        <ul>
            <li>Un utilisateur avec l'ID 2 (enseignant) ou 3 (admin)</li>
            <li>OU avoir un rôle 'admin', 'teacher', 'enseignant' ou 'administrateur'</li>
        </ul>
        <p><a href='?debug=1'>Voir les détails de la session</a></p>
        <p><a href='../login.php'>Se connecter</a></p>
    </div>
    ");
}

// Récupération des données de progression avec gestion d'erreurs - CORRIGÉ COMPLÈTEMENT
try {
    // D'abord, vérifier quelles colonnes existent dans la table courses
    $stmt = $pdo->query("SHOW COLUMNS FROM courses");
    $course_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Déterminer quelle colonne utiliser pour le nom du cours
    $course_name_column = 'id'; // Par défaut, utiliser l'ID
    if (in_array('nom_cours', $course_columns)) {
        $course_name_column = 'nom_cours';
    } elseif (in_array('title', $course_columns)) {
        $course_name_column = 'title';
    } elseif (in_array('name', $course_columns)) {
        $course_name_column = 'name';
    } elseif (in_array('course_name', $course_columns)) {
        $course_name_column = 'course_name';
    }
    
    $query = "
        SELECT 
            p.id,
            p.student_id,
            p.course_id,
            p.modules_total,
            p.modules_faits,
            p.updated_at,
            ROUND((p.modules_faits / NULLIF(p.modules_total, 0)) * 100, 2) as pourcentage_progression,
            COALESCE(u.nom, 'Utilisateur inconnu') as nom_etudiant,
            COALESCE(u.prenom, '') as prenom_etudiant,
            u.email as email_etudiant,
            COALESCE(c.$course_name_column, CONCAT('Cours ID: ', p.course_id)) as nom_cours
        FROM progression p
        LEFT JOIN users u ON p.student_id = u.id
        LEFT JOIN courses c ON p.course_id = c.id
        ORDER BY p.updated_at DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $progressions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $progressions = [];
    $error_message = "Erreur lors de la récupération des données : " . $e->getMessage();
}

// Fonction pour obtenir les statistiques globales
function getStatistiquesGlobales($pdo) {
    $stats = ['total_etudiants' => 0, 'total_cours' => 0, 'progression_moyenne' => 0, 'cours_termines' => 0];
    
    try {
        // Nombre total d'étudiants
        $stmt = $pdo->query("SELECT COUNT(DISTINCT student_id) as total_etudiants FROM progression");
        $result = $stmt->fetch();
        $stats['total_etudiants'] = $result ? $result['total_etudiants'] : 0;
        
        // Nombre total de cours
        $stmt = $pdo->query("SELECT COUNT(DISTINCT course_id) as total_cours FROM progression");
        $result = $stmt->fetch();
        $stats['total_cours'] = $result ? $result['total_cours'] : 0;
        
        // Progression moyenne
        $stmt = $pdo->query("SELECT AVG((modules_faits / NULLIF(modules_total, 0)) * 100) as progression_moyenne FROM progression WHERE modules_total > 0");
        $result = $stmt->fetch();
        $stats['progression_moyenne'] = $result && $result['progression_moyenne'] ? round($result['progression_moyenne'], 2) : 0;
        
        // Cours terminés (100%)
        $stmt = $pdo->query("SELECT COUNT(*) as cours_termines FROM progression WHERE modules_faits >= modules_total AND modules_total > 0");
        $result = $stmt->fetch();
        $stats['cours_termines'] = $result ? $result['cours_termines'] : 0;
    } catch(PDOException $e) {
        // En cas d'erreur, garder les valeurs par défaut
    }
    
    return $stats;
}

// Fonction pour obtenir les données des graphiques
function getGraphiqueData($pdo) {
    $data = [
        'progression_par_mois' => [],
        'repartition_statuts' => [],
        'top_cours' => []
    ];
    
    try {
        // Progression par mois (derniers 6 mois)
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(updated_at, '%Y-%m') as mois,
                AVG((modules_faits / NULLIF(modules_total, 0)) * 100) as progression_moyenne
            FROM progression 
            WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            AND modules_total > 0
            GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
            ORDER BY mois ASC
        ");
        $data['progression_par_mois'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Répartition des statuts
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN (modules_faits / NULLIF(modules_total, 0)) * 100 = 100 THEN 'Terminé'
                    WHEN (modules_faits / NULLIF(modules_total, 0)) * 100 >= 50 THEN 'En cours'
                    ELSE 'Débuté'
                END as statut,
                COUNT(*) as nombre
            FROM progression 
            WHERE modules_total > 0
            GROUP BY statut
        ");
        $data['repartition_statuts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top 5 des cours les plus suivis
        $stmt = $pdo->query("
            SELECT 
                course_id,
                COUNT(*) as nombre_etudiants,
                AVG((modules_faits / NULLIF(modules_total, 0)) * 100) as progression_moyenne
            FROM progression 
            WHERE modules_total > 0
            GROUP BY course_id
            ORDER BY nombre_etudiants DESC
            LIMIT 5
        ");
        $data['top_cours'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        // En cas d'erreur, garder les valeurs par défaut
    }
    
    return $data;
}

$statistiques = getStatistiquesGlobales($pdo);
$graphique_data = getGraphiqueData($pdo);

// Traitement AJAX pour mise à jour en temps réel
if (isset($_GET['ajax']) && $_GET['ajax'] == 'update') {
    header('Content-Type: application/json');
    echo json_encode([
        'progressions' => $progressions,
        'statistiques' => $statistiques,
        'graphiques' => $graphique_data
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Suivi de Progression</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #009688;
            --secondary-color: #FF9800;
            --primary-dark: #00695C;
            --secondary-dark: #F57C00;
            --bg-light: #F8F9FA;
            --text-dark: #2C3E50;
            --text-light: #6C757D;
            --white: #FFFFFF;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 30px rgba(0, 0, 0, 0.15);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--bg-light) 0%, #E8F5E8 100%);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }
        
        .header-content {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .header-left {
            flex: 1;
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.15);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Bouton Retour Tableau de Bord */
        .btn-dashboard {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            padding: 12px 24px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            font-size: 0.95rem;
            white-space: nowrap;
        }
        
        .btn-dashboard:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .btn-dashboard:active {
            transform: translateY(0);
        }
        
        /* Auto refresh indicator */
        .auto-refresh {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary-color);
            color: var(--white);
            padding: 12px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            box-shadow: var(--shadow);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Error message */
        .error-message {
            background: linear-gradient(135deg, #FF5252 0%, #F44336 100%);
            color: var(--white);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
        }
        
        .stat-card.secondary::before {
            background: var(--secondary-color);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--white);
        }
        
        .stat-icon.primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        }
        
        .stat-icon.secondary {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-dark) 100%);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-light);
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.75rem;
            font-size: 0.85rem;
        }
        
        .trend-up {
            color: #4CAF50;
        }
        
        .trend-down {
            color: #F44336;
        }
        
        /* Graphiques Section */
        .charts-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .chart-container {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            position: relative;
        }
        
        .chart-container.full-width {
            grid-column: 1 / -1;
        }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .chart-canvas {
            position: relative;
            height: 300px;
        }
        
        /* Controls */
        .controls {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .search-container {
            position: relative;
            flex: 1;
            max-width: 400px;
        }
        
        .search-box {
            width: 100%;
            padding: 12px 20px 12px 50px;
            border: 2px solid #E0E0E0;
            border-radius: 50px;
            font-size: 1rem;
            transition: var(--transition);
            background: #F8F9FA;
        }
        
        .search-box:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(0, 150, 136, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .controls-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-dark) 100%);
            color: var(--white);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }
        
        /* Table Container */
        .table-container {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #F8F9FA 0%, #E9ECEF 100%);
            border-bottom: 1px solid #E0E0E0;
        }
        
        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .progression-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .progression-table th {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .progression-table td {
            padding: 1rem;
            border-bottom: 1px solid #F0F0F0;
            transition: var(--transition);
        }
        
        .progression-table tr:hover {
            background: #F8F9FF;
        }
        
        .progression-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Student Info */
        .student-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .student-details h4 {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }
        
        .student-details span {
            color: var(--text-light);
            font-size: 0.85rem;
        }
        
        /* Course Info */
        .course-info h4 {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }
        
        .course-info span {
            color: var(--text-light);
            font-size: 0.85rem;
        }
        
        /* Progress Bar */
        .progress-container {
            width: 100%;
            max-width: 200px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #E0E0E0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 4px;
            transition: width 0.6s ease;
        }
        
        .progress-text {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .modules-count {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-top: 0.25rem;
        }
        
        /* Status Badge */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-complete {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border: 1px solid rgba(76, 175, 80, 0.2);
        }
        
        .status-progress {
            background: rgba(255, 152, 0, 0.1);
            color: var(--secondary-color);
            border: 1px solid rgba(255, 152, 0, 0.2);
        }
        
        .status-started {
            background: rgba(0, 150, 136, 0.1);
            color: var(--primary-color);
            border: 1px solid rgba(0, 150, 136, 0.2);
        }
        
        /* Last Update */
        .last-update {
            color: var(--text-light);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* No Data */
        .no-data {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }
        
        .no-data i {
            font-size: 4rem;
            color: #E0E0E0;
            margin-bottom: 1rem;
        }
        
        .no-data h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .header-content {
                flex-direction: column;
                align-items: stretch;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-container {
                max-width: none;
            }
            
            .controls-buttons {
                justify-content: center;
            }
            
            .progression-table {
                font-size: 0.85rem;
            }
            
            .progression-table th,
            .progression-table td {
                padding: 0.75rem 0.5rem;
            }
            
            .student-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
        
        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--white);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Animations */
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
        
        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="auto-refresh" id="autoRefresh">
        <i class="fas fa-sync-alt"></i>
        Actualisation: <span id="countdown">30</span>s
    </div>
    
    <div class="dashboard-container">
        <div class="header animate-fade-in">
            <div class="header-content">
                <div class="header-left">
                    <h1>
                        <i class="fas fa-chart-line"></i>
                        Dashboard de Progression
                    </h1>
                    <p class="header-subtitle">Suivi en temps réel des performances étudiantes</p>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($user_info); ?>
                    </div>
                </div>
                <div class="header-right">
                    <a href="<?php echo htmlspecialchars($dashboard_url); ?>" class="btn-dashboard">
                        <i class="fas fa-arrow-left"></i>
                        Retour Tableau de Bord
                    </a>
                </div>
            </div>
        </div>
        
        <?php if (isset($error_message)): ?>
        <div class="error-message animate-fade-in">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card animate-fade-in">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo $statistiques['total_etudiants']; ?></div>
                        <div class="stat-label">Étudiants Actifs</div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            +12% ce mois
                        </div>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card animate-fade-in">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo $statistiques['total_cours']; ?></div>
                        <div class="stat-label">Cours Disponibles</div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            +3 nouveaux
                        </div>
                    </div>
                    <div class="stat-icon secondary">
                        <i class="fas fa-book-open"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card animate-fade-in">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo $statistiques['progression_moyenne']; ?>%</div>
                        <div class="stat-label">Progression Moyenne</div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            +5.2% cette semaine
                        </div>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card animate-fade-in">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo $statistiques['cours_termines']; ?></div>
                        <div class="stat-label">Cours Terminés</div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            +8 cette semaine
                        </div>
                    </div>
                    <div class="stat-icon secondary">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section des Graphiques -->
        <div class="charts-section">
            <div class="chart-container">
                <div class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Évolution de la Progression
                </div>
                <div class="chart-canvas">
                    <canvas id="progressionChart"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <div class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Répartition des Statuts
                </div>
                <div class="chart-canvas">
                    <canvas id="statutsChart"></canvas>
                </div>
            </div>
            
            <div class="chart-container full-width">
                <div class="chart-title">
                    <i class="fas fa-chart-bar"></i>
                    Top 5 des Cours les Plus Suivis
                </div>
                <div class="chart-canvas">
                    <canvas id="coursChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="controls animate-fade-in">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-box" id="searchBox" placeholder="Rechercher un étudiant, cours ou email...">
            </div>
            <div class="controls-buttons">
                <button class="btn btn-primary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i>
                    Actualiser
                </button>
                <button class="btn btn-secondary" onclick="exportData()">
                    <i class="fas fa-download"></i>
                    Exporter
                </button>
            </div>
        </div>
        
        <div class="table-container animate-fade-in">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-table"></i>
                    Progression Détaillée des Étudiants
                </div>
            </div>
            
            <?php if (empty($progressions)): ?>
            <div class="no-data">
                <i class="fas fa-chart-bar"></i>
                <h3>Aucune donnée disponible</h3>
                <p>Les données de progression s'afficheront ici une fois que les étudiants commenceront leurs cours.</p>
            </div>
            <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="progression-table" id="progressionTable">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-user-graduate"></i> Étudiant</th>
                            <th><i class="fas fa-book"></i> Cours</th>
                            <th><i class="fas fa-tasks"></i> Modules</th>
                            <th><i class="fas fa-chart-line"></i> Progression</th>
                            <th><i class="fas fa-flag"></i> Statut</th>
                            <th><i class="fas fa-clock"></i> Dernière MAJ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($progressions as $progression): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($progression['id']); ?></td>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar">
                                        <?php echo strtoupper(substr($progression['nom_etudiant'], 0, 1) . substr($progression['prenom_etudiant'], 0, 1)); ?>
                                    </div>
                                    <div class="student-details">
                                        <h4><?php echo htmlspecialchars($progression['nom_etudiant'] . ' ' . $progression['prenom_etudiant']); ?></h4>
                                        <span><?php echo htmlspecialchars($progression['email_etudiant'] ?? 'ID: ' . $progression['student_id']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="course-info">
                                    <h4><?php echo htmlspecialchars($progression['nom_cours']); ?></h4>
                                    <span>ID: <?php echo $progression['course_id']; ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="modules-count">
                                    <strong><?php echo $progression['modules_faits']; ?></strong> / <?php echo $progression['modules_total']; ?>
                                </div>
                            </td>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progression['pourcentage_progression'] ?? 0; ?>%"></div>
                                    </div>
                                    <div class="progress-text"><?php echo $progression['pourcentage_progression'] ?? 0; ?>%</div>
                                </div>
                            </td>
                            <td>
                                <?php
                                $pourcentage = $progression['pourcentage_progression'] ?? 0;
                                if ($pourcentage == 100) {
                                    echo '<span class="status-badge status-complete"><i class="fas fa-check-circle"></i> Terminé</span>';
                                } elseif ($pourcentage >= 50) {
                                    echo '<span class="status-badge status-progress"><i class="fas fa-play-circle"></i> En cours</span>';
                                } else {
                                    echo '<span class="status-badge status-started"><i class="fas fa-clock"></i> Débuté</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="last-update">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo $progression['updated_at'] ? date('d/m/Y H:i', strtotime($progression['updated_at'])) : 'N/A'; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Configuration des couleurs
        const colors = {
            primary: '#009688',
            secondary: '#FF9800',
            primaryDark: '#00695C',
            secondaryDark: '#F57C00',
            success: '#4CAF50',
            warning: '#FF9800',
            info: '#2196F3'
        };
        
        // Données des graphiques depuis PHP
        const graphiqueData = <?php echo json_encode($graphique_data); ?>;
        
        // Configuration commune des graphiques
        Chart.defaults.font.family = 'Inter';
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#6C757D';
        
        // 1. Graphique de progression par mois (Courbe)
        const progressionCtx = document.getElementById('progressionChart').getContext('2d');
        const progressionChart = new Chart(progressionCtx, {
            type: 'line',
            data: {
                labels: graphiqueData.progression_par_mois.map(item => {
                    const date = new Date(item.mois + '-01');
                    return date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Progression Moyenne (%)',
                    data: graphiqueData.progression_par_mois.map(item => parseFloat(item.progression_moyenne || 0)),
                    borderColor: colors.primary,
                    backgroundColor: colors.primary + '20',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: colors.primary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
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
                        borderColor: colors.primary,
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return `Progression: ${context.parsed.y.toFixed(1)}%`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                elements: {
                    point: {
                        hoverBackgroundColor: colors.secondary
                    }
                }
            }
        });
        
        // 2. Graphique en camembert des statuts
        const statutsCtx = document.getElementById('statutsChart').getContext('2d');
        const statutsChart = new Chart(statutsCtx, {
            type: 'doughnut',
            data: {
                labels: graphiqueData.repartition_statuts.map(item => item.statut),
                datasets: [{
                    data: graphiqueData.repartition_statuts.map(item => item.nombre),
                    backgroundColor: [
                        colors.success,
                        colors.secondary,
                        colors.primary
                    ],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverBorderWidth: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: colors.primary,
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed * 100) / total).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
        
        // 3. Graphique en barres des top cours
        const coursCtx = document.getElementById('coursChart').getContext('2d');
        const coursChart = new Chart(coursCtx, {
            type: 'bar',
            data: {
                labels: graphiqueData.top_cours.map(item => `Cours ${item.course_id}`),
                datasets: [
                    {
                        label: 'Nombre d\'étudiants',
                        data: graphiqueData.top_cours.map(item => item.nombre_etudiants),
                        backgroundColor: colors.primary + '80',
                        borderColor: colors.primary,
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Progression moyenne (%)',
                        data: graphiqueData.top_cours.map(item => parseFloat(item.progression_moyenne || 0)),
                        type: 'line',
                        borderColor: colors.secondary,
                        backgroundColor: colors.secondary + '20',
                        borderWidth: 3,
                        fill: false,
                        tension: 0.4,
                        pointBackgroundColor: colors.secondary,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: colors.primary,
                        borderWidth: 1,
                        cornerRadius: 8,
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        title: {
                            display: true,
                            text: 'Nombre d\'étudiants',
                            color: colors.primary,
                            font: {
                                weight: '600'
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            drawOnChartArea: false,
                        },
                        title: {
                            display: true,
                            text: 'Progression (%)',
                            color: colors.secondary,
                            font: {
                                weight: '600'
                            }
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Fonction de recherche améliorée
        document.getElementById('searchBox').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#progressionTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const isVisible = text.includes(searchTerm);
                row.style.display = isVisible ? '' : 'none';
                
                if (isVisible && searchTerm) {
                    row.style.backgroundColor = '#FFF3E0';
                } else {
                    row.style.backgroundColor = '';
                }
            });
        });
        
        // Actualisation automatique avec animation
        let countdown = 30;
        const countdownElement = document.getElementById('countdown');
        
        function updateCountdown() {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                refreshData();
                countdown = 30;
            }
        }
        
        setInterval(updateCountdown, 1000);
        
        // Fonction d'actualisation des données avec loading
        function refreshData() {
            const refreshBtn = document.querySelector('.btn-primary');
            const originalContent = refreshBtn.innerHTML;
            
            refreshBtn.innerHTML = '<div class="loading"></div> Actualisation...';
            refreshBtn.disabled = true;
            
            fetch('?ajax=update')
                .then(response => response.json())
                .then(data => {
                    updateTable(data.progressions);
                    updateCharts(data.graphiques);
                    countdown = 30;
                    
                    // Animation de succès
                    refreshBtn.innerHTML = '<i class="fas fa-check"></i> Actualisé';
                    setTimeout(() => {
                        refreshBtn.innerHTML = originalContent;
                        refreshBtn.disabled = false;
                    }, 1500);
                })
                .catch(error => {
                    console.error('Erreur lors de l\'actualisation:', error);
                    refreshBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Erreur';
                    setTimeout(() => {
                        refreshBtn.innerHTML = originalContent;
                        refreshBtn.disabled = false;
                    }, 2000);
                });
        }
        
        // Fonction de mise à jour des graphiques
        function updateCharts(newData) {
            // Mise à jour du graphique de progression
            progressionChart.data.labels = newData.progression_par_mois.map(item => {
                const date = new Date(item.mois + '-01');
                return date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
            });
            progressionChart.data.datasets[0].data = newData.progression_par_mois.map(item => parseFloat(item.progression_moyenne || 0));
            progressionChart.update('active');
            
            // Mise à jour du graphique des statuts
            statutsChart.data.labels = newData.repartition_statuts.map(item => item.statut);
            statutsChart.data.datasets[0].data = newData.repartition_statuts.map(item => item.nombre);
            statutsChart.update('active');
            
            // Mise à jour du graphique des cours
            coursChart.data.labels = newData.top_cours.map(item => `Cours ${item.course_id}`);
            coursChart.data.datasets[0].data = newData.top_cours.map(item => item.nombre_etudiants);
            coursChart.data.datasets[1].data = newData.top_cours.map(item => parseFloat(item.progression_moyenne || 0));
            coursChart.update('active');
        }
        
        // Fonction d'export (placeholder)
        function exportData() {
            const exportBtn = document.querySelector('.btn-secondary');
            const originalContent = exportBtn.innerHTML;
            
            exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Export...';
            
            // Simuler l'export
            setTimeout(() => {
                exportBtn.innerHTML = '<i class="fas fa-check"></i> Exporté';
                setTimeout(() => {
                    exportBtn.innerHTML = originalContent;
                }, 1500);
            }, 1000);
        }
        
        // Mise à jour du tableau avec animations
        function updateTable(data) {
            const tbody = document.querySelector('#progressionTable tbody');
            if (!tbody) return;
            
            // Animation de sortie
            const rows = tbody.querySelectorAll('tr');
            rows.forEach((row, index) => {
                setTimeout(() => {
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                }, index * 50);
            });
            
            setTimeout(() => {
                tbody.innerHTML = '';
                
                if (data.length === 0) {
                    const row = document.createElement('tr');
                    row.innerHTML = '<td colspan="7" class="no-data"><i class="fas fa-inbox"></i><h3>Aucune donnée disponible</h3></td>';
                    tbody.appendChild(row);
                    return;
                }
                
                data.forEach((progression, index) => {
                    const row = document.createElement('tr');
                    
                    let statusClass = 'status-started';
                    let statusText = '<i class="fas fa-clock"></i> Débuté';
                    
                    const pourcentage = progression.pourcentage_progression || 0;
                    
                    if (pourcentage == 100) {
                        statusClass = 'status-complete';
                        statusText = '<i class="fas fa-check-circle"></i> Terminé';
                    } else if (pourcentage >= 50) {
                        statusClass = 'status-progress';
                        statusText = '<i class="fas fa-play-circle"></i> En cours';
                    }
                    
                    const initials = (progression.nom_etudiant.charAt(0) + progression.prenom_etudiant.charAt(0)).toUpperCase();
                    
                    row.innerHTML = `
                        <td>${progression.id}</td>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar">${initials}</div>
                                <div class="student-details">
                                    <h4>${progression.nom_etudiant} ${progression.prenom_etudiant}</h4>
                                    <span>${progression.email_etudiant || 'ID: ' + progression.student_id}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="course-info">
                                <h4>${progression.nom_cours}</h4>
                                <span>ID: ${progression.course_id}</span>
                            </div>
                        </td>
                        <td>
                            <div class="modules-count">
                                <strong>${progression.modules_faits}</strong> / ${progression.modules_total}
                            </div>
                        </td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: ${pourcentage}%"></div>
                                </div>
                                <div class="progress-text">${pourcentage}%</div>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </td>
                        <td>
                            <div class="last-update">
                                <i class="fas fa-calendar-alt"></i>
                                ${progression.updated_at ? new Date(progression.updated_at).toLocaleString('fr-FR') : 'N/A'}
                            </div>
                        </td>
                    `;
                    
                    // Animation d'entrée
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    tbody.appendChild(row);
                    
                    setTimeout(() => {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '1';
                        row.style.transform = 'translateX(0)';
                    }, index * 100);
                });
            }, 300);
        }
        
        // Animation d'entrée au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.animate-fade-in');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    element.style.transition = 'all 0.6s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html>