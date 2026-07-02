<?php
// models/Quiz.php
require_once __DIR__ . '/../config/db.php';

class Quiz
{
    protected $db;

    public function __construct()
    {
        $this->db = getDbConnection();
    }

    public function getAllQuestions()
    {
        $stmt = $this->db->query('SELECT * FROM quizzes ORDER BY question_order ASC, id ASC');
        return $stmt->fetchAll();
    }

    public function getQuestionById($id)
    {
        $stmt = $this->db->prepare('SELECT * FROM quizzes WHERE id = ?');
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    }

    // Calculate maximum possible score for ALL questions
    public function getMaxPossibleScore()
    {
        $questions = $this->getAllQuestions();
        $total = 0;

        foreach ($questions as $q) {
            $type = $q['question_type'];

            if ($type === 'scale') {
                // Scale: max is 5
                $total += 5;
            } elseif ($type === 'multi') {
                // Multi: sum of ALL positive weights (student could pick all)
                $total += max((int)($q['weight_a'] ?? 0), 0) 
                        + max((int)($q['weight_b'] ?? 0), 0) 
                        + max((int)($q['weight_c'] ?? 0), 0) 
                        + max((int)($q['weight_d'] ?? 0), 0);
            } else {
                // Single: highest weight among options
                $total += max(
                    (int)($q['weight_a'] ?? 0),
                    (int)($q['weight_b'] ?? 0),
                    (int)($q['weight_c'] ?? 0),
                    (int)($q['weight_d'] ?? 0)
                );
            }
        }

        return $total;
    }

    // Calculate student's actual score
    public function computeScore(array $answers)
    {
        $total = 0;

        foreach ($answers as $qid => $val) {
            $q = $this->getQuestionById($qid);
            if (!$q) continue;

            $type = $q['question_type'];

            if ($type === 'multi' && is_array($val)) {
                foreach ($val as $opt) {
                    $wk = 'weight_' . strtolower($opt);
                    if (isset($q[$wk])) $total += (int)$q[$wk];
                }
            } elseif ($type === 'scale') {
                // Scale: value is direct (1-5)
                $total += (int)$val;
            } else {
                // Single: look up weight by option letter (A, B, C, D)
                $wk = 'weight_' . strtolower($val);
                if (isset($q[$wk])) $total += (int)$q[$wk];
            }
        }

        return $total;
    }

    // Calculate percentage score
    public function computePercentage(array $answers)
    {
        $score = $this->computeScore($answers);
        $maxScore = $this->getMaxPossibleScore();

        if ($maxScore === 0) return 0;

        return round(($score / $maxScore) * 100, 2);
    }

    // Find career by percentage range
    public function findCareerByPercentage($percentage)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM career_paths 
            WHERE min_percent <= ? AND max_percent >= ?
            ORDER BY id LIMIT 1
        ");
        $stmt->execute([$percentage, $percentage]);
        return $stmt->fetch();
    }

    // Find top careers by percentage
    public function findTopCareersByPercentage($percentage, $limit = 3)
    {
        $exact = $this->findCareerByPercentage($percentage);

        $careers = [];
        if ($exact) {
            $careers[] = $exact;
            $similar = $this->findSimilarCareers($exact['id'], $exact['category'] ?? '', $limit - 1);
            $careers = array_merge($careers, $similar);
        }

        if (count($careers) < $limit) {
            $needed = $limit - count($careers);
            $stmt = $this->db->prepare("
                SELECT * FROM career_paths 
                ORDER BY ABS(((min_percent + max_percent) / 2) - ?) ASC 
                LIMIT ?
            ");
            $stmt->execute([$percentage, $needed]);
            $extras = $stmt->fetchAll();

            $existingIds = array_column($careers, 'id');
            foreach ($extras as $extra) {
                if (!in_array($extra['id'], $existingIds)) {
                    $careers[] = $extra;
                }
            }
        }

        return $careers;
    }

    public function findSimilarCareers($career_id, $category, $limit = 2)
    {
        if (empty($category)) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT * FROM career_paths 
            WHERE category = ? AND id != ?
            ORDER BY RAND() LIMIT ?
        ");
        $stmt->execute([$category, $career_id, $limit]);
        return $stmt->fetchAll();
    }

    public function saveResult($user_id, $career_path_id, $score, $percentage, array $snapshot = [], $notes = null)
    {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO user_results 
                (user_id, career_path_id, score, percentage, quiz_snapshot, recommendation_notes, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ');
            $json = json_encode($snapshot);
            $stmt->execute([$user_id, $career_path_id, $score, $percentage, $json, $notes]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('Quiz saveResult error: ' . $e->getMessage());
            return false;
        }
    }
}