<?php
/**
 * User model — all database logic for registration, login,
 * and profile management lives here (keeps public/*.php clean).
 */

require_once __DIR__ . '/../config/db.php'; // provides getDbConnection()

class User
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDbConnection();
    }

    public function findByEmail(string $email)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Create a new account. Returns ['success' => bool, 'message'?, 'id'?] */
    public function register(string $name, string $email, string $password, string $role = 'user'): array
    {
        if ($this->findByEmail($email)) {
            return ['success' => false, 'message' => 'This email is already registered.'];
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$name, $email, $hashed, $role]);

        return ['success' => true, 'id' => $this->pdo->lastInsertId()];
    }

    /** Returns the user row on success, or false on failure */
    public function verifyLogin(string $email, string $password)
    {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function updateProfile($id, string $name, string $email): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        return $stmt->execute([$name, $email, $id]);
    }

    public function updatePassword($id, string $newPassword): bool
    {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashed, $id]);
    }
}