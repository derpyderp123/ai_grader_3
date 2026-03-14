<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * User Model
 * 
 * Handles user-related database operations
 */

class UserModel {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE username = :username",
            ['username' => $username]
        );
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email = :email",
            ['email' => $email]
        );
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Create new user
     */
    public function create(array $data): int {
        return $this->db->insert('users', [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'role' => $data['role'] ?? 'student',
            'full_name' => $data['full_name'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Verify user password
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /**
     * Hash password
     */
    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Get all students
     */
    public function getAllStudents(): array {
        return $this->db->fetchAll(
            "SELECT id, username, email, full_name, created_at 
             FROM users 
             WHERE role = 'student' 
             ORDER BY username ASC"
        );
    }

    /**
     * Get all teachers
     */
    public function getAllTeachers(): array {
        return $this->db->fetchAll(
            "SELECT id, username, email, full_name 
             FROM users 
             WHERE role = 'teacher' 
             ORDER BY username ASC"
        );
    }

    /**
     * Update user
     */
    public function update(int $id, array $data): bool {
        return $this->db->update('users', $data, 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Delete user
     */
    public function delete(int $id): bool {
        return $this->db->delete('users', 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Count users by role
     */
    public function countByRole(string $role): int {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM users WHERE role = :role",
            ['role' => $role]
        );
        return (int) ($result['count'] ?? 0);
    }
}
