<?php
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../models/Assignment.php';
require_once __DIR__ . '/../models/Submission.php';
require_once __DIR__ . '/../models/Grade.php';

session_start();

class ReportController {
    private $assignmentModel;
    private $submissionModel;
    private $gradeModel;

    public function __construct() {
        $this->assignmentModel = new Assignment();
        $this->submissionModel = new Submission();
        $this->gradeModel = new Grade();
    }

    public function exportExcel($assignmentId) {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
            http_response_code(403);
            exit('Unauthorized');
        }

        $assignment = $this->assignmentModel->getById($assignmentId);
        if (!$assignment) {
            http_response_code(404);
            exit('Assignment not found');
        }

        // Verify teacher owns this assignment
        if ($assignment['teacher_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            exit('Unauthorized');
        }

        // Get all submissions and grades for this assignment
        $submissions = $this->submissionModel->getByAssignmentId($assignmentId);
        
        // Prepare data
        $rows = [];
        foreach ($submissions as $submission) {
            $grade = $this->gradeModel->getBySubmissionId($submission['id']);
            $student = $this->getUserById($submission['student_id']);
            
            $feedbackData = $grade ? json_decode($grade['feedback_json'], true) : [];
            
            $rows[] = [
                'student_name' => $student['username'] ?? 'Unknown',
                'student_id' => $submission['student_id'],
                'submission_id' => $submission['id'],
                'file_path' => $submission['file_path'],
                'status' => $submission['status'],
                'ai_model_used' => $submission['ai_model_used'] ?? 'N/A',
                'submitted_at' => $submission['created_at'],
                'score' => $grade ? $grade['score'] : 'N/A',
                'summary' => $feedbackData['summary'] ?? '',
                'bugs' => isset($feedbackData['bugs']) ? json_encode($feedbackData['bugs']) : '',
                'suggestions' => isset($feedbackData['suggestions']) ? json_encode($feedbackData['suggestions']) : '',
                'correctness' => $feedbackData['correctness'] ?? 0,
                'efficiency' => $feedbackData['efficiency'] ?? 0,
                'security' => $feedbackData['security'] ?? 0,
                'style' => $feedbackData['style'] ?? 0,
                'grading_time' => $grade ? $grade['created_at'] : 'N/A'
            ];
        }

        // Generate Excel
        $this->generateExcel($assignment, $rows);
    }

    private function getUserById($userId) {
        require_once __DIR__ . '/../models/User.php';
        $userModel = new User();
        return $userModel->getById($userId);
    }

    private function generateExcel($assignment, $rows) {
        // Check if PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Fallback: Generate CSV instead
            $this->generateCSV($assignment, $rows);
            return;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        
        // Sheet 1: Summary
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Summary');
        
        $headers = ['Student Name', 'Student ID', 'Total Score', 'AI Model Used', 'Grading Time', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $summarySheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        $rowNum = 2;
        foreach ($rows as $row) {
            $summarySheet->setCellValue('A' . $rowNum, $row['student_name']);
            $summarySheet->setCellValue('B' . $rowNum, $row['student_id']);
            $summarySheet->setCellValue('C' . $rowNum, $row['score']);
            $summarySheet->setCellValue('D' . $rowNum, $row['ai_model_used']);
            $summarySheet->setCellValue('E' . $rowNum, $row['grading_time']);
            $summarySheet->setCellValue('F' . $rowNum, $row['status']);
            $rowNum++;
        }
        
        // Sheet 2: Detailed Feedback
        $detailSheet = $spreadsheet->createSheet();
        $detailSheet->setTitle('Detailed Feedback');
        
        $detailHeaders = ['Student Name', 'Code Snippet', 'AI Summary', 'Bugs Found', 'Optimization Tips', 'Teacher Notes'];
        $col = 'A';
        foreach ($detailHeaders as $header) {
            $detailSheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        $rowNum = 2;
        foreach ($rows as $row) {
            // Get first 5 lines of code
            $codeSnippet = '';
            if (file_exists($row['file_path'])) {
                $lines = file($row['file_path']);
                $codeSnippet = implode('', array_slice($lines, 0, 5));
                if (count($lines) > 5) {
                    $codeSnippet .= "\n...";
                }
            }
            
            $detailSheet->setCellValue('A' . $rowNum, $row['student_name']);
            $detailSheet->setCellValue('B' . $rowNum, $codeSnippet);
            $detailSheet->setCellValue('C' . $rowNum, $row['summary']);
            $detailSheet->setCellValue('D' . $rowNum, $row['bugs']);
            $detailSheet->setCellValue('E' . $rowNum, $row['suggestions']);
            $detailSheet->setCellValue('F' . $rowNum, ''); // Teacher Notes (editable)
            $rowNum++;
        }
        
        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $summarySheet->getColumnDimension($col)->setAutoSize(true);
            $detailSheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Output headers
        $filename = 'Class_Grades_' . preg_replace('/[^a-zA-Z0-9]/', '_', $assignment['title']) . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function generateCSV($assignment, $rows) {
        // Fallback CSV generation if PhpSpreadsheet not installed
        $filename = 'Class_Grades_' . preg_replace('/[^a-zA-Z0-9]/', '_', $assignment['title']) . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Summary headers
        fputcsv($output, ['=== SUMMARY ===']);
        fputcsv($output, ['Student Name', 'Student ID', 'Total Score', 'AI Model Used', 'Grading Time', 'Status']);
        
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['student_name'],
                $row['student_id'],
                $row['score'],
                $row['ai_model_used'],
                $row['grading_time'],
                $row['status']
            ]);
        }
        
        fputcsv($output, []);
        
        // Detailed headers
        fputcsv($output, ['=== DETAILED FEEDBACK ===']);
        fputcsv($output, ['Student Name', 'AI Summary', 'Bugs Found', 'Optimization Tips']);
        
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['student_name'],
                $row['summary'],
                $row['bugs'],
                $row['suggestions']
            ]);
        }
        
        fclose($output);
        exit;
    }

    public function view($assignmentId) {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
            header('Location: /login');
            exit;
        }

        $assignment = $this->assignmentModel->getById($assignmentId);
        if (!$assignment || $assignment['teacher_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = 'Assignment not found';
            header('Location: /dashboard');
            exit;
        }

        $submissions = $this->submissionModel->getByAssignmentId($assignmentId);
        $data = [];
        
        foreach ($submissions as $submission) {
            $grade = $this->gradeModel->getBySubmissionId($submission['id']);
            $student = $this->getUserById($submission['student_id']);
            $submission['student_name'] = $student['username'] ?? 'Unknown';
            $submission['grade'] = $grade;
            $data[] = $submission;
        }

        require __DIR__ . '/../views/reports/view.php';
    }
}
