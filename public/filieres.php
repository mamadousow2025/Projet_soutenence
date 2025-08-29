<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || $_SESSION['role_id'] != 3) {
    header('Location: ../public/login.php');
    exit();
}

// Ajouter une filière
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nom'])) {
    $stmt = $pdo->prepare("INSERT INTO filiere (nom) VALUES (?)");
    $stmt->execute([$_POST['nom']]);
    header("Location: filieres.php");
    exit();
}

// Supprimer une filière
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM filiere WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: filieres.php");
    exit();
}

$filieres = $pdo->query("SELECT * FROM filiere")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des Filières</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8 bg-gray-100">
  <h1 class="text-3xl font-bold text-primary mb-6">Gestion des Filières</h1>

  <form method="post" class="mb-6 flex gap-3">
    <input type="text" name="nom" placeholder="Nom de la filière" class="border p-2 flex-1 rounded">
    <button type="submit" class="bg-primary text-white px-4 py-2 rounded">Ajouter</button>
  </form>

  <table class="w-full bg-white shadow rounded">
    <thead>
      <tr class="bg-gray-200 text-left">
        <th class="p-3">ID</th>
        <th class="p-3">Nom</th>
        <th class="p-3">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($filieres as $f): ?>
      <tr class="border-t">
        <td class="p-3"><?= $f['id'] ?></td>
        <td class="p-3"><?= htmlspecialchars($f['nom']) ?></td>
        <td class="p-3">
          <a href="?delete=<?= $f['id'] ?>" class="text-red-600" onclick="return confirm('Supprimer cette filière ?')">Supprimer</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
