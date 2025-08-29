<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérification : utilisateur connecté et rôle enseignant (role_id = 2)
if (!isLoggedIn() || $_SESSION['role_id'] != 2) {
    header('Location: ../public/login.php');
    exit();
}

$teacher_name = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);

// Étudiants inscrits (jointure inscriptions → cours)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT inscriptions.student_id)
    FROM inscriptions
    JOIN cours ON inscriptions.course_id = cours.id
    WHERE cours.enseignant_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$students_count = $stmt->fetchColumn() ?: 0;

// Cours actifs (avec filtre sur status = 'active')
$stmt = $pdo->prepare("SELECT COUNT(*) FROM cours WHERE enseignant_id = ? AND status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$courses_count = $stmt->fetchColumn() ?: 0;

// Quiz publiés
$stmt = $pdo->prepare("SELECT COUNT(*) FROM quiz WHERE enseignant_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$quizzes_count = $stmt->fetchColumn() ?: 0;

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Tableau de Bord Enseignant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#009688',
                        accent: '#FF9800',
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gray-100 flex min-h-screen">

    <!-- Menu latéral -->
    <aside class="w-64 bg-primary text-white flex flex-col">
        <div class="p-6 text-2xl font-bold border-b border-white/20 flex items-center gap-2">
            <i data-feather="book" class="stroke-[2px]"></i>
            <span>Espace Enseignant</span>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="http://localhost/lms_isep/public/cours.php" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="book-open"></i> Gestion des cours
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="file-plus"></i> Ajout de contenus
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="help-circle"></i> Quiz
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="clipboard"></i> Devoirs
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="edit-3"></i> Correction & Feedback
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="trending-up"></i> Suivi des progrès
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="message-circle"></i> Forums
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="video"></i> Sessions live
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="tv"></i> Visioconférence & Partage
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="activity"></i> Chat & Sondages
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="film"></i> Enregistrements
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded hover:bg-accent transition">
                <i data-feather="send"></i> Messagerie
            </a>
        </nav>
        <div class="p-4 border-t border-white/20">
            <a href="../public/logout.php" 
               class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition">
                <i data-feather="log-out"></i> Déconnexion
            </a>
        </div>
    </aside>

    <!-- Contenu principal -->
    <main class="flex-1 p-8 overflow-auto">
        <!-- Barre supérieure -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-primary">Bienvenue, <span class="text-accent"><?= htmlspecialchars($_SESSION['prenom']) ?></span></h1>
            <div class="flex items-center gap-3">
                <span class="text-gray-600">Enseignant connecté</span>
                <div class="w-10 h-10 rounded-full bg-accent flex items-center justify-center text-white font-bold text-lg">
                    <?= strtoupper(substr($_SESSION['prenom'], 0, 1)) ?>
                </div>
            </div>
        </div>

        <!-- Cartes statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Étudiants inscrits -->
            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition cursor-default">
                <div class="flex items-center gap-4">
                    <div class="bg-primary p-3 rounded-full text-white">
                        <i data-feather="users"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold"><?= $students_count ?></h2>
                        <p class="text-gray-500">Étudiants inscrits</p>
                    </div>
                </div>
            </div>

            <!-- Cours actifs -->
            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition cursor-default">
                <div class="flex items-center gap-4">
                    <div class="bg-accent p-3 rounded-full text-white">
                        <i data-feather="layers"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold"><?= $courses_count ?></h2>
                        <p class="text-gray-500">Cours actifs</p>
                    </div>
                </div>
            </div>

            <!-- Quiz publiés -->
            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition cursor-default">
                <div class="flex items-center gap-4">
                    <div class="bg-primary p-3 rounded-full text-white">
                        <i data-feather="file-text"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold"><?= $quizzes_count ?></h2>
                        <p class="text-gray-500">Quiz publiés</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section raccourcis -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php
            $features = [
                ["Gestion des cours", "Créer, modifier et organiser vos cours.", "book-open"],
                ["Ajout de contenus multimédias", "Importer vidéos, audios et documents.", "file-plus"],
                ["Quiz", "Concevoir et publier des quiz.", "help-circle"],
                ["Devoirs", "Attribuer, corriger et noter les devoirs.", "clipboard"],
                ["Feedback", "Donner un retour aux étudiants.", "edit-3"],
                ["Suivi des progrès", "Analyser les performances.", "trending-up"],
                ["Forums", "Gérer les discussions.", "message-circle"],
                ["Sessions live", "Planifier vos visioconférences.", "video"],
                ["Partage d’écran", "Partager documents et écran.", "tv"],
                ["Chat & Sondages", "Interagir en temps réel.", "activity"],
                ["Enregistrements", "Revoir les sessions passées.", "film"],
                ["Messagerie", "Communiquer avec les étudiants.", "send"],
            ];

            foreach ($features as $f) {
                echo '
                <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition cursor-pointer">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="bg-accent p-3 rounded-full text-white">
                            <i data-feather="'.$f[2].'"></i>
                        </div>
                        <h3 class="text-lg font-semibold">'.$f[0].'</h3>
                    </div>
                    <p class="text-gray-500">'.$f[1].'</p>
                </div>';
            }
            ?>
        </div>
    </main>

    <script>
        feather.replace();
    </script>
</body>
</html>
