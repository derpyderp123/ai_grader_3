<?php
$pageTitle = 'Login - AI Grading System';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg">
            <div class="card-header text-center bg-primary text-white">
                <h4><i class="bi bi-robot"></i> AI-GMS</h4>
                <p class="mb-0 small">AI Grading Management System</p>
            </div>
            <div class="card-body p-4">
                <h5 class="text-center mb-4">Sign In</h5>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/login">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <p class="text-center mb-0">
                    Don't have an account?
                    <a href="/register">Register here</a>
                </p>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-body py-3">
                <h6 class="card-title text-center mb-2">Demo Accounts</h6>
                <p class="card-text small mb-1"><strong>Teacher:</strong> teacher / password123</p>
                <p class="card-text small mb-0"><strong>Student:</strong> student / password123</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
