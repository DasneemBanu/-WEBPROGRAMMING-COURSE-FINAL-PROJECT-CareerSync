<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require_once $root . '/includes/admin_check.php';
require_once $root . '/config/db.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid career path ID.';
    header('Location: manage_career.php');
    exit;
}

$pdo = getDbConnection();

// Verify career path exists
$check = $pdo->prepare("SELECT id FROM career_paths WHERE id = ?");
$check->execute([$id]);
if (!$check->fetch()) {
    $_SESSION['flash_error'] = 'Career path not found.';
    header('Location: manage_career.php');
    exit;
}

$stmt = $pdo->prepare("DELETE FROM career_paths WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['flash_success'] = 'Career path deleted successfully!';
header('Location: manage_career.php');
exit;