<?php
session_start();

// Clear any previous session data
session_destroy();
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    
    if ($role === 'admin' || $role === 'user') {
        $_SESSION['selected_role'] = $role;
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareerSync - Select Role</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #ffffff;
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            overflow-x: hidden;
        }

        /* -------------------------------------------------------------
           Colorful Bubble Background Elements (Inspired by UI Layout)
           ------------------------------------------------------------- */
        .bg-circle {
            position: fixed;
            border-radius: 50%;
            z-index: -1;
        }
        
        /* Vibrant Blue Tone Family */
        .circle-blue-lg {
            width: 320px;
            height: 320px;
            background-color: #5383ec;
            left: -100px;
            top: -40px;
        }
        .circle-blue-md {
            width: 140px;
            height: 140px;
            background-color: #4385f5;
            left: 210px;
            top: 240px;
        }
        .circle-blue-sm {
            width: 45px;
            height: 45px;
            border: 3px solid #4385f5;
            background-color: transparent;
            left: 250px;
            top: 130px;
        }
        .circle-blue-xs {
            width: 50px;
            height: 50px;
            background-color: #e3effd;
            left: 270px;
            top: 450px;
        }

        /* Warm Yellow/Orange Tone Family */
        .circle-yellow-lg {
            width: 340px;
            height: 340px;
            background-color: #f4b400;
            left: -80px;
            bottom: -50px;
        }
        .circle-yellow-md {
            width: 130px;
            height: 130px;
            border: 4px solid #f4b400;
            background-color: transparent;
            left: 110px;
            bottom: 310px;
        }
        .circle-yellow-sm {
            width: 55px;
            height: 55px;
            background-color: #fef0cd;
            left: 310px;
            bottom: 40px;
        }

        /* Fresh Green Tone Family */
        .circle-green-lg {
            width: 420px;
            height: 420px;
            background-color: #0f9d58;
            right: -120px;
            top: -150px;
            opacity: 0.75;
        }
        .circle-green-md {
            width: 160px;
            height: 160px;
            background-color: #0f9d58;
            right: 180px;
            top: 170px;
            opacity: 0.75;
        }
        .circle-green-sm {
            width: 80px;
            height: 80px;
            border: 4px solid #0f9d58;
            background-color: transparent;
            right: 50px;
            top: 290px;
            opacity: 0.75;
        }
        .circle-green-xs {
            width: 60px;
            height: 60px;
            background-color: #eaf6ed;
            right: 280px;
            top: 40px;
        }

        /* Container Content Layout */
        .container {
            max-width: 540px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin: 0 auto;
            z-index: 1;
        }

        /* Scaled-up Logo Branding Container */
        .logo-container {
            margin-bottom: 35px;
            max-width: 460px; /* Made bigger on mobile viewports */
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        h1 {
            font-size: 38px;
            color: #2d3748;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 40px;
            letter-spacing: -0.5px;
        }

        .role-form {
            display: flex;
            flex-direction: column;
            gap: 24px;
            width: 100%;
        }

        /* Card Button Styling */
        .role-card-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            border: 2px solid transparent;
            border-radius: 24px;
            padding: 28px 24px;
            cursor: pointer;
            background: #ffffff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.02), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        }

        .btn-student {
            background-color: #eef2ff;
            border-color: #dee5ff;
        }
        .btn-student .card-title {
            color: #3b5998;
        }

        .btn-admin {
            background-color: #fffbeb;
            border-color: #fef0cd;
        }
        .btn-admin .card-title {
            color: #b45309;
        }

        .role-card-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }
        .btn-student:hover {
            border-color: #4f46e5;
        }
        .btn-admin:hover {
            border-color: #d97706;
        }

        .graphic-area {
            font-size: 54px;
            margin-right: 20px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            transition: transform 0.3s ease;
        }
        .role-card-btn:hover .graphic-area {
            transform: scale(1.08);
        }

        .text-area {
            display: flex;
            align-items: center;
        }

        .card-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .footer {
            margin-top: 50px;
            color: #a0aec0;
            font-size: 13px;
            font-weight: 600;
        }

        /* Desktop Optimization Breakpoint */
        @media (min-width: 850px) {
            .container {
                max-width: 860px;
            }

            .logo-container {
                max-width: 520px; /* Enhanced wide branding display */
                margin-bottom: 40px;
            }

            h1 {
                font-size: 42px;
                margin-bottom: 50px;
            }

            .role-form {
                flex-direction: row;
                gap: 30px;
                justify-content: center;
            }

            .role-card-btn {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 40px 32px;
                width: 320px;
                min-height: 240px;
            }

            .graphic-area {
                margin-right: 0;
                margin-bottom: 16px;
                font-size: 64px;
            }

            .text-area {
                justify-content: center;
                width: 100%;
            }

            .card-title {
                font-size: 24px;
            }
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

<div class="container">
    
    <div class="logo-container">
        <img src="assets/images/logo.png" alt="CareerSync Logo" class="logo-img">
    </div>

    <h1>Select Role</h1>

    <form method="POST" class="role-form">
        <button type="submit" name="role" value="user" class="role-card-btn btn-student">
            <div class="graphic-area">🎓</div>
            <div class="text-area">
                <div class="card-title">Student Portal</div>
            </div>
        </button>

        <button type="submit" name="role" value="admin" class="role-card-btn btn-admin">
            <div class="graphic-area">🛠️</div>
            <div class="text-area">
                <div class="card-title">Administrator Control</div>
            </div>
        </button>
    </form>

    <div class="footer">CareerSync — Web Programming Course Project</div>
</div>

</body>
</html>