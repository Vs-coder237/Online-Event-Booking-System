<?php
/**
 * User Login Page
 * Online Event Booking System
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('../index.php');
}

$page_title = 'Login - ' . SITE_NAME;
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);
    
    // Validation
    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Attempt login
        $login_result = loginUser($email, $password, $remember_me);
        
        if ($login_result['success']) {
            // Check if there's a redirect URL
            $redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '../index.php';
            redirect($redirect_url);
        } else {
            $error_message = $login_result['message'];
        }
    }
}

// Check for success messages from registration
if (isset($_GET['registered'])) {
    $success_message = 'Registration successful! Please login with your credentials.';
}

if (isset($_GET['verified'])) {
    $success_message = 'Email verified successfully! You can now login.';
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="h3 mb-2">Welcome Back</h2>
                        <p class="text-muted">Sign in to your account</p>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                            <label class="form-check-label" for="remember_me">
                                Remember me
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">
                            <a href="forgot-password.php" class="text-decoration-none">
                                Forgot your password?
                            </a>
                        </p>
                        <p class="mb-0">
                            Don't have an account? 
                            <a href="register.php" class="text-decoration-none fw-bold">
                                Sign up here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

<?php include '../includes/footer.php'; ?>