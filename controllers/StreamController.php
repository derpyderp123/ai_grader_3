<?php
require_once __DIR__ . '/../config/Config.php';

/**
 * Stream Controller - Server-Sent Events (SSE)
 * 
 * Provides live grading status updates to clients
 * ISO 9126: Usability - Real-time feedback
 */

class StreamController {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Handle SSE stream for submission status
     */
    public function handle(): void {
        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable nginx buffering
        
        // Get submission ID from query string
        $submissionId = filter_input(INPUT_GET, 'submission_id', FILTER_VALIDATE_INT);
        
        if (!$submissionId) {
            $this->sendEvent('error', ['message' => 'Invalid submission ID']);
            return;
        }

        // Check authentication
        AuthController::requireAuth();

        $maxExecutionTime = 120; // 2 minutes max
        $startTime = time();
        $pollInterval = 2; // seconds

        while (true) {
            // Check if we've exceeded max execution time
            if ((time() - $startTime) > $maxExecutionTime) {
                $this->sendEvent('timeout', [
                    'message' => 'Connection timeout. Please refresh the page.',
                    'submission_id' => $submissionId
                ]);
                break;
            }

            // Get submission status
            $submission = $this->db->fetchOne(
                "SELECT s.*, a.title as assignment_title, g.score as grade_score,
                        g.feedback_json, s.ai_model_used
                 FROM submissions s
                 JOIN assignments a ON s.assignment_id = a.id
                 LEFT JOIN grades g ON s.id = g.submission_id
                 WHERE s.id = :id",
                ['id' => $submissionId]
            );

            if (!$submission) {
                $this->sendEvent('error', [
                    'message' => 'Submission not found',
                    'submission_id' => $submissionId
                ]);
                break;
            }

            // Send status update
            $data = [
                'submission_id' => $submissionId,
                'status' => $submission['status'],
                'assignment_title' => $submission['assignment_title'],
                'ai_model_used' => $submission['ai_model_used'],
                'updated_at' => $submission['updated_at'],
            ];

            if ($submission['status'] === 'completed') {
                $data['score'] = $submission['grade_score'];
                $data['feedback'] = $submission['feedback_json'] 
                    ? json_decode($submission['feedback_json'], true) 
                    : null;
                
                $this->sendEvent('completed', $data);
                break;
            } elseif ($submission['status'] === 'failed') {
                $this->sendEvent('failed', $data);
                break;
            } elseif ($submission['status'] === 'grading') {
                $data['grading_started_at'] = $submission['grading_started_at'];
                $data['elapsed_time'] = time() - strtotime($submission['grading_started_at']);
                $this->sendEvent('grading', $data);
            } else {
                $this->sendEvent('pending', $data);
            }

            // Flush output
            ob_flush();
            flush();

            // Wait before next poll
            sleep($pollInterval);
        }
    }

    /**
     * Send SSE event
     */
    private function sendEvent(string $event, array $data): void {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush();
        flush();
    }
}
