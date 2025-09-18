<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérification connexion et rôle étudiant
if (!isLoggedIn() || $_SESSION['role_id'] != 1) {
    header('Location: ../public/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = ($_SESSION['role_id'] == 1) ? 'etudiant' : 'enseignant';
$user_nom = $_SESSION['nom'];
$user_prenom = $_SESSION['prenom'];

// Traitement de l'envoi d'un message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['envoyer_message'])) {
    $destinataire_id = $_POST['destinataire_id'];
    $sujet = htmlspecialchars($_POST['sujet']);
    $contenu = htmlspecialchars($_POST['contenu']);
    
    // Déterminer le rôle du destinataire (opposé à l'expéditeur)
    $role_destinataire = ($user_role == 'enseignant') ? 'etudiant' : 'enseignant';
    
    // Vérifier que le destinataire existe
    $verif_dest = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role_id = ?");
    $role_id_dest = ($role_destinataire == 'enseignant') ? 2 : 1;
    $verif_dest->execute([$destinataire_id, $role_id_dest]);

    if ($verif_dest->rowCount() > 0) {
        $insert_msg = $pdo->prepare("INSERT INTO messages (expediteur_id, expediteur_role, destinataire_id, destinataire_role, sujet, contenu, date_envoi) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $insert_msg->execute([$user_id, $user_role, $destinataire_id, $role_destinataire, $sujet, $contenu]);
        $message_success = "Message envoyé avec succès!";
    } else {
        $message_erreur = "Destinataire invalide!";
    }
}

// Marquer comme lu
if (isset($_GET['marquer_lu'])) {
    $message_id = $_GET['marquer_lu'];
    $update_msg = $pdo->prepare("UPDATE messages SET lu = 1 WHERE id = ? AND destinataire_id = ? AND destinataire_role = ?");
    $update_msg->execute([$message_id, $user_id, $user_role]);
    header("Location: messagerie.php");
    exit();
}

// Supprimer un message
if (isset($_GET['supprimer'])) {
    $message_id = $_GET['supprimer'];
    $delete_msg = $pdo->prepare("DELETE FROM messages WHERE id = ? AND (expediteur_id = ? OR destinataire_id = ?)");
    $delete_msg->execute([$message_id, $user_id, $user_id]);
    header("Location: messagerie.php");
    exit();
}

// Messages reçus
$messages_recus = $pdo->prepare("
    SELECT m.*, 
           CONCAT(u.prenom, ' ', u.nom) as expediteur_nom
    FROM messages m
    JOIN users u ON m.expediteur_id = u.id
    WHERE m.destinataire_id = ? AND m.destinataire_role = ?
    ORDER BY m.date_envoi DESC
");
$messages_recus->execute([$user_id, $user_role]);

// Messages envoyés
$messages_envoyes = $pdo->prepare("
    SELECT m.*, 
           CONCAT(u.prenom, ' ', u.nom) as destinataire_nom
    FROM messages m
    JOIN users u ON m.destinataire_id = u.id
    WHERE m.expediteur_id = ? AND m.expediteur_role = ?
    ORDER BY m.date_envoi DESC
");
$messages_envoyes->execute([$user_id, $user_role]);

// Destinataires possibles
if ($user_role == 'enseignant') {
    $destinataires = $pdo->query("SELECT id, nom, prenom FROM users WHERE role_id = 1 ORDER BY nom, prenom");
} else {
    $destinataires = $pdo->query("SELECT id, nom, prenom FROM users WHERE role_id = 2 ORDER BY nom, prenom");
}

// Nombre de messages non lus
$nb_non_lus = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE destinataire_id = ? AND destinataire_role = ? AND lu = 0");
$nb_non_lus->execute([$user_id, $user_role]);
$nb_non_lus_count = $nb_non_lus->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Messagerie - Tableau de bord Étudiant</title>

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
      <p class="font-medium"><?= $user_prenom . ' ' . $user_nom ?></p>
      <p class="text-xs text-primary-200"><?= $user_role == 'etudiant' ? 'Étudiant' : 'Enseignant' ?></p>
    </div>
  </div>
  
  <div class="flex-grow py-4">
    <ul class="space-y-2 px-3">
      <li>
        <a href="etudiant_dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-sidebarHover transition-all">
          <i class="fas fa-home w-5 h-5"></i> 
          <span>Tableau de bord</span>
        </a>
      </li>
      <li>
        <a href="messagerie.php" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-sidebarHover font-semibold transition-all">
          <i class="fas fa-comments w-5 h-5"></i> 
          <span>Messagerie</span>
          <?php if ($nb_non_lus_count > 0): ?>
            <span class="ml-auto bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">
              <?= $nb_non_lus_count ?>
            </span>
          <?php endif; ?>
        </a>
      </li>
      <li>
        <a href="cours_direct.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-sidebarHover transition-all">
          <i class="fas fa-video w-5 h-5"></i> 
          <span>Cours en direct</span>
        </a>
      </li>
      <li>
        <a href="forum.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-sidebarHover transition-all">
          <i class="fas fa-users w-5 h-5"></i> 
          <span>Forum</span>
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
<div class="flex-1 ml-64 p-8 max-w-6xl mx-auto">
  <header class="mb-8 bg-white p-6 rounded-xl shadow-sm">
    <h2 class="text-3xl font-bold text-primary-800">Messagerie</h2>
    <div class="flex flex-wrap gap-4 mt-4">
      <div class="flex items-center gap-2 bg-primary-50 px-4 py-2 rounded-lg">
        <i class="fas fa-user text-primary-600"></i>
        <span class="text-gray-700">Connecté en tant que :</span>
        <span class="font-semibold text-accent-600"><?= $user_prenom . ' ' . $user_nom ?></span>
      </div>
      <?php if ($nb_non_lus_count > 0): ?>
        <div class="flex items-center gap-2 bg-red-50 px-4 py-2 rounded-lg">
          <i class="fas fa-envelope text-red-600"></i>
          <span class="text-gray-700">Messages non lus :</span>
          <span class="font-semibold text-red-600"><?= $nb_non_lus_count ?></span>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <div class="bg-white rounded-xl shadow-sm p-8 mb-8">
    <?php if (isset($message_success)): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        <?= $message_success ?>
      </div>
    <?php endif; ?>
    <?php if (isset($message_erreur)): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <?= $message_erreur ?>
      </div>
    <?php endif; ?>

    <div class="message-form mb-8 p-6 bg-gray-50 rounded-lg">
      <h2 class="text-xl font-semibold text-gray-700 mb-4">Nouveau message</h2>
      <form method="POST" action="">
        <div class="form-group mb-4">
          <label for="destinataire" class="block text-gray-700 mb-2">Destinataire:</label>
          <select id="destinataire" name="destinataire_id" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Sélectionnez un destinataire</option>
            <?php while ($dest = $destinataires->fetch(PDO::FETCH_ASSOC)): ?>
              <option value="<?= $dest['id'] ?>"><?= $dest['prenom'] . ' ' . $dest['nom'] ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group mb-4">
          <label for="sujet" class="block text-gray-700 mb-2">Sujet:</label>
          <input type="text" id="sujet" name="sujet" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="form-group mb-4">
          <label for="contenu" class="block text-gray-700 mb-2">Message:</label>
          <textarea id="contenu" name="contenu" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="5"></textarea>
        </div>
        <button type="submit" name="envoyer_message" class="bg-primary-600 text-white px-6 py-2 rounded-lg hover:bg-primary-700 transition-colors">
          Envoyer
        </button>
      </form>
    </div>

    <div class="tabs flex border-b mb-6">
      <div class="tab mr-2 px-4 py-2 bg-primary-100 text-primary-700 rounded-t-lg cursor-pointer" data-tab="recus">
        Messages reçus
      </div>
      <div class="tab px-4 py-2 text-gray-600 rounded-t-lg cursor-pointer hover:bg-gray-100" data-tab="envoyes">
        Messages envoyés
      </div>
    </div>

    <div id="recus" class="tab-content">
      <h2 class="text-xl font-semibold text-gray-700 mb-4">Messages reçus</h2>
      <?php if ($messages_recus->rowCount() > 0): ?>
        <div class="messages-list space-y-4">
          <?php while ($message = $messages_recus->fetch(PDO::FETCH_ASSOC)): ?>
            <div class="message p-4 border rounded-lg <?= $message['lu'] ? 'bg-white' : 'bg-blue-50 border-blue-200' ?>">
              <div class="message-header flex justify-between items-center mb-3">
                <div class="font-semibold">De: <?= $message['expediteur_nom'] ?></div>
                <div class="text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?></div>
              </div>
              <div class="mb-2"><span class="font-medium">Sujet:</span> <?= htmlspecialchars($message['sujet']) ?></div>
              <div class="mb-3"><span class="font-medium">Message:</span> <?= nl2br(htmlspecialchars($message['contenu'])) ?></div>
              <div class="message-actions">
                <?php if (!$message['lu']): ?>
                  <a href="messagerie.php?marquer_lu=<?= $message['id'] ?>" class="text-blue-600 hover:text-blue-800 mr-4">
                    Marquer comme lu
                  </a>
                <?php endif; ?>
                <a href="messagerie.php?supprimer=<?= $message['id'] ?>" 
                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message?')" 
                   class="text-red-600 hover:text-red-800">
                  Supprimer
                </a>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-500 italic">Aucun message reçu.</p>
      <?php endif; ?>
    </div>

    <div id="envoyes" class="tab-content hidden">
      <h2 class="text-xl font-semibold text-gray-700 mb-4">Messages envoyés</h2>
      <?php if ($messages_envoyes->rowCount() > 0): ?>
        <div class="messages-list space-y-4">
          <?php while ($message = $messages_envoyes->fetch(PDO::FETCH_ASSOC)): ?>
            <div class="message p-4 border rounded-lg bg-white">
              <div class="message-header flex justify-between items-center mb-3">
                <div class="font-semibold">À: <?= $message['destinataire_nom'] ?></div>
                <div class="text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?></div>
              </div>
              <div class="mb-2"><span class="font-medium">Sujet:</span> <?= htmlspecialchars($message['sujet']) ?></div>
              <div class="mb-3"><span class="font-medium">Message:</span> <?= nl2br(htmlspecialchars($message['contenu'])) ?></div>
              <div class="message-actions">
                <a href="messagerie.php?supprimer=<?= $message['id'] ?>" 
                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message?')" 
                   class="text-red-600 hover:text-red-800">
                  Supprimer
                </a>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-500 italic">Aucun message envoyé.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Script pour gérer les onglets de la messagerie
document.addEventListener('DOMContentLoaded', function() {
  const tabs = document.querySelectorAll('.tab');
  const tabContents = document.querySelectorAll('.tab-content');
  
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.getAttribute('data-tab');
      
      // Mettre à jour les onglets
      tabs.forEach(t => t.classList.remove('bg-primary-100', 'text-primary-700'));
      tabs.forEach(t => t.classList.add('text-gray-600', 'hover:bg-gray-100'));
      tab.classList.add('bg-primary-100', 'text-primary-700');
      tab.classList.remove('text-gray-600', 'hover:bg-gray-100');
      
      // Afficher le contenu correspondant
      tabContents.forEach(content => content.classList.add('hidden'));
      document.getElementById(target).classList.remove('hidden');
    });
  });
});
</script>

</body>
</html>