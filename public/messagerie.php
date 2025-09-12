<?php
session_start();

// Vérification de la connexion
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    ($_SESSION['role'] != 'enseignant' && $_SESSION['role'] != 'etudiant')) {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données
$host = "localhost";
$dbname = "lms_isep";
$user = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Infos utilisateur
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_nom = $_SESSION['nom'];
$user_prenom = $_SESSION['prenom'];

// Traitement de l'envoi d'un message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['envoyer_message'])) {
    $destinataire_id = $_POST['destinataire_id'];
    $sujet = htmlspecialchars($_POST['sujet']);
    $contenu = htmlspecialchars($_POST['contenu']);

    $role_destinataire = ($user_role == 'enseignant') ? 'etudiant' : 'enseignant';
    $verif_dest = $pdo->prepare("SELECT id FROM " . ($role_destinataire == 'etudiant' ? 'etudiants' : 'enseignants') . " WHERE id = ?");
    $verif_dest->execute([$destinataire_id]);

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
           CASE 
               WHEN m.expediteur_role = 'enseignant' THEN CONCAT(e.nom, ' ', e.prenom)
               WHEN m.expediteur_role = 'etudiant' THEN CONCAT(et.nom, ' ', et.prenom)
           END as expediteur_nom
    FROM messages m
    LEFT JOIN enseignants e ON m.expediteur_id = e.id AND m.expediteur_role = 'enseignant'
    LEFT JOIN etudiants et ON m.expediteur_id = et.id AND m.expediteur_role = 'etudiant'
    WHERE m.destinataire_id = ? AND m.destinataire_role = ?
    ORDER BY m.date_envoi DESC
");
$messages_recus->execute([$user_id, $user_role]);

// Messages envoyés
$messages_envoyes = $pdo->prepare("
    SELECT m.*, 
           CASE 
               WHEN m.destinataire_role = 'enseignant' THEN CONCAT(e.nom, ' ', e.prenom)
               WHEN m.destinataire_role = 'etudiant' THEN CONCAT(et.nom, ' ', et.prenom)
           END as destinataire_nom
    FROM messages m
    LEFT JOIN enseignants e ON m.destinataire_id = e.id AND m.destinataire_role = 'enseignant'
    LEFT JOIN etudiants et ON m.destinataire_id = et.id AND m.destinataire_role = 'etudiant'
    WHERE m.expediteur_id = ? AND m.expediteur_role = ?
    ORDER BY m.date_envoi DESC
");
$messages_envoyes->execute([$user_id, $user_role]);

// Destinataires possibles
if ($user_role == 'enseignant') {
    $destinataires = $pdo->query("SELECT id, nom, prenom FROM etudiants ORDER BY nom, prenom");
} else {
    $destinataires = $pdo->query("SELECT id, nom, prenom FROM enseignants ORDER BY nom, prenom");
}

// Nombre de messages non lus
$nb_non_lus = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE destinataire_id = ? AND destinataire_role = ? AND lu = 0");
$nb_non_lus->execute([$user_id, $user_role]);
$nb_non_lus_count = $nb_non_lus->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messagerie</title>
<style>
    body { font-family: Arial; margin:0; padding:20px; background:#f5f5f5; }
    .container { max-width:1200px; margin:0 auto; background:white; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1);}
    h1,h2 { color:#333; }
    .message-form { margin-bottom:30px; padding:20px; background:#f9f9f9; border-radius:5px; }
    .form-group { margin-bottom:15px; }
    label { display:block; margin-bottom:5px; font-weight:bold; }
    input[type="text"], select, textarea { width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; box-sizing:border-box; }
    textarea { height:150px; }
    button { background:#4CAF50; color:white; padding:10px 15px; border:none; border-radius:4px; cursor:pointer; }
    button:hover { background:#45a049; }
    .messages-list { margin-top:20px; }
    .message { border:1px solid #ddd; padding:15px; margin-bottom:10px; border-radius:5px; background:white; }
    .message.non-lu { background:#e6f7ff; border-left:4px solid #1890ff; }
    .message-header { display:flex; justify-content:space-between; margin-bottom:10px; font-weight:bold; }
    .message-date { color:#777; }
    .message-actions a { margin-left:10px; text-decoration:none; color:#1890ff; }
    .message-actions a:hover { text-decoration:underline; }
    .tabs { display:flex; margin-bottom:20px; }
    .tab { padding:10px 20px; background:#eee; cursor:pointer; border-radius:5px 5px 0 0; margin-right:5px; }
    .tab.active { background:white; border:1px solid #ddd; border-bottom:none; }
    .tab-content { display:none; }
    .tab-content.active { display:block; }
    .alert { padding:10px; margin-bottom:15px; border-radius:4px; }
    .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
    .alert-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
</style>
</head>
<body>
<div class="container">
    <h1>Messagerie</h1>
    <p>Bienvenue, <?php echo $user_prenom . ' ' . $user_nom; ?> (<?php echo $user_role; ?>) <?php if($nb_non_lus_count>0) echo "- Messages non lus: $nb_non_lus_count"; ?></p>

    <?php if (isset($message_success)): ?>
        <div class="alert alert-success"><?php echo $message_success; ?></div>
    <?php endif; ?>
    <?php if (isset($message_erreur)): ?>
        <div class="alert alert-error"><?php echo $message_erreur; ?></div>
    <?php endif; ?>

    <div class="message-form">
        <h2>Nouveau message</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="destinataire">Destinataire:</label>
                <select id="destinataire" name="destinataire_id" required>
                    <option value="">Sélectionnez un destinataire</option>
                    <?php while ($dest = $destinataires->fetch()): ?>
                        <option value="<?php echo $dest['id']; ?>"><?php echo $dest['nom'] . ' ' . $dest['prenom']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="sujet">Sujet:</label>
                <input type="text" id="sujet" name="sujet" required>
            </div>
            <div class="form-group">
                <label for="contenu">Message:</label>
                <textarea id="contenu" name="contenu" required></textarea>
            </div>
            <button type="submit" name="envoyer_message">Envoyer</button>
        </form>
    </div>

    <div class="tabs">
        <div class="tab active" onclick="openTab('recus')">Messages reçus</div>
        <div class="tab" onclick="openTab('envoyes')">Messages envoyés</div>
    </div>

    <div id="recus" class="tab-content active">
        <h2>Messages reçus</h2>
        <?php if ($messages_recus->rowCount() > 0): ?>
            <div class="messages-list">
                <?php while ($message = $messages_recus->fetch()): ?>
                    <div class="message <?php echo $message['lu'] ? '' : 'non-lu'; ?>">
                        <div class="message-header">
                            <div>De: <?php echo $message['expediteur_nom']; ?></div>
                            <div class="message-date"><?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?></div>
                        </div>
                        <div><strong>Sujet:</strong> <?php echo htmlspecialchars($message['sujet']); ?></div>
                        <div><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($message['contenu'])); ?></div>
                        <div class="message-actions">
                            <?php if (!$message['lu']): ?>
                                <a href="messagerie.php?marquer_lu=<?php echo $message['id']; ?>">Marquer comme lu</a>
                            <?php endif; ?>
                            <a href="messagerie.php?supprimer=<?php echo $message['id']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message?')">Supprimer</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>Aucun message reçu.</p>
        <?php endif; ?>
    </div>

    <div id="envoyes" class="tab-content">
        <h2>Messages envoyés</h2>
        <?php if ($messages_envoyes->rowCount() > 0): ?>
            <div class="messages-list">
                <?php while ($message = $messages_envoyes->fetch()): ?>
                    <div class="message">
                        <div class="message-header">
                            <div>À: <?php echo $message['destinataire_nom']; ?></div>
                            <div class="message-date"><?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?></div>
                        </div>
                        <div><strong>Sujet:</strong> <?php echo htmlspecialchars($message['sujet']); ?></div>
                        <div><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($message['contenu'])); ?></div>
                        <div class="message-actions">
                            <a href="messagerie.php?supprimer=<?php echo $message['id']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message?')">Supprimer</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>Aucun message envoyé.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function openTab(tabName) {
    var tabContents = document.getElementsByClassName("tab-content");
    for (var i = 0; i < tabContents.length; i++) tabContents[i].classList.remove("active");
    var tabs = document.getElementsByClassName("tab");
    for (var i = 0; i < tabs.length; i++) tabs[i].classList.remove("active");
    document.getElementById(tabName).classList.add("active");
    for (var i = 0; i < tabs.length; i++) {
        if (tabs[i].getAttribute("onclick").includes(tabName)) tabs[i].classList.add("active");
    }
}
</script>
</body>
</html>
