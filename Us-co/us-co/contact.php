<?php
$page_title = "Contact Us";
require_once 'includes/header.php';

$success = '';
$error = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // In a real application, you would send an email here
            // For now, we'll just show a success message
            $success = 'Thank you for contacting us! We will respond to your message soon.';
            
            // Clear form
            $_POST = [];
        } else {
            $error = 'Please enter a valid email address.';
        }
    } else {
        $error = 'All fields are required.';
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                            <h5>Email</h5>
                            <p class="text-muted">support@everest.com</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-phone fa-2x text-primary mb-3"></i>
                            <h5>Phone</h5>
                            <p class="text-muted">+1 (555) 123-4567</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-clock fa-2x text-primary mb-3"></i>
                            <h5>Hours</h5>
                            <p class="text-muted">Mon-Fri: 9AM-5PM</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Send us a Message</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user me-1"></i>Name
                                </label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo $_POST['name'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo $_POST['email'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">
                                <i class="fas fa-tag me-1"></i>Subject
                            </label>
                            <input type="text" class="form-control" id="subject" name="subject" 
                                   value="<?php echo $_POST['subject'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">
                                <i class="fas fa-comment me-1"></i>Message
                            </label>
                            <textarea class="form-control" id="message" name="message" rows="6" 
                                      required><?php echo $_POST['message'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="send_message" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <p class="text-muted">
                    You can also reach us on social media:
                </p>
                <div class="social-links">
                    <a href="#" class="btn btn-outline-primary me-2">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="#" class="btn btn-outline-info me-2">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="btn btn-outline-danger me-2">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
