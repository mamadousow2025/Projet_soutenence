<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || $_SESSION['role_id'] != 1) {
    header('Location: ../public/login.php');
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);

// Récupérer la filière de l'étudiant
$stmt = $pdo->prepare("SELECT filiere_id FROM etudiant WHERE id = ?");
$stmt->execute([$student_id]);
$filiere_id = $stmt->fetchColumn();

// Récupérer les cours de la filière
$stmt = $pdo->prepare("SELECT c.id, c.titre, c.description FROM cours c WHERE c.filiere_id = ?");
$stmt->execute([$filiere_id]);
$cours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la progression de l'étudiant sur chaque cours
$progressions = [];
$stmt = $pdo->prepare("SELECT course_id, modules_total, modules_faits FROM progression WHERE student_id = ?");
$stmt->execute([$student_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $progressions[$row['course_id']] = $row;
}

// Récupérer les quiz disponibles (non expirés) pour la filière
$stmt = $pdo->prepare("
    SELECT q.id, q.titre, q.date_limite, c.titre AS cours_titre
    FROM quiz q
    JOIN cours c ON q.course_id = c.id
    WHERE c.filiere_id = ?
      AND q.date_limite >= CURDATE()
    ORDER BY q.date_limite ASC
");
$stmt->execute([$filiere_id]);
$quizs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les devoirs disponibles (non expirés) pour la filière
$stmt = $pdo->prepare("
    SELECT d.id, d.titre, d.date_limite, c.titre AS cours_titre
    FROM devoirs d
    JOIN cours c ON d.course_id = c.id
    WHERE c.filiere_id = ?
      AND d.date_limite >= CURDATE()
    ORDER BY d.date_limite ASC
");
$stmt->execute([$filiere_id]);
$devoirs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
          primary: '#009688',
          accent: '#FF9800',
          sidebar: '#004d40',
          sidebarHover: '#00796b'
        }
      }
    }
  }
</script>

<!-- Feather Icons -->
<script src="https://unpkg.com/feather-icons"></script>

<style>
  /* Scrollbar styling */
  ::-webkit-scrollbar {
    width: 8px;
  }
  ::-webkit-scrollbar-thumb {
    background-color: #00796b;
    border-radius: 4px;
  }
</style>
</head>

<body class="flex min-h-screen bg-gray-100 font-sans">

<!-- Sidebar -->
<nav class="w-64 bg-sidebar text-white flex flex-col fixed top-0 left-0 bottom-0 shadow-lg overflow-y-auto">
  <div class="p-6 text-center border-b border-green-700">
    <h1 class="text-3xl font-bold tracking-wide">Espace Étudiant</h1>
  </div>
  <div class="flex-grow">
    <ul class="mt-6 space-y-2 px-4">
      <li>
        <a href="#" data-target="mes-cours" class="nav-link flex items-center gap-3 px-4 py-3 rounded hover:bg-sidebarHover transition">
          <i data-feather="book" class="w-5 h-5"></i>
          Mes cours
        </a>
      </li>
      <li>
        <a href="#" data-target="quiz-devoirs" class="nav-link flex items-center gap-3 px-4 py-3 rounded hover:bg-sidebarHover transition">
          <i data-feather="file-text" class="w-5 h-5"></i>
          Quiz & Devoirs à rendre
        </a>
      </li>
      <li>
        <a href="#" data-target="messagerie" class="nav-link flex items-center gap-3 px-4 py-3 rounded hover:bg-sidebarHover transition">
          <i data-feather="message-circle" class="w-5 h-5"></i>
          Messagerie
        </a>
      </li>
    </ul>
  </div>
  <div class="p-6 border-t border-green-700">
    <a href="../public/logout.php" class="flex items-center gap-3 px-4 py-3 bg-red-600 rounded hover:bg-red-700 transition">
      <i data-feather="log-out" class="w-5 h-5"></i>
      Déconnexion
    </a>
  </div>
</nav>

