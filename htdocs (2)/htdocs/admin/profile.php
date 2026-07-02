<?php
session_start();

$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';

require_once $root . 'includes/admin_check.php';

// Since we have the active database config and safe fallback metrics,
// we fetch the current user's details cleanly via session or direct database check.
require_once $root . 'config/db.php';

try {
    $pdo = getDbConnection();
    $currentId = intval($_SESSION['user_id'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$currentId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Profile fetch error: ' . $e->getMessage());
    $user = null;
}

// Fallback in case your User model tracking structure is completely dependent on session
if (!$user) {
    $user = [
        'name' => $_SESSION['user_name'] ?? 'Admin Account',
        'email' => $_SESSION['user_email'] ?? 'admin@example.com',
        'role' => 'admin',
        'created_at' => 'N/A'
    ];
}

$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$pageTitle = 'My Profile Configuration';
require_once $root . 'templates/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=500;600;700;800&display=swap" rel="stylesheet">

<style>
    html, body, main, .wrapper, #app {
        background: linear-gradient(135deg, #fdf8f9 0%, #f4ebf0 100%) !important; 
        min-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        color: #4e3e43; 
    }

    header, nav, .navbar, .site-header, footer, .site-footer {
        display: none !important;
    }

    .edit-layout-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        width: 100%;
        padding: 40px 20px;
        box-sizing: border-box;
    }

    .workspace-inner-board {
        background-color: #ffffff;
        border-radius: 28px;
        padding: 45px;
        width: 100%;
        max-width: 500px;
        border: 2px solid #e1d3d6 !important;
        box-shadow: 0 20px 50px rgba(128, 0, 32, 0.05);
        box-sizing: border-box;
        text-align: center;
    }

    .center-logo-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        margin-bottom: 20px;
    }

    .profile-avatar-big {
        width: 80px;
        height: 80px;
        background: #800020;
        color: #ffffff; 
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 28px;
        border: 3px solid #ffa3b1;
        box-shadow: 0 8px 24px rgba(128, 0, 32, 0.15);
    }

    .section-title {
        font-size: 22px;
        font-weight: 800;
        color: #3d141d;
        margin-top: 15px;
        margin-bottom: 30px;
        letter-spacing: -0.4px;
    }

    .profile-info-group {
        background: #fdfafb;
        border: 1px solid #ebdbe0;
        border-radius: 16px;
        padding: 20px 24px;
        margin-bottom: 30px;
        text-align: left;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f5eaed;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-size: 13px;
        font-weight: 700;
        color: #6b5358;
    }

    .info-value {
        font-size: 14px;
        font-weight: 600;
        color: #3d141d;
    }

    .role-badge-admin {
        background-color: #f2dee3;
        color: #73001c;
        padding: 4px 12px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
    }

    .actions-row {
        display: flex;
        gap: 12px;
    }

    .btn {
        flex: 1;
        padding: 14px 20px;
        text-decoration: none;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
    }

    /* Soft Pastel Sage Green Edit Button */
    .btn-edit {
        background: #edf7f4 !important;
        color: #2d7a67 !important;
        border: 1px solid #d6ebd9 !important;
    }

    .btn-edit:hover {
        background: #e0f0eb !important;
        color: #1e5c4c !important;
    }

    /* Soft Pastel Cancel/Dashboard Button */
    .btn-back {
        background: #f3edf0 !important;
        color: #6b5358 !important;
        border: 1px solid #edd3d7 !important;
    }

    .btn-back:hover {
        background: #faf0f2 !important;
        color: #3d141d !important;
    }

    .alert-success {
        padding: 14px;
        margin-bottom: 20px;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 600;
        background: #e6f7f3;
        color: #0f766e;
        border: 1px solid #bef2e2;
        text-align: left;
    }
</style>

<div class="edit-layout-container">
    <div class="workspace-inner-board">
        
        <div class="center-logo-container">
            <div class="profile-avatar-big">
                <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
            </div>
        </div>

        <h3 class="section-title">⚙️ Admin System Profile</h3>

        <?php if ($success): ?>
            <div class="alert-success">✨ <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="profile-info-group">
            <div class="info-row">
                <span class="info-label">Full Name</span>
                <span class="info-value"><?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Email Address</span>
                <span class="info-value"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Authorization Role</span>
                <span class="info-value">
                    <span class="role-badge-admin">
                        <?= ucfirst(htmlspecialchars($user['role'] ?? 'Admin', ENT_QUOTES, 'UTF-8')) ?>
                    </span>
                </span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Account Joined</span>
                <span class="info-value">
                    <?= !empty($user['created_at']) && $user['created_at'] !== 'N/A' ? date('d M Y', strtotime($user['created_at'])) : 'N/A' ?>
                </span>
            </div>
        </div>
        
        <div class="actions-row">
            <a href="edit_profile.php" class="btn btn-edit">Edit Profile Settings</a>
            <a href="dashboard.php" class="btn btn-back">← Dashboard</a>
        </div>
        
    </div>
</div>

<?php require_once $root . 'templates/footer.php'; ?>

```