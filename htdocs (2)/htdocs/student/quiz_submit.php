<?php
session_start();

define('BASE_PATH', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');

require_once BASE_PATH . 'includes/session.php';
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'models/Quiz.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// CSRF check
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['quiz_error'] = 'Security token invalid. Please refresh the page and try again.';
    header('Location: quiz.php');
    exit;
}

$quiz = new Quiz();

// Collect answers — DON'T cast string values (A, B, C, D) to int!
$answers = [];
foreach ($_POST as $k => $v) {
    if (strpos($k, 'q_') === 0) {
        $qid = (int) substr($k, 2);
        // Keep single choice values as strings (A, B, C, D)
        // Keep scale values as strings (1-5)
        // Only cast arrays (multi-select) to int
        $answers[$qid] = is_array($v) ? array_map('intval', $v) : $v;
    }
}

if (empty($answers)) {
    $_SESSION['quiz_error'] = 'No answers submitted. Please answer all questions.';
    header('Location: quiz.php');
    exit;
}

// Calculate scores
$score = $quiz->computeScore($answers);
$maxScore = $quiz->getMaxPossibleScore();
$percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;

// Find matching career by percentage
$matchedCareer = $quiz->findCareerByPercentage($percentage);

if (!$matchedCareer) {
    // Fallback: closest match or first available
    $db = getDbConnection();
    try {
        // Try to find a default career first
        $stmt = $db->prepare('SELECT id FROM career_paths WHERE is_default = 1 LIMIT 1');
        $stmt->execute();
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        // is_default column might not exist, fallback
        $row = null;
    }

    // If no default, get first career
    if (!$row) {
        $row = $db->query('SELECT id FROM career_paths ORDER BY id LIMIT 1')->fetch();
    }

    $career_id = $row ? (int) $row['id'] : null;
    $notes = 'No exact match found. Score: ' . $score . '/' . $maxScore . ' (' . $percentage . '%)';
} else {
    $career_id = (int) $matchedCareer['id'];
    $notes = 'Matched: ' . $matchedCareer['title'] . ' (Score: ' . $score . '/' . $maxScore . ', ' . $percentage . '%)';
}

// Always store snapshot in session for recovery
$_SESSION['last_quiz_result'] = [
    'score' => $score,
    'max_score' => $maxScore,
    'percentage' => $percentage,
    'snapshot' => $answers,
    'submitted_at' => date('Y-m-d H:i:s')
];

if (!$career_id) {
    header('Location: recommendations.php');
    exit;
}

// Save result with percentage
$result_id = $quiz->saveResult(
    currentUserId(), 
    $career_id, 
    $score, 
    $percentage, 
    $answers, 
    $notes
);

if (!$result_id) {
    $_SESSION['quiz_error'] = 'Failed to save results. Please try again.';
    header('Location: quiz.php');
    exit;
}

header('Location: recommendations.php?result_id=' . $result_id);
exit;