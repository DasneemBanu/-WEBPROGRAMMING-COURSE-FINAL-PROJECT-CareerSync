<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/models/User.php';

// If already logged in, redirect
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Check if role was selected
$selectedRole = $_SESSION['selected_role'] ?? '';
if ($selectedRole !== 'admin' && $selectedRole !== 'user') {
    header('Location: select_role.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $userModel = new User();
        $result = $userModel->register($name, $email, $password, $selectedRole);

        if ($result['success']) {
            $_SESSION['flash_success'] = "Registration successful! Please login.";
            header('Location: login.php');
            exit;
        } else {
            $errors[] = $result['message'] ?? "Registration failed.";
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/templates/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* Global View Framework Configuration */
    html, body, main, .wrapper, #app {
        background-color: #f7f3f5 !important; 
        min-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        color: #4e3e43;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Hide base template structural headers/footers for modern auth page setup */
    header, nav, .navbar, .site-header, footer, .site-footer {
        display: none !important;
    }

    .auth-container {
        width: 100%;
        max-width: 440px;
        margin: 40px auto;
        padding: 40px;
        background-color: #ffffff;
        border-radius: 24px;
        border: 1px solid #f2e6e9;
        box-shadow: 0 12px 32px rgba(128, 0, 32, 0.05);
        box-sizing: border-box;
    }

    .center { 
        text-align: center; 
    }

    /* Logo Styling Configuration */
    .brand-logo {
        max-width: 300px;
        height: auto;
        margin-bottom: 20px;
        object-fit: contain;
    }

    h2 {
        font-size: 26px;
        font-weight: 800;
        color: #3d141d;
        margin-top: 0;
        margin-bottom: 8px;
    }

    .role-badge {
        display: inline-block;
        text-align: center;
        background: #fdf8f9;
        border: 1px solid #ebd0d6;
        padding: 6px 16px;
        border-radius: 12px;
        margin-bottom: 30px;
        color: #800020;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.3px;
    }

    /* Form Inputs Tracking Structural Matrix */
    input {
        width: 100%;
        padding: 14px 16px;
        margin-bottom: 16px;
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

    input:focus {
        outline: none;
        border-color: #800020;
        box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.1);
    }

    input::placeholder {
        color: #94757c;
        font-weight: 500;
    }

    /* Form Action Trigger Element Configurations */
    button {
        width: 100%;
        padding: 14px;
        background: #800020;
        color: #ffffff;
        border: 1px solid #6b001a;
        border-radius: 12px;
        font-family: inherit;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-top: 5px;
        box-sizing: border-box;
    }

    button:hover { 
        background: #990026; 
        border-color: #800020;
    }

    .error {
        color: #ffffff;
        background: #b3002d;
        padding: 14px 18px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .error p {
        margin: 0 0 4px 0;
    }
    .error p:last-child {
        margin-bottom: 0;
    }

    .divider {
        text-align: center;
        margin: 24px 0;
        color: #94757c;
        font-size: 13px;
        font-weight: 600;
    }

    .btn-login {
        display: block;
        width: 100%;
        padding: 14px;
        background: #edd1d8;
        color: #610018;
        border: 1px solid #e3beae;
        text-align: center;
        text-decoration: none;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        box-sizing: border-box;
        transition: all 0.2s ease;
    }

    .btn-login:hover { 
        background: #610018; 
        color: #ffffff; 
        border-color: #4a0012;
    }

    .links {
        text-align: center;
        margin-top: 24px;
    }

    .links a {
        color: #800020;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        transition: color 0.2s;
    }

    .links a:hover { 
        color: #b3002d;
        text-decoration: underline; 
    }
</style>

<div class="auth-container">
    <div class="center">
        <img src="/assets/images/logo.png" alt="Logo" class="brand-logo">
        
        <h2>Create Account</h2>
        <span class="role-badge">
            <?= $selectedRole === 'admin' ? '🔧 Admin Portal' : '👤 Student Portal' ?>
        </span>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p>⚠️ <?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="name" placeholder="Full name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        <input type="email" name="email" placeholder="Email address" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <input type="password" name="password" placeholder="Password (min 6 chars)" required>
        <input type="password" name="confirm_password" placeholder="Confirm password" required>
        <button type="submit">Register Account</button>
    </form>

    <div class="divider">or</div>

    <a href="login.php" class="btn-login">Already have an account? Sign In</a>

    <div class="links">
        <a href="select_role.php">← Change Security Role</a>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>

