<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * Assignment Model
 * 
 * Handles assignment-related database operations
 */

class AssignmentModel {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Find assignment by ID
     */
    public function findById(int $id): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM assignments WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Get all assignments for a teacher
     */
    public function getByTeacher(int $teacherId): array {
        return $this->db->fetchAll(
            "SELECT * FROM assignments 
             WHERE teacher_id = :teacher_id 
             ORDER BY created_at DESC",
            ['teacher_id' => $teacherId]
        );
    }

    /**
     * Get all active assignments
     */
    public function getActiveAssignments(): array {
        return $this->db->fetchAll(
            "SELECT a.*, u.username as teacher_name 
             FROM assignments a
             JOIN users u ON a.teacher_id = u.id
             WHERE a.is_active = TRUE AND a.deadline > NOW()
             ORDER BY a.deadline ASC"
        );
    }

    /**
     * Create new assignment
     */
    public function create(array $data): int {
        return $this->db->insert('assignments', [
            'teacher_id' => $data['teacher_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'rubric_json' => json_encode($data['rubric']),
            'deadline' => $data['deadline'],
            'max_score' => $data['max_score'] ?? 100,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update assignment
     */
    public function update(int $id, array $data): bool {
        // Handle rubric JSON encoding
        if (isset($data['rubric']) && is_array($data['rubric'])) {
            $data['rubric_json'] = json_encode($data['rubric']);
            unset($data['rubric']);
        }
        
        return $this->db->update('assignments', $data, 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Delete assignment
     */
    public function delete(int $id): bool {
        return $this->db->delete('assignments', 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Deactivate assignment
     */
    public function deactivate(int $id): bool {
        return $this->db->update(
            'assignments', 
            ['is_active' => false], 
            'id = :id', 
            ['id' => $id]
        ) > 0;
    }

    /**
     * Count assignments by teacher
     */
    public function countByTeacher(int $teacherId): int {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM assignments WHERE teacher_id = :teacher_id",
            ['teacher_id' => $teacherId]
        );
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get assignment with rubric decoded
     */
    public function findByIdWithRubric(int $id): ?array {
        $assignment = $this->findById($id);
        
        if ($assignment && isset($assignment['rubric_json'])) {
            $assignment['rubric'] = json_decode($assignment['rubric_json'], true);
        }
        
        return $assignment;
    }

    /**
     * Get assignments with submission counts
     */
    public function getWithSubmissionCounts(int $teacherId): array {
        return $this->db->fetchAll(
            "SELECT 
                a.*,
                COUNT(s.id) as submission_count,
                SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as graded_count
             FROM assignments a
             LEFT JOIN submissions s ON a.id = s.assignment_id
             WHERE a.teacher_id = :teacher_id
             GROUP BY a.id
             ORDER BY a.created_at DESC",
            ['teacher_id' => $teacherId]
        );
    }
}
