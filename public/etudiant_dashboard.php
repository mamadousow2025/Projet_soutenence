<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérification connexion et rôle étudiant
if (!isLoggedIn() || $_SESSION['role_id'] != 1) {
    header('Location: ../public/login.php');
    exit();
}
$student_id = $_SESSION['user_id'];
$student_name = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);

// 1. Récupérer l'id de la filière et l'année depuis la table users
$stmt = $pdo->prepare("SELECT filiere_id, annee FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
$filiere_id = $user_data['filiere_id'];
$annee_etudiant = $user_data['annee'];

// 2. Récupérer le nom réel de la filière depuis la table filieres
if ($filiere_id) {
    $stmt = $pdo->prepare("SELECT nom FROM filieres WHERE id = ?");
    $stmt->execute([$filiere_id]);
    $filiere_nom = $stmt->fetchColumn() ?: "Non défini";
} else {
    $filiere_nom = "Non défini";
}

// 3. Déterminer le libellé de l'année
$libelle_annee = "Non définie";
if ($annee_etudiant == 1) {
    $libelle_annee = "Première année";
} elseif ($annee_etudiant == 2) {
    $libelle_annee = "Deuxième année";
}

// 4. Récupérer les cours de la filière
$cours = [];
if ($filiere_id) {
    $stmt = $pdo->prepare("SELECT id, titre, description FROM cours WHERE filiere_id = ?");
    $stmt->execute([$filiere_id]);
    $cours = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ------------------------------
// 5. Progression de l'étudiant
// ------------------------------
$progressions = [];
$stmt = $pdo->prepare("SELECT course_id, modules_total, modules_faits FROM progression WHERE student_id = ?");
$stmt->execute([$student_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $progressions[$row['course_id']] = $row;
}

// ------------------------------
// 6. Quiz disponibles
// ------------------------------
$quizs = [];
if ($filiere_id) {
    // Vérifier quelle colonne relie le cours au quiz
    // Ici j'utilise 'course_id' puisque c'est une des colonnes de votre table
    $stmt = $pdo->prepare("
        SELECT q.id, q.titre, q.date_limite, c.titre AS cours_titre
        FROM quizz q
        JOIN cours c ON q.course_id = c.id
        WHERE c.filiere_id = ?
        AND q.date_limite >= CURDATE()
        ORDER BY q.date_limite ASC
    ");
    $stmt->execute([$filiere_id]);
    $quizs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// ------------------------------
// 7. Devoirs disponibles
// ------------------------------
$devoirs = [];
if ($filiere_id) {
    $stmt = $pdo->prepare("SELECT d.id, d.titre, d.date_limite, c.titre AS cours_titre
                           FROM devoirs d
                           JOIN cours c ON d.cours_id = c.id
                           WHERE c.filiere_id = ?
                           AND d.date_limite >= CURDATE()
                           ORDER BY d.date_limite ASC");
    $stmt->execute([$filiere_id]);
    $devoirs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Tableau de bord Étudiant</title>

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          primary: {
            50: '#f0fdfa',
            100: '#ccfbf1',
            200: '#99f6e4',
            300: '#5eead4',
            400: '#2dd4bf',
            500: '#14b8a6',
            600: '#0d9488',
            700: '#0f766e',
            800: '#115e59',
            900: '#134e4a',
          },
          accent: {
            50: '#fffbeb',
            100: '#fef3c7',
            200: '#fde68a',
            300: '#fcd34d',
            400: '#fbbf24',
            500: '#f59e0b',
            600: '#d97706',
            700: '#b45309',
            800: '#92400e',
            900: '#78350f',
          },
          sidebar: '#0f766e',
          sidebarHover: '#0d9488'
        }
      }
    }
  }
</script>

<!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
  * {
    font-family: 'Poppins', sans-serif;
  }
  
  ::-webkit-scrollbar { 
    width: 8px; 
  }
  
  ::-webkit-scrollbar-thumb { 
    background-color: #0d9488; 
    border-radius: 4px; 
  }
  
  .card-hover {
    transition: all 0.3s ease;
  }
  
  .card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  }
  
  .progress-bar {
    transition: width 1s ease-in-out;
  }
  
  .notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #ef4444;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
  }
  
  .line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>
</head>

<body class="flex min-h-screen bg-gray-50">

<!-- SIDEBAR -->
<nav class="w-64 bg-sidebar text-white flex flex-col fixed top-0 left-0 bottom-0 shadow-xl overflow-y-auto z-10">
  <div class="p-6 text-center border-b border-primary-700">
    <h1 class="text-2xl font-bold tracking-wide flex items-center justify-center gap-2">
      <i class="fas fa-graduation-cap"></i>
      <span>Espace Étudiant</span>
    </h1>
  </div>
  
  <div class="p-4 flex items-center gap-3 border-b border-primary-700 py-4">
    <div class="w-12 h-12 rounded-full bg-primary-500 flex items-center justify-center text-white font-bold text-lg">
      <?= strtoupper(substr($_SESSION['prenom'], 0, 1) . substr($_SESSION['nom'], 0, 1)) ?>
    </div>
    <div>
      <p class="font-medium"><?= $student_name ?></p>
      <p class="text-xs text-primary-200"><?= htmlspecialchars($filiere_nom) ?></p>
      <p class="text-xs text-primary-200"><?= $libelle_annee ?></p>
    </div>
  </div>
  
  <div class="flex-grow py-4">
    <ul class="space-y-2 px-3">
      <li>
        <a href="#" data-target="mes-cours" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-sidebarHover transition-all">
          <i class="fas fa-book-open w-5 h-5"></i> 
          <span>Mes cours</span>
        </a>
      </li>
      <li>
        <a href="#" data-target="quiz-devoirs" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-sidebarHover transition-all relative">
          <i class="fas fa-tasks w-5 h-5"></i> 
          <span>Quiz & Devoirs</span>
          <?php if (count($quizs) + count($devoirs) > 0): ?>
            <span class="notification-badge"><?= count($quizs) + count($devoirs) ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li>
        <!-- Correction du lien messagerie - retrait de data-target -->
        <a href="messagerie.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-sidebarHover transition-all">
          <i class="fas fa-comments w-5 h-5"></i> 
          <span>Messagerie</span>
        </a>
      </li>
      <li>
        <a href="cours_direct.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-sidebarHover transition-all">
          <i class="fas fa-video w-5 h-5"></i> 
          <span>Cours en direct</span>
        </a>
      </li>
      <li>
             <a href="projet.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-sidebarHover transition-all">
  <i class="fas fa-project-diagram w-5 h-5"></i>
  <span>Projets</span>
</a>
      </li>
    </ul>
  </div>
  
  <div class="p-4 border-t border-primary-700">
    <a href="../public/logout.php" class="flex items-center gap-3 px-4 py-3 bg-red-600 rounded-lg hover:bg-red-700 transition-all">
      <i class="fas fa-sign-out-alt w-5 h-5"></i> 
      <span>Déconnexion</span>
    </a>
  </div>
</nav>

<!-- CONTENU PRINCIPAL -->
<div class="flex-1 ml-64 p-8 max-w-7xl mx-auto">
  <header class="mb-8 bg-white p-6 rounded-xl shadow-sm">
    <h2 class="text-3xl font-bold text-primary-800">Bienvenue, <?= $student_name ?></h2>
    <div class="flex flex-wrap gap-4 mt-4">
      <div class="flex items-center gap-2 bg-primary-50 px-4 py-2 rounded-lg">
        <i class="fas fa-layer-group text-primary-600"></i>
        <span class="text-gray-700">Filière :</span>
        <span class="font-semibold text-accent-600"><?= htmlspecialchars($filiere_nom) ?></span>
      </div>
      <div class="flex items-center gap-2 bg-blue-50 px-4 py-2 rounded-lg">
        <i class="fas fa-calendar-alt text-blue-600"></i>
        <span class="text-gray-700">Année :</span>
        <span class="font-semibold text-blue-700"><?= $libelle_annee ?></span>
      </div>
    </div>
  </header>

  <!-- FONCTIONNALITÉS RAPIDES -->
  <section class="mb-12 grid grid-cols-1 md:grid-cols-3 gap-6">
    <div data-target="mes-cours" class="quick-link bg-white p-6 rounded-xl shadow-sm card-hover flex flex-col items-center text-center cursor-pointer border-l-4 border-primary-500">
      <div class="w-14 h-14 rounded-full bg-primary-100 flex items-center justify-center mb-4">
        <i class="fas fa-chart-line text-primary-600 text-xl"></i>
      </div>
      <h4 class="text-xl font-semibold mb-2 text-gray-800">Suivre sa progression</h4>
      <p class="text-gray-600 mb-4">Consultez l'avancement de vos modules et résultats.</p>
      <a href="#" class="mt-auto inline-block bg-primary-500 text-white px-4 py-2 rounded-lg hover:bg-primary-600 transition-all">Voir mes cours</a>
    </div>
    
    <div class="quick-link bg-white p-6 rounded-xl shadow-sm card-hover flex flex-col items-center text-center cursor-pointer border-l-4 border-accent-500" data-target="cours-direct">
      <div class="w-14 h-14 rounded-full bg-accent-100 flex items-center justify-center mb-4">
        <i class="fas fa-video text-accent-600 text-xl"></i>
      </div>
      <h4 class="text-xl font-semibold mb-2 text-gray-800">Cours en direct</h4>
      <p class="text-gray-600 mb-4">Participez aux classes virtuelles avec vos enseignants.</p>
      <a href="cours_direct.php" class="mt-auto inline-block bg-accent-500 text-white px-4 py-2 rounded-lg hover:bg-accent-600 transition-all">Accéder</a>
    </div>
    
    <div class="quick-link bg-white p-6 rounded-xl shadow-sm card-hover flex flex-col items-center text-center cursor-pointer border-l-4 border-primary-500" data-target="forum">
      <div class="w-14 h-14 rounded-full bg-primary-100 flex items-center justify-center mb-4">
        <i class="fas fa-comments text-primary-600 text-xl"></i>
      </div>
      <h4 class="text-xl font-semibold mb-2 text-gray-800">Messagerie</h4>
      <p class="text-gray-600 mb-4">Discutez avec vos camarades et enseignants.</p>
      <a href="messagerie.php" class="mt-auto inline-block border border-primary-500 text-primary-500 px-4 py-2 rounded-lg hover:bg-primary-500 hover:text-white transition-all">Accéder</a>
    </div>
  </section>

  <!-- MES COURS -->
  <section id="mes-cours" class="mb-10 content-section">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-2xl font-semibold text-primary-800 flex items-center gap-2">
        <i class="fas fa-book-open"></i>
        Mes cours
      </h3>
      <span class="text-sm text-gray-500"><?= count($cours) ?> cours disponibles</span>
    </div>
    
    <?php if (!empty($cours)) : ?>
      <div class="grid md:grid-cols-2 gap-6">
        <?php foreach ($cours as $c):
          $prog = $progressions[$c['id']] ?? ['modules_total'=>0,'modules_faits'=>0];
          $percent = $prog['modules_total']>0? round($prog['modules_faits']/$prog['modules_total']*100) : 0;
        ?>
          <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
            <div class="flex items-start justify-between mb-4">
              <h4 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($c['titre']) ?></h4>
              <span class="text-xs font-semibold px-2 py-1 rounded-full <?= $percent < 50 ? 'bg-red-100 text-red-800' : ($percent < 100 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') ?>">
                <?= $percent ?>% complet
              </span>
            </div>
            <p class="text-gray-600 mb-4 line-clamp-2"><?= nl2br(htmlspecialchars($c['description'])) ?></p>
            
            <div class="mb-4">
              <div class="flex justify-between text-sm text-gray-500 mb-1">
                <span>Progression</span>
                <span><?= $prog['modules_faits'] ?>/<?= $prog['modules_total'] ?> modules</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-primary-500 h-2.5 rounded-full progress-bar" style="width: <?= $percent ?>%"></div>
              </div>
            </div>
            
            <div class="flex justify-between items-center">
              <a href="cours_detail.php?id=<?= $c['id'] ?>" class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-800 font-medium transition-colors">
                <i class="fas fa-arrow-right"></i>
                Accéder au cours
              </a>
              <div class="flex items-center gap-2 text-sm text-gray-500">
                <i class="fas fa-clock"></i>
                <span>Mis à jour récemment</span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="bg-white rounded-xl shadow-sm p-8 text-center">
        <i class="fas fa-book-open text-4xl text-gray-300 mb-4"></i>
        <h4 class="text-lg font-medium text-gray-600 mb-2">Aucun cours disponible</h4>
        <p class="text-gray-500">Aucun cours n'est disponible pour votre filière pour le moment.</p>
      </div>
    <?php endif; ?>
  </section>

  <!-- QUIZ & DEVOIRS -->
  <section id="quiz-devoirs" class="mb-10 content-section" style="display:none;">
    <h3 class="text-2xl font-semibold text-primary-800 mb-6 flex items-center gap-2">
      <i class="fas fa-tasks"></i>
      Quiz & Devoirs à rendre
    </h3>
    
    <?php if (empty($quizs) && empty($devoirs)) : ?>
      <div class="bg-white rounded-xl shadow-sm p-8 text-center">
        <i class="fas fa-check-circle text-4xl text-gray-300 mb-4"></i>
        <h4 class="text-lg font-medium text-gray-600 mb-2">Aucune tâche en attente</h4>
        <p class="text-gray-500">Vous n'avez aucun quiz ou devoir à rendre pour le moment.</p>
      </div>
    <?php else: ?>
      <div class="grid lg:grid-cols-2 gap-8">
        <?php if (!empty($quizs)) : ?>
          <div>
            <div class="flex items-center gap-2 mb-4">
              <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                <i class="fas fa-question-circle text-blue-600"></i>
              </div>
              <h4 class="font-semibold text-lg text-gray-800">Quiz</h4>
              <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full"><?= count($quizs) ?></span>
            </div>
            
            <div class="space-y-4">
              <?php foreach ($quizs as $q) : 
                $days_left = floor((strtotime($q['date_limite']) - time()) / (60 * 60 * 24));
                $urgency = $days_left <= 1 ? 'text-red-600' : ($days_left <= 3 ? 'text-yellow-600' : 'text-green-600');
              ?>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all">
                  <div class="flex justify-between items-start mb-2">
                    <h5 class="font-bold text-gray-800"><?= htmlspecialchars($q['titre']) ?></h5>
                    <span class="text-xs font-semibold px-2 py-1 rounded-full <?= $urgency ?>">
                      <i class="fas fa-clock"></i> 
                      <?= $days_left <= 0 ? 'Aujourd\'hui' : ($days_left == 1 ? '1 jour' : $days_left . ' jours') ?>
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 mb-3">Cours: <?= htmlspecialchars($q['cours_titre']) ?></p>
                  <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500">Date limite: <?= htmlspecialchars($q['date_limite']) ?></span>
                    <a href="quiz_pass.php?id=<?= $q['id'] ?>" class="text-sm bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-1 rounded-lg transition-colors">
                      voir les quiz
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($devoirs)) : ?>
          <div>
            <div class="flex items-center gap-2 mb-4">
              <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                <i class="fas fa-file-alt text-purple-600"></i>
              </div>
              <h4 class="font-semibold text-lg text-gray-800">Devoirs</h4>
              <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-full"><?= count($devoirs) ?></span>
            </div>
            
            <div class="space-y-4">
              <?php foreach ($devoirs as $d) : 
                $days_left = floor((strtotime($d['date_limite']) - time()) / (60 * 60 * 24));
                $urgency = $days_left <= 1 ? 'text-red-600' : ($days_left <= 3 ? 'text-yellow-600' : 'text-green-600');
              ?>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all">
                  <div class="flex justify-between items-start mb-2">
                    <h5 class="font-bold text-gray-800"><?= htmlspecialchars($d['titre']) ?></h5>
                    <span class="text-xs font-semibold px-2 py-1 rounded-full <?= $urgency ?>">
                      <i class="fas fa-clock"></i> 
                      <?= $days_left <= 0 ? 'Aujourd\'hui' : ($days_left == 1 ? '1 jour' : $days_left . ' jours') ?>
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 mb-3">Cours: <?= htmlspecialchars($d['cours_titre']) ?></p>
                  <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500">Date limite: <?= htmlspecialchars($d['date_limite']) ?></span>
                    <a href="devoir_detail.php?id=<?= $d['id'] ?>" class="text-sm bg-purple-100 hover:bg-purple-200 text-purple-800 px-3 py-1 rounded-lg transition-colors">
                      Détails
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- MESSAGERIE -->
  <section id="messagerie" class="content-section" style="display:none;">
    <div class="bg-white rounded-xl shadow-sm p-8 text-center">
      <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
        <i class="fas fa-comments text-3xl text-gray-400"></i>
      </div>
      <h3 class="text-2xl font-semibold text-gray-700 mb-2">Messagerie</h3>
      <p class="text-gray-500 mb-6">Cette fonctionnalité est en cours de développement.</p>
      <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 max-w-md mx-auto">
        <p class="text-blue-800 text-sm flex items-center justify-center gap-2">
          <i class="fas fa-info-circle"></i>
          Bientôt disponible - Restez à l'écoute!
        </p>
      </div>
    </div>
  </section>
</div>

<!-- SCRIPTS -->
<script>
  // Navigation entre les sections - uniquement pour les liens avec data-target
  const navLinks = document.querySelectorAll('.nav-link[data-target]');
  const sections = document.querySelectorAll('.content-section');

  navLinks.forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const target = link.getAttribute('data-target');
      
      sections.forEach(sec => {
        sec.style.display = (sec.id === target) ? 'block' : 'none';
      });
      
      navLinks.forEach(l => l.classList.remove('bg-sidebarHover', 'font-semibold'));
      link.classList.add('bg-sidebarHover', 'font-semibold');
    });
  });

  // Activer la section Mes Cours par défaut
  document.querySelector('.nav-link[data-target="mes-cours"]').click();

  // Animation des barres de progression
  document.addEventListener('DOMContentLoaded', function() {
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });
</script>

</body>
</html>