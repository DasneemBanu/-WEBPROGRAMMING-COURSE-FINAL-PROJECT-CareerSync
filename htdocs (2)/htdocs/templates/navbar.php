<?php
// navbar.php - session.php is already loaded by header.php
// $baseUrl and $isAdminPage are defined in header.php
?>

<nav class="navbar">
    <div class="navbar-brand">
        <a href="<?= htmlspecialchars($baseUrl) ?>/index.php">CareerSync</a>
    </div>

    <div class="navbar-links">

        <?php if (isLoggedIn()): ?>

            <?php if ($isAdminPage): ?>
                <!-- ADMIN NAVIGATION -->
                <a href="<?= htmlspecialchars($baseUrl) ?>/admin/dashboard.php">Dashboard</a>
                <a href="<?= htmlspecialchars($baseUrl) ?>/admin/manage_user.php">Users</a>
                <a href="<?= htmlspecialchars($baseUrl) ?>/admin/manage_question.php">Questions</a>
                <a href="<?= htmlspecialchars($baseUrl) ?>/admin/manage_career.php">Careers</a>
                <a href="<?= htmlspecialchars($baseUrl) ?>/admin/profile.php">Profile</a>
                
                <span class="navbar-user">
                    <?= htmlspecialchars(currentUserName()) ?> (Admin)
                </span>
                
                <a href="<?= htmlspecialchars($baseUrl) ?>/logout.php" class="navbar-logout">Logout</a>

            <?php else: ?>
                <!-- STUDENT NAVIGATION -->
                <a href="<?= htmlspecialchars($baseUrl) ?>/index.php">Home</a>
                <a href="<?= htmlspecialchars($baseUrl) ?>/student/quiz.php">Quiz</a>
                <a href="<?= htmlspecialchars($baseUrl) ?>/student/recommendations.php">Recommendations</a>
                <a href="<?= htmlspecialchars($baseUrl) ?>/student/history.php">History</a>
                <a href="<?= htmlspecialchars($baseUrl) ?>/student/profile.php">Profile</a>

                <?php if (currentUserRole() === 'admin'): ?>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/dashboard.php">Admin</a>
                <?php endif; ?>

                <span class="navbar-user">
                    <?= htmlspecialchars(currentUserName()) ?>
                </span>

                <a href="<?= htmlspecialchars($baseUrl) ?>/logout.php" class="navbar-logout">Logout</a>
            <?php endif; ?>

        <?php else: ?>
            <!-- NOT LOGGED IN -->
            <a href="<?= htmlspecialchars($baseUrl) ?>/login.php">Login</a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/register.php">Register</a>

        <?php endif; ?>

    </div>
</nav>