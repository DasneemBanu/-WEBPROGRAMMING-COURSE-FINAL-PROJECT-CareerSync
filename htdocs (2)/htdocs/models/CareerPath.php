<?php

require_once __DIR__ . '/../config/db.php';

class CareerPath
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = getDbConnection();
    }

    public function create($title, $description, $skills, $education)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO career_paths
            (title, description, required_skills, education_path)
            VALUES (?, ?, ?, ?)"
        );

        return $stmt->execute([
            $title,
            $description,
            $skills,
            $education
        ]);
    }

    public function readAll()
    {
        $stmt = $this->conn->query(
            "SELECT * FROM career_paths ORDER BY title"
        );

        return $stmt->fetchAll();
    }

    public function findById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM career_paths WHERE id=?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public function update($id, $title, $description, $skills, $education)
    {
        $stmt = $this->conn->prepare(
            "UPDATE career_paths
             SET title=?,
                 description=?,
                 required_skills=?,
                 education_path=?
             WHERE id=?"
        );

        return $stmt->execute([
            $title,
            $description,
            $skills,
            $education,
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM career_paths
             WHERE id=?"
        );

        return $stmt->execute([$id]);
    }
}