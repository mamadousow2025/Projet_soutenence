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

// =================== RECHERCHE ===================
$searchTerm = '';
$searchResults = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    
    // Recherche dans les utilisateurs
    $searchStmt = $pdo->prepare("
        SELECT u.id, u.nom, u.prenom, u.email, r.role_name AS role, 'user' as type
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE CONCAT(u.nom, ' ', u.prenom) LIKE :search 
           OR u.nom LIKE :search 
           OR u.prenom LIKE :search 
           OR u.email LIKE :search
           OR r.role_name LIKE :search
        ORDER BY u.id DESC
    ");
    $searchTermLike = '%' . $searchTerm . '%';
    $searchStmt->bindParam(':search', $searchTermLike);
    $searchStmt->execute();
    $searchResults = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
}

// =================== STATISTIQUES GLOBALES ===================
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM cours")->fetchColumn();
$totalProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$totalQuizzes = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();

// Comptage par rôles
$totalEtudiants = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'etudiant'")->fetchColumn();
$totalEnseignants = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'enseignant'")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'admin'")->fetchColumn();

// =================== LISTE UTILISATEURS ===================
$usersStmt = $pdo->query("
    SELECT u.id, u.nom, u.prenom, u.email, r.role_name AS role
    FROM users u
    JOIN roles r ON u.role_id = r.id
    ORDER BY u.id DESC
");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// =================== LISTE DES COURS EN ATTENTE ===================
$coursesStmt = $pdo->query("
    SELECT c.id, c.titre AS title, f.nom AS filiere, c.status
    FROM cours c
    JOIN filieres f ON c.filiere_id = f.id
    WHERE c.status = 'pending'
    ORDER BY c.created_at DESC
");
$courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

// =================== LISTE DES PROJETS ===================
$projectsStmt = $pdo->query("
    SELECT p.id, p.title, CONCAT(u.nom, ' ', u.prenom) AS student, p.status, p.submitted_at
    FROM projects p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.submitted_at DESC
");
$projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

// =================== LISTE DES QUIZZES ===================
  $quizzStmt = $pdo->query("
    SELECT q.id, q.titre AS title, c.titre AS course, 
           (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) AS questions_count,
           q.status
    FROM quizz q
    JOIN cours c ON q.course_id = c.id
    ORDER BY q.id DESC
");
$quizzes = $quizzStmt->fetchAll(PDO::FETCH_ASSOC);



$quizzes = $quizzStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Admin</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ========== RESET ========== */
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI', Tahoma, sans-serif;background:#f4f7fb;display:flex;}

        /* ========== SIDEBAR ========== */
        .sidebar{width:250px;height:100vh;background:#009688;color:#fff;position:fixed;top:0;left:0;display:flex;flex-direction:column;justify-content:space-between;padding:20px 15px;}
        .sidebar h2{font-size:20px;margin-bottom:30px;text-align:center;font-weight:bold;letter-spacing:1px;}
        .menu a{display:flex;align-items:center;gap:12px;text-decoration:none;color:#fff;padding:12px 15px;margin-bottom:10px;border-radius:8px;transition:background 0.3s;}
        .menu a:hover,.menu a.active{background:rgba(255,255,255,0.25);}
        .menu a i{font-size:18px;}
        .logout{text-align:center;}
        .logout a{display:block;background:#e53935;padding:12px;border-radius:8px;color:#fff;text-decoration:none;transition:background 0.3s;}
        .logout a:hover{background:#c62828;}

        /* ========== MAIN CONTENT ========== */
        .main{margin-left:250px;padding:30px;width:calc(100% - 250px);}
        .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;}
        .header h1{font-size:26px;color:#333;}
        .header .user{font-size:16px;color:#666;}

        /* ========== STATS ========== */
        .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:40px;}
        .card{background:#fff;border-radius:14px;padding:25px;text-align:center;box-shadow:0 5px 20px rgba(0,0,0,0.1);transition:transform 0.3s;}
        .card:hover{transform:translateY(-6px);}
        .card i{font-size:32px;color:#009688;margin-bottom:15px;}
        .card h3{font-size:16px;color:#777;margin-bottom:10px;}
        .card p{font-size:28px;font-weight:bold;color:#333;}

        /* ========== TABLES ========== */
        h2.section-title{font-size:22px;margin:30px 0 15px;color:#444;border-left:5px solid #009688;padding-left:10px;}
        table{width:100%;border-collapse:collapse;border-radius:10px;overflow:hidden;background:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.05);margin-bottom:40px;}
        th,td{padding:14px 16px;text-align:left;}
        th{background:#009688;color:#fff;font-size:15px;}
        tr:nth-child(even){background:#f9f9f9;}
        tr:hover{background:#f1f1f1;}

        /* ========== BUTTONS ========== */
        .button{display:inline-block;padding:8px 14px;border-radius:6px;font-size:14px;color:#fff;text-decoration:none;transition:all 0.2s;}
        .add{background:#ff9800;}
        .add:hover{background:#e68a00;}
        .edit{background:#1976d2;}
        .edit:hover{background:#1565c0;}
        .success{background:#43a047;}
        .success:hover{background:#388e3c;}
        .danger{background:#e53935;}
        .danger:hover{background:#c62828;}

        /* ========== FOOTER ========== */
        footer{margin-top:40px;text-align:center;color:#777;font-size:14px;}

        /* ========== RESPONSIVE ========== */
        @media(max-width:768px){.sidebar{width:200px;}.main{margin-left:200px;}}
        @media(max-width:600px){.sidebar{display:none;}.main{margin:0;width:100%;}}

        /* ========== SEARCH BAR ========== */
        .search-container {margin-bottom: 30px;display: flex;justify-content: flex-end;}
        .search-form {display: flex;max-width: 400px;width: 100%;}
        .search-input {flex: 1;padding: 12px 16px;border: 1px solid #ddd;border-radius: 6px 0 0 6px;font-size: 16px;outline: none;}
        .search-button {padding: 12px 20px;background: #009688;color: white;border: none;border-radius: 0 6px 6px 0;cursor: pointer;font-size: 16px;transition: background 0.3s;}
        .search-button:hover {background: #00766c;}

        /* ========== SEARCH RESULTS ========== */
        .search-results {margin-top: 20px;}
        .search-title {font-size: 20px;margin-bottom: 15px;color: #333;border-bottom: 2px solid #009688;padding-bottom: 8px;}
        .no-results {text-align: center;padding: 20px;color: #777;font-style: italic;}
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div>
            <h2>Admin Panel</h2>
            <div class="menu">
                <a href="#" data-section="dashboard" class="active"><i class="fa-solid fa-gauge"></i> Tableau de bord</a>
                <a href="#" data-section="users"><i class="fa-solid fa-users"></i> Utilisateurs</a>
                <a href="#" data-section="courses"><i class="fa-solid fa-book"></i> Cours</a>
                <a href="#" data-section="projects"><i class="fa-solid fa-briefcase"></i> Projets</a>
                <a href="#" data-section="quizzes"><i class="fa-solid fa-circle-question"></i> Quizzes</a>
            </div>
        </div>
        <div class="logout">
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main">
        <div class="header">
            <h1>Bienvenue, Administrateur</h1>
            <div class="user"><i class="fa-solid fa-user-shield"></i> Admin</div>
        </div>

        <!-- BARRE DE RECHERCHE -->
        <div class="search-container">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Rechercher un utilisateur..." value="<?= htmlspecialchars($searchTerm) ?>">
                <button type="submit" class="search-button"><i class="fa-solid fa-search"></i></button>
            </form>
        </div>

        <!-- RÉSULTATS DE RECHERCHE -->
        <?php if (!empty($searchTerm)): ?>
        <div class="search-results">
            <h2 class="search-title">Résultats de recherche pour "<?= htmlspecialchars($searchTerm) ?>"</h2>
            
            <?php if (!empty($searchResults)): ?>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Nom complet</th><th>Email</th><th>Rôle</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($searchResults as $result): ?>
                            <tr>
                                <td><?= $result['id'] ?></td>
                                <td><?= htmlspecialchars($result['nom'].' '.$result['prenom']) ?></td>
                                <td><?= htmlspecialchars($result['email']) ?></td>
                                <td><?= $result['role'] ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?= $result['id'] ?>" class="button edit"><i class="fa fa-edit"></i></a>
                                    <a href="delete_user.php?id=<?= $result['id'] ?>" class="button danger" onclick="return confirm('Confirmer la suppression ?')"><i class="fa fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-results">Aucun résultat trouvé pour votre recherche.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- SECTION DASHBOARD -->
        <div id="dashboard" class="section" style="<?= empty($searchTerm) ? '' : 'display:none;' ?>">
            <div class="stats">
                <div class="card"><i class="fa-solid fa-users"></i><h3>Total Utilisateurs</h3><p><?= $totalUsers ?></p></div>
                <div class="card"><i class="fa-solid fa-user-graduate"></i><h3>Étudiants</h3><p><?= $totalEtudiants ?></p></div>
                <div class="card"><i class="fa-solid fa-chalkboard-user"></i><h3>Enseignants</h3><p><?= $totalEnseignants ?></p></div>
                <div class="card"><i class="fa-solid fa-user-shield"></i><h3>Administrateurs</h3><p><?= $totalAdmins ?></p></div>
                <div class="card"><i class="fa-solid fa-book"></i><h3>Cours</h3><p><?= $totalCourses ?></p></div>
                <div class="card"><i class="fa-solid fa-briefcase"></i><h3>Projets</h3><p><?= $totalProjects ?></p></div>
                <div class="card"><i class="fa-solid fa-circle-question"></i><h3>Quizzes</h3><p><?= $totalQuizzes ?></p></div>
            </div>
        </div>

        <!-- SECTION USERS -->
        <div id="users" class="section" style="display:none;">
            <h2 class="section-title">Liste des utilisateurs</h2>
            <a href="add_users.php" class="button add" style="margin-bottom:15px;">
                <i class="fa fa-plus"></i> Ajouter
            </a>
            <table>
                <thead>
                    <tr><th>ID</th><th>Nom complet</th><th>Email</th><th>Rôle</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['nom'].' '.$user['prenom']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= $user['role'] ?></td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="button edit"><i class="fa fa-edit"></i></a>
                                <a href="delete_user.php?id=<?= $user['id'] ?>" class="button danger" onclick="return confirm('Confirmer la suppression ?')"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- SECTION COURSES -->
        <div id="courses" class="section" style="display:none;">
            <h2 class="section-title">Cours en attente</h2>
            <table>
                <thead><tr><th>ID</th><th>Titre</th><th>Filière</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($courses as $course): ?>
                        <tr>
                            <td><?= $course['id'] ?></td>
                            <td><?= htmlspecialchars($course['title']) ?></td>
                            <td><?= $course['filiere'] ?></td>
                            <td><?= ucfirst($course['status']) ?></td>
                            <td>
                                <a href="validate_course.php?id=<?= $course['id'] ?>" class="button success"><i class="fa fa-check"></i></a>
                                <a href="delete_course.php?id=<?= $course['id'] ?>" class="button danger"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- SECTION PROJECTS -->
        <div id="projects" class="section" style="display:none;">
            <h2 class="section-title">Projets soumis</h2>
            <table>
                <thead><tr><th>ID</th><th>Titre</th><th>Étudiant</th><th>Status</th><th>Soumis le</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($projects as $project): ?>
                        <tr>
                            <td><?= $project['id'] ?></td>
                            <td><?= htmlspecialchars($project['title']) ?></td>
                            <td><?= htmlspecialchars($project['student']) ?></td>
                            <td><?= ucfirst($project['status']) ?></td>
                            <td><?= $project['submitted_at'] ?></td>
                            <td>
                                <a href="validate_project.php?id=<?= $project['id'] ?>" class="button success"><i class="fa fa-check"></i></a>
                                <a href="delete_project.php?id=<?= $project['id'] ?>" class="button danger"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- SECTION QUIZZES -->
        <div id="quizzes" class="section" style="display:none;">
            <h2 class="section-title">Quizzes</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Cours associé</th>
                        <th>Nombre de questions</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($quizzes)): ?>
                        <?php foreach($quizzes as $quiz): ?>
                            <tr>
                                <td><?= $quiz['id'] ?></td>
                                <td><?= htmlspecialchars($quiz['title']) ?></td>
                                <td><?= htmlspecialchars($quiz['course']) ?></td>
                                <td><?= $quiz['questions_count'] ?></td>
                                <td>
                                    <span class="status <?= strtolower($quiz['status']) ?>">
                                        <?= ucfirst($quiz['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_quiz.php?id=<?= $quiz['id'] ?>" class="button edit" title="Modifier">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <a href="delete_quiz.php?id=<?= $quiz['id'] ?>" class="button danger" title="Supprimer"
                                    onclick="return confirm('Voulez-vous vraiment supprimer ce quiz ?');">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">Aucun quiz disponible pour le moment.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SCRIPT NAVIGATION -->
    <script>
        document.querySelectorAll(".menu a").forEach(link=>{
            link.addEventListener("click",function(e){
                e.preventDefault();
                // désactiver tous
                document.querySelectorAll(".menu a").forEach(l=>l.classList.remove("active"));
                document.querySelectorAll(".section").forEach(sec=>sec.style.display="none");
                // activer celui-ci
                this.classList.add("active");
                const target=this.getAttribute("data-section");
                document.getElementById(target).style.display="block";
            });
        });
    </script>
</body>
</html>