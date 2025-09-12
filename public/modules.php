<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérification connexion et rôle (enseignant ou administrateur)
if (!isLoggedIn() || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3)) {
    header('Location: ../public/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role_id'] == 3);

// Initialisation des variables
$error = '';
$success = '';
$cours_id = '';
$titre = '';
$ordre = '';
$editing_id = null;

// Récupérer la liste des cours
$cours_list = [];
if ($is_admin) {
    // Admin peut voir tous les cours
    $stmt = $pdo->prepare("SELECT id, titre FROM cours ORDER BY titre");
    $stmt->execute();
    $cours_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Enseignant ne voit que ses cours
    $stmt = $pdo->prepare("SELECT id, titre FROM cours WHERE enseignant_id = ? ORDER BY titre");
    $stmt->execute([$user_id]);
    $cours_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Traitement du formulaire d'ajout/modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cours_id = $_POST['cours_id'] ?? '';
    $titre = trim($_POST['titre'] ?? '');
    $ordre = $_POST['ordre'] ?? '';
    
    // Validation
    if (empty($cours_id) || empty($titre) || empty($ordre)) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        // Vérifier que l'enseignant a le droit de modifier ce cours
        if (!$is_admin) {
            $stmt = $pdo->prepare("SELECT id FROM cours WHERE id = ? AND enseignant_id = ?");
            $stmt->execute([$cours_id, $user_id]);
            if (!$stmt->fetch()) {
                $error = "Vous n'avez pas l'autorisation de modifier ce cours.";
            }
        }
        
        if (empty($error)) {
            try {
                if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
                    // Modification d'un module existant
                    $editing_id = $_POST['edit_id'];
                    $stmt = $pdo->prepare("UPDATE modules SET cours_id = ?, titre = ?, ordre = ? WHERE id = ?");
                    $stmt->execute([$cours_id, $titre, $ordre, $editing_id]);
                    $success = "Module modifié avec succès.";
                } else {
                    // Ajout d'un nouveau module
                    $stmt = $pdo->prepare("INSERT INTO modules (cours_id, titre, ordre) VALUES (?, ?, ?)");
                    $stmt->execute([$cours_id, $titre, $ordre]);
                    $success = "Module ajouté avec succès.";
                }
                
                // Réinitialiser le formulaire
                $cours_id = '';
                $titre = '';
                $ordre = '';
                $editing_id = null;
            } catch (PDOException $e) {
                $error = "Erreur lors de l'opération: " . $e->getMessage();
            }
        }
    }
}

// Pré-remplir le formulaire en cas d'édition
if (isset($_GET['edit'])) {
    $editing_id = intval($_GET['edit']);
    
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ?");
    $stmt->execute([$editing_id]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($module) {
        // Vérifier les permissions
        if (!$is_admin) {
            $stmt = $pdo->prepare("SELECT id FROM cours WHERE id = ? AND enseignant_id = ?");
            $stmt->execute([$module['cours_id'], $user_id]);
            if (!$stmt->fetch()) {
                $error = "Vous n'avez pas l'autorisation de modifier ce module.";
                $editing_id = null;
            }
        }
        
        if (empty($error)) {
            $cours_id = $module['cours_id'];
            $titre = $module['titre'];
            $ordre = $module['ordre'];
        }
    } else {
        $error = "Module introuvable.";
        $editing_id = null;
    }
}

// Suppression d'un module
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $stmt = $pdo->prepare("SELECT cours_id FROM modules WHERE id = ?");
    $stmt->execute([$delete_id]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($module) {
        // Vérifier les permissions
        if (!$is_admin) {
            $stmt = $pdo->prepare("SELECT id FROM cours WHERE id = ? AND enseignant_id = ?");
            $stmt->execute([$module['cours_id'], $user_id]);
            if (!$stmt->fetch()) {
                $error = "Vous n'avez pas l'autorisation de supprimer ce module.";
            }
        }
        
        if (empty($error)) {
            try {
                // Vérifier s'il y a des ressources associées
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM ressources WHERE module_id = ?");
                $stmt->execute([$delete_id]);
                $ressources_count = $stmt->fetchColumn();
                
                if ($ressources_count > 0) {
                    $error = "Impossible de supprimer ce module car il contient des ressources. Veuillez d'abord supprimer les ressources associées.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM modules WHERE id = ?");
                    $stmt->execute([$delete_id]);
                    $success = "Module supprimé avec succès.";
                }
            } catch (PDOException $e) {
                $error = "Erreur lors de la suppression: " . $e->getMessage();
            }
        }
    } else {
        $error = "Module introuvable.";
    }
}

