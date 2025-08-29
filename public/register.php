<?php
session_start();
require_once '../config/database.php';

$errors = [];

// Charger les filières
$filieres = $pdo->query("SELECT id, nom FROM filieres")->fetchAll();

// Déterminer l'étape actuelle
$step = isset($_POST['step']) ? intval($_POST['step']) : 1;

// Gestion du bouton "Retour"
if (isset($_POST['prev_step'])) {
    $step = max(1, intval($_POST['prev_step']));
}

// Traitement des étapes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['prev_step'])) {
    // Étape 1 : Infos personnelles
    if ($step == 1) {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];

        if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
            $errors[] = "Tous les champs sont obligatoires.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email invalide.";
        }
        if ($password !== $password_confirm) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }

        // Vérif email existant
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email déjà utilisé.";
        }

        if (empty($errors)) {
            $_SESSION['register'] = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'password' => $password
            ];
            $step = 2;
        }
    }
    // Étape 2 : Choix du profil
    elseif ($step == 2) {
        if (empty($_POST['profil'])) {
            $errors[] = "Veuillez choisir un profil.";
        } else {
            $_SESSION['register']['profil'] = $_POST['profil'];
            if ($_POST['profil'] !== 'Etudiant') {
                $step = 4;
            } else {
                $step = 3;
            }
        }
    }
    // Étape 3 : Choix de la filière
    elseif ($step == 3) {
        if (empty($_POST['filiere_id'])) {
            $errors[] = "Veuillez choisir une filière.";
        } else {
            $_SESSION['register']['filiere_id'] = intval($_POST['filiere_id']);
            $step = 4;
        }
    }
    // Étape 4 : Confirmation
    elseif ($step == 4) {
        if (empty($_POST['accept_terms'])) {
            $errors[] = "Vous devez accepter les conditions.";
        } else {
            $data = $_SESSION['register'];
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

            $roles_map = [
                'Etudiant' => 1,
                'Enseignant' => 2,
                'Administrateur' => 3
            ];
            $role_id = $roles_map[$data['profil']] ?? 1;

            $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role_id, filiere_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['nom'],
                $data['prenom'],
                $data['email'],
                $password_hash,
                $role_id,
                $data['filiere_id'] ?? null
            ]);

            unset($_SESSION['register']);
            $_SESSION['success'] = "Compte créé avec succès.";
            header("Location: login.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription multi-étapes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body { background-color: #f3f4f6; font-family: 'Poppins', sans-serif; }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .step-indicator div {
            width: 24%;
            text-align: center;
            padding: 8px;
            border-radius: 6px;
            font-weight: bold;
            color: white;
        }
        .step-active { background-color: #009688; }
        .step-inactive { background-color: #cccccc; }
        .btn-primary { background-color: #009688; color: white; transition: 0.3s; }
        .btn-primary:hover { background-color: #00796b; }
        .btn-secondary { background-color: #FF9800; color: white; transition: 0.3s; }
        .btn-secondary:hover { background-color: #e68900; }
        label span { color: #009688; font-weight: 500; }
        input, select { border: 1px solid #ccc; transition: 0.3s; }
        input:focus, select:focus { border-color: #009688; outline: none; box-shadow: 0 0 5px rgba(0, 150, 136, 0.5); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

<div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
    <h2 class="text-3xl font-bold mb-6 text-center text-gray-700">Inscription</h2>

    <div class="step-indicator mb-6">
        <div class="<?= $step >= 1 ? 'step-active' : 'step-inactive' ?>">1</div>
        <div class="<?= $step >= 2 ? 'step-active' : 'step-inactive' ?>">2</div>
        <div class="<?= $step >= 3 ? 'step-active' : 'step-inactive' ?>">3</div>
        <div class="<?= $step >= 4 ? 'step-active' : 'step-inactive' ?>">4</div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded shadow-inner">
            <ul class="list-disc list-inside">
                <?php foreach($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($step == 1): ?>
    <!-- Étape 1 -->
    <form method="POST" class="space-y-4">
        <input type="hidden" name="step" value="1" />
        <div>
            <label><span><i class="fas fa-user"></i> Prénom :</span></label>
            <input type="text" name="prenom" value="<?= htmlspecialchars($_SESSION['register']['prenom'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg shadow-sm">
        </div>
        <div>
            <label><span><i class="fas fa-user"></i> Nom :</span></label>
            <input type="text" name="nom" value="<?= htmlspecialchars($_SESSION['register']['nom'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg shadow-sm">
        </div>
        <div>
            <label><span><i class="fas fa-envelope"></i> Email :</span></label>
            <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['register']['email'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg shadow-sm">
        </div>
        <div>
            <label><span><i class="fas fa-lock"></i> Mot de passe :</span></label>
            <input type="password" name="password" required class="w-full px-3 py-2 rounded-lg shadow-sm">
        </div>
        <div>
            <label><span><i class="fas fa-lock"></i> Confirmer mot de passe :</span></label>
            <input type="password" name="password_confirm" required class="w-full px-3 py-2 rounded-lg shadow-sm">
        </div>
        <div class="flex justify-end">
            <button type="submit" class="btn-primary py-2 px-6 rounded-lg"><i class="fas fa-arrow-right"></i> Suivant</button>
        </div>
    </form>
    <?php elseif ($step == 2): ?>
    <!-- Étape 2 -->
    <form method="POST" class="space-y-4">
        <input type="hidden" name="step" value="2" />
        <p class="mb-2 text-gray-700 font-semibold"><i class="fas fa-user-cog"></i> Choisissez votre profil :</p>
        <?php
        $profils = ['Etudiant', 'Enseignant', 'Administrateur'];
        $selected_profil = $_SESSION['register']['profil'] ?? '';
        foreach ($profils as $profil): ?>
            <label class="block mb-2 p-2 border rounded-lg hover:bg-gray-50 cursor-pointer">
                <input type="radio" name="profil" value="<?= $profil ?>" <?= ($selected_profil == $profil) ? 'checked' : '' ?> required>
                <span class="ml-2"><?= $profil ?></span>
            </label>
        <?php endforeach; ?>
        <div class="flex justify-between mt-4">
            <button type="submit" name="prev_step" value="1" class="btn-secondary py-2 px-4 rounded-lg"><i class="fas fa-arrow-left"></i> Retour</button>
            <button type="submit" class="btn-primary py-2 px-6 rounded-lg"><i class="fas fa-arrow-right"></i> Suivant</button>
        </div>
    </form>
    <?php elseif ($step == 3): ?>
    <!-- Étape 3 -->
    <form method="POST" class="space-y-4">
        <input type="hidden" name="step" value="3" />
        <label class="block mb-2 text-gray-700 font-semibold"><i class="fas fa-graduation-cap"></i> Choisissez votre filière :</label>
        <select name="filiere_id" required class="w-full px-3 py-2 rounded-lg shadow-sm">
            <option value="">-- Choisir --</option>
            <?php foreach ($filieres as $f): ?>
                <option value="<?= $f['id'] ?>" <?= (isset($_SESSION['register']['filiere_id']) && $_SESSION['register']['filiere_id'] == $f['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['nom']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="flex justify-between mt-4">
            <button type="submit" name="prev_step" value="2" class="btn-secondary py-2 px-4 rounded-lg"><i class="fas fa-arrow-left"></i> Retour</button>
            <button type="submit" class="btn-primary py-2 px-6 rounded-lg"><i class="fas fa-arrow-right"></i> Suivant</button>
        </div>
    </form>
    <?php elseif ($step == 4): ?>
    <!-- Étape 4 -->
    <?php
    $data = $_SESSION['register'];
    $filiere_nom = '';
    if (!empty($data['filiere_id'])) {
        foreach ($filieres as $f) {
            if ($f['id'] == $data['filiere_id']) {
                $filiere_nom = $f['nom'];
                break;
            }
        }
    }
    ?>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="step" value="4" />
        <div class="p-4 bg-gray-50 rounded-lg shadow-inner">
            <p><i class="fas fa-user"></i> <strong>Nom complet :</strong> <?= htmlspecialchars($data['prenom'] . ' ' . $data['nom']) ?></p>
            <p><i class="fas fa-envelope"></i> <strong>Email :</strong> <?= htmlspecialchars($data['email']) ?></p>
            <p><i class="fas fa-user-tag"></i> <strong>Profil :</strong> <?= htmlspecialchars($data['profil']) ?></p>
            <?php if (!empty($filiere_nom)): ?>
                <p><i class="fas fa-graduation-cap"></i> <strong>Filière :</strong> <?= htmlspecialchars($filiere_nom) ?></p>
            <?php endif; ?>
        </div>
        <label class="flex items-center mt-2">
            <input type="checkbox" name="accept_terms" required class="mr-2">
            <span class="text-gray-700">J'accepte les conditions d'utilisation</span>
        </label>
        <div class="flex justify-between mt-4">
            <button type="submit" name="prev_step" value="<?= $data['profil'] === 'Etudiant' ? '3' : '2' ?>" class="btn-secondary py-2 px-4 rounded-lg"><i class="fas fa-arrow-left"></i> Retour</button>
            <button type="submit" class="btn-primary py-2 px-6 rounded-lg"><i class="fas fa-check"></i> Confirmer</button>
        </div>
    </form>
    <?php endif; ?>
</div>

</body>
</html>
