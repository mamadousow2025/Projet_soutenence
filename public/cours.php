<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit;
}
$enseignant_id = $_SESSION['user_id'];

$stmtFilieres = $pdo->query("SELECT id, nom FROM filieres ORDER BY nom");
$filieres = $stmtFilieres->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$success = "";

// Traitement ajout cours
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cours'])) {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $filiere_id = intval($_POST['filiere_id']);
    $imagePath = null;
    $videoPath = null;
    $pdfPath = null;

    // Validation titre et filière
    if (empty($titre)) $errors[] = "Le titre est obligatoire.";
    if ($filiere_id <= 0) $errors[] = "Veuillez choisir une filière valide.";

    // On impose que l'enseignant ne puisse pas ajouter vidéo ET pdf en même temps
    $hasVideo = isset($_FILES['video_cours']) && $_FILES['video_cours']['error'] === UPLOAD_ERR_OK;
    $hasPDF = isset($_FILES['pdf_cours']) && $_FILES['pdf_cours']['error'] === UPLOAD_ERR_OK;

    if ($hasVideo && $hasPDF) {
        $errors[] = "Vous ne pouvez pas ajouter une vidéo et un PDF en même temps. Choisissez l'un ou l'autre.";
    }

    // Upload image couverture (optionnel)
    if (isset($_FILES['image_couverture']) && $_FILES['image_couverture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_couverture']['tmp_name'];
        $fileName = basename($_FILES['image_couverture']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExt, $allowedExts)) {
            $errors[] = "Image : formats autorisés jpg, jpeg, png, gif uniquement.";
        } else {
            $newFileName = uniqid('img_') . '.' . $fileExt;
            $destPath = __DIR__ . '/uploads/' . $newFileName;
            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $errors[] = "Erreur lors de l'upload de l'image.";
            } else {
                $imagePath = 'uploads/' . $newFileName;
            }
        }
    }

    // Upload vidéo (exclusif)
    if ($hasVideo && empty($errors)) {
        $fileTmpPath = $_FILES['video_cours']['tmp_name'];
        $fileName = basename($_FILES['video_cours']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedVideoExts = ['mp4', 'webm', 'ogg'];
        if (!in_array($fileExt, $allowedVideoExts)) {
            $errors[] = "Vidéo : formats autorisés mp4, webm, ogg uniquement.";
        } else {
            $newFileName = uniqid('vid_') . '.' . $fileExt;
            $destPath = __DIR__ . '/uploads/' . $newFileName;
            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $errors[] = "Erreur lors de l'upload de la vidéo.";
            } else {
                $videoPath = 'uploads/' . $newFileName;
            }
        }
    }

    // Upload PDF (exclusif)
    if ($hasPDF && empty($errors)) {
        $fileTmpPath = $_FILES['pdf_cours']['tmp_name'];
        $fileName = basename($_FILES['pdf_cours']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileExt !== 'pdf') {
            $errors[] = "PDF : format autorisé uniquement PDF.";
        } else {
            $newFileName = uniqid('pdf_') . '.' . $fileExt;
            $destPath = __DIR__ . '/uploads/' . $newFileName;
            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $errors[] = "Erreur lors de l'upload du PDF.";
            } else {
                $pdfPath = 'uploads/' . $newFileName;
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO cours (enseignant_id, titre, description, filiere_id, image_couverture, video_cours, pdf_cours) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$enseignant_id, $titre, $description, $filiere_id, $imagePath, $videoPath, $pdfPath]);
        $success = "Cours ajouté avec succès !";
        // Clear POST to reset form after success
        $_POST = [];
    }
}

