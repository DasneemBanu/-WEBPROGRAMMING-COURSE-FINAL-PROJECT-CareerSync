<?php
session_start();

define('BASE_PATH', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');

require_once BASE_PATH . 'includes/auth_check.php';
require_once BASE_PATH . 'models/User.php';

requireLogin();

// CSRF token generation/validation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userModel = new User();
$user = $userModel->findById(currentUserId());

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($name)) {
            $errors[] = 'Name is required.';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Name must be under 100 characters.';
        }

        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        } elseif (strlen($email) > 255) {
            $errors[] = 'Email is too long.';
        } elseif (strtolower($email) !== strtolower($user['email']) && $userModel->findByEmail($email)) {
            $errors[] = 'Email is already taken.';
        }

        $passwordChanged = false;
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $errors[] = 'Current password is required to set a new password.';
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $errors[] = 'Current password is incorrect.';
            } elseif (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match.';
            } else {
                $passwordChanged = true;
            }
        }

        if (empty($errors)) {
            $db = getDbConnection();
            try {
                $db->beginTransaction();

                $updateSuccess = $userModel->updateProfile(currentUserId(), $name, $email);

                if ($passwordChanged) {
                    $userModel->updatePassword(currentUserId(), $newPassword);
                }

                $db->commit();

                if ($updateSuccess) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['flash_success'] = 'Profile updated successfully!';
                    header('Location: profile.php');
                    exit;
                }
            } catch (Exception $e) {
                $db->rollBack();
                error_log('Profile update failed: ' . $e->getMessage());
                $errors[] = 'Failed to update profile. Please try again.';
            }
        }
    }
}

$pageTitle = 'Edit Profile';
require_once BASE_PATH . 'templates/header.php';
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

    /* Profile Form Container Framework Matrix */
    .profile-card-matrix {
        background-color: #fdf8f9;
        border: 3px solid #800020;
        border-radius: 16px;
        padding: 30px;
        margin-top: 25px;
    }

    .form-group-block {
        margin-bottom: 22px;
    }

    .form-group-block label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: #800020;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .form-group-block input[type="text"],
    .form-group-block input[type="email"],
    .form-group-block input[type="password"] {
        width: 100%;
        padding: 14px 16px;
        border: 1px solid #ebd0d6;
        border-radius: 12px;
        font-family: inherit;
        font-size: 14px;
        font-weight: 600;
        color: #3d141d;
        background-color: #ffffff;
        box-sizing: border-box;
        transition: all 0.2s ease;
    }

    .form-group-block input:focus {
        outline: none;
        border-color: #800020;
        box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.1);
    }

    .info-subtext-alert {
        color: #8a7177;
        font-size: 13px;
        font-weight: 600;
        margin: 0 0 20px 0;
        display: flex;
        align-items: center;
        gap: 6px;
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
            <a href="dashboard.php" class="menu-item">📊 Portal Dashboard</a>
            <a href="quiz.php" class="menu-item">📝 Take Assessment</a>
            <a href="history.php" class="menu-item">📋 Evaluation History</a>
            <a href="recommendations.php" class="menu-item">💼 My Recommendations</a>
        </div>

        <div class="menu-section">
            <div class="menu-label">Credentials</div>
            <a href="profile.php" class="menu-item active">⚙️ Profile Information</a>
        </div>

        <div class="sidebar-footer">
            <a href="../logout.php" class="menu-item" style="color: #ffffff; background: #b3002d; border: 1px solid #e6003a;">🚪 Logout</a>
        </div>
    </aside>

    <div class="main-layout">
        <main class="center-content">
            <div class="workspace-inner-board">
                
                <h2 class="section-title">Modify Settings</h2>
                <p style="color: #94757c; font-size: 14px; margin-top: 0; margin-bottom: 25px; font-weight: 600;">Update account identifiers and password tracking hashes securely.</p>

                <?php if (!empty($errors)): ?>
                    <div style="background: #b3002d; color: white; padding: 14px 20px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; font-weight: 600;">
                        <?php foreach ($errors as $error): ?>
                            <div style="margin-bottom: 4px;">⚠️ <?= htmlspecialchars($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['flash_success'])): ?>
                    <div style="background: #2e7d32; color: white; padding: 14px 20px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; font-weight: 600;">
                        ✅ <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="profile-card-matrix">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="form-group-block">
                        <label for="name">Display Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required maxlength="100">
                    </div>

                    <div class="form-group-block">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required maxlength="255">
                    </div>

                    <div style="height: 2px; background-color: #ebd0d6; border: none; margin: 30px 0 25px 0;"></div>

                    <p class="info-subtext-alert">🔒 Leave password fields completely blank if you do not wish to modify it.</p>

                    <div class="form-group-block">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" autocomplete="current-password">
                    </div>

                    <div class="form-group-block">
                        <label for="new_password">New Account Password</label>
                        <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8">
                    </div>

                    <div class="form-group-block">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
                    </div>

                    <div class="button-group-row">
                        <a href="profile.php" class="action-btn btn-muted-back">Cancel</a>
                        <button type="submit" class="action-btn btn-primary-action">Update Profile</button>
                    </div>
                </form>

            </div>
        </main>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('dashboardWrapper').classList.toggle('sidebar-collapsed');
    }
</script>

<?php require_once BASE_PATH . 'templates/footer.php'; ?>