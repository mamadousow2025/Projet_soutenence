<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérification : utilisateur connecté et rôle enseignant (role_id = 2)
if (!isLoggedIn() || $_SESSION['role_id'] != 2) {
    header('Location: ../public/login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Vérifier si l'ID du module est passé en paramètre
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: teacher_dashboard.php');
    exit();
}

$module_id = intval($_GET['id']);

// Récupérer les informations du module
$stmt = $pdo->prepare("
    SELECT m.*, c.titre as course_titre 
    FROM modules m 
    JOIN cours c ON m.cours_id = c.id 
    WHERE m.id = ? AND c.enseignant_id = ?
");
$stmt->execute([$module_id, $teacher_id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$module) {
    header('Location: teacher_dashboard.php');
    exit();
}

// Récupérer les cours de l'enseignant pour le formulaire
$courses_stmt = $pdo->prepare("SELECT id, titre FROM cours WHERE enseignant_id = ? AND status = 'active'");
$courses_stmt->execute([$teacher_id]);
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_module'])) {
    $titre = $_POST['titre'];
    $cours_id = $_POST['cours_id'];
    $ordre = $_POST['ordre'];
    $description = $_POST['description'] ?? '';
    
    // Validation des données
    if (!empty($titre) && !empty($cours_id)) {
        // Vérifier que le nouveau cours appartient bien à l'enseignant
        $check_stmt = $pdo->prepare("SELECT id FROM cours WHERE id = ? AND enseignant_id = ?");
        $check_stmt->execute([$cours_id, $teacher_id]);
        
        if ($check_stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE modules SET titre = ?, cours_id = ?, ordre = ?, description = ? WHERE id = ?");
            
            if ($stmt->execute([$titre, $cours_id, $ordre, $description, $module_id])) {
                $success_message = "Module modifié avec succès!";
                // Recharger les données du module
                $stmt = $pdo->prepare("
                    SELECT m.*, c.titre as course_titre 
                    FROM modules m 
                    JOIN cours c ON m.cours_id = c.id 
                    WHERE m.id = ?
                ");
                $stmt->execute([$module_id]);
                $module = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_message = "Erreur lors de la modification du module.";
            }
        } else {
            $error_message = "Cours non autorisé.";
        }
    } else {
        $error_message = "Veuillez remplir tous les champs obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Modifier le module</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i data-feather="folder" class="inline-block mr-2"></i>
                    Modifier le module
                </h1>
                <a href="teacher_dashboard.php" class="text-primary-600 hover:text-primary-800">
                    <i data-feather="arrow-left" class="inline-block mr-1"></i> Retour
                </a>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= $success_message ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="titre" class="block text-sm font-medium text-gray-700 mb-1">Titre du module *</label>
                    <input type="text" id="titre" name="titre" required 
                           value="<?= htmlspecialchars($module['titre']) ?>"
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label for="cours_id" class="block text-sm font-medium text-gray-700 mb-1">Cours associé *</label>
                    <select id="cours_id" name="cours_id" required 
                            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Sélectionner un cours</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" 
                                <?= $course['id'] == $module['cours_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['titre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="ordre" class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                    <input type="number" id="ordre" name="ordre" 
                           value="<?= htmlspecialchars($module['ordre']) ?>"
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="4"
                           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"><?= htmlspecialchars($module['description'] ?? '') ?></textarea>
                </div>

                <div class="flex justify-end space-x-4 pt-4">
                    <a href="teacher_dashboard.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Annuler
                    </a>
                    <button type="submit" name="modifier_module" 
                            class="px-6 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        Modifier le module
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>