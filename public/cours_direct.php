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
        FOREIGN KEY (teacher_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
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
    // Récupérer les sessions disponibles pour l'étudiant
   $sessions_stmt = $pdo->prepare("
    SELECT cd.*, c.titre as course_titre, u.prenom, u.nom
    FROM cours_direct cd 
    JOIN cours c ON cd.cours_id = c.id 
    JOIN users u ON cd.teacher_id = u.id
    WHERE cd.statut != 'annule'
    ORDER BY cd.date_heure DESC
");

    $sessions_stmt->execute();
    $sessions = $sessions_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cours en Direct - <?= $user_role == 2 ? 'Enseignant' : 'Étudiant' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                        },
                        accent: {
                            50: '#fffbeb',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-primary-700 text-white shadow-lg">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-video text-accent-500 text-2xl"></i>
                <span class="text-xl font-bold">E-Learning</span>
            </div>
            
            <div class="flex items-center space-x-4">
                <span class="hidden md:inline"><?= $user_name ?> (<?= $user_role == 2 ? 'Enseignant' : 'Étudiant' ?>)</span>
                <div class="relative">
                    <div class="w-10 h-10 rounded-full bg-accent-500 flex items-center justify-center text-white font-bold text-lg cursor-pointer">
                        <?= strtoupper(substr($_SESSION['prenom'], 0, 1)) ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="container mx-auto px-4 py-8">
        <div class="flex items-center mb-8">
            <h1 class="text-3xl font-bold text-primary-700">
                <i class="fas fa-chalkboard-teacher mr-3"></i>
                Cours en Direct - <?= $user_role == 2 ? 'Espace Enseignant' : 'Espace Étudiant' ?>
            </h1>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?= $success_message ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <?php if ($user_role == 2): ?>
        <!-- Interface Enseignant -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Formulaire de planification -->
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-100">
                <h2 class="text-2xl font-bold text-primary-700 mb-6 flex items-center">
                    <i class="far fa-calendar-plus mr-3 text-accent-500"></i>
                    Planifier une nouvelle session
                </h2>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label for="cours_id" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-book mr-1 text-primary-600"></i>
                            Cours *
                        </label>
                        <select id="cours_id" name="cours_id" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent">
                            <option value="">Sélectionner un cours</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['titre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="titre" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-heading mr-1 text-primary-600"></i>
                            Titre de la session *
                        </label>
                        <input type="text" id="titre" name="titre" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent"
                               placeholder="Ex: Introduction à la programmation">
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-align-left mr-1 text-primary-600"></i>
                            Description
                        </label>
                        <textarea id="description" name="description" rows="3"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent"
                               placeholder="Description détaillée de la session..."></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="date_heure" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="far fa-calendar-alt mr-1 text-primary-600"></i>
                                Date et heure *
                            </label>
                            <input type="datetime-local" id="date_heure" name="date_heure" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="duree" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="far fa-clock mr-1 text-primary-600"></i>
                                Durée (minutes) *
                            </label>
                            <input type="number" id="duree" name="duree" required min="15" value="60"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label for="max_participants" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-users mr-1 text-primary-600"></i>
                            Nombre maximum de participants
                        </label>
                        <input type="number" id="max_participants" name="max_participants" min="1" max="100" value="50"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-accent-500 focus:border-transparent">
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" name="planifier_session" 
                                class="w-full bg-accent-500 hover:bg-accent-600 text-white font-medium py-3 px-6 rounded-md transition flex items-center justify-center">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Planifier la session
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sessions planifiées -->
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-100">
                <h2 class="text-2xl font-bold text-primary-700 mb-6 flex items-center">
                    <i class="fas fa-list-ol mr-3 text-accent-500"></i>
                    Mes sessions planifiées
                </h2>
                
                <?php if (count($sessions) > 0): ?>
                    <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                        <?php foreach ($sessions as $session): 
                            $now = new DateTime();
                            $session_date = new DateTime($session['date_heure']);
                            $is_past = $session_date < $now;
                            $is_soon = !$is_past && ($session_date->diff($now)->h < 24);
                        ?>
                            <div class="border border-gray-200 rounded-lg p-4 <?= $session['statut'] === 'annule' ? 'bg-gray-100 opacity-75' : '' ?>">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-semibold text-lg text-primary-700">
                                        <i class="fas fa-video mr-2 text-accent-500"></i>
                                        <?= htmlspecialchars($session['titre']) ?>
                                    </h3>
                                    <span class="px-2 py-1 text-xs rounded-full 
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
                                
                                <p class="text-gray-600 mb-2">
                                    <i class="fas fa-book-open mr-2 text-primary-600"></i>
                                    <?= htmlspecialchars($session['course_titre']) ?>
                                </p>
                                <p class="text-sm text-gray-500 mb-3">
                                    <i class="far fa-calendar-alt mr-2 text-primary-600"></i>
                                    <?= date('d/m/Y à H:i', strtotime($session['date_heure'])) ?> 
                                    (<?= $session['duree'] ?> minutes)
                                </p>
                                
                                <?php if (!empty($session['description'])): ?>
                                    <p class="text-gray-700 mb-3">
                                        <i class="fas fa-align-left mr-2 text-primary-600"></i>
                                        <?= nl2br(htmlspecialchars($session['description'])) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="flex justify-between items-center mt-4">
                                    <span class="text-sm text-gray-500">
                                        <i class="fas fa-users mr-1"></i>
                                        Max. <?= $session['max_participants'] ?> participants
                                    </span>
                                    
                                    <div class="flex space-x-2">
                                        <?php if ($session['statut'] === 'planifie'): ?>
                                            <form method="POST" class="m-0">
                                                <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                                                <button type="submit" name="demarrer_session" 
                                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-sm flex items-center">
                                                    <i class="fas fa-play mr-1"></i>
                                                    Démarrer
                                                </button>
                                            </form>
                                            <form method="POST" class="m-0">
                                                <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                                                <button type="submit" name="annuler_session" 
                                                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm flex items-center">
                                                    <i class="fas fa-times mr-1"></i>
                                                    Annuler
                                                </button>
                                            </form>
                                        <?php elseif ($session['statut'] === 'en_cours'): ?>
                                            <a href="<?= $session['lien_visio'] ?>" target="_blank"
                                               class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-2 rounded text-sm flex items-center">
                                                <i class="fas fa-video mr-1"></i>
                                                Rejoindre
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($session['statut'] === 'termine' || $session['statut'] === 'annule'): ?>
                                            <span class="text-sm text-gray-500 italic">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Session terminée
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
                        <i class="fas fa-calendar-times text-4xl mb-4 text-gray-400"></i>
                        <p class="font-medium">Aucune session planifiée.</p>
                        <p class="text-sm mt-1">Planifiez votre première session en utilisant le formulaire à gauche.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section fonctionnalités -->
        <div class="bg-white p-6 rounded-lg shadow-md mt-8 border border-gray-100">
            <h2 class="text-2xl font-bold text-primary-700 mb-6 flex items-center">
                <i class="fas fa-star mr-3 text-accent-500"></i>
                Fonctionnalités des cours en direct
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <i class="fas fa-video text-blue-600 text-4xl mb-3"></i>
                    <h3 class="font-semibold mb-2">Visioconférence intégrée</h3>
                    <p class="text-sm text-gray-600">Interaction en temps réel avec vos étudiants</p>
                </div>
                
                <div class="text-center p-4 bg-green-50 rounded-lg border border-green-100">
                    <i class="fas fa-comments text-green-600 text-4xl mb-3"></i>
                    <h3 class="font-semibold mb-2">Chat interactif</h3>
                    <p class="text-sm text-gray-600">Questions et discussions en direct</p>
                </div>
                
                <div class="text-center p-4 bg-purple-50 rounded-lg border border-purple-100">
                    <i class="fas fa-desktop text-purple-600 text-4xl mb-3"></i>
                    <h3 class="font-semibold mb-2">Partage d'écran</h3>
                    <p class="text-sm text-gray-600">Présentez vos supports de cours</p>
                </div>
                
                <div class="text-center p-4 bg-orange-50 rounded-lg border border-orange-100">
                    <i class="fas fa-chalkboard text-orange-600 text-4xl mb-3"></i>
                    <h3 class="font-semibold mb-2">Tableau blanc</h3>
                    <p class="text-sm text-gray-600">Expliquez les concepts complexes</p>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Interface Étudiant -->
        <div class="bg-white p-6 rounded-lg shadow-md border border-gray-100">
            <h2 class="text-2xl font-bold text-primary-700 mb-6 flex items-center">
                <i class="fas fa-list-ol mr-3 text-accent-500"></i>
                Sessions de cours disponibles
            </h2>
            
            <?php if (count($sessions) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($sessions as $session): 
                        $now = new DateTime();
                        $session_date = new DateTime($session['date_heure']);
                        $is_past = $session_date < $now;
                        $can_join = ($session['statut'] === 'en_cours') || 
                                   ($session['statut'] === 'planifie' && $is_past);
                    ?>
                        <div class="border border-gray-200 rounded-lg p-4 <?= $session['statut'] === 'annule' ? 'bg-gray-100 opacity-75' : '' ?>">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-semibold text-lg text-primary-700">
                                    <i class="fas fa-video mr-2 text-accent-500"></i>
                                    <?= htmlspecialchars($session['titre']) ?>
                                </h3>
                                <span class="px-2 py-1 text-xs rounded-full 
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
                            
                            <p class="text-gray-600 mb-2">
                                <i class="fas fa-book-open mr-2 text-primary-600"></i>
                                <?= htmlspecialchars($session['course_titre']) ?>
                            </p>
                            <p class="text-sm text-gray-500 mb-3">
                                <i class="far fa-calendar-alt mr-2 text-primary-600"></i>
                                <?= date('d/m/Y à H:i', strtotime($session['date_heure'])) ?> 
                                (<?= $session['duree'] ?> minutes)
                            </p>
                            <p class="text-sm text-gray-500 mb-3">
                                <i class="fas fa-user-graduate mr-2 text-primary-600"></i>
                                Enseignant: <?= htmlspecialchars($session['prenom'] . ' ' . $session['nom']) ?>
                            </p>
                            
                            <?php if (!empty($session['description'])): ?>
                                <p class="text-gray-700 mb-3">
                                    <i class="fas fa-align-left mr-2 text-primary-600"></i>
                                    <?= nl2br(htmlspecialchars($session['description'])) ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="flex justify-between items-center mt-4">
                                <span class="text-sm text-gray-500">
                                    <i class="fas fa-users mr-1"></i>
                                    Max. <?= $session['max_participants'] ?> participants
                                </span>
                                
                                <div class="flex space-x-2">
                                    <?php if ($session['statut'] === 'en_cours'): ?>
                                        <a href="<?= $session['lien_visio'] ?>" target="_blank"
                                           class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-2 rounded text-sm flex items-center">
                                            <i class="fas fa-video mr-1"></i>
                                            Rejoindre la session
                                        </a>
                                    <?php elseif ($session['statut'] === 'planifie'): ?>
                                        <?php if ($is_past): ?>
                                            <a href="<?= $session['lien_visio'] ?>" target="_blank"
                                               class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-2 rounded text-sm flex items-center">
                                                <i class="fas fa-video mr-1"></i>
                                                Rejoindre la session
                                            </a>
                                        <?php else: ?>
                                            <span class="bg-gray-200 text-gray-700 px-3 py-2 rounded text-sm flex items-center">
                                                <i class="fas fa-clock mr-1"></i>
                                                Session pas encore commencée
                                            </span>
                                        <?php endif; ?>
                                    <?php elseif ($session['statut'] === 'termine'): ?>
                                        <span class="text-sm text-gray-500 italic">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Session terminée
                                        </span>
                                    <?php elseif ($session['statut'] === 'annule'): ?>
                                        <span class="text-sm text-gray-500 italic">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            Session annulée
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
                    <i class="fas fa-calendar-times text-4xl mb-4 text-gray-400"></i>
                    <p class="font-medium">Aucune session disponible.</p>
                    <p class="text-sm mt-1">Aucun cours en direct n'est planifié pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>

    <footer class="mt-12 py-6 bg-gray-800 text-white text-center">
        <p>© 2023 Plateforme E-Learning. Tous droits réservés.</p>
    </footer>

    <script>
        // Fonction pour formater la date et l'heure locales
        function formatLocalDateTime() {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            return now.toISOString().slice(0, 16);
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
    </script>
</body>
</html>