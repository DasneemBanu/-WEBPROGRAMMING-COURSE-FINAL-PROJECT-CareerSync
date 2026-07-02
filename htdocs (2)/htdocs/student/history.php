<?php
session_start();

define('BASE_PATH', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');

require_once BASE_PATH . 'includes/session.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

require_once BASE_PATH . 'config/db.php';

$pdo = getDbConnection();
$userId = currentUserId();

// Generate CSRF token for delete actions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user's quiz history
$stmt = $pdo->prepare("
    SELECT ur.id, ur.score, ur.percentage, ur.created_at, 
           cp.title as career_title, cp.description
    FROM user_results ur
    JOIN career_paths cp ON ur.career_path_id = cp.id
    WHERE ur.user_id = ?
    ORDER BY ur.created_at DESC
");
$stmt->execute([$userId]);
$results = $stmt->fetchAll();

$pageTitle = 'Quiz History';
require_once BASE_PATH . 'templates/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=500;600;700;800&display=swap" rel="stylesheet">

<meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

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

    /* Pastel Styled Dashboard Table Architecture with Burgundy Accent Borders */
    .history-matrix-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 25px;
        border: 3px solid #800020; 
        border-radius: 16px;
        overflow: hidden;
    }

    .history-matrix-table th {
        background-color: #f5e6e8;
        color: #800020;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 16px;
        border-bottom: 2px solid #800020; 
        border-right: 1px solid rgba(128, 0, 32, 0.15);
    }

    .history-matrix-table th:last-child {
        border-right: none;
    }

    /* Balanced Soft Pastel Alternating Body Rows */
    .history-matrix-table tr.data-row td {
        background-color: #fdf8f9; 
        padding: 18px 16px;
        font-size: 14px;
        color: #5c464c;
        font-weight: 600;
        border-bottom: 1px solid #faecee;
        border-right: 1px solid #faecee;
        vertical-align: middle;
        transition: background-color 0.2s ease;
    }

    .history-matrix-table tr.data-row:nth-child(even) td {
        background-color: #ffffff;
    }

    .history-matrix-table tr.data-row td:last-child {
        border-right: none;
    }

    .history-matrix-table tr.data-row:last-child td {
        border-bottom: none;
    }

    .history-matrix-table tr.data-row:hover td {
        background-color: #f7e6e9; 
    }

    /* System Row Tags & Action Elements */
    .badge-score-rate {
        background-color: #800020;
        color: #ffffff;
        padding: 5px 12px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 13px;
        display: inline-block;
    }

    .button-group-row {
        margin-top: 35px; 
        display: flex; 
        gap: 12px; 
        flex-wrap: wrap;
    }

    /* Vertical Stack Actions Container Block */
    .actions-stacked-cell {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 100%;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 100%;
        max-width: 110px;
        padding: 8px 14px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid rgba(128, 0, 32, 0.15);
        cursor: pointer;
        box-sizing: border-box;
    }

    .btn-view { background-color: #ffffff; color: #800020; border-color: #ebd0d6; }
    .btn-view:hover { background-color: #800020; color: #ffffff; }
    .btn-delete { background-color: #dc3545; color: #ffffff; border: none; display: block; width: 100%; }
    .btn-delete:hover { background-color: #bd2130; color: #ffffff; }
    
    .btn-primary-action { background-color: #800020; color: #ffffff; border-color: #6b001a; padding: 12px 28px; width: auto; max-width: none; }
    .btn-primary-action:hover { background-color: #990026; }
    .btn-muted-back { background-color: #edd1d8; color: #610018; border-color: #e3beae; padding: 12px 28px; width: auto; max-width: none; }
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
            <a href="#" class="menu-item active">📋 Evaluation History</a>
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
        <main class="center-content">
            <div class="workspace-inner-board">
                
                <h2 class="section-title">Evaluation History</h2>
                <p style="color: #94757c; font-size: 14px; margin-top: 0; margin-bottom: 25px; font-weight: 600;">Review your historical academic scores and match performance logs.</p>

                <?php if (!empty($_SESSION['flash_success'])): ?>
                    <div style="background: #28a745; color: white; padding: 14px 20px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; font-weight: 600;">
                        <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['error'])): ?>
                    <div style="background: #b3002d; color: white; padding: 14px 20px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; font-weight: 600;">
                        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($results)): ?>
                    <div style="text-align: center; padding: 40px 20px; background: #fdf8f9; border: 1px dashed #faecee; border-radius: 24px; margin: 20px 0;">
                        <p style="color: #6b5358; font-weight: 600; margin-bottom: 15px;">No historical evaluation analytics records found on this account profile streams.</p>
                        <a href="quiz.php" class="action-btn btn-primary-action">Take your first quiz</a>
                    </div>
                <?php else: ?>
                    
                    <div style="overflow-x: auto; padding: 4px;">
                        <table class="history-matrix-table">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Date Point</th>
                                    <th style="width: 35%;">Career Match Title</th>
                                    <th style="width: 15%;">Raw Score</th>
                                    <th style="width: 15%;">Percentage</th>
                                    <th style="text-align: center; width: 10%;">Actions Matrix</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                    <tr class="data-row">
                                        <td style="color: #8a7177;"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($result['created_at']))) ?></td>
                                        <td style="color: #800020; font-weight: 700;">
                                            <?= htmlspecialchars($result['career_title']) ?>
                                        </td>
                                        <td style="color: #4e3e43; font-weight: 700;"><?= (int)$result['score'] ?> pts</td>
                                        <td><span class="badge-score-rate"><?= number_format((float)$result['percentage'], 1) ?>%</span></td>
                                        <td>
                                            <div class="actions-stacked-cell">
                                                <a href="recommendations.php?result_id=<?= (int)$result['id'] ?>" class="action-btn btn-view">View Matrix</a>
                                                
                                                <a href="delete_result.php" 
                                                   class="action-btn btn-delete" 
                                                   style="padding: 6px 12px; font-size: 13px; max-width: none; text-align: center;"
                                                   data-id="<?= (int)$result['id'] ?>"
                                                   data-csrf="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                   data-date="<?= htmlspecialchars(date('M j, Y', strtotime($result['created_at']))) ?>"
                                                   data-career="<?= htmlspecialchars($result['career_title']) ?>">
                                                    Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>

                <div class="button-group-row">
                    <a href="<?= htmlspecialchars($baseUrl ?? '') ?>/student/dashboard.php" class="action-btn btn-muted-back">← Back to Home</a>
                    <a href="quiz.php" class="action-btn btn-primary-action">Take New Quiz</a>
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

<script src="<?= htmlspecialchars($baseUrl ?? '') ?>/assets/js/history.js"></script>

<?php require_once BASE_PATH . 'templates/footer.php'; ?>