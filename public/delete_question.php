<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier que l'utilisateur est un enseignant
if (!isLoggedIn() || $_SESSION['role_id'] != 2) {
    header('Location: ../public/login.php');
    exit();
}

$enseignant_id = $_SESSION['user_id'];
$question_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$type_quiz = isset($_GET['type']) ? $_GET['type'] : '';

// Vérifier que la question appartient à un quiz de l'enseignant
// Exemple de suppression d’une question en s'assurant que l'enseignant est propriétaire du quiz
$stmt = $pdo->prepare("
    DELETE q
    FROM questions q
    JOIN quizz z ON q.quizz_id = z.id
    WHERE q.id = ? AND z.enseignant_id = ?
");
$stmt->execute([$question_id, $enseignant_id]);

$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    header("Location: add_questions.php?quiz_id=$quiz_id&type=$type_quiz");
    exit();
}

// Supprimer la question et ses réponses
try {
    $pdo->beginTransaction();
    
    // Supprimer les réponses associées
    $stmt = $pdo->prepare("DELETE FROM reponses WHERE question_id = ?");
    $stmt->execute([$question_id]);
    
    // Supprimer la question
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    
    $pdo->commit();
    
    $_SESSION['message'] = "Question supprimée avec succès!";
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
}

// Redirection vers la page d'ajout de questions
header("Location: add_questions.php?quiz_id=$quiz_id&type=$type_quiz");
exit();
?>