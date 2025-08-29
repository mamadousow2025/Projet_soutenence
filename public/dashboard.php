<?php
require_once '../includes/auth.php';
requireLogin();

echo "<h1>Bienvenue " . htmlspecialchars($_SESSION['prenom']) . " " . htmlspecialchars($_SESSION['nom']) . "</h1>";

if (isAdmin()) {
    echo "<p>Vous êtes administrateur.</p>";
    echo "<a href='admin.php'>Aller à l’administration</a>";
} elseif (isEnseignant()) {
    echo "<p>Vous êtes enseignant.</p>";
    echo "<a href='enseignant.php'>Gérer vos cours</a>";
} elseif (isEtudiant()) {
    echo "<p>Vous êtes étudiant.</p>";
    echo "<a href='cours.php'>Voir mes cours</a>";
} else {
    echo "<p>Rôle inconnu.</p>";
}
