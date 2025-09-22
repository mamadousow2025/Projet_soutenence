<?php
session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND actif = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['prenom'] = $user['prenom'];

            if ($user['role_id'] == 1) {
                header("Location: etudiant_dashboard.php");
            } elseif ($user['role_id'] == 2) {
                header("Location: teacher_dashboard.php");
            } elseif ($user['role_id'] == 3) {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | LMS ISEP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #009688;
            --primary-dark: #00796b;
            --secondary-color: #2c3e50;
            --light-gray: #f5f5f5;
            --text-gray: #7f8c8d;
            --error-color: #c62828;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            background: url('../images/background-login.jpg') no-repeat center center fixed;
            background-size: cover;
            padding: 20px;
        }
        
        /* Overlay pour améliorer la lisibilité */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(245, 245, 245, 0.9) 100%);
            z-index: -1;
        }
        
        .login-container {
            display: flex;
            max-width: 1100px;
            width: 100%;
            height: 600px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
        }
        
        .illustration-section {
            flex: 1.2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #2c3e50;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .carousel {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        
        .carousel-inner {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .carousel-item {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            background-size: cover;
            background-position: center;
        }
        
        .carousel-item.active {
            opacity: 1;
        }
        
        .carousel-controls {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            z-index: 10;
        }
        
        .carousel-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            margin: 0 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .carousel-dot.active {
            background: white;
            transform: scale(1.3);
        }
        
        .illustration-section::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            animation: float 15s infinite ease-in-out;
            z-index: 1;
        }
        
        .illustration-section::after {
            content: "";
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            bottom: -80px;
            right: -80px;
            animation: float 12s infinite ease-in-out reverse;
            z-index: 1;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
            }
            33% {
                transform: translate(30px, 30px) rotate(120deg);
            }
            66% {
                transform: translate(-20px, 40px) rotate(240deg);
            }
        }
        
        .illustration-content {
            text-align: center;
            z-index: 2;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.85);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .illustration-image {
            width: 120px;
            height: 120px;
            margin-bottom: 20px;
            filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.2));
        }
        
        .illustration-content h2 {
            font-weight: 700;
            margin-top: 20px;
            font-size: 1.8rem;
            line-height: 1.3;
            margin-bottom: 10px;
            color: var(--primary-dark);
        }
        
        .illustration-content p {
            margin-top: 15px;
            font-size: 1rem;
            opacity: 0.9;
            line-height: 1.6;
            color: var(--secondary-color);
        }
        
        .form-section {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
            position: relative;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            width: 140px;
            height: 140px;
            object-fit: contain;
            transition: all 0.3s ease;
            display: block;
            margin: 0 auto;
        }
        
        .logo:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 5px 10px rgba(0, 150, 136, 0.3));
        }
        
        .form-title {
            color: var(--secondary-color);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 5px;
            text-align: center;
        }
        
        .form-subtitle {
            color: var(--text-gray);
            margin-bottom: 35px;
            font-size: 1rem;
            text-align: center;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .form-input {
            width: 100%;
            padding: 16px 15px 16px 50px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--light-gray);
        }
        
        .form-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 150, 136, 0.2);
            outline: none;
            background: white;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 150, 136, 0.4);
            letter-spacing: 0.5px;
            margin-top: 10px;
            width: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 150, 136, 0.5);
        }
        
        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(0, 150, 136, 0.4);
        }
        
        .error-message {
            background: #ffebee;
            color: var(--error-color);
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            border-left: 4px solid var(--error-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-message i {
            font-size: 1.1rem;
        }
        
        .additional-links {
            text-align: center;
            margin-top: 25px;
            font-size: 0.9rem;
            color: var(--text-gray);
        }
        
        .additional-links a {
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .additional-links a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-gray);
        }
        
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                height: auto;
                max-width: 500px;
            }
            
            .illustration-section {
                padding: 30px;
                order: 2;
                min-height: 300px;
            }
            
            .form-section {
                padding: 40px 30px;
                order: 1;
            }
            
            .illustration-content {
                max-width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 10px;
            }
            
            .login-container {
                border-radius: 15px;
            }
            
            .form-section {
                padding: 30px 20px;
            }
            
            .illustration-section {
                padding: 25px 20px;
            }
            
            .form-title {
                font-size: 1.7rem;
            }
            
            .logo {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="illustration-section">
            <!-- Carrousel d'images -->
            <div class="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active" style="background-image: url('../images/image2.png');"></div>
                    <div class="carousel-item" style="background-image: url('../images/img3.png');"></div>
                    <div class="carousel-item" style="background-image: url('../images/image4.jpg');"></div>
                    <div class="carousel-item" style="background-image: url('../images/image5.jpg');"></div>
                    
                    
                    
                    
                </div>
                <div class="carousel-controls">
                    <div class="carousel-dot active" data-index="0"></div>
                    <div class="carousel-dot" data-index="1"></div>
                    <div class="carousel-dot" data-index="2"></div>
                    <div class="carousel-dot" data-index="3"></div>
                </div>
            </div>
            
            <div class="illustration-content">
                <h2>ISEP ABDOULAYE LY<br>DE THIES</h2>
                <p>Plateforme d'apprentissage en ligne de qualité supérieure</p>
            </div>
        </div>
        
        <div class="form-section">
            <div class="logo-container">
                <img src="/lms_isep/assets/images/mon_logoelhadji.png" alt="Logo LMS ISEP" class="logo">
            </div>
            
            <h1 class="form-title">Connexion à votre compte</h1>
            <p class="form-subtitle">Entrez vos identifiants pour accéder à votre espace</p>
            
            <?php if (!empty($error)) : ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        required 
                        value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" 
                        placeholder="Adresse email"
                        class="form-input"
                    />
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        required 
                        placeholder="Mot de passe"
                        class="form-input"
                    />
                    <span class="password-toggle" id="passwordToggle">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>
         

    <script>
        // Fonctionnalité pour afficher/masquer le mot de passe
        document.getElementById('passwordToggle').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Fonctionnalité du carrousel d'images
        document.addEventListener('DOMContentLoaded', function() {
            const carouselItems = document.querySelectorAll('.carousel-item');
            const dots = document.querySelectorAll('.carousel-dot');
            let currentIndex = 0;
            
            // Fonction pour changer d'image
            function showSlide(index) {
                // Masquer toutes les images
                carouselItems.forEach(item => item.classList.remove('active'));
                dots.forEach(dot => dot.classList.remove('active'));
                
                // Afficher l'image sélectionnée
                carouselItems[index].classList.add('active');
                dots[index].classList.add('active');
                
                currentIndex = index;
            }
            
            // Ajouter les événements aux points de contrôle
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    showSlide(index);
                    resetTimer();
                });
            });
            
            // Fonction pour passer à l'image suivante
            function nextSlide() {
                let nextIndex = (currentIndex + 1) % carouselItems.length;
                showSlide(nextIndex);
            }
            
            // Défilement automatique
            let carouselTimer = setInterval(nextSlide, 5000);
            
            // Réinitialiser le timer
            function resetTimer() {
                clearInterval(carouselTimer);
                carouselTimer = setInterval(nextSlide, 5000);
            }
            
            // Arrêter le défilement automatique au survol
            const carousel = document.querySelector('.carousel');
            carousel.addEventListener('mouseenter', () => {
                clearInterval(carouselTimer);
            });
            
            // Reprendre le défilement automatique quand la souris quitte
            carousel.addEventListener('mouseleave', () => {
                carouselTimer = setInterval(nextSlide, 5000);
            });
        });
    </script>
</body>
</html>