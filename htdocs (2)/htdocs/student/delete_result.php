<?php
session_start();

define('BASE_PATH', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');

require_once BASE_PATH . 'includes/session.php';
require_once BASE_PATH . 'config/db.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    // If you see this, it means your browser stripped the POST data during submission or redirected.
    echo "<h2>Error: Invalid Request Method</h2>";
    echo "<p>This page must be accessed via a secure POST request from the history log form.</p>";
    echo "<p><strong>Troubleshooting:</strong> If you clicked 'Delete Log' and still ended up here, make sure your browser address bar matches your site's protocol (e.g., explicitly use <code>https://</code> if your host forces secure connections).</p>";
    echo "<br><a href='history.php'>Return to History Logs</a>";
    exit;
}

// CSRF check validation
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['error'] = 'Invalid security token. Please try again.';
    header('Location: history.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    $_SESSION['error'] = 'Invalid record ID.';
    header('Location: history.php');
    exit;
}

$userId = currentUserId();
$pdo = getDbConnection();

try {
    // Verify ownership before deleting
    $stmt = $pdo->prepare("SELECT user_id FROM user_results WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();

    if (!$record || (int)$record['user_id'] !== $userId) {
        $_SESSION['error'] = 'Record not found or access denied.';
        header('Location: history.php');
        exit;
    }

    // Delete the record securely matching both ID and User ID
    $stmt = $pdo->prepare("DELETE FROM user_results WHERE id = ? AND user_id = ?");
    $deleted = $stmt->execute([$id, $userId]);

    if ($deleted) {
        $_SESSION['flash_success'] = 'Record deleted successfully.';
    } else {
        $_SESSION['error'] = 'Failed to delete record.';
    }

} catch (PDOException $e) {
    error_log('Database Delete Error: ' . $e->getMessage());
    $_SESSION['error'] = 'A system database error occurred. Please try again.';
}

header('Location: history.php');
exit;