<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * Submission Model
 * 
 * Handles submission-related database operations
 */

class SubmissionModel {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Find submission by ID
     */
    public function findById(int $id): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM submissions WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Get submission with grade and assignment info
     */
    public function findByIdWithDetails(int $id): ?array {
        return $this->db->fetchOne(
            "SELECT s.*, a.title as assignment_title, a.max_score,
                    u.username as student_name, g.score as grade_score,
                    g.feedback_json, g.teacher_notes
             FROM submissions s
             JOIN assignments a ON s.assignment_id = a.id
             JOIN users u ON s.student_id = u.id
             LEFT JOIN grades g ON s.id = g.submission_id
             WHERE s.id = :id",
            ['id' => $id]
        );
    }

    /**
     * Get submissions by assignment
     */
    public function getByAssignment(int $assignmentId): array {
        return $this->db->fetchAll(
            "SELECT s.*, u.username as student_name, u.full_name,
                    g.score as grade_score, g.created_at as graded_at
             FROM submissions s
             JOIN users u ON s.student_id = u.id
             LEFT JOIN grades g ON s.id = g.submission_id
             WHERE s.assignment_id = :assignment_id
             ORDER BY s.created_at DESC",
            ['assignment_id' => $assignmentId]
        );
    }

    /**
     * Get submissions by student
     */
    public function getByStudent(int $studentId): array {
        return $this->db->fetchAll(
            "SELECT s.*, a.title as assignment_title, a.deadline,
                    g.score as grade_score, g.feedback_json
             FROM submissions s
             JOIN assignments a ON s.assignment_id = a.id
             LEFT JOIN grades g ON s.id = g.submission_id
             WHERE s.student_id = :student_id
             ORDER BY s.created_at DESC",
            ['student_id' => $studentId]
        );
    }

    /**
     * Create new submission
     */
    public function create(array $data): int {
        return $this->db->insert('submissions', [
            'assignment_id' => $data['assignment_id'],
            'student_id' => $data['student_id'],
            'file_path' => $data['file_path'],
            'file_name' => $data['file_name'],
            'file_hash' => $data['file_hash'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update submission status
     */
    public function updateStatus(int $id, string $status): bool {
        $data = ['status' => $status];
        
        if ($status === 'grading') {
            $data['grading_started_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'completed' || $status === 'failed') {
            $data['grading_completed_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update('submissions', $data, 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Set AI model used for grading
     */
    public function setAiModel(int $id, string $model): bool {
        return $this->db->update(
            'submissions',
            ['ai_model_used' => $model],
            'id = :id',
            ['id' => $id]
        ) > 0;
    }

    /**
     * Check if student already submitted to assignment
     */
    public function existsForStudentAndAssignment(int $studentId, int $assignmentId): bool {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM submissions 
             WHERE student_id = :student_id AND assignment_id = :assignment_id",
            ['student_id' => $studentId, 'assignment_id' => $assignmentId]
        );
        return (int) ($result['count'] ?? 0) > 0;
    }

    /**
     * Get pending submissions
     */
    public function getPending(): array {
        return $this->db->fetchAll(
            "SELECT s.*, a.title as assignment_title, u.username as student_name
             FROM submissions s
             JOIN assignments a ON s.assignment_id = a.id
             JOIN users u ON s.student_id = u.id
             WHERE s.status = 'pending'
             ORDER BY s.created_at ASC"
        );
    }

    /**
     * Get grading submissions (in progress)
     */
    public function getGrading(): array {
        return $this->db->fetchAll(
            "SELECT s.*, a.title as assignment_title, u.username as student_name
             FROM submissions s
             JOIN assignments a ON s.assignment_id = a.id
             JOIN users u ON s.student_id = u.id
             WHERE s.status = 'grading'
             ORDER BY s.grading_started_at ASC"
        );
    }

    /**
     * Count submissions by status
     */
    public function countByStatus(string $status): int {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM submissions WHERE status = :status",
            ['status' => $status]
        );
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get recent submissions
     */
    public function getRecent(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT s.*, a.title as assignment_title, u.username as student_name,
                    g.score as grade_score
             FROM submissions s
             JOIN assignments a ON s.assignment_id = a.id
             JOIN users u ON s.student_id = u.id
             LEFT JOIN grades g ON s.id = g.submission_id
             ORDER BY s.created_at DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
}
