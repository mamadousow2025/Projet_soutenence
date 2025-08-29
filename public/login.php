<?php
session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND actif = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['prenom'] = $user['prenom'];

            if ($user['role_id'] == 1) {
                header("Location: etudiant_dashboard.php");
            } elseif ($user['role_id'] == 2) {
                header("Location: teacher_dashboard.php");
            } elseif ($user['role_id'] == 3) {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Connexion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100 font-sans">

<div class="bg-white rounded-xl shadow-lg w-96 max-w-full p-10 text-center relative">

  <div class="mb-6">
    <img src="/lms_isep/assets/images/mon_logoelhadji.png" alt="Logo de connexion" class="mx-auto w-16 h-16" />
</div>
    <h2 class="text-3xl font-extrabold mb-8 text-[#009688]">Connexion</h2>

    <?php if (!empty($error)) : ?>
        <div class="bg-red-100 border border-red-600 text-red-700 rounded-md py-3 px-4 mb-6 font-semibold shadow-sm">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="space-y-6 text-left">

        <label for="email" class="block mb-1 font-semibold text-gray-700">Adresse Email</label>
        <div class="relative">
            <input 
                type="email" 
                name="email" 
                id="email" 
                required 
                value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" 
                placeholder="votre email"
                class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-[#009688] focus:shadow-[0_0_8px_rgba(0,150,136,0.7)] transition"
            />
            <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-[#ff9800]"></i>
        </div>

        <label for="password" class="block mb-1 font-semibold text-gray-700">Mot de passe</label>
        <div class="relative">
            <input 
                type="password" 
                name="password" 
                id="password" 
                required 
                placeholder="votre mot de passe"
                class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-[#009688] focus:shadow-[0_0_8px_rgba(0,150,136,0.7)] transition"
            />
            <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-[#ff9800]"></i>
        </div>

        <button type="submit" class="w-full bg-[#009688] text-white font-bold text-lg py-4 rounded-xl shadow-md hover:bg-[#00796b] hover:shadow-lg transition">
            Se connecter
        </button>
    </form>
</div>

</body>
</html>