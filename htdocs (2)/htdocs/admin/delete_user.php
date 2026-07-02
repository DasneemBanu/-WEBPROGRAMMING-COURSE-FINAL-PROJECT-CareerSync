<?php
session_start();

$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';
require_once $root . 'includes/admin_check.php';
require_once $root . 'config/db.php';

// Only allow POST (or add CSRF check for GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // For now, keep GET but add confirmation
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
} else {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    $_SESSION['flash_error'] = 'Invalid user ID.';
    header('Location: manage_user.php');
    exit;
}

// Prevent self-deletion
if ($id === (int)($_SESSION['user_id'] ?? 0)) {
    $_SESSION['flash_error'] = 'You cannot delete your own account.';
    header('Location: manage_user.php');
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash_success'] = 'User deleted successfully.';
} catch (PDOException $e) {
    $_SESSION['flash_error'] = 'Failed to delete user.';
}

header('Location: manage_user.php');
exit;