<!-- Contenu principal -->
<div class="flex-1 ml-64 p-8 max-w-7xl mx-auto">

  <header class="mb-8">
    <h2 class="text-3xl font-bold text-primary">Bienvenue, <?= $student_name ?></h2>
    <p class="text-gray-700 mt-2">Voici votre tableau de bord personnalisé</p>
  </header>

  <!-- Fonctionnalités rapides -->
  <section class="mb-12 grid grid-cols-1 sm:grid-cols-3 gap-6">
    <div data-target="mes-cours" class="quick-link bg-white p-6 rounded-lg shadow hover:shadow-lg transition flex flex-col items-center text-center cursor-pointer">
      <i data-feather="bar-chart-2" class="w-12 h-12 text-primary mb-4"></i>
      <h4 class="text-xl font-semibold mb-2">Suivre sa progression</h4>
      <p class="text-gray-600 mb-4">Consultez l’avancement de vos modules et vos résultats.</p>
      <a href="#" class="mt-auto inline-block bg-primary text-white px-4 py-2 rounded hover:bg-green-700 transition">Voir mes cours</a>
    </div>
    <div class="quick-link bg-white p-6 rounded-lg shadow hover:shadow-lg transition flex flex-col items-center text-center cursor-pointer" data-target="cours-direct">
      <i data-feather="video" class="w-12 h-12 text-primary mb-4"></i>
      <h4 class="text-xl font-semibold mb-2">Participer aux cours en direct</h4>
      <p class="text-gray-600 mb-4">Rejoignez les sessions en direct avec vos enseignants.</p>
      <a href="cours_direct.php" class="mt-auto inline-block bg-accent text-white px-4 py-2 rounded hover:bg-yellow-700 transition">Accéder aux cours en direct</a>
    </div>
    <div class="quick-link bg-white p-6 rounded-lg shadow hover:shadow-lg transition flex flex-col items-center text-center cursor-pointer" data-target="forum">
      <i data-feather="message-square" class="w-12 h-12 text-primary mb-4"></i>
      <h4 class="text-xl font-semibold mb-2">Participer au forum</h4>
      <p class="text-gray-600 mb-4">Échangez avec vos camarades et professeurs.</p>
      <a href="forum.php" class="mt-auto inline-block border border-primary text-primary px-4 py-2 rounded hover:bg-primary hover:text-white transition">Accéder au forum</a>
    </div>
  </section>

  <!-- Section Mes cours -->
  <section id="mes-cours" class="mb-10 content-section">
    <h3 class="text-2xl font-semibold text-primary mb-4">Mes cours</h3>
    <?php if (!empty($cours)) : ?>
      <div class="grid md:grid-cols-2 gap-6">
        <?php foreach ($cours as $c):
          $prog = $progressions[$c['id']] ?? ['modules_total' => 0, 'modules_faits' => 0];
          $percent = $prog['modules_total'] > 0 ? round($prog['modules_faits'] / $prog['modules_total'] * 100) : 0;
        ?>
          <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <h4 class="text-xl font-bold mb-2"><?= htmlspecialchars($c['titre']) ?></h4>
            <p class="text-gray-600 mb-4"><?= nl2br(htmlspecialchars($c['description'])) ?></p>
            <div class="mb-3">
              <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="bg-accent h-4 rounded-full" style="width: <?= $percent ?>%"></div>
              </div>
              <p class="text-sm text-gray-500 mt-1">
                Progression : <?= $prog['modules_faits'] ?>/<?= $prog['modules_total'] ?> modules (<?= $percent ?>%)
              </p>
            </div>
            <a href="cours_detail.php?id=<?= $c['id'] ?>" class="inline-block bg-primary text-white px-4 py-2 rounded hover:bg-green-700 transition">Accéder au cours</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-gray-600 italic">Aucun cours disponible pour votre filière pour le moment.</p>
    <?php endif; ?>
  </section>

  <!-- Section Quiz & Devoirs -->
  <section id="quiz-devoirs" class="mb-10 content-section" style="display:none;">
    <h3 class="text-2xl font-semibold text-primary mb-4">Quiz & Devoirs à rendre</h3>
    <?php if (empty($quizs) && empty($devoirs)) : ?>
      <p class="text-gray-600 italic">Aucun quiz ou devoir à rendre pour l’instant.</p>
    <?php else : ?>
      <div class="grid md:grid-cols-2 gap-6">
        <?php if (!empty($quizs)) : ?>
          <div>
            <h4 class="font-semibold mb-3">Quiz</h4>
            <ul class="list-disc list-inside space-y-2">
              <?php foreach ($quizs as $q) : ?>
                <li class="bg-white p-4 rounded shadow hover:shadow-md transition">
                  <strong><?= htmlspecialchars($q['titre']) ?></strong> - <?= htmlspecialchars($q['cours_titre']) ?><br>
                  Date limite : <span class="font-semibold"><?= htmlspecialchars($q['date_limite']) ?></span><br>
                  <a href="quiz_pass.php?id=<?= $q['id'] ?>" class="text-accent hover:underline">Passer le quiz</a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <?php if (!empty($devoirs)) : ?>
          <div>
            <h4 class="font-semibold mb-3">Devoirs</h4>
            <ul class="list-disc list-inside space-y-2">
              <?php foreach ($devoirs as $d) : ?>
                <li class="bg-white p-4 rounded shadow hover:shadow-md transition">
                  <strong><?= htmlspecialchars($d['titre']) ?></strong> - <?= htmlspecialchars($d['cours_titre']) ?><br>
                  Date limite : <span class="font-semibold"><?= htmlspecialchars($d['date_limite']) ?></span><br>
                  <a href="devoir_detail.php?id=<?= $d['id'] ?>" class="text-accent hover:underline">Voir le devoir</a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Section Messagerie -->
  <section id="messagerie" class="content-section" style="display:none;">
    <h3 class="text-2xl font-semibold text-primary mb-4">Messagerie</h3>
    <p class="text-gray-600 italic">Fonctionnalité en cours de développement...</p>
  </section>

</div>

<script>
  feather.replace();

  // Gestion simple de la navigation dans le dashboard (afficher/masquer sections)
  const navLinks = document.querySelectorAll('.nav-link');
  const sections = document.querySelectorAll('.content-section');

  navLinks.forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const target = link.getAttribute('data-target');

      // Afficher la section ciblée, cacher les autres
      sections.forEach(sec => {
        if (sec.id === target) {
          sec.style.display = 'block';
        } else {
          sec.style.display = 'none';
        }
      });

      // Optionnel: gérer un état actif dans la sidebar
      navLinks.forEach(l => l.classList.remove('bg-sidebarHover'));
      link.classList.add('bg-sidebarHover');
    });
  });

  // Optionnel: afficher par défaut la section Mes cours
  document.querySelector('.nav-link[data-target="mes-cours"]').click();
</script>

</body>
</html>
