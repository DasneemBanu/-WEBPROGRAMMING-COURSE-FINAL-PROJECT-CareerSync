<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Establish the root base path for file inclusion
define('BASE_PATH', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');

// Include your system session/authentication and framework models
require_once BASE_PATH . 'includes/session.php';
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'models/User.php';

// Verify the student is authorized and logged in
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$pdo = getDbConnection();
$userId = currentUserId();

// Fetch user data using your project's specific model architecture
$userModel = new User();
$user = $userModel->findById($userId);

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Count total completed assessments for stats
$quizAttempts = $pdo->prepare("SELECT COUNT(*) FROM user_results WHERE user_id = ?");
$quizAttempts->execute([$userId]);
$myTotalQuizzes = $quizAttempts->fetchColumn();

// Count total career paths available in your system
$careerCount = $pdo->query("SELECT COUNT(*) FROM career_paths")->fetchColumn();

// Fetch the highest percentage score recorded for this user
$highestScoreQuery = $pdo->prepare("SELECT MAX(percentage) FROM user_results WHERE user_id = ?");
$highestScoreQuery->execute([$userId]);
$highestScore = $highestScoreQuery->fetchColumn();
$highestScore = $highestScore !== null ? number_format((float)$highestScore, 1) . '%' : 'N/A';

// Fetch the single most recent matching career path result
$latestResultQuery = $pdo->prepare("
    SELECT ur.percentage, ur.created_at, cp.title as career_title 
    FROM user_results ur
    JOIN career_paths cp ON ur.career_path_id = cp.id
    WHERE ur.user_id = ? 
    ORDER BY ur.created_at DESC LIMIT 1
");
$latestResultQuery->execute([$userId]);
$latestResult = $latestResultQuery->fetch();

$pageTitle = 'Student Portal Dashboard';
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

    /* Hide standard site wrappers if your layout breaks */
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

    /* Top Logo Center Header Wrapper */
    .dashboard-logo-container {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 10px;
    }

    .dashboard-logo-container img {
        max-height: 300px;
        width: auto;
        object-fit: contain;
    }

    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: #3d141d;
        margin-bottom: 20px;
    }

    /* Analytics Dashboard Metric Grid Blocks */
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
    }

    .mini-stat-info div:last-child {
        font-size: 16px;
        color: #42121c;
        font-weight: 700;
        margin-top: 2px;
    }

    /* Action Links Matrix Area Layout Blocks */
    .operations-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .op-card {
        border-radius: 24px;
        padding: 26px;
        box-shadow: 0 8px 24px rgba(128, 0, 32, 0.03);
    }

    .op-card.card-quiz { background-color: #f7e9ec; border: 1px solid #ebd0d6; }      
    .op-card.card-history { background-color: #f5e4e8; border: 1px solid #e8ccd2; }   
    .op-card.card-careers { background-color: #f2dee3; border: 1px solid #e3beae; }   
    .op-card.card-profile { background-color: #f9ecee; border: 1px solid #edd3d7; }   

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
    }

    .profile-details-box {
        margin-top: 25px;
        padding: 20px;
        border-radius: 16px;
        background-color: #fcf9fa;
        border: 1px solid #f2e6e9;
    }

    .profile-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px dashed #f2e6e9;
        font-size: 14px;
    }
    .profile-row:last-child { border-bottom: none; }
    .profile-row span:first-child { font-weight: 600; color: #8a7177; }
    .profile-row span:last-child { font-weight: 700; color: #3d141d; }

    /* Right Analytic Panel Configuration Styling */
    .right-panel {
        width: 340px;
        background-color: #800020; 
        border-left: 1px solid #6b001a;
        padding: 40px 30px;
        padding-top: 85px;
        box-sizing: border-box;
    }

    .right-panel .section-title { color: #ffffff !important; }

    .student-profile-header {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        margin-bottom: 40px;
    }

    .student-avatar {
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
    }

    .student-name {
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
    .dot-my-quizzes { background-color: #ffcbd3; } 
    .dot-top-score { background-color: #fbcfe8; }
    .dot-total-careers { background-color: #ffe4e6; }
    .metric-count { font-size: 14px; font-weight: 700; color: #ffffff; }

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
            <a href="#" class="menu-item active">📊 Portal Dashboard</a>
            <a href="quiz.php" class="menu-item">📝 Take Assessment</a>
            <a href="history.php" class="menu-item">📋 Evaluation History</a>
            <a href="recommendations.php" class="menu-item">💼 My Recommendations</a>
        </div>

        <div class="menu-section">
            <div class="menu-label">Credentials</div>
            <a href="profile.php" class="menu-item">⚙️ Profile Information</a>
        </div>

        <div class="sidebar-footer">
            <a href="../logout.php" class="menu-item" style="color: #ffffff; background: #b3002d; border: 1px solid #e6003a;">🚪 Logout</a>
        </div>
    </aside>

    <div class="main-layout">
        <div class="center-content">
            <div class="workspace-inner-board">
                
                <div class="dashboard-logo-container">
                    <img src="../assets/images/logo.png" alt="Portal Logo" onerror="this.style.display='none';">
                </div>
                
                <h3 class="section-title">My Academic Metrics</h3>
                <div class="quick-stats-row">
                    <div class="mini-stat-card">
                        <div class="mini-icon-box">📝</div>
                        <div class="mini-stat-info">
                            <div>COMPLETED</div>
                            <div><?= (int)$myTotalQuizzes ?> Assessments</div>
                        </div>
                    </div>
                    <div class="mini-stat-card">
                        <div class="mini-icon-box">🏆</div>
                        <div class="mini-stat-info">
                            <div>HIGHEST SCORE</div>
                            <div><?= htmlspecialchars($highestScore) ?></div>
                        </div>
                    </div>
                    <div class="mini-stat-card">
                        <div class="mini-icon-box">💼</div>
                        <div class="mini-stat-info">
                            <div>AVAILABLE</div>
                            <div><?= (int)$careerCount ?> Pathways</div>
                        </div>
                    </div>
                </div>

                <h3 class="section-title">Portal Assessment Workspaces</h3>
                <div class="operations-grid">
                    <div class="op-card card-quiz">
                        <span class="op-badge">Assessment</span>
                        <h4>Take Career Quiz</h4>
                        <p>Answer specialized tracking profile statements to discover your optimal target career framework.</p>
                        <a href="quiz.php" class="op-link">Launch Quiz Module</a>
                    </div>
                    <div class="op-card card-history">
                        <span class="op-badge">Logs</span>
                        <h4>Evaluation History</h4>
                        <p>Review historical scores, date points, and detailed analytical record history streams.</p>
                        <a href="history.php" class="op-link">Open History Logs</a>
                    </div>
                    <div class="op-card card-careers">
                        <span class="op-badge">Pathways</span>
                        <h4>View Recommendations</h4>
                        <p>Examine deep curriculum analysis maps for suggested career frameworks matched to you.</p>
                        <a href="recommendations.php" class="op-link">See Core Fit Matrix</a>
                    </div>
                    <div class="op-card card-profile">
                        <span class="op-badge">Identity</span>
                        <h4>Account Configuration</h4>
                        <p>Modify basic credentials, update passwords, and edit active identity parameters safely.</p>
                        <a href="update_profile.php" class="op-link">Edit Settings Info</a>
                    </div>
                </div>

                <div class="profile-details-box">
                    <h4 style="margin: 0 0 15px 0; font-size: 15px; color: #3d141d;">Personal Identity Verification</h4>
                    <div class="profile-row">
                        <span>Full Name:</span>
                        <span><?= htmlspecialchars($user['name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="profile-row">
                        <span>Email Address:</span>
                        <span><?= htmlspecialchars($user['email'] ?? 'N/A') ?></span>
                    </div>
                    <div class="profile-row">
                        <span>Registration Date:</span>
                        <span><?= htmlspecialchars(!empty($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : 'N/A') ?></span>
                    </div>
                </div>
                
            </div>
        </div>

        <div class="right-panel">
            <div class="student-profile-header">
                <span class="student-name"><?= htmlspecialchars($user['name'] ?? 'Student') ?></span>
                <div class="student-avatar">
                    <?= strtoupper(substr($user['name'] ?? 'S', 0, 1)) ?>
                </div>
            </div>

            <h3 class="section-title" style="margin-bottom: 25px;">Performance Balance</h3>

            <div class="metric-list">
                <div class="metric-row">
                    <div class="metric-title-box"><div class="dot dot-my-quizzes"></div> Taken Quizzes</div>
                    <span class="metric-count"><?= (int)$myTotalQuizzes ?></span>
                </div>
                <div class="metric-row">
                    <div class="metric-title-box"><div class="dot dot-top-score"></div> Top Score</div>
                    <span class="metric-count"><?= htmlspecialchars($highestScore) ?></span>
                </div>
                <div class="metric-row">
                    <div class="metric-title-box"><div class="dot dot-total-careers"></div> System Careers</div>
                    <span class="metric-count"><?= (int)$careerCount ?></span>
                </div>
            </div>
            
            <?php if ($latestResult): ?>
                <h3 class="section-title" style="margin-top: 40px; margin-bottom: 15px; font-size: 14px;">Latest Match</h3>
                <div style="background: rgba(255,255,255,0.1); border: 1px dashed rgba(255,255,255,0.2); padding: 15px; border-radius: 14px;">
                    <div style="color: #ffd1dc; font-size: 12px; font-weight: 700;"><?= strtoupper(date('M d, Y', strtotime($latestResult['created_at']))) ?></div>
                    <div style="color: #ffffff; font-size: 15px; font-weight: 800; margin-top: 4px;"><?= htmlspecialchars($latestResult['career_title']) ?></div>
                    <div style="color: #ffa3b1; font-size: 13px; font-weight: 700; margin-top: 2px;">Match Rate: <?= number_format((float)$latestResult['percentage'], 1) ?>%</div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('dashboardWrapper').classList.toggle('sidebar-collapsed');
    }
</script>

<?php require_once BASE_PATH . 'templates/footer.php'; ?>

```