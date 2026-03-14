<?php
/**
 * AI-Grading Management System - Web-Based Installer
 * 
 * This script provides a browser-based installation wizard.
 * Access via: http://your-domain/install.php
 * 
 * Features:
 * - System requirements check
 * - Database configuration
 * - Admin account creation
 * - Automatic schema import
 * - .env file generation
 */

// Disable error reporting for clean UI (enable for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Prevent re-running if already installed
if (file_exists(__DIR__ . '/config/.env') && filesize(__DIR__ . '/config/.env') > 10) {
    // Check if we should allow re-install
    if (!isset($_GET['reinstall'])) {
        die('<h1>System Already Installed</h1><p>The AI-GMS is already configured. To reinstall, add <code>?reinstall=1</code> to the URL.</p>');
    }
}

// Configuration
$steps = [
    1 => 'Requirements Check',
    2 => 'Database Configuration',
    3 => 'Admin Account Setup',
    4 => 'Installation Complete'
];

$currentStep = isset($_POST['step']) ? (int)$_POST['step'] : 1;
$errors = [];
$success = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($currentStep) {
        case 1:
            // Move to step 2
            $currentStep = 2;
            break;
            
        case 2:
            // Validate database connection
            $dbHost = $_POST['db_host'] ?? 'localhost';
            $dbName = $_POST['db_name'] ?? '';
            $dbUser = $_POST['db_user'] ?? '';
            $dbPass = $_POST['db_pass'] ?? '';
            $dbPort = $_POST['db_port'] ?? '3306';
            
            if (empty($dbName)) {
                $errors[] = "Database name is required";
            }
            if (empty($dbUser)) {
                $errors[] = "Database username is required";
            }
            
            if (empty($errors)) {
                // Test connection
                try {
                    $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                    
                    // Create database if not exists
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    
                    // Test full connection
                    $pdo->exec("USE `{$dbName}`");
                    
                    // Save to session for next step
                    $_SESSION['db_config'] = [
                        'host' => $dbHost,
                        'port' => $dbPort,
                        'name' => $dbName,
                        'user' => $dbUser,
                        'pass' => $dbPass
                    ];
                    
                    $success[] = "Database connection successful!";
                    $currentStep = 3;
                    
                } catch (PDOException $e) {
                    $errors[] = "Database connection failed: " . $e->getMessage();
                }
            }
            break;
            
        case 3:
            // Validate admin account
            $adminUser = $_POST['admin_username'] ?? '';
            $adminPass = $_POST['admin_password'] ?? '';
            $adminEmail = $_POST['admin_email'] ?? '';
            
            if (empty($adminUser)) {
                $errors[] = "Admin username is required";
            }
            if (strlen($adminPass) < 6) {
                $errors[] = "Password must be at least 6 characters";
            }
            if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email address";
            }
            
            if (empty($errors) && isset($_SESSION['db_config'])) {
                // Create database tables and admin user
                try {
                    $db = $_SESSION['db_config'];
                    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    
                    // Read and execute SQL schema
                    $schemaFile = __DIR__ . '/database/schema.sql';
                    if (!file_exists($schemaFile)) {
                        throw new Exception("Schema file not found");
                    }
                    
                    $sql = file_get_contents($schemaFile);
                    $statements = array_filter(
                        array_map('trim', explode(';', $sql)),
                        function($s) { return !empty($s) && !preg_match('/^--/', $s); }
                    );
                    
                    foreach ($statements as $statement) {
                        $pdo->exec($statement);
                    }
                    
                    // Create admin user
                    $passwordHash = password_hash($adminPass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, 'teacher', NOW())");
                    $stmt->execute([$adminUser, $adminEmail, $passwordHash]);
                    
                    // Generate .env file
                    $envContent = generateEnvFile($db, $adminUser);
                    $envPath = __DIR__ . '/config/.env';
                    
                    if (file_put_contents($envPath, $envContent) === false) {
                        throw new Exception("Failed to create .env file. Please create it manually.");
                    }
                    
                    // Create uploads directory
                    $uploadsDir = __DIR__ . '/uploads';
                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0755, true);
                    }
                    
                    // Create .htaccess for uploads
                    $htaccessContent = "Options -Indexes\n<FilesMatch \"\\.(php|py|pl|sh|exe)$\">\n    Deny from all\n</FilesMatch>";
                    file_put_contents($uploadsDir . '/.htaccess', $htaccessContent);
                    
                    $success[] = "Installation completed successfully!";
                    $success[] = "Admin account created: {$adminUser}";
                    $success[] = "Configuration saved to config/.env";
                    
                    $currentStep = 4;
                    unset($_SESSION['db_config']);
                    
                } catch (Exception $e) {
                    $errors[] = "Installation failed: " . $e->getMessage();
                }
            } else {
                $errors[] = "Database configuration not found. Please go back.";
            }
            break;
    }
}

