<?php
session_start();

$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');

require_once $root . '/includes/admin_check.php';
require_once $root . '/config/db.php';

// Verify admin role (defense in depth)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pdo = getDbConnection();
$id = intval($_GET['id'] ?? 0);
$errors = [];

$question = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$question->execute([$id]);
$question = $question->fetch();

if (!$question) {
    header('Location: manage_question.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = trim($_POST['question_text'] ?? '');
    $question_type = $_POST['question_type'] ?? 'single';
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $weight_a = intval($_POST['weight_a'] ?? 1);
    $weight_b = intval($_POST['weight_b'] ?? 2);
    $weight_c = intval($_POST['weight_c'] ?? 3);
    $weight_d = intval($_POST['weight_d'] ?? 4);
    $category = trim($_POST['category'] ?? '');
    $question_order = intval($_POST['question_order'] ?? 0);

    if (empty($question_text)) {
        $errors[] = 'Question text is required.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE quizzes SET 
                    question_text = ?, question_type = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?,
                    weight_a = ?, weight_b = ?, weight_c = ?, weight_d = ?, category = ?, question_order = ?
                WHERE id = ?
            ");
            $stmt->execute([$question_text, $question_type, $option_a, $option_b, $option_c, $option_d,
                $weight_a, $weight_b, $weight_c, $weight_d, $category, $question_order, $id]);
            
            $_SESSION['flash_success'] = 'Question updated successfully!';
            header('Location: manage_question.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database operation failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Question';
require_once $root . '/templates/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=500;600;700;800&display=swap" rel="stylesheet">

<style>
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

    header, nav, .navbar, .site-header, footer, .site-footer {
        display: none !important;
    }

    /* Minimalist Centralized Form Container */
    .admin-container { 
        width: 100%;
        max-width: 580px; 
        margin: 40px auto; 
        padding: 45px 40px; 
        background-color: #ffffff !important;
        border-radius: 24px;
        border: 1px solid #ebdbe0 !important;
        box-shadow: 0 8px 24px rgba(128, 0, 32, 0.02);
        box-sizing: border-box;
    }

    .center-logo-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        margin-bottom: 25px;
    }

    .center-logo-img {
        max-width: 300px;
        height: auto;
        display: block;
    }

    h2 {
        font-size: 22px;
        font-weight: 800;
        color: #3d141d !important;
        margin-top: 0;
        margin-bottom: 30px;
        letter-spacing: -0.4px;
        text-align: center;
    }

    .form-group { 
        margin-bottom: 20px; 
    }

    .form-group label { 
        display: block; 
        margin-bottom: 8px; 
        font-weight: 700; 
        font-size: 13px;
        color: #6b5358; 
    }

    .form-group input, 
    .form-group textarea, 
    .form-group select { 
        width: 100%; 
        padding: 13px 16px; 
        border: 1px solid #e1d5d8; 
        border-radius: 12px; 
        background-color: #fdfbfb; 
        color: #3d141d; 
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 14px;
        font-weight: 600;
        box-sizing: border-box;
        transition: all 0.2s ease;
    }

    .form-group input:focus, 
    .form-group textarea:focus, 
    .form-group select:focus {
        outline: none;
        border-color: #800020;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.05);
    }

    .options-grid { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 16px; 
        margin-bottom: 10px;
    }

    .scale-info { 
        background: #fdf0f3; 
        padding: 16px; 
        border-radius: 14px; 
        border: 1px solid #f9d5dd; 
        margin-bottom: 20px; 
        color: #800020; 
        font-size: 13px;
        font-weight: 600;
        line-height: 1.5;
    }

    .hidden { 
        display: none !important; 
    }

    /* Form Action Control Buttons Alignment */
    .form-actions-row {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-top: 30px;
    }

    .btn { 
        flex: 1;
        padding: 14px 20px; 
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 14px;
        font-weight: 700;
        border: none; 
        border-radius: 12px; 
        cursor: pointer; 
        display: inline-flex; 
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease; 
        text-decoration: none;
        box-sizing: border-box;
    }

    /* Unified Identical Pastel Mint Styling for Both Buttons */
    .btn-pastel {
        background-color: #e6f4f0 !important;
        color: #1e5e4e !important;
        border: 1px solid #cce8e0 !important;
    }

    .btn-pastel:hover {
        background-color: #d4ece5 !important;
    }

    .error-banner {
        color: #b91c1c; 
        background-color: #fef2f2;
        border: 1px solid #fee2e2;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 20px;
    }
</style>

<div class="admin-container">
    <div class="center-logo-container">
        <img src="/assets/images/logo.png" alt="CareerSync Logo" class="center-logo-img">
    </div>

    <h2>📝 Edit Question Component</h2>

    <?php if (!empty($errors)): ?>
        <div class="error-banner">
            <?php foreach ($errors as $e): ?>
                <div>⚠️ <?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="question-form">
        <div class="form-group">
            <label>Question Prompt Text</label>
            <textarea name="question_text" rows="3" required placeholder="Enter the evaluation question query statement..."><?= htmlspecialchars($question['question_text'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form-group">
            <label>Question Evaluation Type</label>
            <select name="question_type" id="question-type" onchange="toggleOptions()">
                <option value="single" <?= $question['question_type'] === 'single' ? 'selected' : '' ?>>Single Choice</option>
                <option value="multi" <?= $question['question_type'] === 'multi' ? 'selected' : '' ?>>Multiple Choice</option>
                <option value="scale" <?= $question['question_type'] === 'scale' ? 'selected' : '' ?>>Scale Matrix (1-5)</option>
            </select>
        </div>

        <div id="scale-info" class="scale-info <?= $question['question_type'] === 'scale' ? '' : 'hidden' ?>">
            <strong>ℹ️ Scale Evaluation Parameters:</strong> Students will evaluate ranges from 1 to 5. The absolute numeric scale position calculation becomes the score component weight. No custom options array is necessary.
        </div>

        <div id="options-section" class="<?= $question['question_type'] === 'scale' ? 'hidden' : '' ?>">
            <div class="options-grid">
                <div class="form-group">
                    <label>Option A Label</label>
                    <input type="text" name="option_a" id="option_a" placeholder="Label A" value="<?= htmlspecialchars($question['option_a'], ENT_QUOTES, 'UTF-8') ?>">
                    <label style="margin-top: 10px;">Weight Factor A</label>
                    <input type="number" name="weight_a" id="weight_a" value="<?= intval($question['weight_a']) ?>">
                </div>
                <div class="form-group">
                    <label>Option B Label</label>
                    <input type="text" name="option_b" id="option_b" placeholder="Label B" value="<?= htmlspecialchars($question['option_b'], ENT_QUOTES, 'UTF-8') ?>">
                    <label style="margin-top: 10px;">Weight Factor B</label>
                    <input type="number" name="weight_b" id="weight_b" value="<?= intval($question['weight_b']) ?>">
                </div>
            </div>
            <div class="options-grid">
                <div class="form-group">
                    <label>Option C Label</label>
                    <input type="text" name="option_c" id="option_c" placeholder="Label C" value="<?= htmlspecialchars($question['option_c'], ENT_QUOTES, 'UTF-8') ?>">
                    <label style="margin-top: 10px;">Weight Factor C</label>
                    <input type="number" name="weight_c" id="weight_c" value="<?= intval($question['weight_c']) ?>">
                </div>
                <div class="form-group">
                    <label>Option D Label</label>
                    <input type="text" name="option_d" id="option_d" placeholder="Label D" value="<?= htmlspecialchars($question['option_d'], ENT_QUOTES, 'UTF-8') ?>">
                    <label style="margin-top: 10px;">Weight Factor D</label>
                    <input type="number" name="weight_d" id="weight_d" value="<?= intval($question['weight_d']) ?>">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Metric Category Segment</label>
            <input type="text" name="category" placeholder="e.g., Interest, Technical, Role" value="<?= htmlspecialchars($question['category'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label>Sequence Matrix Order</label>
            <input type="number" name="question_order" value="<?= intval($question['question_order']) ?>">
        </div>

        <div class="form-actions-row">
            <button type="submit" class="btn btn-pastel">Update Question</button>
            <a href="manage_question.php" class="btn btn-pastel">Cancel Changes</a>
        </div>
    </form>
</div>

<script>
function toggleOptions() {
    var type = document.getElementById('question-type').value;
    var optionsSection = document.getElementById('options-section');
    var scaleInfo = document.getElementById('scale-info');
    
    if (type === 'scale') {
        optionsSection.classList.add('hidden');
        scaleInfo.classList.remove('hidden');
        
        // Clean out option values when switching types manually
        document.getElementById('option_a').value = '';
        document.getElementById('option_b').value = '';
        document.getElementById('option_c').value = '';
        document.getElementById('option_d').value = '';
    } else {
        optionsSection.classList.remove('hidden');
        scaleInfo.classList.add('hidden');
    }
}

// Map dynamic data visibility matrices cleanly onto synchronous initial page render loops
document.addEventListener('DOMContentLoaded', function() {
    var type = document.getElementById('question-type').value;
    if (type === 'scale') {
        document.getElementById('options-section').classList.add('hidden');
        document.getElementById('scale-info').classList.remove('hidden');
    }
});
</script>

<?php require_once $root . '/templates/footer.php'; ?>

```