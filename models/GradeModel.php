<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * Grade Model
 * 
 * Handles grade-related database operations
 */

class GradeModel {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Find grade by ID
     */
    public function findById(int $id): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM grades WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Find grade by submission ID
     */
    public function findBySubmissionId(int $submissionId): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM grades WHERE submission_id = :submission_id",
            ['submission_id' => $submissionId]
        );
    }

    /**
     * Get grades by assignment
     */
    public function getByAssignment(int $assignmentId): array {
        return $this->db->fetchAll(
            "SELECT g.*, s.file_name, u.username as student_name, u.full_name,
                    a.title as assignment_title
             FROM grades g
             JOIN submissions s ON g.submission_id = s.id
             JOIN users u ON s.student_id = u.id
             JOIN assignments a ON s.assignment_id = a.id
             WHERE s.assignment_id = :assignment_id
             ORDER BY g.score DESC",
            ['assignment_id' => $assignmentId]
        );
    }

    /**
     * Create new grade
     */
    public function create(array $data): int {
        return $this->db->insert('grades', [
            'submission_id' => $data['submission_id'],
            'score' => $data['score'],
            'feedback_json' => json_encode($data['feedback']),
            'teacher_notes' => $data['teacher_notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update grade
     */
    public function update(int $id, array $data): bool {
        // Handle feedback JSON encoding
        if (isset($data['feedback']) && is_array($data['feedback'])) {
            $data['feedback_json'] = json_encode($data['feedback']);
            unset($data['feedback']);
        }
        
        return $this->db->update('grades', $data, 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Add/update teacher notes
     */
    public function addTeacherNotes(int $id, string $notes, int $reviewedBy): bool {
        return $this->db->update('grades', [
            'teacher_notes' => $notes,
            'is_reviewed' => true,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Delete grade
     */
    public function delete(int $id): bool {
        return $this->db->delete('grades', 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Get grade with full details
     */
    public function findByIdWithDetails(int $id): ?array {
        $grade = $this->db->fetchOne(
            "SELECT g.*, s.file_path, s.file_name, s.ai_model_used,
                    u.username as student_name, u.full_name as student_full_name,
                    a.title as assignment_title, a.max_score,
                    ru.username as reviewed_by_name
             FROM grades g
             JOIN submissions s ON g.submission_id = s.id
             JOIN users u ON s.student_id = u.id
             JOIN assignments a ON s.assignment_id = a.id
             LEFT JOIN users ru ON g.reviewed_by = ru.id
             WHERE g.id = :id",
            ['id' => $id]
        );
        
        if ($grade && isset($grade['feedback_json'])) {
            $grade['feedback'] = json_decode($grade['feedback_json'], true);
        }
        
        return $grade;
    }

    /**
     * Get statistics for an assignment
     */
    public function getAssignmentStats(int $assignmentId): ?array {
        return $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total_graded,
                AVG(g.score) as average_score,
                MIN(g.score) as min_score,
                MAX(g.score) as max_score,
                SUM(CASE WHEN g.score >= 70 THEN 1 ELSE 0 END) as passing_count
             FROM grades g
             JOIN submissions s ON g.submission_id = s.id
             WHERE s.assignment_id = :assignment_id",
            ['assignment_id' => $assignmentId]
        );
    }

    /**
     * Get grade distribution
     */
    public function getDistribution(int $assignmentId): array {
        return $this->db->fetchAll(
            "SELECT 
                CASE 
                    WHEN score >= 90 THEN 'A (90-100)'
                    WHEN score >= 80 THEN 'B (80-89)'
                    WHEN score >= 70 THEN 'C (70-79)'
                    WHEN score >= 60 THEN 'D (60-69)'
                    ELSE 'F (0-59)'
                END as grade_range,
                COUNT(*) as count
             FROM grades g
             JOIN submissions s ON g.submission_id = s.id
             WHERE s.assignment_id = :assignment_id
             GROUP BY grade_range
             ORDER BY MIN(score) DESC",
            ['assignment_id' => $assignmentId]
        );
    }

    /**
     * Check if submission has been graded
     */
    public function existsForSubmission(int $submissionId): bool {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM grades WHERE submission_id = :submission_id",
            ['submission_id' => $submissionId]
        );
        return (int) ($result['count'] ?? 0) > 0;
    }
}
