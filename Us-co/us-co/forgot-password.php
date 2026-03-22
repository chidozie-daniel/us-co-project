<?php
$page_title = "Forgot Password";
require_once 'includes/header.php';

$success = '';
$error = '';

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $email = sanitize($_POST['email']);
    
    if (!empty($email)) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                // In a real application, you would:
                // 1. Generate a unique reset token
                // 2. Store it in the database with an expiration time
                // 3. Send an email with the reset link
                
                $success = 'If an account exists with that email, you will receive password reset instructions.';
            } else {
                // Don't reveal if email exists or not (security best practice)
                $success = 'If an account exists with that email, you will receive password reset instructions.';
            }
        } else {
            $error = 'Please enter a valid email address.';
        }
    } else {
        $error = 'Email is required.';
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-key me-2"></i>Forgot Password
                    </h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                        </div>
                        <div class="text-center">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Back to Login
                            </a>
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <p class="text-muted mb-4">
                            Enter your email address and we'll send you instructions to reset your password.
                        </p>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email Address
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Enter your email" required
                                       value="<?php echo $_POST['email'] ?? ''; ?>">
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="reset_password" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                                </button>
                                <a href="login.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <p class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Remember your password? <a href="login.php" class="text-primary text-decoration-none">Sign in</a>
                </p>
                <p class="text-muted small">
                    Don't have an account? <a href="register.php" class="text-primary text-decoration-none fw-bold">Create one now</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
