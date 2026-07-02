<?php

require_once __DIR__ . '/../config/db.php';

class Result
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = getDbConnection();
    }

    public function saveResult(
        $user_id,
        $career_path_id,
        $score,
        $snapshot = [],
        $notes = null
    )
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO user_results
            (
                user_id,
                career_path_id,
                score,
                quiz_snapshot,
                recommendation_notes
            )
            VALUES (?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $user_id,
            $career_path_id,
            $score,
            json_encode($snapshot),
            $notes
        ]);
    }

    public function fetchUserHistory($user_id)
    {
        $stmt = $this->conn->prepare(
            "SELECT
                ur.*,
                cp.title AS career_title
            FROM user_results ur
            LEFT JOIN career_paths cp
            ON ur.career_path_id = cp.id
            WHERE ur.user_id = ?
            ORDER BY ur.created_at DESC"
        );

        $stmt->execute([$user_id]);

        return $stmt->fetchAll();
    }

    public function deleteResult($id, $user_id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM user_results
             WHERE id = ?
             AND user_id = ?"
        );

        return $stmt->execute([
            $id,
            $user_id
        ]);
    }
}