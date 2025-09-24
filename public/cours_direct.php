<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérification : utilisateur connecté
if (!isLoggedIn()) {
    header('Location: ../public/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);
$user_role = $_SESSION['role_id'];

// Vérifier et créer la table cours_direct si nécessaire
try {
    $pdo->query("SELECT 1 FROM cours_direct LIMIT 1");
} catch (PDOException $e) {
    // Créer la table si elle n'existe pas
    $create_table = "
    CREATE TABLE cours_direct (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        titre VARCHAR(255) NOT NULL,
        description TEXT,
        date_heure DATETIME NOT NULL,
        lien_visio VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        cours_id INT NOT NULL,
        duree INT NOT NULL COMMENT 'Durée en minutes',
        max_participants INT DEFAULT 50,
        statut ENUM('planifie', 'en_cours', 'termine', 'annule') DEFAULT 'planifie',
        FOREIGN KEY (cours_id) REFERENCES cours(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($create_table);
}

// Interface différente selon le rôle
if ($user_role == 2) { // Enseignant
    $teacher_id = $user_id;

    // Récupérer les cours de l'enseignant pour le formulaire
    $courses_stmt = $pdo->prepare("SELECT id, titre FROM cours WHERE enseignant_id = ? AND status = 'active'");
    $courses_stmt->execute([$teacher_id]);
    $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les sessions de cours en direct planifiées
    $sessions_stmt = $pdo->prepare("
        SELECT cd.*, c.titre as course_titre 
        FROM cours_direct cd 
        JOIN cours c ON cd.cours_id = c.id 
        WHERE cd.teacher_id = ? 
        ORDER BY cd.date_heure DESC
    ");
    $sessions_stmt->execute([$teacher_id]);
    $sessions = $sessions_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Traitement du formulaire de planification de session
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['planifier_session'])) {
            $cours_id = $_POST['cours_id'];
            $titre = $_POST['titre'];
            $description = $_POST['description'] ?? '';
            $date_heure = $_POST['date_heure'];
            $duree = $_POST['duree'];
            $max_participants = $_POST['max_participants'] ?? 50;
            
            // Validation des données
            if (!empty($titre) && !empty($cours_id) && !empty($date_heure) && !empty($duree)) {
                // Générer un lien de visioconférence unique
                $lien_visio = "https://meet.jit.si/" . uniqid('class_');
                
                $stmt = $pdo->prepare("
                    INSERT INTO cours_direct (teacher_id, cours_id, titre, description, date_heure, duree, max_participants, statut, lien_visio) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'planifie', ?)
                ");
                
                if ($stmt->execute([$teacher_id, $cours_id, $titre, $description, $date_heure, $duree, $max_participants, $lien_visio])) {
                    $success_message = "Session planifiée avec succès!";
                    
                    // Recharger les sessions
                    $sessions_stmt->execute([$teacher_id]);
                    $sessions = $sessions_stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error_message = "Erreur lors de la planification de la session.";
                }
            } else {
                $error_message = "Veuillez remplir tous les champs obligatoires.";
            }
        } 
        elseif (isset($_POST['demarrer_session'])) {
            $session_id = $_POST['session_id'];
            
            // Vérifier que la session appartient à l'enseignant
            $check_stmt = $pdo->prepare("
                SELECT cd.* 
                FROM cours_direct cd 
                WHERE cd.id = ? AND cd.teacher_id = ?
            ");
            $check_stmt->execute([$session_id, $teacher_id]);
            $session = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session) {
                // Mettre à jour le statut de la session
                $stmt = $pdo->prepare("UPDATE cours_direct SET statut = 'en_cours' WHERE id = ?");
                if ($stmt->execute([$session_id])) {
                    // Rediriger vers la page de la session en cours
                    header("Location: " . $session['lien_visio']);
                    exit();
                } else {
                    $error_message = "Erreur lors du démarrage de la session.";
                }
            } else {
                $error_message = "Session non trouvée ou non autorisée.";
            }
        }
        elseif (isset($_POST['annuler_session'])) {
            $session_id = $_POST['session_id'];
            
            // Vérifier que la session appartient à l'enseignant
            $check_stmt = $pdo->prepare("
                SELECT cd.* 
                FROM cours_direct cd 
                WHERE cd.id = ? AND cd.teacher_id = ?
            ");
            $check_stmt->execute([$session_id, $teacher_id]);
            $session = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session) {
                // Mettre à jour le statut de la session
                $stmt = $pdo->prepare("UPDATE cours_direct SET statut = 'annule' WHERE id = ?");
                if ($stmt->execute([$session_id])) {
                    $success_message = "Session annulée avec succès!";
                    
                    // Recharger les sessions
                    $sessions_stmt->execute([$teacher_id]);
                    $sessions = $sessions_stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error_message = "Erreur lors de l'annulation de la session.";
                }
            } else {
                $error_message = "Session non trouvée ou non autorisée.";
            }
        }
    }
} else { // Étudiant ou autre rôle
    // Récupérer la filière de l'étudiant
    $filiere_stmt = $pdo->prepare("SELECT filiere_id FROM users WHERE id = ?");
    $filiere_stmt->execute([$user_id]);
    $user_filiere = $filiere_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_filiere && isset($user_filiere['filiere_id'])) {
        $filiere_id = $user_filiere['filiere_id'];
        
        // Récupérer les sessions disponibles pour l'étudiant selon sa filière
        $sessions_stmt = $pdo->prepare("
            SELECT cd.*, c.titre as course_titre, u.prenom, u.nom, f.nom as filiere_nom
            FROM cours_direct cd 
            JOIN cours c ON cd.cours_id = c.id 
            JOIN users u ON cd.teacher_id = u.id
            JOIN filieres f ON c.filiere_id = f.id
            WHERE cd.statut != 'annule' 
            AND c.filiere_id = ?
            ORDER BY cd.date_heure DESC
        ");
        
        $sessions_stmt->execute([$filiere_id]);
        $sessions = $sessions_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Si l'étudiant n'a pas de filière définie, on ne montre aucun cours
        $sessions = [];
        $error_message = "Vous n'êtes inscrit dans aucune filière. Veuillez contacter l'administration.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cours en Direct - Plateforme E-Learning</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        teal: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#009688',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        },
                        orange: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#FF9800',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                        },
                        success: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                        },
                        danger: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-in': 'slideIn 0.3s ease-out',
                        'bounce-in': 'bounceIn 0.6s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideIn: {
                            '0%': { transform: 'translateY(-10px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        bounceIn: {
                            '0%': { transform: 'scale(0.3)', opacity: '0' },
                            '50%': { transform: 'scale(1.05)' },
                            '70%': { transform: 'scale(0.9)' },
                            '100%': { transform: 'scale(1)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #009688 0%, #FF9800 100%);
        }
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-teal-50 to-orange-50 min-h-screen font-inter">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-xl border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo et titre -->
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-teal-500 to-teal-700 rounded-xl flex items-center justify-center">
                            <i class="fas fa-graduation-cap text-white text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h1 class="text-xl font-bold text-gray-900">E-Learning ISEP ABDOULAYE LY</h1>
                            <p class="text-xs text-gray-500">Cours en Direct</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="etudiant_dashboard.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-700 hover:text-teal-600 hover:bg-teal-50 transition-all duration-200">
                        <i class="fas fa-tachometer-alt text-lg"></i>
                        <span class="font-medium">Tableau de Bord</span>
                    </a>
                    
                    <a href="etudiant_dashboard.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-700 hover:text-teal-600 hover:bg-teal-50 transition-all duration-200">
                        <i class="fas fa-book-open text-lg"></i>
                        <span class="font-medium">Mes Cours</span>
                    </a>

                    <div class="h-6 w-px bg-gray-300"></div>

                    <!-- Profil utilisateur -->
                    <div class="relative group">
                        <div class="flex items-center space-x-3 cursor-pointer">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                <?= strtoupper(substr($_SESSION['prenom'], 0, 1) . substr($_SESSION['nom'], 0, 1)) ?>
                            </div>
                            <div class="hidden lg:block">
                                <p class="text-sm font-semibold text-gray-900"><?= $user_name ?></p>
                                <p class="text-xs text-gray-500"><?= $user_role == 2 ? 'Enseignant' : 'Étudiant' ?></p>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-xs group-hover:text-gray-600"></i>
                        </div>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform group-hover:translate-y-0 translate-y-2">
                         
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="../public/logout.php" class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <i class="fas fa-sign-out-alt mr-3 text-red-500"></i>
                                    Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" class="text-gray-500 hover:text-gray-600 focus:outline-none focus:text-gray-600" onclick="toggleMobileMenu()">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t border-gray-100">
            <div class="px-4 py-3 space-y-2">
                <a href="etudiant_dashboard.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-teal-50">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de Bord</span>
                </a>
                <a href="../courses/mes_cours.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-teal-50">
                    <i class="fas fa-book-open"></i>
                    <span>Mes Cours</span>
                </a>
                <div class="border-t border-gray-200 my-2"></div>
                <a href="../profile/profile.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-user-circle"></i>
                    <span>Mon Profil</span>
                </a>
                <a href="../public/logout.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-red-600 hover:bg-red-50">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- En-tête de page -->
        <div class="mb-8 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-video text-teal-600 mr-4"></i>
                        Cours en Direct
                    </h1>
                    <p class="text-lg text-gray-600">
                        <?= $user_role == 2 ? 'Gérez vos sessions de cours en direct' : 'Rejoignez les sessions de cours en direct' ?>
                    </p>
                </div>
                <div class="hidden lg:flex items-center space-x-4">
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-green-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-users text-white text-lg"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Sessions actives</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?= count(array_filter($sessions, function($s) { return $s['statut'] === 'en_cours'; })) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages de notification -->
        <?php if (isset($success_message)): ?>
            <div class="mb-6 animate-slide-in">
                <div class="bg-success-50 border-l-4 border-success-500 p-4 rounded-r-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-success-500 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-success-800 font-medium"><?= $success_message ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="mb-6 animate-slide-in">
                <div class="bg-danger-50 border-l-4 border-danger-500 p-4 rounded-r-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-danger-500 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-danger-800 font-medium"><?= $error_message ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($user_role == 2): ?>
        <!-- Interface Enseignant -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <!-- Formulaire de planification -->
            <div class="xl:col-span-1">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden card-hover animate-fade-in">
                    <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-plus-circle mr-3"></i>
                            Nouvelle Session
                        </h2>
                        <p class="text-teal-100 text-sm mt-1">Planifiez un cours en direct</p>
                    </div>
                    
                    <div class="p-6">
                        <form method="POST" class="space-y-5">
                            <div>
                                <label for="cours_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-book text-teal-600 mr-2"></i>
                                    Cours *
                                </label>
                                <select id="cours_id" name="cours_id" required 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white">
                                    <option value="">Sélectionner un cours</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['titre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="titre" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-heading text-teal-600 mr-2"></i>
                                    Titre de la session *
                                </label>
                                <input type="text" id="titre" name="titre" required 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white"
                                       placeholder="Ex: Introduction à la programmation">
                            </div>
                            
                            <div>
                                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-align-left text-teal-600 mr-2"></i>
                                    Description
                                </label>
                                <textarea id="description" name="description" rows="3"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white resize-none"
                                       placeholder="Description détaillée de la session..."></textarea>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="date_heure" class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="far fa-calendar-alt text-teal-600 mr-2"></i>
                                        Date et heure *
                                    </label>
                                    <input type="datetime-local" id="date_heure" name="date_heure" required 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white">
                                </div>
                                
                                <div>
                                    <label for="duree" class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="far fa-clock text-teal-600 mr-2"></i>
                                        Durée (min) *
                                    </label>
                                    <input type="number" id="duree" name="duree" required min="15" value="60"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white">
                                </div>
                            </div>
                            
                            <div>
                                <label for="max_participants" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-users text-teal-600 mr-2"></i>
                                    Participants max
                                </label>
                                <input type="number" id="max_participants" name="max_participants" min="1" max="100" value="50"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white">
                            </div>
                            
                            <div class="pt-2">
                                <button type="submit" name="planifier_session" 
                                        class="w-full bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 hover:shadow-lg flex items-center justify-center">
                                    <i class="fas fa-plus-circle mr-2"></i>
                                    Planifier la session
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sessions planifiées -->
            <div class="xl:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden card-hover animate-fade-in">
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-calendar-check mr-3"></i>
                            Mes Sessions Planifiées
                        </h2>
                        <p class="text-orange-100 text-sm mt-1"><?= count($sessions) ?> session(s) au total</p>
                    </div>
                    
                    <div class="p-6">
                        <?php if (count($sessions) > 0): ?>
                            <div class="space-y-4 max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                                <?php foreach ($sessions as $session): 
                                    $now = new DateTime();
                                    $session_date = new DateTime($session['date_heure']);
                                    $is_past = $session_date < $now;
                                    $is_soon = !$is_past && ($session_date->diff($now)->h < 24);
                                ?>
                                    <div class="border border-gray-200 rounded-xl p-5 hover:shadow-md transition-all duration-200 <?= $session['statut'] === 'annule' ? 'bg-gray-50 opacity-75' : 'bg-white' ?>">
                                        <div class="flex justify-between items-start mb-3">
                                            <h3 class="font-bold text-lg text-gray-900">
                                                <i class="fas fa-video text-teal-600 mr-2"></i>
                                                <?= htmlspecialchars($session['titre']) ?>
                                            </h3>
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                                <?= $session['statut'] === 'planifie' ? 'bg-blue-100 text-blue-800' : '' ?>
                                                <?= $session['statut'] === 'en_cours' ? 'bg-green-100 text-green-800' : '' ?>
                                                <?= $session['statut'] === 'termine' ? 'bg-gray-100 text-gray-800' : '' ?>
                                                <?= $session['statut'] === 'annule' ? 'bg-red-100 text-red-800' : '' ?>">
                                                <i class="fas 
                                                    <?= $session['statut'] === 'planifie' ? 'fa-clock' : '' ?>
                                                    <?= $session['statut'] === 'en_cours' ? 'fa-play-circle' : '' ?>
                                                    <?= $session['statut'] === 'termine' ? 'fa-check-circle' : '' ?>
                                                    <?= $session['statut'] === 'annule' ? 'fa-times-circle' : '' ?>
                                                    mr-1"></i>
                                                <?= ucfirst($session['statut']) ?>
                                            </span>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                                            <p class="text-gray-600 flex items-center">
                                                <i class="fas fa-book-open text-teal-600 mr-2"></i>
                                                <?= htmlspecialchars($session['course_titre']) ?>
                                            </p>
                                            <p class="text-gray-600 flex items-center">
                                                <i class="far fa-calendar-alt text-teal-600 mr-2"></i>
                                                <?= date('d/m/Y à H:i', strtotime($session['date_heure'])) ?>
                                            </p>
                                            <p class="text-gray-600 flex items-center">
                                                <i class="far fa-clock text-teal-600 mr-2"></i>
                                                <?= $session['duree'] ?> minutes
                                            </p>
                                            <p class="text-gray-600 flex items-center">
                                                <i class="fas fa-users text-teal-600 mr-2"></i>
                                                Max. <?= $session['max_participants'] ?> participants
                                            </p>
                                        </div>
                                        
                                        <?php if (!empty($session['description'])): ?>
                                            <p class="text-gray-700 mb-4 p-3 bg-gray-50 rounded-lg">
                                                <i class="fas fa-align-left text-teal-600 mr-2"></i>
                                                <?= nl2br(htmlspecialchars($session['description'])) ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="flex justify-end space-x-3">
                                            <?php if ($session['statut'] === 'planifie'): ?>
                                                <form method="POST" class="m-0">
                                                    <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                                                    <button type="submit" name="demarrer_session" 
                                                            class="bg-gradient-to-r from-success-500 to-success-600 hover:from-success-600 hover:to-success-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center transition-all duration-200 transform hover:scale-105">
                                                        <i class="fas fa-play mr-2"></i>
                                                        Démarrer
                                                    </button>
                                                </form>
                                                <form method="POST" class="m-0">
                                                    <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                                                    <button type="submit" name="annuler_session" 
                                                            class="bg-gradient-to-r from-danger-500 to-danger-600 hover:from-danger-600 hover:to-danger-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center transition-all duration-200 transform hover:scale-105">
                                                        <i class="fas fa-times mr-2"></i>
                                                        Annuler
                                                    </button>
                                                </form>
                                            <?php elseif ($session['statut'] === 'en_cours'): ?>
                                                <a href="<?= $session['lien_visio'] ?>" target="_blank"
                                                   class="bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center transition-all duration-200 transform hover:scale-105">
                                                    <i class="fas fa-video mr-2"></i>
                                                    Rejoindre
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-calendar-times text-4xl text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune session planifiée</h3>
                                <p class="text-gray-500 mb-4">Commencez par planifier votre première session de cours en direct.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section fonctionnalités pour enseignants -->
        <div class="mt-8 animate-fade-in">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden card-hover">
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-magic mr-3"></i>
                        Fonctionnalités Avancées
                    </h2>
                    <p class="text-purple-100 text-sm mt-1">Outils professionnels pour vos cours en direct</p>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="text-center p-6 bg-gradient-to-br from-teal-50 to-teal-100 rounded-xl border border-teal-200 card-hover">
                            <div class="w-16 h-16 bg-gradient-to-r from-teal-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-video text-white text-2xl"></i>
                            </div>
                            <h3 class="font-bold text-gray-900 mb-2">Visioconférence HD</h3>
                            <p class="text-sm text-gray-600">Qualité vidéo haute définition pour une expérience immersive</p>
                        </div>
                        
                        <div class="text-center p-6 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl border border-orange-200 card-hover">
                            <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-comments text-white text-2xl"></i>
                            </div>
                            <h3 class="font-bold text-gray-900 mb-2">Chat Interactif</h3>
                            <p class="text-sm text-gray-600">Communication en temps réel avec vos étudiants</p>
                        </div>
                        
                        <div class="text-center p-6 bg-gradient-to-br from-teal-50 to-teal-100 rounded-xl border border-teal-200 card-hover">
                            <div class="w-16 h-16 bg-gradient-to-r from-teal-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-desktop text-white text-2xl"></i>
                            </div>
                            <h3 class="font-bold text-gray-900 mb-2">Partage d'Écran</h3>
                            <p class="text-sm text-gray-600">Présentez vos supports de cours en direct</p>
                        </div>
                        
                        <div class="text-center p-6 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl border border-orange-200 card-hover">
                            <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-chalkboard text-white text-2xl"></i>
                            </div>
                            <h3 class="font-bold text-gray-900 mb-2">Tableau Blanc</h3>
                            <p class="text-sm text-gray-600">Illustrez vos concepts avec un tableau interactif</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Interface Étudiant -->
        <div class="animate-fade-in">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden card-hover">
                <div class="bg-gradient-to-r from-teal-600 to-orange-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-play-circle mr-3"></i>
                        Sessions Disponibles
                        <?php if (isset($filiere_id) && !empty($sessions)): ?>
                            <span class="ml-3 text-sm font-normal text-white bg-white bg-opacity-20 px-3 py-1 rounded-full">
                                <?= htmlspecialchars($sessions[0]['filiere_nom'] ?? 'Votre filière') ?>
                            </span>
                        <?php endif; ?>
                    </h2>
                    <p class="text-white text-opacity-80 text-sm mt-1"><?= count($sessions) ?> session(s) disponible(s)</p>
                </div>
                
                <div class="p-6">
                    <?php if (count($sessions) > 0): ?>
                        <div class="space-y-6">
                            <?php foreach ($sessions as $session): 
                                $now = new DateTime();
                                $session_date = new DateTime($session['date_heure']);
                                $is_past = $session_date < $now;
                                $can_join = ($session['statut'] === 'en_cours') || 
                                           ($session['statut'] === 'planifie' && $is_past);
                            ?>
                                <div class="border border-gray-200 rounded-xl p-6 hover:shadow-lg transition-all duration-300 <?= $session['statut'] === 'annule' ? 'bg-gray-50 opacity-75' : 'bg-white' ?> card-hover">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 class="font-bold text-xl text-gray-900 mb-2">
                                                <i class="fas fa-video text-teal-600 mr-3"></i>
                                                <?= htmlspecialchars($session['titre']) ?>
                                            </h3>
                                            <div class="flex items-center space-x-4 text-sm text-gray-600">
                                                <span class="flex items-center">
                                                    <i class="fas fa-book-open text-teal-600 mr-2"></i>
                                                    <?= htmlspecialchars($session['course_titre']) ?>
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="fas fa-user-graduate text-teal-600 mr-2"></i>
                                                    <?= htmlspecialchars($session['prenom'] . ' ' . $session['nom']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <span class="px-4 py-2 text-sm font-semibold rounded-full 
                                            <?= $session['statut'] === 'planifie' ? 'bg-blue-100 text-blue-800' : '' ?>
                                            <?= $session['statut'] === 'en_cours' ? 'bg-green-100 text-green-800' : '' ?>
                                            <?= $session['statut'] === 'termine' ? 'bg-gray-100 text-gray-800' : '' ?>
                                            <?= $session['statut'] === 'annule' ? 'bg-red-100 text-red-800' : '' ?>">
                                            <i class="fas 
                                                <?= $session['statut'] === 'planifie' ? 'fa-clock' : '' ?>
                                                <?= $session['statut'] === 'en_cours' ? 'fa-play-circle' : '' ?>
                                                <?= $session['statut'] === 'termine' ? 'fa-check-circle' : '' ?>
                                                <?= $session['statut'] === 'annule' ? 'fa-times-circle' : '' ?>
                                                mr-2"></i>
                                            <?= ucfirst($session['statut']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <div class="flex items-center text-gray-600">
                                            <i class="far fa-calendar-alt text-teal-600 mr-3"></i>
                                            <span><?= date('d/m/Y à H:i', strtotime($session['date_heure'])) ?></span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <i class="far fa-clock text-teal-600 mr-3"></i>
                                            <span><?= $session['duree'] ?> minutes</span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-users text-teal-600 mr-3"></i>
                                            <span>Max. <?= $session['max_participants'] ?> participants</span>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($session['description'])): ?>
                                        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                                            <p class="text-gray-700">
                                                <i class="fas fa-align-left text-teal-600 mr-2"></i>
                                                <?= nl2br(htmlspecialchars($session['description'])) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex justify-end">
                                        <?php if ($session['statut'] === 'en_cours'): ?>
                                            <a href="<?= $session['lien_visio'] ?>" target="_blank"
                                               class="bg-gradient-to-r from-success-500 to-success-600 hover:from-success-600 hover:to-success-700 text-white px-6 py-3 rounded-xl font-semibold flex items-center transition-all duration-200 transform hover:scale-105 hover:shadow-lg">
                                                <i class="fas fa-video mr-2"></i>
                                                Rejoindre la Session
                                            </a>
                                        <?php elseif ($session['statut'] === 'planifie'): ?>
                                            <?php if ($is_past): ?>
                                                <a href="<?= $session['lien_visio'] ?>" target="_blank"
                                                   class="bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white px-6 py-3 rounded-xl font-semibold flex items-center transition-all duration-200 transform hover:scale-105 hover:shadow-lg">
                                                    <i class="fas fa-video mr-2"></i>
                                                    Rejoindre la Session
                                                </a>
                                            <?php else: ?>
                                                <span class="bg-gray-100 text-gray-600 px-6 py-3 rounded-xl font-medium flex items-center">
                                                    <i class="fas fa-clock mr-2"></i>
                                                    Session pas encore commencée
                                                </span>
                                            <?php endif; ?>
                                        <?php elseif ($session['statut'] === 'termine'): ?>
                                            <span class="text-gray-500 italic flex items-center">
                                                <i class="fas fa-check-circle mr-2"></i>
                                                Session terminée
                                            </span>
                                        <?php elseif ($session['statut'] === 'annule'): ?>
                                            <span class="text-red-500 italic flex items-center">
                                                <i class="fas fa-times-circle mr-2"></i>
                                                Session annulée
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-16">
                            <div class="w-32 h-32 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-calendar-times text-6xl text-gray-400"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">Aucune session disponible</h3>
                            <p class="text-gray-500 text-lg mb-6">Aucun cours en direct n'est planifié pour votre filière pour le moment.</p>
                            <div class="flex justify-center space-x-4">
                                <a href="../dashboard/dashboard.php" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Retour au tableau de bord
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
   

    <script>
        // Fonction pour formater la date et l'heure locales
        function formatLocalDateTime() {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            return now.toISOString().slice(0, 16);
        }
        
        // Toggle mobile menu
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        }
        
        <?php if ($user_role == 2): ?>
        // Définir la date et heure minimales (maintenant)
        document.getElementById('date_heure').min = formatLocalDateTime();
        
        // Notification pour les sessions à venir
        function checkUpcomingSessions() {
            const now = new Date();
            const oneHourFromNow = new Date(now.getTime() + 60 * 60000);
            
            <?php foreach ($sessions as $session): ?>
                <?php if ($session['statut'] === 'planifie'): ?>
                    const sessionDate = new Date('<?= str_replace(' ', 'T', $session['date_heure']) ?>');
                    if (sessionDate > now && sessionDate < oneHourFromNow) {
                        if (Notification.permission === 'granted') {
                            new Notification('Session à venir', {
                                body: 'Votre session "<?= $session['titre'] ?>" commence bientôt!',
                                icon: '/path/to/icon.png'
                            });
                        } else if (Notification.permission !== 'denied') {
                            Notification.requestPermission().then(permission => {
                                if (permission === 'granted') {
                                    new Notification('Session à venir', {
                                        body: 'Votre session "<?= $session['titre'] ?>" commence bientôt!',
                                        icon: '/path/to/icon.png'
                                    });
                                }
                            });
                        }
                    }
                <?php endif; ?>
            <?php endforeach; ?>
        }
        
        // Demander la permission pour les notifications
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        
        // Vérifier les sessions à venir toutes les 5 minutes
        setInterval(checkUpcomingSessions, 300000);
        checkUpcomingSessions(); // Vérifier au chargement de la page
        <?php endif; ?>

        // Animation au scroll
        window.addEventListener('scroll', function() {
            const elements = document.querySelectorAll('.animate-fade-in');
            elements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 150;
                
                if (elementTop < window.innerHeight - elementVisible) {
                    element.classList.add('opacity-100');
                }
            });
        });

        // Smooth scrolling pour les liens d'ancrage
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</body>
</html>