// Récupérer les cours de l’enseignant
$stmtCours = $pdo->prepare("
    SELECT c.*, f.nom AS filiere_nom 
    FROM cours c 
    JOIN filieres f ON c.filiere_id = f.id 
    WHERE c.enseignant_id = ? 
    ORDER BY c.created_at DESC
");
$stmtCours->execute([$enseignant_id]);
$cours = $stmtCours->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Enseignant - Mes Cours</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        /* Couleurs principales */
        :root {
            --color-teal: #009688;
            --color-orange: #FF9800;
        }
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Sidebar */
        aside {
            background-color: var(--color-teal);
            color: #fff;
        }
        aside a {
            color: #b2dfdb;
        }
        aside a:hover {
            color: #e0f2f1;
        }
        .btn-teal {
            background-color: var(--color-teal);
            color: white;
        }
        .btn-teal:hover {
            background-color: #00796b;
        }
        .btn-orange {
            background-color: var(--color-orange);
            color: white;
        }
        .btn-orange:hover {
            background-color: #e67e22;
        }
        /* Card hover */
        .card:hover {
            box-shadow: 0 10px 20px rgba(0,0,0,0.12);
            transform: translateY(-4px);
            transition: all 0.3s ease;
        }
        /* Ligne clamped */
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;  
            overflow: hidden;
        }
        /* Modal PDF */
        .modal-bg {
            background: rgba(0,0,0,0.6);
        }
        .modal-content {
            background: white;
            max-width: 90vw;
            max-height: 90vh;
            overflow: auto;
            border-radius: 8px;
            padding: 1rem;
            position: relative;
        }
        .close-btn {
            position: absolute;
            top: 8px;
            right: 12px;
            cursor: pointer;
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen text-gray-800">

    <!-- Sidebar -->
    <aside class="w-64 flex flex-col shadow-lg">
        <div class="p-6 border-b border-teal-700 flex items-center space-x-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="M12 14l9-5-9-5-9 5 9 5z" />
              <path d="M12 14l6.16-3.422A12.083 12.083 0 0112 21.5a12.083 12.083 0 01-6.16-10.922L12 14z" />
            </svg>
            <h1 class="text-2xl font-bold text-white">Espace Enseignant</h1>
        </div>
        <nav class="flex-grow px-6 py-4 space-y-4">
            <a href="#" class="flex items-center gap-3 font-semibold hover:text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6" />
                </svg>
                Tableau de bord
            </a>
            <a href="#form-add" class="flex items-center gap-3 hover:text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M12 4v16m8-8H4" />
                </svg>
                Ajouter un cours
            </a>
            <a href="#cours-list" class="flex items-center gap-3 hover:text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                Mes cours
            </a>
        </nav>
        <div class="p-6 border-t border-teal-700">
            <a href="logout.php" class="text-red-300 hover:text-red-100 font-semibold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M17 16l4-4m0 0l-4-4m4 4H7" />
                </svg>
                Déconnexion
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-10 overflow-auto">

        <h2 class="text-4xl font-extrabold text-teal-700 mb-10 border-b-4 border-orange-500 pb-2">Gestion des cours</h2>

        <!-- Messages -->
        <div id="messages" class="max-w-4xl mb-10">
            <?php foreach ($errors as $err): ?>
                <div class="mb-3 p-4 rounded-md bg-red-100 text-red-700 border border-red-300 shadow-sm"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
            <?php if ($success): ?>
                <div class="mb-3 p-4 rounded-md bg-green-100 text-green-700 border border-green-300 shadow-sm"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
        </div>

        <!-- Formulaire ajout cours -->
        <section id="form-add" class="max-w-4xl bg-white p-10 rounded-2xl shadow-lg mb-16">
            <h3 class="text-2xl font-semibold mb-8 text-teal-700 flex items-center gap-3 border-b-2 border-orange-500 pb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="inline-block w-7 h-7 text-teal-600" fill="none" stroke="var(--color-teal)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M12 4v16m8-8H4" />
                </svg>
                Ajouter un nouveau cours
            </h3>

            <form method="POST" enctype="multipart/form-data" class="space-y-8" novalidate>
                <input type="hidden" name="add_cours" value="1" />

                <div>
                    <label for="titre" class="block text-lg font-medium text-gray-700 mb-2">Titre du cours <span class="text-red-600">*</span></label>
                    <input type="text" id="titre" name="titre" required
                        value="<?= isset($_POST['titre']) ? htmlspecialchars($_POST['titre']) : '' ?>"
                        placeholder="Ex : Introduction à la Programmation"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-4 focus:ring-teal-400 transition" />
                </div>

                <div>
                    <label for="description" class="block text-lg font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="description" name="description" rows="5"
                        placeholder="Une brève description du cours"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 resize-none focus:outline-none focus:ring-4 focus:ring-teal-400 transition"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                </div>

                <div>
                    <label for="filiere_id" class="block text-lg font-medium text-gray-700 mb-2">Filière <span class="text-red-600">*</span></label>
                    <select id="filiere_id" name="filiere_id" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-4 focus:ring-teal-400 transition">
                        <option value="">-- Choisir une filière --</option>
                        <?php foreach ($filieres as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= (isset($_POST['filiere_id']) && $_POST['filiere_id'] == $f['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="image_couverture" class="block text-lg font-medium text-gray-700 mb-2">Image de couverture (jpg, png, gif)</label>
                    <input type="file" id="image_couverture" name="image_couverture" accept=".jpg,.jpeg,.png,.gif" class="block w-full text-gray-600" />
                </div>

                <p class="text-sm text-gray-600 mb-2 italic">Vous pouvez soit ajouter une vidéo <b>ou</b> un document PDF. Ne mettez pas les deux en même temps.</p>

                <div>
                    <label for="video_cours" class="block text-lg font-medium text-gray-700 mb-2">Vidéo du cours (mp4, webm, ogg)</label>
                    <input type="file" id="video_cours" name="video_cours" accept="video/mp4,video/webm,video/ogg" class="block w-full text-gray-600" />
                </div>

                <div>
                    <label for="pdf_cours" class="block text-lg font-medium text-gray-700 mb-2">Document PDF (téléchargeable)</label>
                    <input type="file" id="pdf_cours" name="pdf_cours" accept=".pdf" class="block w-full text-gray-600" />
                </div>

                <button type="submit" class="btn-teal inline-flex items-center gap-3 font-semibold rounded-lg px-6 py-3 transition shadow-md hover:shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M12 4v16m8-8H4" />
                    </svg>
                    Ajouter le cours
                </button>
            </form>
        </section>

        <!-- Liste des cours -->
        <section id="cours-list" class="max-w-7xl grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

            <?php if (count($cours) > 0): ?>
                <?php foreach ($cours as $c): ?>
                <article class="card bg-white rounded-2xl shadow-md overflow-hidden flex flex-col hover:shadow-xl transition transform hover:-translate-y-1">
                    <div class="relative h-48 bg-gray-100 flex items-center justify-center overflow-hidden">
                        <?php if ($c['image_couverture']): ?>
                            <img src="<?= htmlspecialchars($c['image_couverture']) ?>" alt="Image couverture <?= htmlspecialchars($c['titre']) ?>" class="object-cover w-full h-full" />
                        <?php elseif ($c['video_cours']): ?>
                            <video controls class="object-contain max-h-48 bg-black w-full" preload="metadata">
                                <source src="<?= htmlspecialchars($c['video_cours']) ?>" type="video/mp4" />
                                Votre navigateur ne supporte pas la vidéo.
                            </video>
                        <?php elseif ($c['pdf_cours']): ?>
                            <div class="flex flex-col items-center justify-center p-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-orange-500 mb-2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M12 2v20m8-8H4" />
                                </svg>
                                <button 
                                    onclick="openPdfModal('<?= htmlspecialchars($c['pdf_cours']) ?>')"
                                    class="btn-orange px-4 py-2 rounded-lg font-semibold shadow hover:bg-orange-600 transition">
                                    Visualiser le PDF
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="text-gray-400 italic font-semibold">Pas d'image / vidéo / PDF</div>
                        <?php endif; ?>
                    </div>
                    <div class="p-6 flex flex-col justify-between flex-grow">
                        <header>
                            <h4 class="text-xl font-bold text-teal-700 truncate" title="<?= htmlspecialchars($c['titre']) ?>"><?= htmlspecialchars($c['titre']) ?></h4>
                            <p class="mt-2 text-gray-700 line-clamp-3 whitespace-pre-line"><?= nl2br(htmlspecialchars($c['description'])) ?></p>
                        </header>
                        <div class="mt-4 flex justify-between items-center text-sm text-gray-600">
                            <span class="font-semibold text-orange-500"><?= htmlspecialchars($c['filiere_nom']) ?></span>
                            <time datetime="<?= htmlspecialchars($c['created_at']) ?>" class="italic"><?= date('d/m/Y', strtotime($c['created_at'])) ?></time>
                        </div>
                        <div class="mt-5 flex justify-between items-center gap-4">

                            <?php if ($c['pdf_cours'] && !$c['video_cours']): ?>
                            <a href="<?= htmlspecialchars($c['pdf_cours']) ?>" download
                                class="btn-orange px-4 py-2 rounded-lg flex items-center gap-2 text-white hover:bg-orange-600 transition shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M12 4v16m8-8H4" />
                                </svg>
                                Télécharger PDF
                            </a>
                            <?php endif; ?>

                            <a href="modifier_cours.php?id=<?= $c['id'] ?>" 
                               class="text-teal-700 hover:text-teal-900 transition" title="Modifier">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3-6 6H9v-3z" />
                                </svg>
                            </a>

                            <form method="POST" action="supprimer_cours.php" onsubmit="return confirm('Voulez-vous vraiment supprimer ce cours ?');" class="inline">
                                <input type="hidden" name="cours_id" value="<?= $c['id'] ?>" />
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Supprimer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M19 7l-1 12a2 2 0 01-2 2H8a2 2 0 01-2-2L5 7m5-4h4m-4 0a2 2 0 00-2 2v0h8v0a2 2 0 00-2-2m-4 0v0" />
                                    </svg>
                                </button>
                            </form>

                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-600 italic text-center col-span-full">Vous n'avez pas encore ajouté de cours.</p>
            <?php endif; ?>
        </section>

    </main>

    <!-- Modal PDF -->
    <div id="pdfModal" class="fixed inset-0 hidden items-center justify-center modal-bg z-50">
        <div class="modal-content">
            <button class="close-btn" onclick="closePdfModal()">×</button>
            <iframe id="pdfViewer" src="" width="100%" height="80vh" frameborder="0"></iframe>
        </div>
    </div>

<script>
    feather.replace();

    function openPdfModal(pdfUrl) {
        const modal = document.getElementById('pdfModal');
        const viewer = document.getElementById('pdfViewer');
        viewer.src = pdfUrl;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closePdfModal() {
        const modal = document.getElementById('pdfModal');
        const viewer = document.getElementById('pdfViewer');
        viewer.src = '';
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }
</script>

</body>
</html>
