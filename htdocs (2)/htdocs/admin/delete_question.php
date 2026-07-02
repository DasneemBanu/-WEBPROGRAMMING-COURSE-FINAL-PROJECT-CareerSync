<?php
session_start();

$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';
require_once $root . 'includes/admin_check.php';
require_once $root . 'config/db.php';

// Verify admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_question.php');
    exit;
}

// CSRF check
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['flash_success'] = 'Invalid security token.';
    header('Location: manage_question.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    $_SESSION['flash_success'] = 'Invalid question ID.';
    header('Location: manage_question.php');
    exit;
}

$pdo = getDbConnection();
$stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['flash_success'] = 'Question deleted successfully.';
header('Location: manage_question.php');
exit;