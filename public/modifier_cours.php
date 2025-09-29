<?php
session_start();
require_once '../config/database.php';

// Vérification rôle enseignant (2) ou admin (3)
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3)) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de cours invalide.");
}
$cours_id = intval($_GET['id']);

// Récupérer le cours avec des permissions différentes selon le rôle
if ($role_id == 3) {
    // Admin peut modifier tous les cours
    $stmtCours = $pdo->prepare("SELECT c.*, f.nom AS filiere_nom, u.nom AS enseignant_nom 
                               FROM cours c 
                               LEFT JOIN filieres f ON c.filiere_id = f.id 
                               LEFT JOIN users u ON c.enseignant_id = u.id 
                               WHERE c.id = ?");
    $stmtCours->execute([$cours_id]);
} else {
    // Enseignant ne peut modifier que ses propres cours
    $stmtCours = $pdo->prepare("SELECT c.*, f.nom AS filiere_nom, u.nom AS enseignant_nom 
                               FROM cours c 
                               LEFT JOIN filieres f ON c.filiere_id = f.id 
                               LEFT JOIN users u ON c.enseignant_id = u.id 
                               WHERE c.id = ? AND c.enseignant_id = ?");
    $stmtCours->execute([$cours_id, $user_id]);
}

$cours = $stmtCours->fetch(PDO::FETCH_ASSOC);

if (!$cours) {
    die("Cours non trouvé ou accès refusé.");
}

$errors = [];
$success = "";

// Traitement modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $annee = trim($_POST['annee']);
    $imagePath = $cours['image_couverture'];
    $videoPath = $cours['video_cours'];
    $pdfPath = $cours['pdf_cours'];

    // Validation
    if (empty($titre)) $errors[] = "Le titre est obligatoire.";
    
    if (empty($annee) || !in_array($annee, ['premiere', 'deuxieme'])) {
        $errors[] = "Veuillez sélectionner une année valide.";
    }

    $hasVideo = isset($_FILES['video_cours']) && $_FILES['video_cours']['error'] === UPLOAD_ERR_OK;
    $hasPDF = isset($_FILES['pdf_cours']) && $_FILES['pdf_cours']['error'] === UPLOAD_ERR_OK;

    if ($hasVideo && $hasPDF) $errors[] = "Vous ne pouvez pas ajouter une vidéo et un PDF en même temps.";

    // Upload image
    if (isset($_FILES['image_couverture']) && $_FILES['image_couverture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_couverture']['tmp_name'];
        $fileName = basename($_FILES['image_couverture']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg','jpeg','png','gif'];
        if (!in_array($fileExt, $allowedExts)) {
            $errors[] = "Image : formats autorisés jpg, jpeg, png, gif uniquement.";
        } else {
            $newFileName = uniqid('img_').'.'.$fileExt;
            $destPath = __DIR__.'/uploads/'.$newFileName;
            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $errors[] = "Erreur lors de l'upload de l'image.";
            } else {
                // Supprimer l'ancienne image si elle existe
                if ($imagePath && file_exists(__DIR__.'/'.$imagePath)) {
                    unlink(__DIR__.'/'.$imagePath);
                }
                $imagePath = 'uploads/'.$newFileName;
            }
        }
    }

    // Upload vidéo
    if ($hasVideo && empty($errors)) {
        $fileTmpPath = $_FILES['video_cours']['tmp_name'];
        $fileName = basename($_FILES['video_cours']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedVideoExts = ['mp4','webm','ogg'];
        if (!in_array($fileExt, $allowedVideoExts)) {
            $errors[] = "Vidéo : formats autorisés mp4, webm, ogg uniquement.";
        } else {
            $newFileName = uniqid('vid_').'.'.$fileExt;
            $destPath = __DIR__.'/uploads/'.$newFileName;
            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $errors[] = "Erreur lors de l'upload de la vidéo.";
            } else {
                // Supprimer les anciens fichiers
                if ($videoPath && file_exists(__DIR__.'/'.$videoPath)) {
                    unlink(__DIR__.'/'.$videoPath);
                }
                if ($pdfPath && file_exists(__DIR__.'/'.$pdfPath)) { 
                    unlink(__DIR__.'/'.$pdfPath); 
                    $pdfPath = null; 
                }
                $videoPath = 'uploads/'.$newFileName;
            }
        }
    }

    // Upload PDF
    if ($hasPDF && empty($errors)) {
        $fileTmpPath = $_FILES['pdf_cours']['tmp_name'];
        $fileName = basename($_FILES['pdf_cours']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileExt !== 'pdf') {
            $errors[] = "PDF : format autorisé uniquement PDF.";
        } else {
            $newFileName = uniqid('pdf_').'.'.$fileExt;
            $destPath = __DIR__.'/uploads/'.$newFileName;
            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $errors[] = "Erreur lors de l'upload du PDF.";
            } else {
                // Supprimer les anciens fichiers
                if ($pdfPath && file_exists(__DIR__.'/'.$pdfPath)) {
                    unlink(__DIR__.'/'.$pdfPath);
                }
                if ($videoPath && file_exists(__DIR__.'/'.$videoPath)) { 
                    unlink(__DIR__.'/'.$videoPath); 
                    $videoPath = null; 
                }
                $pdfPath = 'uploads/'.$newFileName;
            }
        }
    }

    if (empty($errors)) {
        // Mise à jour selon le rôle
        if ($role_id == 3) {
            // Admin peut modifier tous les champs
            $stmtUpdate = $pdo->prepare("UPDATE cours SET titre=?, description=?, annee=?, image_couverture=?, video_cours=?, pdf_cours=? WHERE id=?");
            $stmtUpdate->execute([$titre, $description, $annee, $imagePath, $videoPath, $pdfPath, $cours_id]);
        } else {
            // Enseignant ne peut modifier que ses propres cours
            $stmtUpdate = $pdo->prepare("UPDATE cours SET titre=?, description=?, annee=?, image_couverture=?, video_cours=?, pdf_cours=? WHERE id=? AND enseignant_id=?");
            $stmtUpdate->execute([$titre, $description, $annee, $imagePath, $videoPath, $pdfPath, $cours_id, $user_id]);
        }
        
        $success = "Cours mis à jour avec succès !";
        
        // Recharger les données du cours
        if ($role_id == 3) {
            $stmtCours->execute([$cours_id]);
        } else {
            $stmtCours->execute([$cours_id, $user_id]);
        }
        $cours = $stmtCours->fetch(PDO::FETCH_ASSOC);
    }
}

