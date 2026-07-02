<?php
session_start();

$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';

require_once $root . 'includes/admin_check.php';
require_once $root . 'config/db.php';

// Verify admin role (defense in depth)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Generate CSRF token for delete actions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pdo = getDbConnection();
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

// Fetch metric balances for the sidebar
try {
    $userCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $quizCount = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
    $careerCount = $pdo->query("SELECT COUNT(*) FROM career_paths")->fetchColumn();
} catch (PDOException $e) {
    $userCount = $adminCount = $quizCount = $careerCount = 0;
}

try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY role, created_at DESC");
    $users = $stmt->fetchAll();
    $dbError = null;
} catch (PDOException $e) {
    error_log('Manage users error: ' . $e->getMessage());
    $users = [];
    $dbError = 'Failed to load users. Please try again.';
}

$pageTitle = 'Manage Users Pool';
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

    /* Primary Dashboard Wrapper Matrix Layout */
    .dashboard-wrapper {
        display: flex;
        min-height: 100vh;
        width: 100%;
        box-sizing: border-box;
    }

    /* Fixed Left Sidebar Panel Component */
    .sidebar {
        width: 260px;
        background-color: #800020; 
        padding: 15px 0 30px 20px;
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

    /* 3-Dash Menu Bar Button Top-Left Alignment Container */
    .sidebar-header {
        padding-top: 10px;
        padding-bottom: 25px;
        padding-left: 10px;
        display: flex;
        justify-content: flex-start;
        align-items: center;
    }
    
    /* White Box Container Button */
    .menu-toggle {
        background-color: #ffffff !important;
        border: none;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 4px;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s ease, background-color 0.2s ease;
        padding: 0 !important;
    }

    .menu-toggle:hover {
        background-color: #fdf5f6 !important;
        transform: scale(1.02);
    }

    /* Maroon dash lines inside white box */
    .menu-toggle .bar {
        width: 18px;
        height: 2.5px;
        background-color: #800020 !important; 
        border-radius: 2px;
        display: block;
    }

    .menu-section {
        margin-top: 5px;
        margin-bottom: 20px;
    }

    /* Category Labels (OVERVIEW / SETTINGS) */
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
        transition: all 0.2s ease;
        margin-bottom: 4px;
    }

    .menu-item:hover {
        background-color: #990026; 
        color: #ffffff;
        border-radius: 12px 0 0 12px;
    }

    /* Capsule highlighting configuration matching layout standards */
    .menu-item.active {
        background-color: #ffffff !important; 
        color: #800020 !important; 
        border-radius: 25px 0 0 25px !important;
    }

    .sidebar-footer {
        margin-top: auto;
        border-top: 1px solid #6b001a;
        padding-top: 20px;
        padding-right: 20px;
    }

    /* Layout Area Calculation Realignment */
    .main-layout {
        margin-left: 260px; 
        flex: 1;
        display: flex;
        box-sizing: border-box;
        min-height: 100vh;
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Clean balanced workspace padding alignment */
    .center-content {
        flex: 1;
        padding: 40px;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }

    /* Central White Board Table Container Card */
    .workspace-inner-board {
        background-color: #ffffff !important;
        border-radius: 24px;
        padding: 40px;
        border: 1px solid #ebdbe0 !important;
        box-shadow: 0 8px 24px rgba(128, 0, 32, 0.03);
        width: 100%;
        box-sizing: border-box;
    }

    .section-title {
        font-size: 22px;
        font-weight: 800;
        color: #3d141d !important;
        margin-top: 0;
        margin-bottom: 25px;
        letter-spacing: -0.3px;
    }

    /* Unified Content Layout Table Configuration */
    .pool-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 20px;
        background-color: #ffffff !important;
    }

    .pool-table th {
        background-color: #fdf8f9 !important;
        color: #8a6870 !important;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        padding: 16px 20px;
        border-bottom: 2px solid #f5e9ec !important;
        text-align: left;
    }

    .pool-table th.actions-header-cell {
        text-align: center !important;
    }

    .pool-table td {
        padding: 18px 20px;
        font-size: 14px;
        font-weight: 600;
        color: #3d141d !important;
        background-color: #ffffff !important;
        border-bottom: 1px solid #f7eff1 !important;
        vertical-align: middle;
    }

    .user-id-badge {
        font-weight: 700;
        color: #800020;
        background: #fdf0f3;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 13px;
        display: inline-block;
    }

    .role-user-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        background-color: #f1f5f9;
        color: #475569;
    }

    .role-admin-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        background-color: #fdf2f8;
        color: #9d174d;
        border: 1px solid #fce7f3;
    }

    /* Side-by-side Actions Alignment Cell */
    .actions-flex-cell {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
    }

    .btn-action {
        width: auto;
        min-width: 75px;
        padding: 8px 14px;
        text-decoration: none;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
        box-sizing: border-box;
    }

    /* Refined Muted Cool Slate / Blue-Gray for Edit Link */
    .btn-edit {
        background-color: #f1f5f9 !important;
        color: #334155 !important;
        border: 1px solid #cbd5e1 !important;
    }

    .btn-edit:hover {
        background-color: #e2e8f0 !important;
    }

    /* Soft Pastel Rose / Deep Red Tone for Destructive Actions */
    .btn-delete {
        background-color: #fef2f2 !important;
        color: #991b1b !important;
        border: 1px solid #fee2e2 !important;
    }

    .btn-delete:hover {
        background-color: #fee2e2 !important;
    }

    .btn-secondary-back {
        background-color: #f3edf0 !important;
        color: #6b5358 !important;
        border: 1px solid #edd3d7 !important;
        margin-top: 25px;
        text-decoration: none;
        width: auto;
    }

    .alert-banner-success {
        padding: 15px 20px;
        margin-bottom: 25px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 600;
        background-color: #edfdf7;
        color: #14532d;
        border: 1px solid #bbf7d0;
    }

    .alert-banner-error {
        padding: 15px 20px;
        margin-bottom: 25px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 600;
        background-color: #fef2f2;
        color: #991b1b;
        border: 1px solid #fee2e2;
    }

    /* Right Side Fixed Panel */
    .right-panel {
        width: 340px;
        background-color: #800020; 
        border-left: 1px solid #6b001a;
        padding: 40px 30px;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
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
    }

    .metric-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 12px;
        border-bottom: 1px solid #b3002d;
    }

    .metric-row:last-child { border-bottom: none; }

    .metric-title-box {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 600;
        color: #fde8eb;
    }

    .dot { width: 8px; height: 8px; border-radius: 50%; }
    .dot-users { background-color: #ffcbd3; } 
    .dot-quizzes { background-color: #ffe4e6; }
    .dot-careers { background-color: #ffd1dc; }
    .metric-count { font-size: 14px; font-weight: 700; color: #ffffff; }

    /* Responsive Sidebar Collapsed States */
    .dashboard-wrapper.sidebar-collapsed .sidebar {
        transform: translateX(-260px);
    }
    .dashboard-wrapper.sidebar-collapsed .main-layout {
        margin-left: 0;
    }
</style>

<div class="dashboard-wrapper" id="dashboardWrapper">

    <aside class="sidebar">
        <div class="sidebar-header">
            <button class="menu-toggle" id="sidebarToggleBtn" aria-label="Toggle Navigation Menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>

        <div class="menu-section">
            <a href="dashboard.php" class="menu-item">📊 Dashboard</a>
            <a href="manage_user.php" class="menu-item active">👥 Users Pool</a>
            <a href="manage_question.php" class="menu-item">📝 Quiz Metrics</a>
            <a href="manage_career.php" class="menu-item">💼 Career Framework</a>
        </div>

        <div class="menu-section">
            <div class="menu-label">Settings</div>
            <a href="profile.php" class="menu-item">⚙️ Account Settings</a>
        </div>

        <div class="sidebar-footer">
            <a href="/logout.php" class="menu-item" style="color: #ffffff; background: #b3002d; border: 1px solid #e6003a; border-radius: 12px;">🚪 Logout</a>
        </div>
    </aside>

    <div class="main-layout">
        
        <main class="center-content">

            <div class="workspace-inner-board">
                <h3 class="section-title">👥 Manage Registered Users Pool</h3>

                <?php if ($success): ?>
                    <div class="alert-banner-success">🎉 <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <?php if (!empty($dbError)): ?>
                    <div class="alert-banner-error">⚠️ <?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <?php if (empty($users)): ?>
                    <div style="text-align: center; padding: 40px; color: #8a6870;">
                        <p style="font-weight: 600;">No accounts mapped within the matrix directory.</p>
                    </div>
                <?php else: ?>
                    <table class="pool-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">ID</th>
                                <th style="width: 25%;">Name</th>
                                <th style="width: 30%;">Email</th>
                                <th style="width: 15%;">Role</th>
                                <th style="width: 10%;">Joined</th>
                                <th class="actions-header-cell" style="width: 10%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): 
                                $userId = (int)$u['id'];
                                $currentId = (int)($_SESSION['user_id'] ?? 0);
                                $badgeClass = ($u['role'] ?? 'user') === 'admin' ? 'role-admin-badge' : 'role-user-badge';
                            ?>
                                <tr>
                                    <td>
                                        <span class="user-id-badge">#<?= $userId ?></span>
                                    </td>
                                    <td style="font-weight: 700;">
                                        <?= htmlspecialchars($u['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <span class="<?= $badgeClass ?>"><?= ucfirst(htmlspecialchars($u['role'] ?? 'user', ENT_QUOTES, 'UTF-8')) ?></span>
                                    </td>
                                    <td>
                                        <?= !empty($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : 'N/A' ?>
                                    </td>
                                    <td>
                                        <div class="actions-flex-cell">
                                            <a href="edit_user.php?id=<?= $userId ?>" class="btn-action btn-edit">Edit</a>
                                            <?php if ($userId !== $currentId): ?>
                                                <form method="POST" action="delete_user.php" style="display: inline; margin: 0;" onsubmit="return confirm('Delete user <?= htmlspecialchars($u['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>? This action cannot be reverted.')">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="id" value="<?= $userId ?>">
                                                    <button type="submit" class="btn-action btn-delete">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div style="text-align: left;">
                    <a href="dashboard.php" class="btn-action btn-secondary-back">← Back to Dashboard Overview</a>
                </div>
            </div>
        </main>

        <div class="right-panel">
            <div class="admin-profile-header">
                <span class="admin-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin Account') ?> (Admin)</span>
                <div class="admin-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                </div>
            </div>

            <h3 class="section-title" style="color: #ffffff !important; text-align: left; font-size: 20px; margin-bottom: 20px;">Data Balance</h3>

            <div class="metric-list">
                <div class="metric-row">
                    <div class="metric-title-box"><div class="dot dot-users"></div> Users Total</div>
                    <span class="metric-count"><?= (int)$userCount ?></span>
                </div>
                <div class="metric-row">
                    <div class="metric-title-box"><div class="dot dot-users"></div> Admin Level</div>
                    <span class="metric-count"><?= (int)$adminCount ?></span>
                </div>
                <div class="metric-row">
                    <div class="metric-title-box"><div class="dot dot-quizzes"></div> Questions Pool</div>
                    <span class="metric-count"><?= (int)$quizCount ?></span>
                </div>
                <div class="metric-row">
                    <div class="metric-title-box"><div class="dot dot-careers"></div> Career Matrix</div>
                    <span class="metric-count"><?= (int)$careerCount ?></span>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggleBtn = document.getElementById('sidebarToggleBtn');
    var wrapper = document.getElementById('dashboardWrapper');
    
    if (toggleBtn && wrapper) {
        toggleBtn.addEventListener('click', function() {
            wrapper.classList.toggle('sidebar-collapsed');
        });
    }
});
</script>

<?php require_once $root . 'templates/footer.php'; ?>
      