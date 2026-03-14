<?php
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../models/Assignment.php';
require_once __DIR__ . '/../services/GradingManager.php';
require_once __DIR__ . '/../models/Submission.php';

session_start();

class AssignmentController {
    private $assignmentModel;
    private $submissionModel;
    private $gradingManager;

    public function __construct() {
        $this->assignmentModel = new Assignment();
        $this->submissionModel = new Submission();
        $this->gradingManager = new GradingManager();
    }

    public function create() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $rubric = $_POST['rubric'] ?? '';
            $deadline = $_POST['deadline'] ?? null;

            if (empty($title) || empty($description)) {
                $_SESSION['error'] = 'Title and description are required';
                header('Location: /assignments/create');
                exit;
            }

            $rubricData = [
                'correctness' => 40,
                'efficiency' => 20,
                'security' => 20,
                'style' => 20
            ];

            try {
                $parsedRubric = json_decode($rubric, true);
                if ($parsedRubric) {
                    $rubricData = $parsedRubric;
                }
            } catch (Exception $e) {
                // Use default rubric
            }

            $assignmentId = $this->assignmentModel->create(
                $_SESSION['user_id'],
                $title,
                $description,
                json_encode($rubricData),
                $deadline
            );

            if ($assignmentId) {
                $_SESSION['success'] = 'Assignment created successfully';
                header('Location: /dashboard');
            } else {
                $_SESSION['error'] = 'Failed to create assignment';
                header('Location: /assignments/create');
            }
            exit;
        }

        require __DIR__ . '/../views/assignments/create.php';
    }

    public function view($id) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $assignment = $this->assignmentModel->getById($id);
        if (!$assignment) {
            $_SESSION['error'] = 'Assignment not found';
            header('Location: /dashboard');
            exit;
        }

        $submissions = [];
        if ($_SESSION['role'] === 'teacher') {
            $submissions = $this->submissionModel->getByAssignmentId($id);
        } else {
            $mySubmission = $this->submissionModel->getByAssignmentAndStudent($id, $_SESSION['user_id']);
            if ($mySubmission) {
                $submissions = [$mySubmission];
            }
        }

        require __DIR__ . '/../views/assignments/view.php';
    }

    public function submit($id) {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
            header('Location: /login');
            exit;
        }

        $assignment = $this->assignmentModel->getById($id);
        if (!$assignment) {
            $_SESSION['error'] = 'Assignment not found';
            header('Location: /dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['code_file'])) {
            $file = $_FILES['code_file'];
            
            // Validate file
            $allowedExtensions = ['py', 'java', 'cpp', 'js', 'c', 'php'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowedExtensions)) {
                $_SESSION['error'] = 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions);
                header("Location: /assignments/$id/submit");
                exit;
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'File upload error';
                header("Location: /assignments/$id/submit");
                exit;
            }

            // Generate secure filename
            $secureName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filePath = $uploadDir . $secureName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Create submission
                $submissionId = $this->submissionModel->create(
                    $id,
                    $_SESSION['user_id'],
                    $filePath,
                    'pending'
                );

                if ($submissionId) {
                    // Start grading asynchronously
                    $this->startGrading($submissionId, $filePath, $assignment['rubric_json']);
                    
                    $_SESSION['success'] = 'Submission received. Grading in progress...';
                    header("Location: /assignments/$id");
                } else {
                    unlink($filePath);
                    $_SESSION['error'] = 'Failed to save submission';
                    header("Location: /assignments/$id/submit");
                }
            } else {
                $_SESSION['error'] = 'Failed to upload file';
                header("Location: /assignments/$id/submit");
            }
            exit;
        }

        require __DIR__ . '/../views/assignments/submit.php';
    }

    private function startGrading($submissionId, $filePath, $rubricJson) {
        // Update status to grading
        $this->submissionModel->updateStatus($submissionId, 'grading');

        // Read code file
        $code = file_get_contents($filePath);
        $rubric = json_decode($rubricJson, true);

        // Run grading in background (simulate async with fastcgi_finish_request or just proceed)
        // In production, use a queue system like Redis/RabbitMQ
        try {
            $result = $this->gradingManager->gradeWithFallback($code, $rubric);
            
            // Save grade
            $this->gradeModel->create(
                $submissionId,
                $result['score'],
                json_encode([
                    'summary' => $result['summary'],
                    'bugs' => $result['bugs'],
                    'suggestions' => $result['suggestions'],
                    'correctness' => $result['metrics']['correctness'] ?? 0,
                    'efficiency' => $result['metrics']['efficiency'] ?? 0,
                    'security' => $result['metrics']['security'] ?? 0,
                    'style' => $result['metrics']['style'] ?? 0
                ])
            );

            // Update submission
            $this->submissionModel->updateStatus($submissionId, 'completed');
            $this->submissionModel->setAiModelUsed($submissionId, $result['model_used']);

        } catch (Exception $e) {
            // Log error and mark as failed
            error_log("Grading failed for submission $submissionId: " . $e->getMessage());
            $this->submissionModel->updateStatus($submissionId, 'failed');
        }
    }
}
