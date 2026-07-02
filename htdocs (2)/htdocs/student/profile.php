<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require_once $root . '/includes/auth_check.php';
require_once $root . '/models/User.php';

$userModel = new User();
$user = $userModel->findById(currentUserId());

$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

// Dynamically build clean base URL prefix for accurate cross-environment folder routing
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$baseUrl = $protocol . $_SERVER['HTTP_HOST'];

$pageTitle = 'My Profile';
require_once $root . '/templates/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* Global Page Structure Override */
    html, body, main, .wrapper, #app {
        background-color: #f7f3f5 !important; 
        min-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        color: #4e3e43; 
    }

    /* Hide standard site wrappers */
    header, nav, .navbar, .site-header, footer, .site-footer {
        display: none !important;
    }

    .dashboard-wrapper {
        display: flex;
        min-height: 100vh;
        width: 100%;
        box-sizing: border-box;
    }

    /* Sidebar Toggle Styling */
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
    }

    .toggle-sidebar-btn span {
        display: block;
        width: 18px;
        height: 2px;
        background-color: #800020; 
        border-radius: 2px;
    }

    /* Modern Minimalist Sidebar Navigation */
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
        transition: transform 0.3s ease;
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
        margin-bottom: 4px;
        transition: background 0.2s;
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

    /* Core Content Layout Workspace Area */
    .main-layout {
        margin-left: 260px;
        flex: 1;
        display: flex;
        box-sizing: border-box;
        transition: margin-left 0.3s ease;
    }

    .center-content {
        flex: 1;
        padding: 40px;
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

    .section-title {
        font-size: 24px;
        font-weight: 800;
        color: #3d141d;
        margin-bottom: 6px;
    }

    /* Profile Detail List Layout */
    .profile-card-matrix {
        background-color: #fdf8f9;
        border: 3px solid #800020;
        border-radius: 16px;
        padding: 24px;
        margin-top: 25px;
    }

    .profile-field-row {
        display: flex;
        padding: 14px 0;
        border-bottom: 1px dashed #faecee;
        font-size: 15px;
    }

    .profile-field-row:last-child {
        border-bottom: none;
    }

    .profile-label {
        width: 140px;
        color: #8a7177;
        font-weight: 700;
    }

    .profile-value {
        color: #3d141d;
        font-weight: 600;
    }

    /* Control Element Actions Layout */
    .button-group-row {
        margin-top: 35px; 
        display: flex; 
        gap: 12px; 
        flex-wrap: wrap;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 12px 28px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid rgba(128, 0, 32, 0.15);
        cursor: pointer;
    }

    .btn-primary-action { background-color: #800020; color: #ffffff; border-color: #6b001a; }
    .btn-primary-action:hover { background-color: #990026; }
    .btn-muted-back { background-color: #edd1d8; color: #610018; border-color: #e3beae; }
    .btn-muted-back:hover { background-color: #610018; color: #ffffff; }

    /* Collapsed Responsive Layout Rules */
    .dashboard-wrapper.sidebar-collapsed .sidebar { transform: translateX(-260px); }
    .dashboard-wrapper.sidebar-collapsed .main-layout { margin-left: 0; }
</style>

<div class="dashboard-wrapper" id="dashboardWrapper">
    
    <div class="toggle-sidebar-btn" onclick="toggleSidebar()">
        <span></span><span></span><span></span>
    </div>

    <aside class="sidebar">
        <div class="menu-section">
            <a href="<?= $baseUrl ?>/student/dashboard.php" class="menu-item">📊 Portal Dashboard</a>
            <a href="<?= $baseUrl ?>/student/quiz.php" class="menu-item">📝 Take Assessment</a>
            <a href="<?= $baseUrl ?>/student/history.php" class="menu-item">📋 Evaluation History</a>
            <a href="<?= $baseUrl ?>/student/recommendations.php" class="menu-item">💼 My Recommendations</a>
        </div>

        <div class="menu-section">
            <div class="menu-label">Credentials</div>
            <a href="#" class="menu-item active">⚙️ Profile Information</a>
           
        </div>

        <div class="sidebar-footer">
            <a href="<?= $baseUrl ?>/logout.php" class="menu-item" style="color: #ffffff; background: #b3002d; border: 1px solid #e6003a;">🚪 Logout</a>
        </div>
    </aside>

    <div class="main-layout">
        <main class="center-content">
            <div class="workspace-inner-board">
                
                <h2 class="section-title">My Profile</h2>
                <p style="color: #94757c; font-size: 14px; margin-top: 0; margin-bottom: 25px; font-weight: 600;">Manage your primary profile settings and account credentials mapping history.</p>

                <?php if ($success): ?>
                    <div style="background: #28a745; color: white; padding: 14px 20px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; font-weight: 600;">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <div class="profile-card-matrix">
                    <div class="profile-field-row">
                        <div class="profile-label">Name:</div>
                        <div class="profile-value"><?= htmlspecialchars($user['name']) ?></div>
                    </div>
                    <div class="profile-field-row">
                        <div class="profile-label">Email:</div>
                        <div class="profile-value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <div class="profile-field-row">
                        <div class="profile-label">Joined:</div>
                        <div class="profile-value" style="color: #8a7177;"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($user['created_at']))) ?></div>
                    </div>
                </div>

                <div class="button-group-row">
                    <a href="<?= $baseUrl ?>/student/dashboard.php" class="action-btn btn-muted-back">← Back to Dashboard</a>
                    <a href="update_profile.php" class="action-btn btn-primary-action">Edit Profile Settings</a>
                </div>

            </div>
        </main>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('dashboardWrapper').classList.toggle('sidebar-collapsed');
    }
</script>

<?php require_once $root . '/templates/footer.php'; ?>