// Fonction pour déterminer le tableau de bord
function getDashboardUrl($role_id) {
    switch ($role_id) {
        case 1: return "student_dashboard.php";
        case 2: return "teacher_dashboard.php";
        case 3: return "admin_dashboard.php";
        default: return "dashboard.php";
    }
}

$dashboard_url = getDashboardUrl($role_id);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Modifier Cours - <?= htmlspecialchars($cours['titre']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body { font-family: 'Poppins', sans-serif; background-color: #f5f7fa; }
    .btn-teal { transition: all 0.3s ease; }
    .btn-teal:hover { transform: translateY(-2px) scale(1.02); }
    input, textarea, select { transition: all 0.2s; }
    input:focus, textarea:focus, select:focus { border-color: #14b8a6; box-shadow: 0 0 10px rgba(20,184,166,0.3); }
    label i { color: #14b8a6; margin-right: 5px; }
    .admin-badge { background: linear-gradient(45deg, #FF9800, #F57C00); }
    .teacher-badge { background: linear-gradient(45deg, #009688, #00796B); }
</style>
</head>
<body class="min-h-screen p-4 md:p-8 text-gray-800">

<div class="max-w-6xl mx-auto">
    <!-- Header avec navigation -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="<?= $dashboard_url ?>" class="text-teal-700 font-semibold hover:underline inline-flex items-center">
                <i class="fas fa-tachometer-alt mr-2"></i> Tableau de bord
            </a>
            <span class="mx-2 text-gray-400">/</span>
            <a href="cours.php" class="text-teal-700 font-semibold hover:underline inline-flex items-center">
                <i class="fas fa-book mr-2"></i> Mes cours
            </a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-600">Modifier</span>
        </div>
        
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 rounded-full text-white text-sm font-medium <?= $role_id == 3 ? 'admin-badge' : 'teacher-badge' ?>">
                <i class="fas fa-<?= $role_id == 3 ? 'crown' : 'chalkboard-teacher' ?> mr-1"></i>
                <?= $role_id == 3 ? 'Administrateur' : 'Enseignant' ?>
            </span>
            <?php if ($role_id == 3): ?>
                <span class="text-sm text-gray-600">(Accès complet)</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <h1 class="text-3xl md:text-4xl font-bold text-teal-700 flex items-center">
                <i class="fas fa-edit mr-3"></i> Modifier le cours
            </h1>
            <?php if ($role_id == 3): ?>
                <div class="mt-2 md:mt-0 text-sm text-gray-600 bg-blue-50 px-3 py-2 rounded-lg">
                    <i class="fas fa-info-circle mr-1"></i> Mode administration - Modification de tous les cours
                </div>
            <?php endif; ?>
        </div>

        <!-- Informations sur l'enseignant (visible seulement pour l'admin) -->
        <?php if ($role_id == 3): ?>
            <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-user-tie mr-2"></i> Informations sur l'enseignant
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium">Enseignant :</span>
                        <span class="text-gray-600"><?= htmlspecialchars($cours['enseignant_nom']) ?></span>
                    </div>
                    <div>
                        <span class="font-medium">Filière :</span>
                        <span class="text-gray-600"><?= htmlspecialchars($cours['filiere_nom']) ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-6 p-4 rounded bg-green-100 text-green-800 border border-green-300 flex items-center">
            <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <?php foreach ($errors as $err): ?>
        <div class="mb-3 p-3 rounded bg-red-100 text-red-700 border border-red-300 flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($err) ?>
        </div>
        <?php endforeach; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="titre" class="block font-medium mb-2">
                        <i class="fas fa-heading"></i> Titre <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="titre" name="titre" required 
                           value="<?= htmlspecialchars($cours['titre']) ?>" 
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-4 focus:ring-teal-400" />
                </div>

                <div>
                    <label for="annee" class="block font-medium mb-2">
                        <i class="fas fa-calendar-alt"></i> Année <span class="text-red-600">*</span>
                    </label>
                    <select id="annee" name="annee" required 
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-4 focus:ring-teal-400">
                        <option value="">Sélectionnez une année</option>
                        <option value="premiere" <?= $cours['annee'] === 'premiere' ? 'selected' : '' ?>>Première année</option>
                        <option value="deuxieme" <?= $cours['annee'] === 'deuxieme' ? 'selected' : '' ?>>Deuxième année</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="description" class="block font-medium mb-2">
                    <i class="fas fa-align-left"></i> Description
                </label>
                <textarea id="description" name="description" rows="5" 
                          class="w-full border border-gray-300 rounded-lg px-4 py-3 resize-none focus:outline-none focus:ring-4 focus:ring-teal-400"><?= htmlspecialchars($cours['description']) ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-medium mb-2">
                        <i class="fas fa-layer-group"></i> Filière
                    </label>
                    <input type="text" class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-100" 
                           value="<?= htmlspecialchars($cours['filiere_nom']) ?>" disabled />
                </div>

                <div>
                    <label class="block font-medium mb-2">
                        <i class="fas fa-user-tie"></i> Statut
                    </label>
                    <input type="text" class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-100" 
                           value="<?= $role_id == 3 ? 'Administrateur (Modification complète)' : 'Enseignant (Vos cours seulement)' ?>" disabled />
                </div>
            </div>

            <!-- Section Médias -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-images mr-2"></i> Médias du cours
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Image de couverture -->
                    <div class="space-y-4">
                        <div>
                            <label class="block font-medium mb-2">
                                <i class="fas fa-image"></i> Image couverture actuelle :
                            </label>
                            <?php if ($cours['image_couverture'] && file_exists(__DIR__.'/'.$cours['image_couverture'])): ?>
                                <img src="<?= htmlspecialchars($cours['image_couverture']) ?>" 
                                     alt="Image couverture" 
                                     class="w-full max-w-xs h-auto rounded-lg mb-2 border shadow" />
                                <button type="button" onclick="removeFile('image')" 
                                        class="text-red-600 text-sm hover:underline flex items-center">
                                    <i class="fas fa-trash mr-1"></i> Supprimer cette image
                                </button>
                            <?php else: ?>
                                <p class="italic text-gray-500">Aucune image de couverture</p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <label for="image_couverture" class="block font-medium mb-1">
                                <i class="fas fa-upload"></i> Changer l'image :
                            </label>
                            <input type="file" id="image_couverture" name="image_couverture" 
                                   accept=".jpg,.jpeg,.png,.gif" 
                                   class="block w-full text-gray-600 border border-gray-300 rounded-lg p-2" />
                        </div>
                    </div>

                    <!-- Vidéo -->
                    <div class="space-y-4">
                        <div>
                            <label class="block font-medium mb-2">
                                <i class="fas fa-video"></i> Vidéo actuelle :
                            </label>
                            <?php if ($cours['video_cours'] && file_exists(__DIR__.'/'.$cours['video_cours'])): ?>
                                <div class="relative">
                                    <video controls class="w-full rounded-lg mb-2 bg-black shadow">
                                        <source src="<?= htmlspecialchars($cours['video_cours']) ?>" type="video/mp4" />
                                        Votre navigateur ne supporte pas la vidéo.
                                    </video>
                                    <button type="button" onclick="removeFile('video')" 
                                            class="absolute top-2 right-2 bg-red-600 text-white p-1 rounded-full text-xs">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <p class="text-sm text-gray-600">Format: MP4</p>
                            <?php else: ?>
                                <p class="italic text-gray-500">Aucune vidéo</p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <label for="video_cours" class="block font-medium mb-1">
                                <i class="fas fa-upload"></i> Changer la vidéo :
                            </label>
                            <input type="file" id="video_cours" name="video_cours" 
                                   accept="video/mp4,video/webm,video/ogg" 
                                   class="block w-full text-gray-600 border border-gray-300 rounded-lg p-2" />
                        </div>
                    </div>

                    <!-- PDF -->
                    <div class="space-y-4">
                        <div>
                            <label class="block font-medium mb-2">
                                <i class="fas fa-file-pdf"></i> Document PDF actuel :
                            </label>
                            <?php if ($cours['pdf_cours'] && file_exists(__DIR__.'/'.$cours['pdf_cours'])): ?>
                                <div class="flex items-center gap-3 mb-2">
                                    <i class="fas fa-file-pdf text-red-500 text-2xl"></i>
                                    <div>
                                        <a href="<?= htmlspecialchars($cours['pdf_cours']) ?>" target="_blank" 
                                           class="text-orange-600 underline font-semibold hover:text-orange-700">
                                            <i class="fas fa-external-link-alt mr-1"></i>Ouvrir le PDF
                                        </a>
                                        <button type="button" onclick="removeFile('pdf')" 
                                                class="ml-3 text-red-600 text-sm hover:underline">
                                            <i class="fas fa-trash mr-1"></i>Supprimer
                                        </button>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600">Document téléchargeable</p>
                            <?php else: ?>
                                <p class="italic text-gray-500">Aucun document PDF</p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <label for="pdf_cours" class="block font-medium mb-1">
                                <i class="fas fa-upload"></i> Changer le PDF :
                            </label>
                            <input type="file" id="pdf_cours" name="pdf_cours" accept=".pdf" 
                                   class="block w-full text-gray-600 border border-gray-300 rounded-lg p-2" />
                        </div>
                    </div>
                </div>

                <p class="text-sm text-gray-600 italic mt-4">
                    <i class="fas fa-info-circle mr-1"></i> 
                    Note : Vous ne pouvez avoir qu'une vidéo ou un PDF par cours. L'ajout d'un nouveau fichier remplacera l'ancien.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                <button type="submit" 
                        class="btn-teal bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-lg font-semibold shadow flex items-center justify-center gap-2 flex-1">
                    <i class="fas fa-save"></i> Mettre à jour le cours
                </button>
                
                <a href="cours.php" 
                   class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-8 py-3 rounded-lg font-semibold shadow flex items-center justify-center gap-2 flex-1">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function removeFile(type) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?')) {
        // Cette fonctionnalité nécessiterait une implémentation AJAX pour une suppression en temps réel
        // Pour l'instant, on peut simplement désélectionner le fichier
        switch(type) {
            case 'image':
                document.getElementById('image_couverture').value = '';
                break;
            case 'video':
                document.getElementById('video_cours').value = '';
                break;
            case 'pdf':
                document.getElementById('pdf_cours').value = '';
                break;
        }
        alert('Le fichier sera supprimé lors de la sauvegarde. Actualisez la page pour voir les changements.');
    }
}

// Empêcher la sélection simultanée de vidéo et PDF
document.getElementById('video_cours').addEventListener('change', function() {
    if (this.files.length > 0) {
        document.getElementById('pdf_cours').value = '';
    }
});

document.getElementById('pdf_cours').addEventListener('change', function() {
    if (this.files.length > 0) {
        document.getElementById('video_cours').value = '';
    }
});
</script>

</body>
</html>