<?php
session_start();

$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';

require_once $root . 'includes/admin_check.php';
require_once $root . 'config/db.php';

$pdo = getDbConnection();
$id = intval($_GET['id'] ?? 0);
$errors = [];

$career = $pdo->prepare("SELECT * FROM career_paths WHERE id = ?");
$career->execute([$id]);
$career = $career->fetch();

if (!$career) {
    header('Location: manage_career.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $required_skills = trim($_POST['required_skills'] ?? '');
    $education_path = trim($_POST['education_path'] ?? '');

    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($description)) $errors[] = 'Description is required.';
    if (empty($required_skills)) $errors[] = 'Required skills are required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE career_paths SET title = ?, description = ?, required_skills = ?, education_path = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $required_skills, $education_path, $id]);
        
        $_SESSION['flash_success'] = 'Career path updated successfully!';
        header('Location: manage_career.php');
        exit;
    }
}

$pageTitle = 'Edit Career Path';
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

    .form-group input, .form-group textarea {
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
        resize: vertical;
    }

    .form-group input:focus, .form-group textarea:focus {
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

        <h3 class="section-title">✏️ Edit Career Framework</h3>

        <?php foreach ($errors as $e): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <div class="form-group">
                <label>Career Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($career['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-group">
                <label>Job Description</label>
                <textarea name="description" rows="4" required><?= htmlspecialchars($career['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="form-group">
                <label>Required Skills</label>
                <textarea name="required_skills" rows="3" required><?= htmlspecialchars($career['required_skills'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="form-group">
                <label>Education Path</label>
                <textarea name="education_path" rows="2"><?= htmlspecialchars($career['education_path'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            
            <div class="actions-row">
                <button type="submit" class="btn btn-submit">Update Career Path</button>
                <a href="manage_career.php" class="btn btn-cancel">Cancel Changes</a>
            </div>
        </form>
        
    </div>
</div>

<?php require_once $root . 'templates/footer.php'; ?>