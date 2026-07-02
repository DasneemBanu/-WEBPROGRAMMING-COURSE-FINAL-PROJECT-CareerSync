<?php
session_start();

$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';

require_once $root . 'includes/admin_check.php';
require_once $root . 'config/db.php';

$pdo = getDbConnection();
$id = intval($_GET['id'] ?? 0);
$errors = [];

$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$id]);
$user = $user->fetch();

if (!$user) {
    header('Location: manage_user.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';

    if (empty($name)) $errors[] = 'Name field is required.';
    if (empty($email)) $errors[] = 'Email field is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
        $stmt->execute([$name, $email, $role, $id]);
        $_SESSION['flash_success'] = 'User records updated successfully!';
        header('Location: manage_user.php');
        exit;
    }
}

$pageTitle = 'Edit User Layout';
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
        /* Custom prominent border line style around the edit board block */
        border: 2px solid #e1d3d6 !important;
        box-shadow: 0 20px 50px rgba(128, 0, 32, 0.05);
        box-sizing: border-box;
    }

    .center-logo-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        margin-bottom: 30px;
    }

    .center-logo-img {
        width: 100%;
        max-width: 240px;
        height: auto;
        object-fit: contain;
        display: block;
    }

    .section-title {
        font-size: 22px;
        font-weight: 800;
        color: #3d141d;
        margin-top: 0;
        margin-bottom: 30px;
        letter-spacing: -0.4px;
        text-align: center;
    }

    .form-group {
        margin-bottom: 22px;
        text-align: left;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 700;
        font-size: 13px;
        color: #6b5358;
        letter-spacing: 0.2px;
    }

    .form-group input, .form-group select {
        width: 100%;
        padding: 14px 16px;
        border: 1px solid #ebdbe0;
        border-radius: 12px;
        background-color: #fdfafb;
        color: #3d141d;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        font-size: 14px;
        font-weight: 600;
        box-sizing: border-box;
        transition: all 0.2s ease;
    }

    .form-group input:focus, .form-group select:focus {
        outline: none;
        border-color: #c9425e;
        background-color: #ffffff;
        box-shadow: 0 0 0 4px rgba(201, 66, 94, 0.08);
    }

    .actions-row {
        display: flex;
        gap: 12px;
        margin-top: 35px;
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

    /* Soft Pastel Sage Green Update Button */
    .btn-submit {
        background: #edf7f4 !important;
        color: #2d7a67 !important;
        border: 1px solid #d6ebd9 !important;
    }

    .btn-submit:hover {
        background: #e0f0eb !important;
        color: #1e5c4c !important;
    }

    /* Soft Pastel Cancel Button */
    .btn-cancel {
        background: #f3edf0 !important;
        color: #6b5358 !important;
        border: 1px solid #edd3d7 !important;
    }

    .btn-cancel:hover {
        background: #faf0f2 !important;
        color: #3d141d !important;
    }

    .alert-error {
        padding: 14px;
        margin-bottom: 20px;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 600;
        background: #fdf2f4;
        color: #b3002d;
        border: 1px solid #fcdede;
        text-align: left;
    }
</style>

<div class="edit-layout-container">
    <div class="workspace-inner-board">
        
        <div class="center-logo-container">
            <img src="/assets/images/logo.png" alt="CareerSync Logo" class="center-logo-img">
        </div>

        <h3 class="section-title">✏️ Edit Account Configuration</h3>

        <?php foreach ($errors as $e): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <div class="form-group">
                <label>Account Full Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>System Authorization Role</label>
                <select name="role">
                    <option value="user" <?= ($user['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>User Access Status</option>
                    <option value="admin" <?= ($user['role'] ?? 'user') === 'admin' ? 'selected' : '' ?>>Admin Authority Status</option>
                </select>
            </div>
            
            <div class="actions-row">
                <button type="submit" class="btn btn-submit">Update Configuration</button>
                <a href="manage_user.php" class="btn btn-cancel">Cancel Changes</a>
            </div>
        </form>
        
    </div>
</div>

<?php require_once $root . 'templates/footer.php'; ?>