// Helper function to generate .env content
function generateEnvFile($db, $adminUser) {
    $apiKeyOllama = getenv('OLLAMA_API_KEY') ?: '';
    $apiKeyGemini = getenv('GEMINI_API_KEY') ?: '';
    $apiKeyOpenAI = getenv('OPENAI_API_KEY') ?: '';
    $apiKeyAnthropic = getenv('ANTHROPIC_API_KEY') ?: '';
    
    return "# AI-Grading Management System Configuration
# Generated on: " . date('Y-m-d H:i:s') . "

# Database Configuration
DB_HOST={$db['host']}
DB_PORT={$db['port']}
DB_NAME={$db['name']}
DB_USER={$db['user']}
DB_PASS={$db['pass']}

# Application Settings
APP_NAME=\"AI-Grading Management System\"
APP_URL=\"http://localhost\"
APP_ENV=production
DEBUG_MODE=false

# Session Settings
SESSION_LIFETIME=120
SESSION_SECURE=false

# AI Provider API Keys
# Leave empty to disable that provider
OLLAMA_API_URL=http://localhost:11434
OLLAMA_MODEL=llama3.2

GEMINI_API_KEY={$apiKeyGemini}
GEMINI_MODEL=gemini-pro

OPENAI_API_KEY={$apiKeyOpenAI}
OPENAI_MODEL=gpt-4o-mini

ANTHROPIC_API_KEY={$apiKeyAnthropic}
ANTHROPIC_MODEL=claude-3-haiku-20240307

# Grading Settings
DEFAULT_AI_PROVIDER=ollama
MAX_FILE_SIZE=5242880
ALLOWED_EXTENSIONS=py,java,cpp,c,js,ts

# Security
ENCRYPTION_KEY=\"\" # Generate with bin2hex(random_bytes(32))
";
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI-GMS Installation Wizard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .installer-card { max-width: 800px; margin: 50px auto; }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .step { flex: 1; text-align: center; padding: 10px; position: relative; }
        .step:not(:last-child)::after {
            content: ''; position: absolute; top: 50%; right: -50%; width: 100%; height: 2px;
            background: #dee2e6; z-index: 1;
        }
        .step.active .step-number { background: #667eea; color: white; border-color: #667eea; }
        .step.completed .step-number { background: #28a745; color: white; border-color: #28a745; }
        .step-number {
            width: 40px; height: 40px; border-radius: 50%; background: #f8f9fa;
            border: 2px solid #dee2e6; display: inline-flex; align-items: center;
            justify-content: center; font-weight: bold; position: relative; z-index: 2;
        }
        .check-item { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .check-pass { background: #d4edda; color: #155724; }
        .check-fail { background: #f8d7da; color: #721c24; }
        .loading-spinner { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card installer-card shadow-lg">
            <div class="card-header bg-white">
                <h2 class="text-center mb-0">🚀 AI-Grading Management System</h2>
                <p class="text-center text-muted mb-0">Installation Wizard</p>
            </div>
            <div class="card-body p-4">
                
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <?php foreach ($steps as $num => $label): ?>
                    <div class="step <?= $num <= $currentStep ? 'active' : '' ?> <?= $num < $currentStep ? 'completed' : '' ?>">
                        <div class="step-number"><?= $num ?></div>
                        <div class="step-label mt-2 small"><?= $label ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Alerts -->
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <ul class="mb-0">
                        <?php foreach ($success as $msg): ?>
                        <li><?= htmlspecialchars($msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Step 1: Requirements Check -->
                <?php if ($currentStep === 1): ?>
                <h4 class="mb-4">System Requirements Check</h4>
                <div class="requirements-list">
                    <?php
                    $checks = [
                        'PHP Version >= 8.2' => version_compare(PHP_VERSION, '8.2.0', '>='),
                        'PDO Extension' => extension_loaded('pdo'),
                        'PDO MySQL' => extension_loaded('pdo_mysql'),
                        'cURL Extension' => extension_loaded('curl'),
                        'MBString Extension' => extension_loaded('mbstring'),
                        'JSON Extension' => extension_loaded('json'),
                        'OpenSSL Extension' => extension_loaded('openssl'),
                        'Config Directory Writable' => is_writable(__DIR__ . '/config'),
                        'Uploads Directory Writable' => is_writable(__DIR__) || is_dir(__DIR__ . '/uploads') || @mkdir(__DIR__ . '/uploads', 0755),
                    ];
                    
                    $allPassed = true;
                    foreach ($checks as $requirement => $passed):
                        $allPassed = $allPassed && $passed;
                    ?>
                    <div class="check-item <?= $passed ? 'check-pass' : 'check-fail' ?>">
                        <strong><?= $passed ? '✓' : '✗' ?></strong> <?= htmlspecialchars($requirement) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($allPassed): ?>
                <form method="POST" class="mt-4">
                    <input type="hidden" name="step" value="1">
                    <button type="submit" class="btn btn-primary btn-lg w-100">Continue to Database Setup →</button>
                </form>
                <?php else: ?>
                <div class="alert alert-warning mt-3">
                    Please fix the above issues before continuing.
                </div>
                <?php endif; ?>
                
                <?php endif; ?>

                <!-- Step 2: Database Configuration -->
                <?php if ($currentStep === 2): ?>
                <h4 class="mb-4">Database Configuration</h4>
                <form method="POST">
                    <input type="hidden" name="step" value="2">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Database Host *</label>
                            <input type="text" name="db_host" class="form-control" value="localhost" required>
                            <small class="text-muted">Usually "localhost" or "127.0.0.1"</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Database Port</label>
                            <input type="number" name="db_port" class="form-control" value="3306">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Database Name *</label>
                        <input type="text" name="db_name" class="form-control" placeholder="ai_grading_system" required>
                        <small class="text-muted">Will be created automatically if it doesn't exist</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Database Username *</label>
                            <input type="text" name="db_user" class="form-control" placeholder="root" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Database Password</label>
                            <input type="password" name="db_pass" class="form-control" placeholder="Leave empty if no password">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> The installer will create the database and all required tables automatically.
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100">Test Connection & Continue →</button>
                    <a href="?step=1" class="btn btn-outline-secondary w-100 mt-2">← Back</a>
                </form>
                <?php endif; ?>

                <!-- Step 3: Admin Account Setup -->
                <?php if ($currentStep === 3): ?>
                <h4 class="mb-4">Create Admin Account</h4>
                <form method="POST">
                    <input type="hidden" name="step" value="3">
                    
                    <div class="mb-3">
                        <label class="form-label">Admin Username *</label>
                        <input type="text" name="admin_username" class="form-control" value="admin" required>
                        <small class="text-muted">You'll use this to login</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="admin_email" class="form-control" placeholder="admin@example.com" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="admin_password" class="form-control" minlength="6" required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <strong>Important:</strong> After installation, you can configure AI API keys in <code>config/.env</code>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <span class="loading-spinner spinner-border spinner-border-sm me-2"></span>
                        Install System & Create Admin
                    </button>
                    <a href="?step=2" class="btn btn-outline-secondary w-100 mt-2">← Back</a>
                </form>
                
                <script>
                document.querySelector('form').addEventListener('submit', function() {
                    document.querySelector('.loading-spinner').style.display = 'inline-block';
                    document.querySelector('button[type="submit"]').disabled = true;
                });
                </script>
                <?php endif; ?>

                <!-- Step 4: Installation Complete -->
                <?php if ($currentStep === 4): ?>
                <div class="text-center py-4">
                    <div class="display-1 text-success mb-4">✓</div>
                    <h3>Installation Complete!</h3>
                    <p class="lead">The AI-Grading Management System is ready to use.</p>
                    
                    <div class="card bg-light text-left mt-4">
                        <div class="card-body">
                            <h5>Next Steps:</h5>
                            <ol>
                                <li>Configure AI API keys in <code>config/.env</code></li>
                                <li>Login with your admin account</li>
                                <li>Create your first assignment</li>
                                <li>Invite students to join</li>
                            </ol>
                            
                            <hr>
                            
                            <h5>Default Login:</h5>
                            <p><strong>Username:</strong> <?= htmlspecialchars($_POST['admin_username'] ?? 'admin') ?><br>
                            <strong>Password:</strong> The password you just set</p>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="public/index.php" class="btn btn-primary btn-lg">Go to Login Page →</a>
                    </div>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            For security, delete or rename <code>install.php</code> after setup.
                        </small>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        
        <footer class="text-center text-white-50 pb-4">
            <small>AI-Grading Management System v1.0 | ISO 9126 Compliant</small>
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
