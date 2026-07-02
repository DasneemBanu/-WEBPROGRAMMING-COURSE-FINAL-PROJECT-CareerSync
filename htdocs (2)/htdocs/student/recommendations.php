<?php
session_start();

define('BASE_PATH', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');

require_once BASE_PATH . 'includes/session.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'models/Quiz.php';

$pdo = getDbConnection();
$quiz = new Quiz();
$userId = currentUserId();

// Check for specific result_id in URL
$resultId = isset($_GET['result_id']) ? (int)$_GET['result_id'] : null;

if ($resultId) {
    // Verify this result belongs to current user
    $stmt = $pdo->prepare("
        SELECT ur.*, cp.title, cp.description, cp.required_skills, cp.education_path, cp.category, cp.min_percent, cp.max_percent
        FROM user_results ur
        JOIN career_paths cp ON ur.career_path_id = cp.id
        WHERE ur.id = ? AND ur.user_id = ?
    ");
    $stmt->execute([$resultId, $userId]);
    $result = $stmt->fetch();

    if (!$result) {
        // Result not found or doesn't belong to user
        $_SESSION['error'] = 'Result not found or access denied.';
        header('Location: history.php');
        exit;
    }
} else {
    // Get latest result
    $stmt = $pdo->prepare("
        SELECT ur.*, cp.title, cp.description, cp.required_skills, cp.education_path, cp.category, cp.min_percent, cp.max_percent
        FROM user_results ur
        JOIN career_paths cp ON ur.career_path_id = cp.id
        WHERE ur.user_id = ?
        ORDER BY ur.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
}

$careers = [];
if ($result) {
    $detected_trait = $result['title'];
    $score = (int)$result['score'];
    $percentage = (float)$result['percentage'];

    // Validate percentage bounds
    $percentage = max(0, min(100, $percentage));

    $careers = $quiz->findTopCareersByPercentage($percentage, 3);
} else {
    $detected_trait = "Not assessed yet";
    $score = 0;
    $percentage = 0;
}

$pageTitle = 'Your Recommendations';
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

    /* Sidebar Navigation Configuration */
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

    /* Main Workspace Panels Layout */
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

    .alert-banner {
        background: #f7e9ec; 
        border-left: 5px solid #800020; 
        padding: 15px; 
        margin-bottom: 25px; 
        border-radius: 12px;
        font-size: 14px;
        color: #38020c;
    }

    /* Score Matrix & Progress Bars */
    .score-summary-box {
        background: #faf0f2; 
        border-radius: 24px; 
        padding: 26px; 
        border: 1px solid #ebd0d6;
        text-align: center;
        margin: 25px 0;
    }

    .progress-track-bar {
        width: 100%; 
        height: 16px; 
        background: #edd1d8; 
        border-radius: 10px; 
        margin-top: 20px; 
        overflow: hidden;
    }

    .progress-fill-bar {
        height: 100%; 
        background: #800020; 
        border-radius: 10px; 
        transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Recommended Tracks List */
    .career-track-card {
        background-color: #ffffff; 
        border: 1px solid #f2e6e9; 
        border-radius: 20px; 
        padding: 24px; 
        margin-bottom: 20px;
        box-shadow: 0 4px 16px rgba(128, 0, 32, 0.02);
    }

    .career-track-card.best-fit {
        border-left: 6px solid #800020;
        background: #fcf9fa;
    }

    .badge-tag {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 700;
        background-color: rgba(128, 0, 32, 0.1); 
        color: #73001c;
    }

    .career-title {
        color: #38020c; 
        margin-top: 10px;
        margin-bottom: 12px;
        font-size: 18px;
        font-weight: 700;
    }

    .meta-row {
        font-size: 14px;
        color: #6b5358;
        line-height: 1.6;
        margin: 8px 0;
    }

    .meta-row strong {
        color: #8a7177;
        font-weight: 600;
    }

    /* System Action Buttons layout */
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
        padding: 12px 24px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid rgba(128, 0, 32, 0.2);
        cursor: pointer;
    }

    .btn-primary { background-color: #800020; color: #ffffff; border-color: #6b001a; }
    .btn-primary:hover { background-color: #990026; }
    .btn-secondary { background-color: rgba(255, 255, 255, 0.8); color: #800020; }
    .btn-secondary:hover { background-color: #800020; color: #ffffff; }
    .btn-muted { background-color: #edd1d8; color: #610018; border-color: #e3beae; }
    .btn-muted:hover { background-color: #610018; color: #ffffff; }

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
            <a href="#" class="menu-item active">💼 My Recommendations</a>
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
        <main class="center-content">
            <div class="workspace-inner-board">
                
                <?php if (!empty($_SESSION['error'])): ?>
                    <div style="background: #b3002d; color: white; padding: 14px 20px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; font-weight: 600;">
                        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="alert-banner">
                    <strong>🎯 Career Analysis Complete</strong><br>
                    Based on your comprehensive response profiles, we have mapped out your core targeted fit metrics.
                </div>

                <h2 class="section-title">Congratulations, <?= htmlspecialchars(currentUserName() ?? 'Student') ?>!</h2>
                <p style="color: #94757c; font-size: 14px; margin-top: 0; margin-bottom: 20px; font-weight: 600;">Here is your strategic curriculum fit dashboard matrix.</p>

                <?php if ($result): ?>

                <div class="score-summary-box">
                    <div style="font-size: 12px; color: #94757c; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Your Career Match Score</div>
                    <div style="font-size: 54px; font-weight: 800; color: #800020; margin: 4px 0;"><?= number_format($percentage, 1) ?>%</div>
                    <div style="color: #6b5358; font-size: 13px; font-weight: 600;"><?= $score ?> points aggregated total</div>

                    <div class="progress-track-bar">
                        <div class="progress-fill-bar" style="width: <?= $percentage ?>%;"></div>
                    </div>
                </div>

                <div style="margin-bottom: 30px; font-size: 15px; font-weight: 600;">
                    Primary Fit Framework: <span class="badge-tag" style="font-size: 13px; padding: 6px 14px; margin-left: 6px;"><?= htmlspecialchars($detected_trait) ?></span>
                </div>

                <h3 style="font-size: 16px; font-weight: 700; color: #3d141d; margin-bottom: 20px;">Recommended Career Tracks</h3>

                <?php if (!empty($careers)): ?>
                    <?php foreach ($careers as $index => $career): 
                        $isBestMatch = ($index === 0);
                        $careerPercentRange = ($career['min_percent'] ?? 0) . '% - ' . ($career['max_percent'] ?? 100) . '%';
                    ?>
                        <div class="career-track-card <?= $isBestMatch ? 'best-fit' : '' ?>">
                            <?php if ($isBestMatch): ?>
                                <div style="color: #800020; font-size: 11px; font-weight: 800; letter-spacing: 0.8px; text-transform: uppercase;">⭐ Best Fit Alignment Matrix (<?= number_format($percentage, 1) ?>%)</div>
                            <?php endif; ?>
                            
                            <h4 class="career-title"><?= htmlspecialchars($career['title'] ?? 'Unknown Pathways Framework') ?></h4>
                            
                            <div class="meta-row"><strong>Description:</strong> <?= htmlspecialchars($career['description'] ?? 'No deep path definition history logs available inside the repository.') ?></div>
                            <div class="meta-row"><strong>Required Skills:</strong> <?= htmlspecialchars($career['required_skills'] ?? 'General technical baseline capacity.') ?></div>
                            <div class="meta-row"><strong>Education Path:</strong> <?= htmlspecialchars($career['education_path'] ?? 'Standard framework tracking system map.') ?></div>
                            
                            <div style="color: #94757c; font-size: 11px; font-weight: 700; margin-top: 15px; text-transform: uppercase;">
                                System Match Window Criteria: <?= $careerPercentRange ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #6b5358; font-weight: 600; padding: 20px 0;">No matching alternative system career tracks mapped for this target score window balance profile.</p>
                <?php endif; ?>

                <?php else: // No quiz taken results logic ?>

                <div style="text-align: center; padding: 50px 20px; background: #faf0f2; border: 1px dashed #ebd0d6; border-radius: 24px; margin: 30px 0;">
                    <div style="font-size: 54px; margin-bottom: 15px;">📋</div>
                    <h3 style="color: #38020c; font-weight: 700; margin-bottom: 8px;">No Evaluation Analytics Matched</h3>
                    <p style="color: #6b5358; max-width: 440px; margin: 0 auto 25px auto; font-size: 14px; line-height: 1.5;">You haven't completed a career profiling tracking evaluation under this student portal profile infrastructure yet.</p>
                    <a href="quiz.php" class="action-btn btn-primary" style="padding: 14px 35px; font-size: 14px;">Launch Assessment Module</a>
                </div>

                <?php endif; ?>

                <div class="button-group-row">
                    <a href="../index.php" class="action-btn btn-muted">← Return Home</a>
                    <?php if ($result): ?>
                        <a href="history.php" class="action-btn btn-secondary">Open History Logs</a>
                        <a href="quiz.php" class="action-btn btn-primary">Retake Evaluation Module</a>
                    <?php endif; ?>
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

<?php require_once BASE_PATH . 'templates/footer.php'; ?>