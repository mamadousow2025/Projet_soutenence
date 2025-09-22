<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/notification.php';

// Vérification connexion pour tous les rôles
if (!isLoggedIn()) {
    header('Location: ../public/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role_id = $_SESSION['role_id'];
$user_nom = $_SESSION['nom'];
$user_prenom = $_SESSION['prenom'];
$user_filiere_id = $_SESSION['filiere_id'] ?? null;
$user_matiere_id = $_SESSION['matiere_id'] ?? null;

// Déterminer le rôle de l'utilisateur
switch ($user_role_id) {
    case 1:
        $user_role = 'etudiant';
        $dashboard_page = 'etudiant_dashboard.php';
        break;
    case 2:
        $user_role = 'enseignant';
        $dashboard_page = 'teacher_dashboard.php';
        break;
    case 3:
        $user_role = 'admin';
        $dashboard_page = 'admin_dashboard.php';
        break;
    default:
        header('Location: ../public/login.php');
        exit();
}

// Récupérer la liste des filières
$filieres = $pdo->query("SELECT id, nom FROM filieres ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

// Traitement de l'envoi d'un message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['envoyer_message'])) {
    $type_destinataire = $_POST['type_destinataire'];
    $sujet = htmlspecialchars($_POST['sujet']);
    $contenu = htmlspecialchars($_POST['contenu']);
    $message_success = "";
    $message_erreur = "";
    
    // Vérification des champs obligatoires
    if (empty($sujet) || empty($contenu)) {
        $message_erreur = "Le sujet et le contenu du message sont obligatoires!";
    } else {
        // Envoi selon le type de destinataire
        if ($type_destinataire == 'individuel') {
            $destinataire_id = $_POST['destinataire_id'];
            $role_destinataire = $_POST['destinataire_role'];
            
            // Vérifier que le destinataire existe
            $verif_dest = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role_id = ?");
            $role_id_dest = ($role_destinataire == 'enseignant') ? 2 : (($role_destinataire == 'admin') ? 3 : 1);
            $verif_dest->execute([$destinataire_id, $role_id_dest]);

            if ($verif_dest->rowCount() > 0) {
                $insert_msg = $pdo->prepare("INSERT INTO messages (expediteur_id, expediteur_role, destinataire_id, destinataire_role, sujet, contenu, date_envoi) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $insert_msg->execute([$user_id, $user_role, $destinataire_id, $role_destinataire, $sujet, $contenu]);
                $message_success = "Message envoyé avec succès!";
                
                // Ajouter une notification pour le destinataire
                addNotification($destinataire_id, "Nouveau message de " . $user_prenom . " " . $user_nom, $sujet, "message");
            } else {
                $message_erreur = "Destinataire invalide!";
            }
        } 
        // Envoi par filière
        elseif ($type_destinataire == 'filiere') {
            $filiere_id = $_POST['filiere_id'];
            
            // Vérifier que la filière existe
            $verif_filiere = $pdo->prepare("SELECT id FROM filieres WHERE id = ?");
            $verif_filiere->execute([$filiere_id]);
            
            if ($verif_filiere->rowCount() > 0) {
                // Récupérer tous les étudiants de cette filière
                $etudiants_filiere = $pdo->prepare("SELECT id FROM users WHERE filiere_id = ? AND role_id = 1 AND actif = 1");
                $etudiants_filiere->execute([$filiere_id]);
                
                $compteur = 0;
                while ($etudiant = $etudiants_filiere->fetch(PDO::FETCH_ASSOC)) {
                    $insert_msg = $pdo->prepare("INSERT INTO messages (expediteur_id, expediteur_role, destinataire_id, destinataire_role, sujet, contenu, date_envoi, is_bulk) VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)");
                    $insert_msg->execute([$user_id, $user_role, $etudiant['id'], 'etudiant', $sujet, $contenu]);
                    $compteur++;
                    
                    // Ajouter une notification pour chaque étudiant
                    addNotification($etudiant['id'], "Nouveau message de " . $user_prenom . " " . $user_nom, $sujet, "message");
                }
                
                if ($compteur > 0) {
                    $message_success = "Message envoyé à $compteur étudiant(s) de la filière!";
                } else {
                    $message_erreur = "Aucun étudiant trouvé dans cette filière!";
                }
            } else {
                $message_erreur = "Filière invalide!";
            }
        }
        // Envoi à tous les étudiants
        elseif ($type_destinataire == 'tous_etudiants') {
            // Récupérer tous les étudiants
            $tous_etudiants = $pdo->query("SELECT id FROM users WHERE role_id = 1 AND actif = 1");
            
            $compteur = 0;
            while ($etudiant = $tous_etudiants->fetch(PDO::FETCH_ASSOC)) {
                $insert_msg = $pdo->prepare("INSERT INTO messages (expediteur_id, expediteur_role, destinataire_id, destinataire_role, sujet, contenu, date_envoi, is_bulk) VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)");
                $insert_msg->execute([$user_id, $user_role, $etudiant['id'], 'etudiant', $sujet, $contenu]);
                $compteur++;
                
                // Ajouter une notification pour chaque étudiant
                addNotification($etudiant['id'], "Nouveau message de " . $user_prenom . " " . $user_nom, $sujet, "message");
            }
            
            if ($compteur > 0) {
                $message_success = "Message envoyé à tous les étudiants ($compteur)!";
            } else {
                $message_erreur = "Aucun étudiant trouvé!";
            }
        }
        // Envoi à tous les enseignants
        elseif ($type_destinataire == 'tous_enseignants') {
            // Récupérer tous les enseignants
            $tous_enseignants = $pdo->query("SELECT id FROM users WHERE role_id = 2 AND actif = 1");
            
            $compteur = 0;
            while ($enseignant = $tous_enseignants->fetch(PDO::FETCH_ASSOC)) {
                $insert_msg = $pdo->prepare("INSERT INTO messages (expediteur_id, expediteur_role, destinataire_id, destinataire_role, sujet, contenu, date_envoi, is_bulk) VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)");
                $insert_msg->execute([$user_id, $user_role, $enseignant['id'], 'enseignant', $sujet, $contenu]);
                $compteur++;
                
                // Ajouter une notification pour chaque enseignant
                addNotification($enseignant['id'], "Nouveau message de " . $user_prenom . " " . $user_nom, $sujet, "message");
            }
            
            if ($compteur > 0) {
                $message_success = "Message envoyé à tous les enseignants ($compteur)!";
            } else {
                $message_erreur = "Aucun enseignant trouvé!";
            }
        }
    }
}

// Marquer comme lu (sauf pour admin)
if (isset($_GET['marquer_lu']) && $user_role != 'admin') {
    $message_id = $_GET['marquer_lu'];
    $update_msg = $pdo->prepare("UPDATE messages SET lu = 1 WHERE id = ? AND destinataire_id = ? AND destinataire_role = ?");
    $update_msg->execute([$message_id, $user_id, $user_role]);
    
    // Mettre à jour la notification
    markNotificationAsRead($user_id, $message_id, 'message');
    
    header("Location: messagerie.php");
    exit();
}

// Marquer tous comme lu
if (isset($_GET['marquer_tous_lu']) && $user_role != 'admin') {
    $update_msg = $pdo->prepare("UPDATE messages SET lu = 1 WHERE destinataire_id = ? AND destinataire_role = ? AND lu = 0");
    $update_msg->execute([$user_id, $user_role]);
    
    // Mettre à jour toutes les notifications
    markAllNotificationsAsRead($user_id, 'message');
    
    header("Location: messagerie.php");
    exit();
}

// Supprimer un message
if (isset($_GET['supprimer'])) {
    $message_id = $_GET['supprimer'];
    
    if ($user_role == 'admin') {
        // Les admins peuvent supprimer n'importe quel message
        $delete_msg = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $delete_msg->execute([$message_id]);
    } else {
        // Les autres utilisateurs ne peuvent supprimer que leurs messages
        $delete_msg = $pdo->prepare("DELETE FROM messages WHERE id = ? AND (expediteur_id = ? OR destinataire_id = ?)");
        $delete_msg->execute([$message_id, $user_id, $user_id]);
    }
    
    // Supprimer la notification associée
    deleteNotification($message_id, 'message');
    
    header("Location: messagerie.php");
    exit();
}

// Messages reçus (sauf pour admin)
if ($user_role != 'admin') {
    $messages_recus = $pdo->prepare("
        SELECT m.*, 
               CONCAT(u.prenom, ' ', u.nom) as expediteur_nom,
               u.role_id as expediteur_role_id,
               f.nom as filiere_nom
        FROM messages m
        JOIN users u ON m.expediteur_id = u.id
        LEFT JOIN filieres f ON u.filiere_id = f.id
        WHERE m.destinataire_id = ? AND m.destinataire_role = ?
        ORDER BY m.date_envoi DESC
    ");
    $messages_recus->execute([$user_id, $user_role]);
} else {
    $messages_recus = null;
}

// Messages envoyés
$messages_envoyes = $pdo->prepare("
    SELECT m.*, 
           CONCAT(u.prenom, ' ', u.nom) as destinataire_nom,
           u.role_id as destinataire_role_id,
           f.nom as filiere_nom
    FROM messages m
    JOIN users u ON m.destinataire_id = u.id
    LEFT JOIN filieres f ON u.filiere_id = f.id
    WHERE m.expediteur_id = ? AND m.expediteur_role = ?
    ORDER BY m.date_envoi DESC
");
$messages_envoyes->execute([$user_id, $user_role]);

// Destinataires possibles selon le rôle et la filière/matière
if ($user_role == 'enseignant') {
    // Enseignants peuvent envoyer aux étudiants de leur matière et aux admins
    if ($user_matiere_id) {
        $destinataires = $pdo->prepare("
            SELECT u.id, u.nom, u.prenom, u.role_id, f.nom as filiere_nom
            FROM users u
            LEFT JOIN user_filieres uf ON u.id = uf.user_id
            LEFT JOIN filieres f ON uf.filiere_id = f.id
            WHERE (u.role_id = 1 AND uf.filiere_id IN (
                SELECT filiere_id FROM matieres WHERE id = ?
            )) OR u.role_id = 3
            ORDER BY u.role_id, u.nom, u.prenom
        ");
        $destinataires->execute([$user_matiere_id]);
    } else {
        // Si l'enseignant n'a pas de matière assignée, il peut envoyer à tous les étudiants et admins
        $destinataires = $pdo->query("
            SELECT u.id, u.nom, u.prenom, u.role_id, f.nom as filiere_nom 
            FROM users u 
            LEFT JOIN filieres f ON u.filiere_id = f.id 
            WHERE u.role_id IN (1, 3) 
            ORDER BY u.role_id, u.nom, u.prenom
        ");
    }
} else if ($user_role == 'etudiant') {
    // Étudiants peuvent envoyer aux enseignants de leur filière et aux admins
    if ($user_filiere_id) {
        $destinataires = $pdo->prepare("
            SELECT u.id, u.nom, u.prenom, u.role_id, f.nom as filiere_nom
            FROM users u
            LEFT JOIN user_matieres um ON u.id = um.user_id
            LEFT JOIN filieres f ON u.filiere_id = f.id
            WHERE (u.role_id = 2 AND um.matiere_id IN (
                SELECT id FROM matieres WHERE filiere_id = ?
            )) OR u.role_id = 3
            ORDER BY u.role_id, u.nom, u.prenom
        ");
        $destinataires->execute([$user_filiere_id]);
    } else {
        // Si l'étudiant n'a pas de filière assignée, il peut envoyer à tous les enseignants et admins
        $destinataires = $pdo->query("
            SELECT u.id, u.nom, u.prenom, u.role_id, f.nom as filiere_nom 
            FROM users u 
            LEFT JOIN filieres f ON u.filiere_id = f.id 
            WHERE u.role_id IN (2, 3) 
            ORDER BY u.role_id, u.nom, u.prenom
        ");
    }
} else if ($user_role == 'admin') {
    // Admins peuvent envoyer à tout le monde sauf eux-mêmes
    $destinataires = $pdo->query("
        SELECT u.id, u.nom, u.prenom, u.role_id, f.nom as filiere_nom 
        FROM users u 
        LEFT JOIN filieres f ON u.filiere_id = f.id 
        WHERE u.id != $user_id 
        ORDER BY u.role_id, u.nom, u.prenom
    ");
}

// Nombre de messages non lus (sauf pour admin)
if ($user_role != 'admin') {
    $nb_non_lus = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE destinataire_id = ? AND destinataire_role = ? AND lu = 0");
    $nb_non_lus->execute([$user_id, $user_role]);
    $nb_non_lus_count = $nb_non_lus->fetchColumn();
} else {
    $nb_non_lus_count = 0;
}

// Titre du tableau de bord selon le rôle
$role_titles = [
    'etudiant' => 'Étudiant',
    'enseignant' => 'Enseignant',
    'admin' => 'Administrateur'
];

$role_title = $role_titles[$user_role];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Messagerie - Tableau de bord <?= $role_title ?></title>

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
  
  .message-card {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
  }
  
  .message-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
  
  .message-card.unread {
    border-left-color: #3b82f6;
    background-color: #eff6ff;
  }
  
  .badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
  }
  
  .badge-student {
    background-color: #dbeafe;
    color: #1e40af;
  }
  
  .badge-teacher {
    background-color: #f0fdf4;
    color: #166534;
  }
  
  .badge-admin {
    background-color: #fef3c7;
    color: #92400e;
  }
  
  .badge-filiere {
    background-color: #e9d5ff;
    color: #7e22ce;
  }
  
  .badge-bulk {
    background-color: #fce7f3;
    color: #be185d;
  }
  
  .tab-content {
    display: none;
  }
  
  .tab-content.active {
    display: block;
  }
</style>
</head>

<body class="flex min-h-screen bg-gray-50">

<!-- SIDEBAR -->
<nav class="w-64 bg-sidebar text-white flex flex-col fixed top-0 left-0 bottom-0 shadow-xl overflow-y-auto z-10">
  <div class="p-6 text-center border-b border-primary-700">
    <h1 class="text-2xl font-bold tracking-wide flex items-center justify-center gap-2">
      <i class="fas fa-graduation-cap"></i>
      <span>Espace <?= $role_title ?></span>
    </h1>
  </div>
  
  <div class="p-4 flex items-center gap-3 border-b border-primary-700 py-4">
    <div class="w-12 h-12 rounded-full bg-primary-500 flex items-center justify-center text-white font-bold text-lg">
      <?= strtoupper(substr($_SESSION['prenom'], 0, 1) . substr($_SESSION['nom'], 0, 1)) ?>
    </div>
    <div>
      <p class="font-medium"><?= $user_prenom . ' ' . $user_nom ?></p>
      <p class="text-xs text-primary-200"><?= $role_title ?></p>
      <?php if ($user_filiere_id): ?>
        <p class="text-xs text-primary-300"><?= $_SESSION['filiere_nom'] ?? 'Filière' ?></p>
      <?php endif; ?>
      <?php if ($user_matiere_id): ?>
        <p class="text-xs text-primary-300"><?= $_SESSION['matiere_nom'] ?? 'Matière' ?></p>
      <?php endif; ?>
    </div>
  </div>
  
  <div class="flex-grow py-4">
    <ul class="space-y-2 px-3">
      <li>
        <a href="<?= $dashboard_page ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-sidebarHover transition-all">
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
      
      <?php if ($user_role != 'admin'): ?>
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
      <?php else: ?>
      <li>
        <a href="gestion_utilisateurs.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-sidebarHover transition-all">
          <i class="fas fa-user-cog w-5 h-5"></i> 
          <span>Gestion des utilisateurs</span>
        </a>
      </li>
      <li>
        <a href="statistiques.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-sidebarHover transition-all">
          <i class="fas fa-chart-line w-5 h-5"></i> 
          <span>Statistiques</span>
        </a>
      </li>
      <?php endif; ?>
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
    <div class="flex justify-between items-center">
      <h2 class="text-3xl font-bold text-primary-800 flex items-center gap-2">
        <i class="fas fa-comments"></i>
        <span>Messagerie</span>
      </h2>
      <?php if ($user_role == 'admin'): ?>
        <span class="badge badge-admin">
          <i class="fas fa-shield-alt mr-1"></i> Mode administrateur
        </span>
      <?php endif; ?>
    </div>
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
      <?php if ($user_role == 'admin'): ?>
        <div class="flex items-center gap-2 bg-yellow-50 px-4 py-2 rounded-lg">
          <i class="fas fa-info-circle text-yellow-600"></i>
          <span class="text-gray-700">Vous ne recevez pas de messages en tant qu'admin</span>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <div class="bg-white rounded-xl shadow-sm p-8 mb-8">
    <?php if (isset($message_success)): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <?= $message_success ?>
      </div>
    <?php endif; ?>
    <?php if (isset($message_erreur)): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?= $message_erreur ?>
      </div>
    <?php endif; ?>

    <div class="message-form mb-8 p-6 bg-gray-50 rounded-lg border border-gray-200">
      <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center gap-2">
        <i class="fas fa-paper-plane text-primary-600"></i>
        <span>Nouveau message</span>
      </h2>
      <form method="POST" action="" id="messageForm">
        <div class="form-group mb-4">
          <label class="block text-gray-700 mb-2 font-medium">Type d'envoi:</label>
          <div class="flex flex-wrap gap-4">
            <label class="inline-flex items-center">
              <input type="radio" name="type_destinataire" value="individuel" checked class="form-radio text-primary-600" onchange="toggleDestinataireType()">
              <span class="ml-2">Individuel</span>
            </label>
            <?php if ($user_role != 'etudiant'): ?>
            <label class="inline-flex items-center">
              <input type="radio" name="type_destinataire" value="filiere" class="form-radio text-primary-600" onchange="toggleDestinataireType()">
              <span class="ml-2">Par filière</span>
            </label>
            <label class="inline-flex items-center">
              <input type="radio" name="type_destinataire" value="tous_etudiants" class="form-radio text-primary-600" onchange="toggleDestinataireType()">
              <span class="ml-2">Tous les étudiants</span>
            </label>
            <?php endif; ?>
            <?php if ($user_role == 'admin'): ?>
            <label class="inline-flex items-center">
              <input type="radio" name="type_destinataire" value="tous_enseignants" class="form-radio text-primary-600" onchange="toggleDestinataireType()">
              <span class="ml-2">Tous les enseignants</span>
            </label>
            <?php endif; ?>
          </div>
        </div>
        
        <div id="destinataire-individuel" class="destinataire-type">
          <div class="form-group mb-4">
            <label for="destinataire" class="block text-gray-700 mb-2 font-medium">Destinataire:</label>
            <select id="destinataire" name="destinataire_id" required class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
              <option value="">Sélectionnez un destinataire</option>
              <?php 
              if ($destinataires) {
                  while ($dest = $destinataires->fetch(PDO::FETCH_ASSOC)): 
                    $dest_role = ($dest['role_id'] == 1) ? 'etudiant' : (($dest['role_id'] == 2) ? 'enseignant' : 'admin');
                    $role_label = ($dest['role_id'] == 1) ? 'Étudiant' : (($dest['role_id'] == 2) ? 'Enseignant' : 'Admin');
                    $filiere_info = !empty($dest['filiere_nom']) ? " - " . $dest['filiere_nom'] : "";
              ?>
                <option value="<?= $dest['id'] ?>" data-role="<?= $dest_role ?>">
                  <?= $dest['prenom'] . ' ' . $dest['nom'] ?> (<?= $role_label ?><?= $filiere_info ?>)
                </option>
              <?php 
                  endwhile; 
              } else {
                  echo '<option value="">Aucun destinataire disponible</option>';
              }
              ?>
            </select>
            <input type="hidden" name="destinataire_role" id="destinataire_role" value="">
          </div>
        </div>
        
        <?php if ($user_role != 'etudiant'): ?>
        <div id="destinataire-filiere" class="destinataire-type hidden">
          <div class="form-group mb-4">
            <label for="filiere_id" class="block text-gray-700 mb-2 font-medium">Sélectionner une filière:</label>
            <select id="filiere_id" name="filiere_id" class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
              <option value="">Sélectionnez une filière</option>
              <?php foreach ($filieres as $filiere): ?>
                <option value="<?= $filiere['id'] ?>"><?= $filiere['nom'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <?php endif; ?>
        
        <div class="form-group mb-4">
          <label for="sujet" class="block text-gray-700 mb-2 font-medium">Sujet:</label>
          <input type="text" id="sujet" name="sujet" required class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Objet de votre message">
        </div>
        <div class="form-group mb-4">
          <label for="contenu" class="block text-gray-700 mb-2 font-medium">Message:</label>
          <textarea id="contenu" name="contenu" required class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" rows="5" placeholder="Tapez votre message ici..."></textarea>
        </div>
        <button type="submit" name="envoyer_message" class="bg-primary-600 text-white px-6 py-3 rounded-lg hover:bg-primary-700 transition-colors flex items-center gap-2">
          <i class="fas fa-paper-plane"></i>
          <span>Envoyer le message</span>
        </button>
      </form>
    </div>

    <div class="tabs flex border-b mb-6">
      <div class="tab mr-2 px-4 py-2 bg-primary-100 text-primary-700 rounded-t-lg cursor-pointer flex items-center gap-2" data-tab="envoyes">
        <i class="fas fa-paper-plane"></i>
        <span>Messages envoyés</span>
      </div>
      <?php if ($user_role != 'admin'): ?>
      <div class="tab px-4 py-2 text-gray-600 rounded-t-lg cursor-pointer hover:bg-gray-100 flex items-center gap-2" data-tab="recus">
        <i class="fas fa-inbox"></i>
        <span>Messages reçus</span>
        <?php if ($nb_non_lus_count > 0): ?>
          <span class="bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
            <?= $nb_non_lus_count ?>
          </span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($user_role != 'admin'): ?>
    <div id="recus" class="tab-content">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-700 flex items-center gap-2">
          <i class="fas fa-inbox text-primary-600"></i>
          <span>Messages reçus</span>
        </h2>
        <?php if ($nb_non_lus_count > 0): ?>
          <a href="messagerie.php?marquer_tous_lu" class="text-blue-600 hover:text-blue-800 flex items-center gap-1 text-sm">
            <i class="fas fa-check-double"></i>
            <span>Marquer tous comme lus</span>
          </a>
        <?php endif; ?>
      </div>
      <?php if ($messages_recus && $messages_recus->rowCount() > 0): ?>
        <div class="messages-list space-y-4">
          <?php while ($message = $messages_recus->fetch(PDO::FETCH_ASSOC)): 
            $exp_role = ($message['expediteur_role_id'] == 2) ? 'Enseignant' : (($message['expediteur_role_id'] == 3) ? 'Admin' : 'Étudiant');
            $badge_class = ($message['expediteur_role_id'] == 2) ? 'badge-teacher' : (($message['expediteur_role_id'] == 3) ? 'badge-admin' : 'badge-student');
            $is_bulk = isset($message['is_bulk']) && $message['is_bulk'] == 1;
          ?>
            <div class="message-card p-5 border rounded-lg <?= !$message['lu'] ? 'unread' : 'bg-white' ?>">
              <div class="message-header flex justify-between items-center mb-3">
                <div class="font-semibold flex items-center gap-2 flex-wrap">
                  <span>De: <?= $message['expediteur_nom'] ?></span>
                  <span class="badge <?= $badge_class ?> text-xs">
                    <?= $exp_role ?>
                  </span>
                  <?php if ($is_bulk): ?>
                    <span class="badge badge-bulk text-xs">
                      <i class="fas fa-users mr-1"></i> Message groupé
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($message['filiere_nom'])): ?>
                    <span class="badge badge-filiere text-xs">
                      <i class="fas fa-graduation-cap mr-1"></i> <?= $message['filiere_nom'] ?>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="text-sm text-gray-500 flex items-center gap-1">
                  <i class="far fa-clock"></i>
                  <?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?>
                </div>
              </div>
              <div class="mb-2">
                <span class="font-medium text-gray-700">Sujet:</span> 
                <span class="font-semibold text-primary-700"><?= htmlspecialchars($message['sujet']) ?></span>
              </div>
              <div class="mb-4 text-gray-600">
                <?= nl2br(htmlspecialchars($message['contenu'])) ?>
              </div>
              <div class="message-actions flex gap-3">
                <?php if (!$message['lu']): ?>
                  <a href="messagerie.php?marquer_lu=<?= $message['id'] ?>" class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                    <i class="fas fa-check-circle"></i>
                    <span>Marquer comme lu</span>
                  </a>
                <?php else: ?>
                  <span class="text-green-600 flex items-center gap-1">
                    <i class="fas fa-check-double"></i>
                    <span>Message lu</span>
                  </span>
                <?php endif; ?>
                <a href="messagerie.php?supprimer=<?= $message['id'] ?>" 
                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message?')" 
                   class="text-red-600 hover:text-red-800 flex items-center gap-1">
                  <i class="fas fa-trash-alt"></i>
                  <span>Supprimer</span>
                </a>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="text-center py-10 bg-gray-50 rounded-lg border border-dashed border-gray-300">
          <i class="fas fa-inbox text-4xl text-gray-400 mb-3"></i>
          <p class="text-gray-500 italic">Aucun message reçu.</p>
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div id="envoyes" class="tab-content <?= $user_role == 'admin' ? 'active' : '' ?>">
      <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center gap-2">
        <i class="fas fa-paper-plane text-primary-600"></i>
        <span>Messages envoyés</span>
      </h2>
      <?php if ($messages_envoyes->rowCount() > 0): ?>
        <div class="messages-list space-y-4">
          <?php while ($message = $messages_envoyes->fetch(PDO::FETCH_ASSOC)): 
            $dest_role = ($message['destinataire_role_id'] == 2) ? 'Enseignant' : (($message['destinataire_role_id'] == 3) ? 'Admin' : 'Étudiant');
            $badge_class = ($message['destinataire_role_id'] == 2) ? 'badge-teacher' : (($message['destinataire_role_id'] == 3) ? 'badge-admin' : 'badge-student');
            $is_bulk = isset($message['is_bulk']) && $message['is_bulk'] == 1;
          ?>
            <div class="message-card p-5 border rounded-lg bg-white">
              <div class="message-header flex justify-between items-center mb-3">
                <div class="font-semibold flex items-center gap-2 flex-wrap">
                  <span>À: <?= $message['destinataire_nom'] ?></span>
                  <span class="badge <?= $badge_class ?> text-xs">
                    <?= $dest_role ?>
                  </span>
                  <?php if ($is_bulk): ?>
                    <span class="badge badge-bulk text-xs">
                      <i class="fas fa-users mr-1"></i> Message groupé
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($message['filiere_nom'])): ?>
                    <span class="badge badge-filiere text-xs">
                      <i class="fas fa-graduation-cap mr-1"></i> <?= $message['filiere_nom'] ?>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="text-sm text-gray-500 flex items-center gap-1">
                  <i class="far fa-clock"></i>
                  <?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?>
                </div>
              </div>
              <div class="mb-2">
                <span class="font-medium text-gray-700">Sujet:</span> 
                <span class="font-semibold text-primary-700"><?= htmlspecialchars($message['sujet']) ?></span>
              </div>
              <div class="mb-4 text-gray-600">
                <?= nl2br(htmlspecialchars($message['contenu'])) ?>
              </div>
              <div class="message-actions">
                <a href="messagerie.php?supprimer=<?= $message['id'] ?>" 
                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message?')" 
                   class="text-red-600 hover:text-red-800 flex items-center gap-1">
                  <i class="fas fa-trash-alt"></i>
                  <span>Supprimer</span>
                </a>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="text-center py-10 bg-gray-50 rounded-lg border border-dashed border-gray-300">
          <i class="fas fa-paper-plane text-4xl text-gray-400 mb-3"></i>
          <p class="text-gray-500 italic">Aucun message envoyé.</p>
        </div>
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
      tabs.forEach(t => {
        t.classList.remove('bg-primary-100', 'text-primary-700');
        t.classList.add('text-gray-600', 'hover:bg-gray-100');
      });
      tab.classList.add('bg-primary-100', 'text-primary-700');
      tab.classList.remove('text-gray-600', 'hover:bg-gray-100');
      
      // Afficher le contenu correspondant
      tabContents.forEach(content => content.classList.remove('active'));
      document.getElementById(target).classList.add('active');
    });
  });
  
  // Gérer la sélection du destinataire
  const destinataireSelect = document.getElementById('destinataire');
  const destinataireRoleInput = document.getElementById('destinataire_role');
  
  if (destinataireSelect) {
    destinataireSelect.addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      if (selectedOption && selectedOption.dataset.role) {
        destinataireRoleInput.value = selectedOption.dataset.role;
      }
    });
    
    // Initialiser la valeur au chargement
    if (destinataireSelect.selectedIndex >= 0) {
      const selectedOption = destinataireSelect.options[destinataireSelect.selectedIndex];
      if (selectedOption && selectedOption.dataset.role) {
        destinataireRoleInput.value = selectedOption.dataset.role;
      }
    }
  }
});

