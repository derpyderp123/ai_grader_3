<?php
require_once __DIR__ . '/../models/UserModel.php';

/**
 * Auth Controller
 * 
 * Handles user authentication (login, logout, register)
 */

class AuthController {
    private UserModel $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    /**
     * Show login page
     */
    public function showLogin(): void {
        include __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Process login
     */
    public function login(): void {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';

            if (empty($username) || empty($password)) {
                throw new Exception('Username and password are required');
            }

            $user = $this->userModel->findByUsername($username);
            
            if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
                throw new Exception('Invalid username or password');
            }

            // Set session
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['logged_in'] = true;

            echo json_encode([
                'success' => true,
                'redirect' => $user['role'] === 'teacher' ? '/dashboard/teacher' : '/dashboard/student',
                'message' => 'Login successful'
            ]);

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Logout
     */
    public function logout(): void {
        session_start();
        session_destroy();
        header('Location: /');
        exit;
    }

    /**
     * Show registration page
     */
    public function showRegister(): void {
        include __DIR__ . '/../views/auth/register.php';
    }

    /**
     * Process registration
     */
    public function register(): void {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $username = trim($input['username'] ?? '');
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            $fullName = trim($input['full_name'] ?? '');
            $role = $input['role'] ?? 'student';

            // Validation
            if (empty($username) || empty($email) || empty($password)) {
                throw new Exception('All fields are required');
            }

            if (strlen($password) < Config::getInt('PASSWORD_MIN_LENGTH', 8)) {
                throw new Exception('Password must be at least ' . Config::getInt('PASSWORD_MIN_LENGTH', 8) . ' characters');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }

            // Check if user exists
            if ($this->userModel->findByUsername($username)) {
                throw new Exception('Username already taken');
            }

            if ($this->userModel->findByEmail($email)) {
                throw new Exception('Email already registered');
            }

            // Create user
            $userId = $this->userModel->create([
                'username' => $username,
                'email' => $email,
                'password_hash' => $this->userModel->hashPassword($password),
                'role' => in_array($role, ['student', 'teacher']) ? $role : 'student',
                'full_name' => $fullName,
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Registration successful. Please login.',
                'redirect' => '/login'
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Get current user
     */
    public static function getCurrentUser(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
        ];
    }

    /**
     * Require authentication
     */
    public static function requireAuth(): void {
        if (!self::isLoggedIn()) {
            if (self::isAjaxRequest()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Authentication required']);
                exit;
            }
            header('Location: /login');
            exit;
        }
    }

    /**
     * Require specific role
     */
    public static function requireRole(string $role): void {
        self::requireAuth();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (($_SESSION['role'] ?? '') !== $role) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }

    /**
     * Check if request is AJAX
     */
    private static function isAjaxRequest(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
