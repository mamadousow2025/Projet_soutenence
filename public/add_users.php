<?php
session_start();

// Connexion à la base
require_once __DIR__ . '/../config/database.php';

// Authentification
require_once __DIR__ . '/../includes/auth.php';

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Récupérer les filières
$filieresStmt = $pdo->query("SELECT id, nom FROM filieres ORDER BY nom ASC");
$filieres = $filieresStmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les rôles
$rolesStmt = $pdo->query("SELECT id, role_name FROM roles ORDER BY role_name ASC");
$roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

// Trouver l'ID du rôle "Étudiant"
$etudiant_role_id = null;
foreach ($roles as $role) {
    if (strtolower($role['role_name']) === 'étudiant' || strtolower($role['role_name']) === 'etudiant') {
        $etudiant_role_id = $role['id'];
        break;
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role_id = $_POST['role_id'];
    $filiere_id = $_POST['filiere_id'];
    $annee = isset($_POST['annee']) ? $_POST['annee'] : null;

    if (!$nom || !$prenom || !$email || !$password || !$role_id) {
        $message = "Tous les champs obligatoires doivent être remplis.";
    } else if ($role_id == $etudiant_role_id) {
        if (!$filiere_id || !$annee) {
            $message = "Pour un étudiant, la filière et l'année sont obligatoires.";
        }
    } else {
        if (!$filiere_id) {
            $message = "La filière est obligatoire.";
        }
    }

    if (!$message) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role_id, filiere_id, annee) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $email, $hashedPassword, $role_id, $filiere_id, $annee]);
            $message = "Utilisateur ajouté avec succès !";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Erreur : email déjà utilisé.";
            } else {
                $message = "Erreur : " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un utilisateur</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* RESET */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #009688, #26a69a);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* CONTAINER */
        .form-container {
            background: #fff;
            width: 450px;
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            animation: fadeIn 0.7s ease-in-out;
        }
        .form-container h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: #009688;
        }

        /* MESSAGES */
        .message {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            font-size: 14px;
            text-align: center;
            font-weight: 500;
        }
        .success { background: #c8e6c9; color: #256029; }
        .error { background: #ffcdd2; color: #c62828; }

        /* FORM */
        form { display: flex; flex-direction: column; gap: 18px; }
        .form-group {
            display: flex;
            flex-direction: column;
            position: relative;
        }
        label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group i {
            position: absolute;
            right: 12px;
            top: 40px;
            color: #999;
        }
        input, select {
            padding: 12px 40px 12px 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            outline: none;
            font-size: 14px;
            transition: border 0.3s;
            width: 100%;
        }
        input:focus, select:focus {
            border-color: #009688;
            box-shadow: 0 0 0 2px rgba(0,150,136,0.2);
        }

        /* ANNEE FIELD */
        #annee-field {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        #annee-field.show {
            display: flex;
            opacity: 1;
        }

        /* BUTTON */
        button {
            background: #009688;
            color: #fff;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
            transition: all 0.3s;
            margin-top: 10px;
        }
        button:hover {
            background: #00796b;
            transform: translateY(-2px);
        }
        button i { margin-right: 6px; }

        /* ANIMATION */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* RESPONSIVE */
        @media (max-width: 480px) {
            .form-container { width: 100%; padding: 20px; }
        }
    </style>
</head>
<body>
    
<div class="form-container">
    <h1><i class="fa fa-user-plus"></i> Ajouter un utilisateur</h1>

    <?php if ($message): ?>
        <div class="message <?= strpos($message, 'succès') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post" id="userForm">
        <div class="form-group">
            <label for="nom">Nom :</label>
            <input type="text" name="nom" id="nom" required>
            <i class="fa fa-user"></i>
        </div>

        <div class="form-group">
            <label for="prenom">Prénom :</label>
            <input type="text" name="prenom" id="prenom" required>
            <i class="fa fa-user"></i>
        </div>

        <div class="form-group">
            <label for="email">Email :</label>
            <input type="email" name="email" id="email" required>
            <i class="fa fa-envelope"></i>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" required>
            <i class="fa fa-lock"></i>
        </div>

        <div class="form-group">
            <label for="role_id">Rôle :</label>
            <select name="role_id" id="role_id" required>
                <option value="">-- Choisir un rôle --</option>
                <?php foreach($roles as $role): ?>
                    <option value="<?= $role['id'] ?>" <?= ($role['id'] == $etudiant_role_id) ? 'data-is-student="true"' : '' ?>>
                        <?= htmlspecialchars($role['role_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <i class="fa fa-user-shield"></i>
        </div>

        <div class="form-group">
            <label for="filiere_id">Filière :</label>
            <select name="filiere_id" id="filiere_id" required>
                <option value="">-- Choisir une filière --</option>
                <?php foreach($filieres as $filiere): ?>
                    <option value="<?= $filiere['id'] ?>"><?= htmlspecialchars($filiere['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <i class="fa fa-graduation-cap"></i>
        </div>

        <div class="form-group" id="annee-field">
            <label for="annee">Année :</label>
            <select name="annee" id="annee">
                <option value="">-- Choisir l'année --</option>
                <option value="1">Première année</option>
                <option value="2">Deuxième année</option>
            </select>
            <i class="fa fa-calendar-alt"></i>
        </div>

        <button type="submit"><i class="fa fa-plus"></i> Ajouter</button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role_id');
        const anneeField = document.getElementById('annee-field');
        const anneeSelect = document.getElementById('annee');
        
        function toggleAnneeField() {
            const selectedOption = roleSelect.options[roleSelect.selectedIndex];
            const isStudent = selectedOption.getAttribute('data-is-student') === 'true';
            
            if (isStudent) {
                anneeField.classList.add('show');
                anneeSelect.setAttribute('required', 'required');
            } else {
                anneeField.classList.remove('show');
                anneeSelect.removeAttribute('required');
                anneeSelect.value = '';
            }
        }
        
        // Écouter les changements sur le select des rôles
        roleSelect.addEventListener('change', toggleAnneeField);
        
        // Appeler la fonction au chargement initial
        toggleAnneeField();
    });
</script>
</body>
</html>