// Récupérer la liste des modules avec les noms des cours
$modules = [];
if ($is_admin) {
    $stmt = $pdo->prepare("
        SELECT m.*, c.titre as cours_titre 
        FROM modules m 
        JOIN cours c ON m.cours_id = c.id 
        ORDER BY c.titre, m.ordre
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT m.*, c.titre as cours_titre 
        FROM modules m 
        JOIN cours c ON m.cours_id = c.id 
        WHERE c.enseignant_id = ?
        ORDER BY c.titre, m.ordre
    ");
    $stmt->execute([$user_id]);
}
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Modules</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .sortable-ghost {
            opacity: 0.5;
        }
        
        .module-row:hover {
            background-color: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- En-tête -->
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="flex-1 min-w-0">
                <h1 class="text-3xl font-bold text-gray-900">Gestion des Modules</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Créez et organisez les modules de vos cours
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="dashboard.php" class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-arrow-left mr-2"></i> Retour au tableau de bord
                </a>
            </div>
        </div>

        <!-- Messages d'alerte -->
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?= htmlspecialchars($success) ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Formulaire d'ajout/modification -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        <?= $editing_id ? 'Modifier le module' : 'Ajouter un nouveau module' ?>
                    </h2>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="edit_id" value="<?= $editing_id ?>">
                        
                        <div class="mb-4">
                            <label for="cours_id" class="block text-sm font-medium text-gray-700 mb-1">Cours</label>
                            <select id="cours_id" name="cours_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="">Sélectionner un cours</option>
                                <?php foreach ($cours_list as $cours): ?>
                                    <option value="<?= $cours['id'] ?>" <?= $cours_id == $cours['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cours['titre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="titre" class="block text-sm font-medium text-gray-700 mb-1">Titre du module</label>
                            <input type="text" id="titre" name="titre" value="<?= htmlspecialchars($titre) ?>" required 
                                class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                        </div>
                        
                        <div class="mb-6">
                            <label for="ordre" class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                            <input type="number" id="ordre" name="ordre" value="<?= htmlspecialchars($ordre) ?>" min="1" required 
                                class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                            <p class="mt-1 text-xs text-gray-500">Détermine l'ordre d'affichage du module dans la liste</p>
                        </div>
                        
                        <div class="flex items-center justify-end">
                            <?php if ($editing_id): ?>
                                <a href="modules.php" class="mr-4 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Annuler
                                </a>
                            <?php endif; ?>
                            
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas <?= $editing_id ? 'fa-edit' : 'fa-plus' ?> mr-2"></i>
                                <?= $editing_id ? 'Modifier' : 'Ajouter' ?> le module
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Liste des modules -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-4 py-5 sm:px-6 bg-gray-50">
                        <h3 class="text-lg font-medium text-gray-900">Liste des modules</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Tous les modules créés pour vos cours
                        </p>
                    </div>
                    
                    <div class="px-4 py-5 sm:p-6">
                        <?php if (empty($modules)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">Aucun module n'a été créé pour le moment.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-hidden border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cours</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordre</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($modules as $module): ?>
                                            <tr class="module-row">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($module['titre']) ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($module['cours_titre']) ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        <?= $module['ordre'] ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <a href="ressources.php?module_id=<?= $module['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Gérer les ressources">
                                                        <i class="fas fa-file"></i>
                                                    </a>
                                                    <a href="modules.php?edit=<?= $module['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="modules.php?delete=<?= $module['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce module ?')" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Statistiques -->
                <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <i class="fas fa-book-open text-white"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total des modules</dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold text-gray-900"><?= count($modules) ?></div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <i class="fas fa-book text-white"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Cours disponibles</dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold text-gray-900"><?= count($cours_list) ?></div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                    <i class="fas fa-list-ol text-white"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Ordre max</dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-2xl font-semibold text-gray-900">
                                                <?= !empty($modules) ? max(array_column($modules, 'ordre')) : 0 ?>
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour suggérer le prochain ordre disponible
        document.getElementById('cours_id').addEventListener('change', function() {
            const coursId = this.value;
            if (!coursId) return;
            
            // Envoyer une requête AJAX pour obtenir le dernier ordre utilisé pour ce cours
            fetch(`api/get_last_order.php?cours_id=${coursId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('ordre').value = data.last_order + 1;
                    }
                })
                .catch(error => console.error('Erreur:', error));
        });
        
        // Si on est en mode édition, désactiver la modification du cours
        <?php if ($editing_id): ?>
            document.getElementById('cours_id').disabled = true;
        <?php endif; ?>
    </script>
</body>
</html>