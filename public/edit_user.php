<?php
// ===========================================
// edit_user.php - Modification d'un utilisateur
// ===========================================

// Connexion base de données
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php'; // Chemin corrigé

// Vérifier si admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Initialiser les variables
$id = $_GET['id'] ?? null;
$message = '';
$error = '';

if (!$id) {
    die("ID utilisateur manquant.");
}

// Récupérer les infos de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Utilisateur non trouvé.");
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email  = trim($_POST['email']);
    $role   = $_POST['role'];
    $password = $_POST['password'];

    if (empty($nom) || empty($prenom) || empty($email) || empty($role)) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } else {
        try {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $sql = "UPDATE users SET nom=?, prenom=?, email=?, role=?, password=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $ok = $stmt->execute([$nom, $prenom, $email, $role, $hashedPassword, $id]);
            } else {
                $sql = "UPDATE users SET nom=?, prenom=?, email=?, role=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $ok = $stmt->execute([$nom, $prenom, $email, $role, $id]);
            }

            if ($ok) {
                $message = "✅ Utilisateur mis à jour avec succès.";
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Erreur lors de la mise à jour.";
            }
        } catch (PDOException $e) {
            $error = "Erreur SQL : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier utilisateur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ===== RESET ===== */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, sans-serif; }
        body { background:#f4f6f9; color:#333; }

        /* ===== NAVBAR ===== */
        .navbar {
            background: linear-gradient(90deg,#009688,#26a69a);
            padding: 15px 30px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            color:#fff;
            box-shadow:0 4px 10px rgba(0,0,0,0.1);
        }
        .navbar h1 {
            font-size:22px;
            display:flex;
            align-items:center;
            gap:8px;
        }
        .navbar a {
            color:#fff;
            margin-left:20px;
            text-decoration:none;
            font-weight:500;
            transition:0.3s;
        }
        .navbar a:hover { color:#ffeb3b; }

        /* ===== CONTAINER ===== */
        .container {
            max-width: 700px;
            margin: 40px auto;
            background:#fff;
            padding:40px;
            border-radius:20px;
            box-shadow:0 12px 25px rgba(0,0,0,0.12);
            transition:0.3s;
        }
        .container:hover { transform: translateY(-5px); }
        h2 {
            margin-bottom:25px;
            color:#009688;
            text-align:center;
            font-size:28px;
            font-weight:700;
            display:flex;
            justify-content:center;
            align-items:center;
            gap:10px;
        }

        /* ===== MESSAGES ===== */
        .msg {
            padding:15px;
            margin-bottom:25px;
            border-radius:10px;
            font-size:15px;
            display:flex;
            align-items:center;
            gap:10px;
            box-shadow:0 2px 5px rgba(0,0,0,0.1);
        }
        .msg.success { background:#e8f5e9; color:#2e7d32; border:1px solid #a5d6a7; }
        .msg.error { background:#ffebee; color:#c62828; border:1px solid #ef9a9a; }

        /* ===== FORM ===== */
        form { display:flex; flex-direction:column; gap:22px; }
        label {
            font-weight:600;
            margin-bottom:6px;
            display:block;
            color:#555;
            font-size:15px;
        }
        input, select {
            width:100%;
            padding:14px 12px;
            border-radius:10px;
            border:1px solid #ccc;
            transition:0.4s;
            font-size:16px;
            color:#333;
        }
        input::placeholder { color:#aaa; }
        input:focus, select:focus {
            border-color:#009688;
            outline:none;
            box-shadow:0 0 8px rgba(0,150,136,0.3);
        }

        /* ===== ICONS INPUT ===== */
        .input-icon { position: relative; }
        .input-icon i {
            position:absolute;
            left:12px;
            top:50%;
            transform: translateY(-50%);
            color:#aaa;
        }
        .input-icon input, .input-icon select { padding-left:35px; }

        /* ===== BUTTONS ===== */
        .actions {
            display:flex;
            justify-content:space-between;
            margin-top:20px;
        }
        button, .btn-cancel {
            padding:14px 30px;
            border:none;
            border-radius:12px;
            font-size:16px;
            cursor:pointer;
            display:flex;
            align-items:center;
            gap:10px;
            transition:0.3s;
            font-weight:600;
        }
        .btn-save { background:#009688; color:#fff; }
        .btn-save:hover { background:#00796b; box-shadow:0 4px 10px rgba(0,150,136,0.4); }
        .btn-cancel { background:#f5f5f5; color:#333; text-decoration:none; justify-content:center; }
        .btn-cancel:hover { background:#e0e0e0; }

        /* ===== FOOTER ===== */
        footer {
            margin-top:50px;
            text-align:center;
            color:#777;
            font-size:14px;
        }

        /* ===== RESPONSIVE ===== */
        @media(max-width:768px) {
            .container { margin:20px; padding:25px; }
            .actions { flex-direction:column; gap:15px; }
        }
    </style>
</head>
<body>
  <!-- NAVBAR -->
<div class="navbar">
    <h1><i class="fa fa-user-cog"></i> Panneau Admin</h1>
    <div>
        <a href="admin_dashboard.php"><i class="fa fa-home"></i>Tableau de Board</a>
        <a href="add_users.php"><i class="fa fa-user-plus"></i> Nouvel utilisateur</a>
        <a href="logout.php" style="color: white; background-color: red; padding: 5px 10px; border-radius: 5px; text-decoration: none;">
            <i class="fa fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</div>

    <!-- CONTAINER -->
    <div class="container">
        <h2><i class="fa fa-edit"></i> Modifier l'utilisateur</h2>

        <?php if ($message): ?>
            <div class="msg success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg error"><i class="fa fa-times-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="input-icon">
                <label for="nom"><i class="fa fa-user"></i> Nom :</label>
                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
            </div>

            <div class="input-icon">
                <label for="prenom"><i class="fa fa-user"></i> Prénom :</label>
                <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required>
            </div>

            <div class="input-icon">
                <label for="email"><i class="fa fa-envelope"></i> E-mail :</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="input-icon">
                <label for="role"><i class="fa fa-users"></i> Rôle :</label>
                <select id="role" name="role" required>
                    <option value="etudiant" <?= $user['role']==='etudiant'?'selected':'' ?>>Étudiant</option>
                    <option value="enseignant" <?= $user['role']==='enseignant'?'selected':'' ?>>Enseignant</option>
                    <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Administrateur</option>
                </select>
            </div>

            <div class="input-icon">
                <label for="password"><i class="fa fa-lock"></i> Nouveau mot de passe :</label>
                <input type="password" id="password" name="password" placeholder="••••••••">
            </div>

            <div class="actions">
                <button type="submit" class="btn-save"><i class="fa fa-save"></i> Enregistrer</button>
                <a href="admin_dashboard.php" class="btn-cancel"><i class="fa fa-times"></i> Annuler</a>
            </div>
        </form>
    </div>

    <!-- FOOTER -->
    <footer>
        <p>© <?= date("Y") ?> Plateforme E-learning ISEP Thiès - Tous droits réservés</p>
    </footer>
</body>
</html>
