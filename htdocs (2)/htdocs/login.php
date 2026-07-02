<?php
session_start();
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/models/User.php';

// If already logged in, redirect right away
if (isLoggedIn()) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit;
}

// Check if role was selected
$selectedRole = $_SESSION['selected_role'] ?? '';
if ($selectedRole !== 'admin' && $selectedRole !== 'user') {
    header('Location: select_role.php');
    exit;
}

// Establish CSRF Protection Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF Token matching parameters
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Security token validation failed. Please try again.";
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $errors[] = "Email and password are required.";
        } else {
            $userModel = new User();
            $user = $userModel->verifyLogin($email, $password);

            if ($user) {
                if ($user['role'] !== $selectedRole) {
                    $errors[] = "This account is registered as " . ucfirst($user['role']) . ". Please select the correct role.";
                } else {
                    // Regenerate session id to protect against Session Fixation attacks
                    session_regenerate_id(true);

                    loginUser($user['id'], $user['name'], $user['email'], $user['role']);
                    unset($_SESSION['selected_role']);

                    if ($user['role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                    } else {
                        header('Location: student/dashboard.php');
                    }
                    exit;
                }
            } else {
                $errors[] = "Invalid email or password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CareerSync</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght=600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-x: hidden;
            position: relative;
        }

        /* Background Bubbles Framework */
        .bg-circle {
            position: fixed;
            border-radius: 50%;
            z-index: 0;
        }
        .circle-blue-lg { width: 320px; height: 320px; background-color: #5383ec; left: -100px; top: -40px; }
        .circle-blue-md { width: 140px; height: 140px; background-color: #4385f5; left: 210px; top: 240px; }
        .circle-blue-sm { width: 45px; height: 45px; border: 3px solid #4385f5; background-color: transparent; left: 250px; top: 130px; }
        .circle-blue-xs { width: 50px; height: 50px; background-color: #e3effd; left: 270px; top: 450px; }

        .circle-yellow-lg { width: 340px; height: 340px; background-color: #f4b400; left: -80px; bottom: -50px; }
        .circle-yellow-md { width: 130px; height: 130px; border: 4px solid #f4b400; background-color: transparent; left: 110px; bottom: 310px; }
        .circle-yellow-sm { width: 55px; height: 55px; background-color: #fef0cd; left: 310px; bottom: 40px; }

        .circle-green-lg { width: 420px; height: 420px; background-color: #0f9d58; right: -120px; top: -150px; opacity: 0.75; }
        .circle-green-md { width: 160px; height: 160px; background-color: #0f9d58; right: 180px; top: 170px; opacity: 0.75; }
        .circle-green-sm { width: 80px; height: 80px; border: 4px solid #0f9d58; background-color: transparent; right: 50px; top: 290px; opacity: 0.75; }
        .circle-green-xs { width: 60px; height: 60px; background-color: #eaf6ed; right: 280px; top: 40px; }

        /* Colored Form Container Card */
        .auth-container {
            max-width: 450px;
            width: 100%;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 45px 35px;
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.01);
            border: 2px solid #e2e8f0;
            text-align: center;
            z-index: 1;
            margin: auto;
        }

        .logo-container {
            margin-bottom: 20px;
            max-width: 240px;
            margin-left: auto;
            margin-right: auto;
        }
        .logo-img {
            width: 100%;
            height: auto;
            vertical-align: middle;
        }

        h2 {
            font-size: 26px;
            color: #1e293b;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 32px;
        }
        .role-student {
            background-color: #eef2ff;
            color: #4f46e5;
            border: 1px solid #c7d2fe;
        }
        .role-admin {
            background-color: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .input-group {
            width: 100%;
            margin-bottom: 16px;
        }
        
        input {
            width: 100%;
            padding: 16px 20px;
            border-radius: 16px;
            font-family: 'Quicksand', sans-serif;
            font-size: 15px;
            font-weight: 600;
            outline: none;
            transition: all 0.2s ease;
        }

        .student-input {
            background-color: #ffffff;
            border: 2px solid #cbd5e1;
            color: #1e293b;
        }
        .student-input::placeholder { color: #94a3b8; }
        .student-input:focus {
            border-color: #4385f5;
            box-shadow: 0 0 0 4px rgba(67, 133, 245, 0.15);
        }

        .admin-input {
            background-color: #ffffff;
            border: 2px solid #cbd5e1;
            color: #1e293b;
        }
        .admin-input::placeholder { color: #94a3b8; }
        .admin-input:focus {
            border-color: #f4b400;
            box-shadow: 0 0 0 4px rgba(244, 180, 0, 0.15);
        }

        button {
            width: 100%;
            padding: 16px;
            background: #4385f5;
            color: white;
            border: none;
            border-radius: 16px;
            font-family: 'Quicksand', sans-serif;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(67, 133, 245, 0.2);
            margin-top: 8px;
        }
        button:hover {
            background: #2a6edb;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(67, 133, 245, 0.25);
        }

        .divider {
            margin: 22px 0;
            color: #94a3b8;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-register {
            display: block;
            width: 100%;
            padding: 16px;
            background: #eaf6ed;
            color: #0f9d58;
            text-align: center;
            text-decoration: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 700;
            transition: all 0.2s ease;
            border: 1px solid #c2ebd4;
        }
        .btn-register:hover {
            background: #d3edd9;
            transform: translateY(-1px);
        }

        .links {
            margin-top: 24px;
        }
        .links a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: color 0.2s ease;
        }
        .links a:hover {
            color: #4385f5;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 16px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            text-align: left;
            line-height: 1.5;
        }
        .alert-success {
            background-color: #eaf6ed;
            color: #0f9d58;
            border: 1px solid #c2ebd4;
        }
        .alert-error {
            background-color: #fff5f5;
            color: #e53e3e;
            border: 1px solid #fed7d7;
        }
    </style>
</head>
<body>

    <div class="bg-circle circle-blue-lg"></div>
    <div class="bg-circle circle-blue-md"></div>
    <div class="bg-circle circle-blue-sm"></div>
    <div class="bg-circle circle-blue-xs"></div>

    <div class="bg-circle circle-yellow-lg"></div>
    <div class="bg-circle circle-yellow-md"></div>
    <div class="bg-circle circle-yellow-sm"></div>

    <div class="bg-circle circle-green-lg"></div>
    <div class="bg-circle circle-green-md"></div>
    <div class="bg-circle circle-green-sm"></div>
    <div class="bg-circle circle-green-xs"></div>

    <div class="auth-container">
        <div class="logo-container">
            <img src="assets/images/logo.png" alt="CareerSync Logo" class="logo-img">
        </div>

        <h2>Welcome Back</h2>
        
        <span class="role-badge <?= $selectedRole === 'admin' ? 'role-admin' : 'role-student' ?>">
            <?= $selectedRole === 'admin' ? '🛠️ Administrator Control' : '🎓 Student Portal' ?>
        </span>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p>⚠️ <?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="input-group">
                <input type="email" 
                       name="email" 
                       class="<?= $selectedRole === 'admin' ? 'admin-input' : 'student-input' ?>" 
                       placeholder="Email address" 
                       required 
                       autofocus>
            </div>
            <div class="input-group">
                <input type="password" 
                       name="password" 
                       class="<?= $selectedRole === 'admin' ? 'admin-input' : 'student-input' ?>" 
                       placeholder="Password" 
                       required>
            </div>
            <button type="submit">Sign In</button>
        </form>

        <div class="divider">or</div>

        <a href="register.php" class="btn-register">Create New Account</a>

        <div class="links">
            <a href="select_role.php">← Change Account Role</a>
        </div>
    </div>

</body>
</html>

```