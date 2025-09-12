<?php
session_start();
require_once '../config/database.php';

// Vérification rôle enseignant
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit;
}

$enseignant_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de cours invalide.");
}
$cours_id = intval($_GET['id']);

// Récupérer le cours
$stmtCours = $pdo->prepare("SELECT c.*, f.nom AS filiere_nom FROM cours c LEFT JOIN filieres f ON c.filiere_id = f.id WHERE c.id = ? AND c.enseignant_id = ?");
$stmtCours->execute([$cours_id, $enseignant_id]);
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
    $imagePath = $cours['image_couverture'];
    $videoPath = $cours['video_cours'];
    $pdfPath = $cours['pdf_cours'];

    if (empty($titre)) $errors[] = "Le titre est obligatoire.";

    $hasVideo = isset($_FILES['video_cours']) && $_FILES['video_cours']['error'] === UPLOAD_ERR_OK;
    $hasPDF = isset($_FILES['pdf_cours']) && $_FILES['pdf_cours']['error'] === UPLOAD_ERR_OK;

    if ($hasVideo && $hasPDF) $errors[] = "Vous ne pouvez pas ajouter une vidéo et un PDF en même temps.";

    // Upload image
    if (isset($_FILES['image_couverture']) && $_FILES['image_couverture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_couverture']['tmp_name'];
        $fileName = basename($_FILES['image_couverture']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg','jpeg','png','gif'];
        if (!in_array($fileExt, $allowedExts)) $errors[] = "Image : formats autorisés jpg, jpeg, png, gif uniquement.";
        else {
            $newFileName = uniqid('img_').'.'.$fileExt;
            $destPath = __DIR__.'/uploads/'.$newFileName;
            if (!move_uploaded_file($fileTmpPath, $destPath)) $errors[] = "Erreur lors de l'upload de l'image.";
            else {
                if ($imagePath && file_exists(__DIR__.'/'.$imagePath)) unlink(__DIR__.'/'.$imagePath);
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
        if (!in_array($fileExt, $allowedVideoExts)) $errors[] = "Vidéo : formats autorisés mp4, webm, ogg uniquement.";
        else {
            $newFileName = uniqid('vid_').'.'.$fileExt;
            $destPath = __DIR__.'/uploads/'.$newFileName;
            if (!move_uploaded_file($fileTmpPath, $destPath)) $errors[] = "Erreur lors de l'upload de la vidéo.";
            else {
                if ($videoPath && file_exists(__DIR__.'/'.$videoPath)) unlink(__DIR__.'/'.$videoPath);
                if ($pdfPath && file_exists(__DIR__.'/'.$pdfPath)) { unlink(__DIR__.'/'.$pdfPath); $pdfPath = null; }
                $videoPath = 'uploads/'.$newFileName;
            }
        }
    }

    // Upload PDF
    if ($hasPDF && empty($errors)) {
        $fileTmpPath = $_FILES['pdf_cours']['tmp_name'];
        $fileName = basename($_FILES['pdf_cours']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileExt !== 'pdf') $errors[] = "PDF : format autorisé uniquement PDF.";
        else {
            $newFileName = uniqid('pdf_').'.'.$fileExt;
            $destPath = __DIR__.'/uploads/'.$newFileName;
            if (!move_uploaded_file($fileTmpPath, $destPath)) $errors[] = "Erreur lors de l'upload du PDF.";
            else {
                if ($pdfPath && file_exists(__DIR__.'/'.$pdfPath)) unlink(__DIR__.'/'.$pdfPath);
                if ($videoPath && file_exists(__DIR__.'/'.$videoPath)) { unlink(__DIR__.'/'.$videoPath); $videoPath = null; }
                $pdfPath = 'uploads/'.$newFileName;
            }
        }
    }

    if (empty($errors)) {
        $stmtUpdate = $pdo->prepare("UPDATE cours SET titre=?, description=?, image_couverture=?, video_cours=?, pdf_cours=? WHERE id=? AND enseignant_id=?");
        $stmtUpdate->execute([$titre, $description, $imagePath, $videoPath, $pdfPath, $cours_id, $enseignant_id]);
        $success = "Cours mis à jour avec succès !";
        $stmtCours->execute([$cours_id, $enseignant_id]);
        $cours = $stmtCours->fetch(PDO::FETCH_ASSOC);
    }
}
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
    body { font-family: 'Poppins', sans-serif; }
    .btn-teal { transition: all 0.3s ease; }
    .btn-teal:hover { transform: translateY(-2px) scale(1.02); }
    input, textarea { transition: all 0.2s; }
    input:focus, textarea:focus { border-color: #14b8a6; box-shadow: 0 0 10px rgba(20,184,166,0.3); }
    label i { color: #14b8a6; margin-right: 5px; }
</style>
</head>
<body class="bg-gray-50 min-h-screen p-8 text-gray-800">

<div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-lg p-8">
    <a href="cours.php" class="text-teal-700 font-semibold hover:underline mb-6 inline-block"><i class="fas fa-arrow-left"></i> Retour à la liste</a>

    <h1 class="text-4xl font-bold mb-8 text-teal-700 flex items-center"><i class="fas fa-edit mr-3"></i> Modifier le cours</h1>

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
        <div>
            <label for="titre" class="block font-medium mb-2"><i class="fas fa-heading"></i> Titre <span class="text-red-600">*</span></label>
            <input type="text" id="titre" name="titre" required value="<?= htmlspecialchars($cours['titre']) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-4 focus:ring-teal-400" />
        </div>

        <div>
            <label for="description" class="block font-medium mb-2"><i class="fas fa-align-left"></i> Description</label>
            <textarea id="description" name="description" rows="5" class="w-full border border-gray-300 rounded-lg px-4 py-3 resize-none focus:outline-none focus:ring-4 focus:ring-teal-400"><?= htmlspecialchars($cours['description']) ?></textarea>
        </div>

        <div>
            <label class="block font-medium mb-2"><i class="fas fa-layer-group"></i> Filière <span class="text-gray-500">(fixée par l'administrateur)</span></label>
            <input type="text" class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-100" value="<?= htmlspecialchars($cours['filiere_nom']) ?>" disabled />
        </div>

        <div>
            <label class="block font-medium mb-2"><i class="fas fa-image"></i> Image couverture actuelle :</label>
            <?php if ($cours['image_couverture'] && file_exists(__DIR__.'/'.$cours['image_couverture'])): ?>
                <img src="<?= htmlspecialchars($cours['image_couverture']) ?>" alt="Image couverture" class="w-64 h-auto rounded-lg mb-2 border shadow" />
            <?php else: ?>
                <p class="italic text-gray-500">Pas d'image</p>
            <?php endif; ?>
            <label for="image_couverture" class="block font-medium mb-1 mt-4"><i class="fas fa-upload"></i> Changer l'image de couverture :</label>
            <input type="file" id="image_couverture" name="image_couverture" accept=".jpg,.jpeg,.png,.gif" class="block w-full text-gray-600" />
        </div>

        <div>
            <label class="block font-medium mb-2"><i class="fas fa-video"></i> Vidéo actuelle :</label>
            <?php if ($cours['video_cours'] && file_exists(__DIR__.'/'.$cours['video_cours'])): ?>
                <video controls class="w-full max-w-md rounded-lg mb-2 bg-black shadow">
                    <source src="<?= htmlspecialchars($cours['video_cours']) ?>" type="video/mp4" />
                    Votre navigateur ne supporte pas la vidéo.
                </video>
            <?php else: ?>
                <p class="italic text-gray-500">Pas de vidéo</p>
            <?php endif; ?>
            <label for="video_cours" class="block font-medium mb-1 mt-4"><i class="fas fa-upload"></i> Changer la vidéo :</label>
            <input type="file" id="video_cours" name="video_cours" accept="video/mp4,video/webm,video/ogg" class="block w-full text-gray-600" />
        </div>

        <div>
            <label class="block font-medium mb-2"><i class="fas fa-file-pdf"></i> Document PDF actuel :</label>
            <?php if ($cours['pdf_cours'] && file_exists(__DIR__.'/'.$cours['pdf_cours'])): ?>
                <a href="<?= htmlspecialchars($cours['pdf_cours']) ?>" target="_blank" class="text-orange-600 underline font-semibold mb-2 inline-block"><i class="fas fa-file-pdf mr-1"></i> Voir le PDF</a>
            <?php else: ?>
                <p class="italic text-gray-500">Pas de PDF</p>
            <?php endif; ?>
            <label for="pdf_cours" class="block font-medium mb-1 mt-4"><i class="fas fa-upload"></i> Changer le PDF :</label>
            <input type="file" id="pdf_cours" name="pdf_cours" accept=".pdf" class="block w-full text-gray-600" />
        </div>

        <p class="text-sm text-gray-600 italic mt-4 mb-6"><i class="fas fa-info-circle mr-1"></i> Note : Vous ne pouvez avoir qu'une vidéo ou un PDF par cours.</p>

        <button type="submit" class="btn-teal bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold shadow flex items-center justify-center gap-2">
            <i class="fas fa-save"></i> Mettre à jour le cours
        </button>
    </form>
</div>

</body>
</html>
