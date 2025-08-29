<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3;
}

function isEnseignant() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
}

function isEtudiant() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
}

function requireRole($role_id) {
    requireLogin();
    if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != $role_id) {
        header("HTTP/1.1 403 Forbidden");
        exit("Accès refusé");
    }
}
