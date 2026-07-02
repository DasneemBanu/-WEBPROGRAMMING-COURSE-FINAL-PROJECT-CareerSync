<?php
session_start();

$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';

require_once $root . 'includes/admin_check.php';
require_once $root . 'config/db.php';

try {
    $pdo = getDbConnection();
    $careers = $pdo->query("SELECT * FROM career_paths ORDER BY title")->fetchAll();
    
    // Safely fetch dashboard metrics from the shared schema
    $userCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $quizCount = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
    $careerCount = $pdo->query("SELECT COUNT(*) FROM career_paths")->fetchColumn();
} catch (PDOException $e) {
    error_log('Manage careers error: ' . $e->getMessage());
    $careers = [];
    $dbError = 'Failed to load career frameworks. Please try again.';
    $userCount = $adminCount = $quizCount = $careerCount = 0;
}

$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$pageTitle = 'Manage Careers';
require_once $root . 'templates/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=500;600;700;800&display=swap" rel="stylesheet">

<style>
    html, body, main, .wrapper, #app {
        background-color: #f7f3f5 !important; 
        min-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        color: #4e3e43; 
    }

    header, nav, .navbar, .site-header, footer, .site-footer {
        display: none !important;
    }

    .dashboard-wrapper {
        display: flex;
        min-height: 100vh;
        width: 100%;
        box-sizing: border-box;
    }

    .toggle-sidebar-btn {
        position: fixed;
        top: 25px;
        left: 20px;
        z-index: 200;
        background: #ffffff;
        border: 1px solid #ebdbe0;
        border-radius: 12px;
        width: 42px;
        height: 42px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 5px;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(128, 0, 32, 0.06);
        transition: all 0.3s ease;
    }

    .toggle-sidebar-btn span {
        display: block;
        width: 18px;
        height: 2px;
        background-color: #800020; 
        border-radius: 2px;
        transition: all 0.3s ease;
    }

    .sidebar {
        width: 260px;
        background-color: #800020; 
        padding: 45px 20px 30px 20px;
        display: flex;
        flex-direction: column;
        border-right: 1px solid #6b001a;
        position: fixed;
        height: 100vh;
        left: 0;
        top: 0;
        box-sizing: border-box;
        z-index: 100;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .menu-section {
        margin-top: 50px;
        margin-bottom: 30px;
    }

    .menu-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #ffa3b1; 
        font-weight: 700;
        margin-bottom: 12px;
        padding-left: 10px;
    }

    .menu-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        color: #fde8eb; 
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        border-radius: 12px;
        transition: all 0.2s ease;
        margin-bottom: 4px;
    }

    .menu-item:hover {
        background-color: #990026; 
        color: #ffffff;
    }

    .menu-item.active {
        background-color: #ffffff; 
        color: #800020; 
    }

    .sidebar-footer {
        margin-top: auto;
        border-top: 1px solid #6b001a;
        padding-top: 20px;
    }

    .main-layout {
        margin-left: 260px;
        flex: 1;
        display: flex;
        box-sizing: border-box;
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .center-content {
        flex: 1;
        padding: 40px;
        padding-top: 30px;
        max-width: 950px;
        box-sizing: border-box;
    }

    .workspace-inner-board {
        background-color: #ffffff;
        border-radius: 28px;
        padding: 35px;
        border: 1px solid #f2e6e9;
        box-shadow: 0 10px 30px rgba(128, 0, 32, 0.04);
    }

    .dashboard-wrapper.sidebar-collapsed .sidebar {
        transform: translateX(-260px);
    }

    .dashboard-wrapper.sidebar-collapsed .main-layout {
        margin-left: 0;
    }

    .section-title {
        font-size: 22px;
        font-weight: 800;
        color: #3d141d;
        margin-top: 0;
        margin-bottom: 25px;
        letter-spacing: -0.4px;
        display: flex;
        align-items: center;
        justify-content: center; 
        gap: 8px;
        text-align: center;
        width: 100%;
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 20px;
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #f5eaed;
    }

    th, td {
        padding: 16px;
        text-align: left;
        font-size: 13px;
    }

    th {
        background-color: #fcf4f6 !important; 
        color: #8c2641 !important; 
        font-weight: 700;
        letter-spacing: 0.6px;
        border-bottom: 2px solid #f5eaed !important;
        text-transform: uppercase;
        font-size: 11px;
    }

    td {
        border-bottom: 1px solid #fdf8f9;
        color: #5c4a4f;
        background-color: #ffffff;
        font-weight: 500;
    }

    tr:last-child td {
        border-bottom: none;
    }

    tr:hover td {
        background-color: #fdfafb; 
    }

    .btn {
        padding: 8px 18px;
        text-decoration: none;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }

    .btn-edit {
        background: #edf7f4 !important;
        background-image: none !important;
        color: #2d7a67 !important;
        border: 1px solid #d6ebd9 !important;
        margin-bottom: 5px;
        display: block;
        text-align: center;
        width: 70px;
    }

    .btn-edit:hover {
        background: #e0f0eb !important;
        color: #1e5c4c !important;
    }

    .btn-delete {
        background: #fff2f4 !important;
        background-image: none !important;
        color: #c9425e !important;
        border: 1px solid #fcdde2 !important;
        display: block;
        text-align: center;
        width: 70px;
    }

    .btn-delete:hover {
        background: #ffe5ea !important;
        color: #a32943 !important;
    }

    .btn-add {
        background: #edf7f4 !important;
        color: #2d7a67 !important;
        border: 1px solid #d6ebd9 !important;
        margin-bottom: 20px;
        display: inline-flex;
    }

    .btn-add:hover {
        background: #e0f0eb !important;
        color: #1e5c4c !important;
    }

    .btn-back {
        background: #f3edf0 !important;
        background-image: none !important;
        color: #6b5358 !important;
        border: 1px solid #edd3d7;
    }

    .btn-back:hover {
        background: #faf0f2 !important;
        color: #3d141d !important;
    }

    .alert {
        padding: 14px;
        margin-bottom: 20px;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 600;
        background: #e6f7f3;
        color: #0f766e;
        border: 1px solid #bef2e2;
    }

    .alert-error {
        background: #fdf2f4;
        color: #b3002d;
        border: 1px solid #fcdede;
    }

    .empty-msg {
        text-align: center;
        padding: 50px;
        color: #94757c;
        font-size: 14px;
        font-weight: 600;
    }

    .right-panel {
        width: 340px;
        background-color: #800020; 
        border-left: 1px solid #6b001a;
        padding: 40px 30px;
        padding-top: 85px;
        box-sizing: border-box;
    }

    .right-panel .section-title {
        color: #ffffff !important; 
        justify-content: flex-start;
        text-align: left;
    }

    .admin-profile-header {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        margin-bottom: 40px;
    }

    .admin-avatar {
        width: 38px;
        height: 38px;
        background: #ffffff;
        color: #800020; 
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 14px;
        border: 1px solid #ffa3b1;
    }

    .admin-name {
        font-size: 13px;
        font-weight: 600;
        color: #fde8eb;
    }

    .metric-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
        background: #990026; 
        padding: 20px;
        border-radius: 18px;
        border: 1px solid #b3002d;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
    }

    .metric-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 12px;
        border-bottom: 1px solid #b3002d;
    }

    .metric-row:last-child {
        border-bottom: none;
    }

    .metric-title-box {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 600;
        color: #fde8eb;
    }

    .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    
    .dot-users { background-color: #ffcbd3; } 
    .dot-admins { background-color: #fbcfe8; }
    .dot-quizzes { background-color: #ffe4e6; }
    .dot-careers { background-color: #ffd1dc; }

    .metric-count {
        font-size: 14px;
        font-weight: 700;
        color: #ffffff;
    }
</style>

<div class="dashboard-wrapper" id="dashboardWrapper">
    
    <div class="toggle-sidebar-btn" onclick="toggleSidebar()">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <aside class="sidebar">
        <div class="menu-section">
            <a href="dashboard.php" class="menu-item">📊 Dashboard</a>
            <a href="manage_user.php" class="menu-item">👥 Users Pool</a>
            <a href="manage_question.php" class="menu-item">📝 Quiz Metrics</a>
            <a href="manage_career.php" class="menu-item active">💼 Career Framework</a>
        </div>

        <div class="menu-section">
            <div class="menu-label">Settings</div>
            <a href="profile.php" class="menu-item">⚙️ Account Settings</a>
        </div>

        <div class="sidebar-footer">
            <a href="/logout.php" class="menu-item" style="color: #ffffff; background: #b3002d; border: 1px solid #e6003a;">🚪 Logout</a>
        </div>
    </aside>

    <div class="main-layout">
        <div class="center-content">

            <div class="workspace-inner-board">
                
                <h3 class="section-title">💼 Manage Career Paths Overview</h3>

                <a href="add_career.php" class="btn btn-add">+ Add New Career</a>

                <?php if ($success): ?>
                    <div class="alert"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <?php if (!empty($dbError)): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <?php if (empty($careers)): ?>
                    <div class="empty-msg">No structured career framework frameworks stored inside the directory.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 20%;">Title</th>
                                <th style="width: 30%;">Description</th>
                                <th style="width: 22%;">Required Skills</th>
                                <th style="width: 15%;">Education</th>
                                <th style="width: 13%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($careers as $c): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($c['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><?= htmlspecialchars(substr($c['description'] ?? '', 0, 50), ENT_QUOTES, 'UTF-8') ?>...</td>
                                    <td><?= htmlspecialchars(substr($c['required_skills'] ?? '', 0, 40), ENT_QUOTES, 'UTF-8') ?>...</td>
                                    <td><?= htmlspecialchars($c['education_path'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <a href="edit_career.php?id=<?= (int)$c['id'] ?>" class="btn btn-edit">Edit</a>
                                        <a href="delete_career.php?id=<?= (int)$c['id'] ?>" 
                                           class="btn btn-delete" 
                                           onclick="return confirm('Delete this career path? This cannot be undone.')">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div style="margin-top: 30px; text-align: center;">
                    <a href="dashboard.php" class="btn btn-back">← Back to Dashboard</a>
                </div>
                
            </div>
        </div>

        <div class="right-panel">
            <div class="admin-profile-header">
                <span class="admin-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?> (Admin)</span>
                <div class="admin-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                </div>
            </div>

            <h3 class="section-title" style="margin-bottom: 25px;">Data Balance</h3>

            <div class="metric-list">
                <div class="metric-row">
                    <div class="metric-title-box">
                        <div class="dot dot-users"></div> Users
                    </div>
                    <span class="metric-count"><?= (int)$userCount ?></span>
                </div>
                <div class="metric-row">
                    <div class="metric-title-box">
                        <div class="dot dot-admins"></div> Admins
                    </div>
                    <span class="metric-count"><?= (int)$adminCount ?></span>
                </div>
                <div class="metric-row">
                    <div class="metric-title-box">
                        <div class="dot dot-quizzes"></div> Questions
                    </div>
                    <span class="metric-count"><?= (int)$quizCount ?></span>
                </div>
                <div class="metric-row">
                    <div class="metric-title-box">
                        <div class="dot dot-careers"></div> Careers
                    </div>
                    <span class="metric-count"><?= (int)$careerCount ?></span>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function toggleSidebar() {
        const wrapper = document.getElementById('dashboardWrapper');
        wrapper.classList.toggle('sidebar-collapsed');
    }
</script>

<?php require_once $root . 'templates/footer.php'; ?>
