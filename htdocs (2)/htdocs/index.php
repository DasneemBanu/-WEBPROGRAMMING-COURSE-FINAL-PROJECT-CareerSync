<?php
require_once __DIR__ . '/includes/session.php';

// If not logged in, go to role selection
if (!isLoggedIn()) {
    header('Location: select_role.php');
    exit;
}

// If admin, redirect to admin dashboard
if (currentUserRole() === 'admin') {
    header('Location: admin/dashboard.php');
    exit;
}

$pageTitle = 'CareerSync Dashboard';
require_once __DIR__ . '/templates/header.php';
?>

<div class="container">
    <div class="card">
        <h1>Welcome, <?= htmlspecialchars(currentUserName()) ?>!</h1>
        <span style="display: inline-block; background: var(--success); color: var(--bg); padding: 5px 15px; border-radius: 20px; font-size: 14px; margin-top: 10px;">
            <?= ucfirst(htmlspecialchars(currentUserRole())) ?>
        </span>
        <p style="margin-top: 20px; color: var(--text-muted);">
            Start by taking the personality and interest quiz to receive career suggestions.
        </p>
        <a href="student/quiz.php" class="btn" style="display: inline-block; width: auto; margin-top: 15px; padding: 12px 25px;">
            Start Quiz
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>