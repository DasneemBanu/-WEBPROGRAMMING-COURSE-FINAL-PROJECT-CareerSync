<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = $_SERVER['DOCUMENT_ROOT']; 

require_once $root . '/includes/admin_check.php';
require_once $root . '/config/db.php';

$pageTitle = 'Admin Dashboard';
require_once $root . '/templates/header.php';

$pdo = getDbConnection();
$userCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$quizCount = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
$careerCount = $pdo->query("SELECT COUNT(*) FROM career_paths")->fetchColumn();
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* -------------------------------------------------------------
       Global Canvas Resets & Structure
       ------------------------------------------------------------- */
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

    /* -------------------------------------------------------------
       Sidebar Toggle Control Menu Trigger Button
       ------------------------------------------------------------- */
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

    /* -------------------------------------------------------------
       Left Navigation Menu Sidebar Layout (Dark Maroon)
       ------------------------------------------------------------- */
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

    /* -------------------------------------------------------------
       Main Content Layout Space Area
       ------------------------------------------------------------- */
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
    
    /* Bigger & Bold Centered Logo Layout Settings */
    .center-logo-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        margin-bottom: 35px;
        padding-top: 10px;
    }

    .center-logo-img {
        width: 100%;
        max-width: 320px; /* Increased from 210px to make it noticeably larger and clearer */
        height: auto;
        object-fit: contain;
        display: block;
    }

    .workspace-inner-board {
        background-color: #ffffff;
        border-radius: 28px;
        padding: 35px;
        border: 1px solid #f2e6e9;
        box-shadow: 0 10px 30px rgba(128, 0, 32, 0.04);
    }

    /* Collapsed Sidebar States */
    .dashboard-wrapper.sidebar-collapsed .sidebar {
        transform: translateX(-260px);
    }

    .dashboard-wrapper.sidebar-collapsed .main-layout {
        margin-left: 0;
    }

    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: #3d141d;
        margin-bottom: 20px;
        letter-spacing: -0.3px;
    }

    /* -------------------------------------------------------------
       Middle Content Elements (Pastel Maroon Configurations)
       ------------------------------------------------------------- */
    .quick-stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 35px;
    }

    .mini-stat-card {
        padding: 20px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 6px 16px rgba(128, 0, 32, 0.02);
        background-color: #faf0f2; 
        border: 1px solid #f0dae0;
    }

    .mini-icon-box {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        background-color: #edd1d8;
        color: #610018;
    }

    .mini-stat-info div:first-child {
        font-size: 11px;
        color: #94757c;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .mini-stat-info div:last-child {
        font-size: 16px;
        color: #42121c;
        font-weight: 700;
        margin-top: 2px;
    }

    .operations-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .op-card {
        border-radius: 24px;
        padding: 26px;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        box-shadow: 0 8px 24px rgba(128, 0, 32, 0.03);
    }

    .op-card.card-users { background-color: #f7e9ec; border: 1px solid #ebd0d6; }     
    .op-card.card-quizzes { background-color: #f5e4e8; border: 1px solid #e8ccd2; }   
    .op-card.card-careers { background-color: #f2dee3; border: 1px solid #e3beae; }   
    .op-card.card-profile { background-color: #f9ecee; border: 1px solid #edd3d7; }   

    .op-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(128, 0, 32, 0.08);
    }

    .op-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 700;
        margin-bottom: 16px;
        background-color: rgba(128, 0, 32, 0.1); 
        color: #73001c;
    }

    .op-card h4 {
        font-size: 16px;
        color: #38020c;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .op-card p {
        font-size: 13px;
        color: #6b5358;
        line-height: 1.5;
        margin-bottom: 22px;
        min-height: 40px;
    }

    .op-link {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 12px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        background-color: rgba(255, 255, 255, 0.8);
        color: #800020;
        border: 1px solid rgba(128, 0, 32, 0.2);
        transition: all 0.2s ease;
    }

    .op-link:hover {
        background-color: #800020;
        color: #ffffff;
        border-color: #800020;
    }

    /* -------------------------------------------------------------
       Right Sidebar Analytics Panel Summary (Dark Maroon)
       ------------------------------------------------------------- */
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
            <a href="#" class="menu-item active">📊 Dashboard</a>
            <a href="manage_user.php" class="menu-item">👥 Users Pool</a>
            <a href="manage_question.php" class="menu-item">📝 Quiz Metrics</a>
            <a href="manage_career.php" class="menu-item">💼 Career Framework</a>
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
            
            <div class="center-logo-container">
                <img src="/assets/images/logo.png" alt="CareerSync Logo" class="center-logo-img">
            </div>

            <div class="workspace-inner-board">
                
                <h3 class="section-title">Platform Statistics</h3>
                <div class="quick-stats-row">
                    <div class="mini-stat-card">
                        <div class="mini-icon-box">👥</div>
                        <div class="mini-stat-info">
                            <div>REGISTERED</div>
                            <div><?= $userCount ?> Students</div>
                        </div>
                    </div>
                    <div class="mini-stat-card">
                        <div class="mini-icon-box">📝</div>
                        <div class="mini-stat-info">
                            <div>ACTIVE POOL</div>
                            <div><?= $quizCount ?> Questions</div>
                        </div>
                    </div>
                    <div class="mini-stat-card">
                        <div class="mini-icon-box">💼</div>
                        <div class="mini-stat-info">
                            <div>MAPPED</div>
                            <div><?= $careerCount ?> Careers</div>
                        </div>
                    </div>
                </div>

                <h3 class="section-title">Quick Management Actions</h3>
                <div class="operations-grid">
                    <div class="op-card card-users">
                        <span class="op-badge">Accounts</span>
                        <h4>Manage Users</h4>
                        <p>Track, modify, or verify registered students and administrators profiles.</p>
                        <a href="manage_user.php" class="op-link">Open Users Pool</a>
                    </div>
                    <div class="op-card card-quizzes">
                        <span class="op-badge">Evaluations</span>
                        <h4>Quiz Metrics</h4>
                        <p>Construct, analyze, or delete core interactive quiz assessment questions.</p>
                        <a href="manage_question.php" class="op-link">Open Quiz Metrics</a>
                    </div>
                    <div class="op-card card-careers">
                        <span class="op-badge">Vocations</span>
                        <h4>Manage Careers</h4>
                        <p>Update skill properties roadmaps, core criteria details, and descriptions text strings.</p>
                        <a href="manage_career.php" class="op-link">Adjust Career Paths</a>
                    </div>
                    <div class="op-card card-profile">
                        <span class="op-badge">Security</span>
                        <h4>My Profile</h4>
                        <p>Change your active configuration settings, password access controls safely.</p>
                        <a href="profile.php" class="op-link">View Settings Panel</a>
                    </div>
                </div>
                
            </div>
        </div>

        <div class="right-panel">
            <div class="admin-profile-header">
                <span class="admin-name"><?= htmlspecialchars($_SESSION['user_name']) ?> (Admin)</span>
                <div class="admin-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                </div>
            </div>

            <h3 class="section-title" style="margin-bottom: 25px;">Data Balance</h3>

            <div class="metric-list">
                <div class="metric-row">
                    <div class="metric-title-box">
                        <div class="dot dot-users"></div> Users
                    </div>
                    <span class="metric-count"><?= $userCount ?></span>
                </div>
                <div class="metric-row">
                    <div class="metric-title-box">
                        <div class="dot dot-admins"></div> Admins
                    </div>
                    <span class="metric-count"><?= $adminCount ?></span>
                </div>
                <div class="metric-row">
                    <div class="metric-title-box">
                        <div class="dot dot-quizzes"></div> Questions
                    </div>
                    <span class="metric-count"><?= $quizCount ?></span>
                </div>
                <div class="metric-row">
                    <div class="metric-title-box">
                        <div class="dot dot-careers"></div> Careers
                    </div>
                    <span class="metric-count"><?= $careerCount ?></span>
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

</body>
</html>
