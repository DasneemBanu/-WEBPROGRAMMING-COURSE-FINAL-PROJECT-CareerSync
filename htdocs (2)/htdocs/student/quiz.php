<?php
session_start();

// Define base path for InfinityFree compatibility
define('BASE_PATH', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');

require_once BASE_PATH . 'includes/session.php';
require_once BASE_PATH . 'models/Quiz.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$quiz = new Quiz();
$questions = $quiz->getAllQuestions();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Take Quiz';
require_once BASE_PATH . 'templates/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* Global Page Structure Override to match dashboard */
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

    /* Sidebar Styling matching Dashboard */
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

    /* Sidebar Toggle Button */
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

    /* Layout Positioning */
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
        margin: 0 0 8px 0;
    }

    /* Modernized Assessment Question Containers */
    .question-card {
        margin-bottom: 25px;
        padding: 26px;
        background-color: #faf0f2; 
        border: 1px solid #ebd0d6;
        border-radius: 24px;
        box-shadow: 0 6px 18px rgba(128, 0, 32, 0.02);
    }

    .question-legend {
        font-size: 16px;
        font-weight: 700;
        color: #38020c;
        margin-bottom: 16px;
        padding: 0;
    }

    /* Form Option Labels */
    .option-label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 10px 0;
        padding: 12px 16px;
        background-color: rgba(255, 255, 255, 0.7);
        border: 1px solid #f2e6e9;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        color: #6b5358;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .option-label:hover {
        background-color: #ffffff;
        border-color: #800020;
        color: #38020c;
    }

    .option-label input[type="radio"],
    .option-label input[type="checkbox"] {
        accent-color: #800020;
        width: 16px;
        height: 16px;
        margin: 0;
    }

    /* Call to action Submit Button styling matching the core theme */
    .submit-quiz-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 44px;
        border-radius: 14px;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
        background-color: #800020;
        color: #ffffff;
        border: 1px solid #6b001a;
        cursor: pointer;
        box-shadow: 0 6px 20px rgba(128, 0, 32, 0.15);
        transition: all 0.2s ease;
    }

    .submit-quiz-btn:hover {
        background-color: #990026;
        box-shadow: 0 8px 24px rgba(128, 0, 32, 0.25);
    }

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
            <a href="#" class="menu-item active">📝 Take Assessment</a>
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
        <main class="center-content">
            <div class="workspace-inner-board">
                
                <h2 class="section-title">Career Assessment Quiz</h2>
                <p style="color: #94757c; font-size: 14px; margin-top: 0; margin-bottom: 30px; font-weight: 600;">
                    Answer all questions to get your career recommendations.
                </p>

                <?php if (!empty($_SESSION['quiz_error'])): ?>
                    <div class="alert alert-error" style="background: #b3002d; color: white; padding: 14px 20px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; font-weight: 600;">
                        <?php echo htmlspecialchars($_SESSION['quiz_error']); unset($_SESSION['quiz_error']); ?>
                    </div>
                <?php endif; ?>

                <form id="quiz-form" method="post" action="quiz_submit.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <?php if (empty($questions)): ?>
                    <p style="text-align: center; color: #6b5358; padding: 40px 0; font-weight: 600;">
                        No quiz questions found. Please ask an admin to add questions.
                    </p>

                <?php else: ?>

                    <?php foreach ($questions as $q): 
                        $id = (int)$q['id'];
                        $text = htmlspecialchars($q['question_text'], ENT_QUOTES, 'UTF-8');
                        $type = htmlspecialchars($q['question_type'] ?? 'single', ENT_QUOTES, 'UTF-8');
                    ?>

                    <div data-required="true" class="question question-card" id="question-<?php echo $id ?>">
                        <div class="question-legend"><?php echo $text; ?></div>

                        <?php if ($type === 'single'): ?>
                            <?php foreach (['a','b','c','d'] as $opt): 
                                $optKey = 'option_' . $opt;
                                if (!empty($q[$optKey])):
                                    $label = htmlspecialchars($q[$optKey], ENT_QUOTES, 'UTF-8');
                                    $optVal = strtoupper($opt);
                            ?>
                                <label class="option-label">
                                    <input type="radio" name="q_<?php echo $id ?>" value="<?php echo $optVal ?>" required>
                                    <span><?php echo $label ?></span>
                                </label>
                            <?php endif; endforeach; ?>

                        <?php elseif ($type === 'multi'): ?>
                            <?php foreach (['a','b','c','d'] as $opt): 
                                $optKey = 'option_' . $opt;
                                if (!empty($q[$optKey])):
                                    $label = htmlspecialchars($q[$optKey], ENT_QUOTES, 'UTF-8');
                                    $optVal = strtoupper($opt);
                            ?>
                                <label class="option-label">
                                    <input type="checkbox" name="q_<?php echo $id ?>[]" value="<?php echo $optVal ?>">
                                    <span><?php echo $label ?></span>
                                </label>
                            <?php endif; endforeach; ?>

                        <?php else: // SCALE ?>
                            <div style="margin: 15px 0;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; color: #94757c; font-size: 13px; font-weight: 700;">
                                    <span>1 - Not at all</span>
                                    <span>5 - Extremely</span>
                                </div>
                                <input type="range" min="1" max="5" value="3" name="q_<?php echo $id ?>" 
                                       style="width: 100%; accent-color: #800020; cursor: pointer;"
                                       oninput="this.nextElementSibling.textContent = this.value"
                                       required>
                                <div style="text-align: center; margin-top: 12px; font-size: 22px; font-weight: 800; color: #800020;">3</div>
                            </div>
                        <?php endif; ?>

                    </div>

                    <?php endforeach; ?>

                    <div style="text-align: center; margin-top: 40px; padding-bottom: 20px;">
                        <button type="submit" class="submit-quiz-btn">Submit Quiz Results</button>
                    </div>

                <?php endif; ?>

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

<script src="<?= htmlspecialchars($baseUrl ?? '') ?>/assets/js/quiz.js"></script>

<?php require_once BASE_PATH . 'templates/footer.php'; ?>