<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3;
}

function isEnseignant() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
}

function isEtudiant() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
}

function requireRole($role_id) {
    requireLogin();
    if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != $role_id) {
        header("HTTP/1.1 403 Forbidden");
        exit("Accès refusé");
    }
}

// Déterminer la page de tableau de bord appropriée
function getDashboardPage() {
    if (isAdmin()) {
        return 'admin_dashboard.php';
    } elseif (isEnseignant()) {
        return 'teacher_dashboard.php';
    } elseif (isEtudiant()) {
        return 'etudiant_dashboard.php';
    } else {
        return 'index.php';
    }
}

// Vérifier si l'utilisateur a confirmé la déconnexion
if (isset($_POST['confirm_logout'])) {
    // Détruire toutes les données de session
    $_SESSION = array();
    
    // Détruire le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Détruire la session
    session_destroy();
    
    // Détruire les cookies personnalisés
    $cookies = ['remember_token', 'user_type', 'user_id', 'auth_token'];
    foreach ($cookies as $cookie) {
        if (isset($_COOKIE[$cookie])) {
            setcookie($cookie, '', time() - 3600, '/');
        }
    }
    
    // Rediriger vers la page de login appropriée
    $redirect_pages = [
        'admin' => 'admin_login.php',
        'etudiant' => 'login.php',
        'enseignant' => 'teacher_login.php',
        'default' => 'login.php'
    ];
    
    $user_type = 'default';
    if (isAdmin()) $user_type = 'admin';
    if (isEnseignant()) $user_type = 'enseignant';
    if (isEtudiant()) $user_type = 'etudiant';
    
    $redirect_to = $redirect_pages[$user_type] ?? 'login.php';
    header("Location: $redirect_to?logout=success");
    exit();
}

// Récupérer le nom du rôle pour l'affichage
function getRoleName() {
    if (isAdmin()) {
        return "Administrateur";
    } elseif (isEnseignant()) {
        return "Enseignant";
    } elseif (isEtudiant()) {
        return "Étudiant";
    } else {
        return "Utilisateur";
    }
}

$dashboard_page = getDashboardPage();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion | Système de Gestion</title>
    <!-- Icônes Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #009688; /* Teal */
            --secondary-color: #FF9800; /* Orange */
            --light-teal: #e0f2f1;
            --light-orange: #fff3e0;
            --dark-teal: #00695c;
            --dark-orange: #ef6c00;
            --dark-color: #37474f;
            --light-color: #f5f5f5;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--light-teal) 0%, var(--light-orange) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .logout-container {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 150, 136, 0.2), 0 5px 15px rgba(255, 152, 0, 0.2);
            width: 100%;
            max-width: 500px;
            padding: 40px 35px;
            text-align: center;
            animation: fadeIn 0.6s ease-out;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .logout-container:hover {
            transform: translateY(-5px);
        }
        
        .logout-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-25px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .icon-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 120px;
            width: 120px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 50%;
            box-shadow: 0 10px 20px rgba(0, 150, 136, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 150, 136, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(0, 150, 136, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 150, 136, 0); }
        }
        
        .icon {
            font-size: 50px;
            color: white;
        }
        
        h1 {
            color: var(--dark-color);
            font-size: 32px;
            margin-bottom: 15px;
            font-weight: 700;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        p {
            color: #546e7a;
            margin-bottom: 25px;
            font-size: 17px;
            line-height: 1.6;
        }
        
        .user-info {
            background: var(--light-color);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: left;
            border-left: 5px solid var(--primary-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .user-info p {
            margin: 10px 0;
            display: flex;
            align-items: center;
            color: var(--dark-color);
        }
        
        .user-info i {
            margin-right: 12px;
            color: var(--primary-color);
            width: 22px;
            font-size: 18px;
        }
        
        .buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 25px;
        }
        
        button {
            padding: 14px 30px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 180px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .confirm-btn {
            background: var(--primary-color);
            color: white;
        }
        
        .confirm-btn:hover {
            background: var(--dark-teal);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 150, 136, 0.4);
        }
        
        .cancel-btn {
            background: var(--secondary-color);
            color: white;
        }
        
        .cancel-btn:hover {
            background: var(--dark-orange);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 152, 0, 0.4);
        }
        
        .footer-text {
            margin-top: 30px;
            font-size: 14px;
            color: #90a4ae;
        }
        
        @media (max-width: 576px) {
            .logout-container {
                padding: 30px 20px;
            }
            
            .buttons {
                flex-direction: column;
            }
            
            button {
                width: 100%;
            }
            
            h1 {
                font-size: 28px;
            }
            
            .icon-wrapper {
                height: 100px;
                width: 100px;
            }
            
            .icon {
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="icon-wrapper">
            <div class="icon">
                <i class="fas fa-door-open"></i>
            </div>
        </div>
        <h1>Confirmation de déconnexion</h1>
        
        <div class="user-info">
            <p><i class="fas fa-user-tag"></i> Rôle : <strong><?php echo getRoleName(); ?></strong></p>
            <?php if (isset($_SESSION['username'])): ?>
            <p><i class="fas fa-user"></i> Nom d'utilisateur : <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
            <?php endif; ?>
            <?php if (isset($_SESSION['email'])): ?>
            <p><i class="fas fa-envelope"></i> Email : <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong></p>
            <?php endif; ?>
        </div>
        
        <p>Êtes-vous sûr de vouloir vous déconnecter du système ?</p>
        
        <form method="POST" action="">
            <div class="buttons">
                <button type="submit" name="confirm_logout" value="1" class="confirm-btn">
                    <i class="fas fa-sign-out-alt"></i> Se déconnecter
                </button>
                <button type="button" onclick="stayHere()" class="cancel-btn">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
        
        <p class="footer-text"> PLATFORME D'APPRENTISAGE ISEP ABDOULAYE LY DE THIES © <?php echo date('Y'); ?></p>
    </div>

    <script>
        function stayHere() {
            // Rediriger vers le tableau de bord approprié
            window.location.href = "<?php echo $dashboard_page; ?>";
        }
    </script>
</body>
</html>