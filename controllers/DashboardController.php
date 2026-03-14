<?php
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Assignment.php';
require_once __DIR__ . '/../models/Submission.php';
require_once __DIR__ . '/../models/Grade.php';

session_start();

class DashboardController {
    private $userModel;
    private $assignmentModel;
    private $submissionModel;
    private $gradeModel;

    public function __construct() {
        $this->userModel = new User();
        $this->assignmentModel = new Assignment();
        $this->submissionModel = new Submission();
        $this->gradeModel = new Grade();
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        $data = [];

        if ($role === 'teacher') {
            $data['assignments'] = $this->assignmentModel->getByTeacherId($userId);
            $data['recent_submissions'] = $this->submissionModel->getRecentByTeacher($userId, 5);
        } else {
            $data['assignments'] = $this->assignmentModel->getAllActive();
            $data['my_submissions'] = $this->submissionModel->getByStudentId($userId);
        }

        $data['user'] = $_SESSION;
        require __DIR__ . '/../views/dashboard.php';
    }
}
