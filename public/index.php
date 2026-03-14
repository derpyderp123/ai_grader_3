<?php
/**
 * AI-Grading Management System - Main Entry Point
 * 
 * Routes all requests through this file
 */

// Load configuration
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../config/Database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple router
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Route mapping
$routes = [
    'GET' => [
        '/' => 'HomeController@index',
        '/login' => 'AuthController@showLogin',
        '/register' => 'AuthController@showRegister',
        '/dashboard/teacher' => 'TeacherController@dashboard',
        '/dashboard/student' => 'StudentController@dashboard',
        '/assignments' => 'AssignmentController@index',
        '/assignments/create' => 'AssignmentController@create',
        '/assignments/view' => 'AssignmentController@view',
        '/submissions' => 'SubmissionController@index',
        '/submissions/upload' => 'SubmissionController@uploadForm',
        '/grades/export' => 'ReportController@exportExcel',
        '/stream' => 'StreamController@handle',
    ],
    'POST' => [
        '/api/login' => 'AuthController@login',
        '/api/logout' => 'AuthController@logout',
        '/api/register' => 'AuthController@register',
        '/api/assignments/create' => 'AssignmentController@store',
        '/api/submissions/upload' => 'SubmissionController@upload',
        '/api/submissions/grade' => 'GradingController@grade',
        '/api/grades/review' => 'GradingController@review',
    ],
];

// Find matching route
$routeKey = $requestUri;
$controller = null;
$action = null;

if (isset($routes[$method][$routeKey])) {
    list($controllerClass, $actionMethod) = explode('@', $routes[$method][$routeKey]);
    
    // Load controller
    $controllerFile = __DIR__ . "/../controllers/{$controllerClass}.php";
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        $controller = new $controllerClass();
        $action = $actionMethod;
    }
}

// Handle 404
if (!$controller || !$action) {
    // Check for parameterized routes (e.g., /assignments/view?id=1)
    $basePath = explode('?', $requestUri)[0];
    
    if (isset($routes[$method][$basePath])) {
        list($controllerClass, $actionMethod) = explode('@', $routes[$method][$basePath]);
        
        $controllerFile = __DIR__ . "/../controllers/{$controllerClass}.php";
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $controller = new $controllerClass();
            $action = $actionMethod;
        }
    }
}

// Execute controller action
if ($controller && $action && method_exists($controller, $action)) {
    try {
        $controller->$action();
    } catch (Exception $e) {
        http_response_code(500);
        if (Config::isDebug()) {
            echo "<h1>Error</h1><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        } else {
            echo json_encode(['success' => false, 'message' => 'An error occurred']);
        }
        error_log("Error in {$controllerClass}@{$action}: " . $e->getMessage());
    }
} else {
    http_response_code(404);
    include __DIR__ . '/../views/errors/404.php';
}
