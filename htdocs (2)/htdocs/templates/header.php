<?php
// templates/header.php
require_once __DIR__ . '/../includes/session.php';

// Simple base URL for InfinityFree (project is directly in htdocs)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . $host;

// Detect if we're on an admin page
$isAdminPage = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'CareerSync') ?></title>
    
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="page-content">