// Fonction pour basculer entre les types de destinataires
function toggleDestinataireType() {
  const typeDestinataire = document.querySelector('input[name="type_destinataire"]:checked').value;
  const allDestinataireTypes = document.querySelectorAll('.destinataire-type');
  
  // Masquer tous les types de destinataires
  allDestinataireTypes.forEach(type => {
    type.classList.add('hidden');
  });
  
  // Afficher le type sélectionné
  const selectedType = document.getElementById('destinataire-' + typeDestinataire);
  if (selectedType) {
    selectedType.classList.remove('hidden');
  }
  
  // Mettre à jour les champs requis
  if (typeDestinataire === 'individuel') {
    document.getElementById('destinataire').setAttribute('required', 'required');
    if (document.getElementById('filiere_id')) {
      document.getElementById('filiere_id').removeAttribute('required');
    }
  } else if (typeDestinataire === 'filiere') {
    document.getElementById('destinataire').removeAttribute('required');
    if (document.getElementById('filiere_id')) {
      document.getElementById('filiere_id').setAttribute('required', 'required');
    }
  } else {
    document.getElementById('destinataire').removeAttribute('required');
    if (document.getElementById('filiere_id')) {
      document.getElementById('filiere_id').removeAttribute('required');
    }
  }
}

// Initialiser au chargement de la page
window.onload = function() {
  toggleDestinataireType();
};
</script>

</body>
</html>