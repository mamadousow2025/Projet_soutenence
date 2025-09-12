<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id']; // 2 = enseignant, 1 = étudiant

// --- AJOUT COURS (enseignant uniquement) ---
if ($role_id == 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $date_heure = $_POST['date_heure'];
    $lien_visio = trim($_POST['lien_visio']);

    if (!empty($titre) && !empty($date_heure) && !empty($lien_visio)) {
        $stmt = $pdo->prepare("INSERT INTO cours_direct (teacher_id, titre, description, date_heure, lien_visio) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $titre, $description, $date_heure, $lien_visio]);
        $success = "Cours planifié avec succès ✅";
    } else {
        $error = "Veuillez remplir tous les champs obligatoires ❌";
    }
}

// --- RECUPERATION DES COURS PLANIFIÉS ---
$stmt = $pdo->query("SELECT cd.*, u.nom, u.prenom 
                     FROM cours_direct cd
                     JOIN users u ON cd.teacher_id = u.id
                     ORDER BY cd.date_heure ASC");
$cours = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cours en Direct</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin:0; padding:20px; }
        h1 { color:#333; }
        .form-box, .cours-box { background:white; padding:20px; border-radius:8px; margin-bottom:20px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
        .form-box input, .form-box textarea { width:100%; padding:10px; margin:8px 0; border:1px solid #ccc; border-radius:5px; }
        .form-box button { background:#009688; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; }
        .form-box button:hover { background:#00796b; }
        .cours-item { border-bottom:1px solid #ddd; padding:10px 0; }
        .btn-join { background:#ff5722; color:white; padding:8px 15px; border-radius:5px; text-decoration:none; }
        .btn-join:hover { background:#e64a19; }
        .msg { padding:10px; border-radius:5px; margin-bottom:15px; }
        .success { background:#d4edda; color:#155724; }
        .error { background:#f8d7da; color:#721c24; }
    </style>
</head>
<body>

    <h1>📺 Cours en Direct</h1>

    <!-- Message succès ou erreur -->
    <?php if (!empty($success)): ?>
        <div class="msg success"><?= $success ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="msg error"><?= $error ?></div>
    <?php endif; ?>

    <!-- Formulaire enseignant -->
    <?php if ($role_id == 2): ?>
    <div class="form-box">
        <h2>Planifier un nouveau cours</h2>
        <form method="post">
            <input type="text" name="titre" placeholder="Titre du cours" required>
            <textarea name="description" placeholder="Description (optionnel)"></textarea>
            <input type="datetime-local" name="date_heure" required>
            <input type="text" name="lien_visio" placeholder="Lien visioconférence (Zoom, Meet, Jitsi...)" required>
            <button type="submit">Planifier</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Liste des cours -->
    <div class="cours-box">
        <h2>📅 Sessions planifiées</h2>
        <?php if (count($cours) > 0): ?>
            <?php foreach ($cours as $c): ?>
                <div class="cours-item">
                    <h3><?= htmlspecialchars($c['titre']) ?></h3>
                    <p><?= nl2br(htmlspecialchars($c['description'])) ?></p>
                    <p><strong>Enseignant :</strong> <?= htmlspecialchars($c['prenom'] . " " . $c['nom']) ?></p>
                    <p><strong>Date :</strong> <?= date("d/m/Y H:i", strtotime($c['date_heure'])) ?></p>
                    <?php if (new DateTime() >= new DateTime($c['date_heure'])): ?>
                        <a class="btn-join" href="<?= htmlspecialchars($c['lien_visio']) ?>" target="_blank">👉 Rejoindre</a>
                    <?php else: ?>
                        <em>⏳ Pas encore commencé</em>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aucun cours planifié pour le moment.</p>
        <?php endif; ?>
    </div>

</body>
</html>
