<?php
/**
 * User Registration Page
 * Online Event Booking System
 */

require_once __DIR__ . '../config/config.php';
require_once __DIR__ . '../config/database.php';
require_once __DIR__ . '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('../index.php');
}

$page_title = 'Register - ' . SITE_NAME;
$error_message = '';
$success_message = '';
$form_data = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_data = [
        'first_name' => sanitizeInput($_POST['first_name']),
        'last_name' => sanitizeInput($_POST['last_name']),
        'email' => sanitizeInput($_POST['email']),
        'phone' => sanitizeInput($_POST['phone']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password']
    ];
    
    // Validation
    $errors = [];
    
    if (empty($form_data['first_name'])) {
        $errors[] = 'First name is required.';
    }
    
    if (empty($form_data['last_name'])) {
        $errors[] = 'Last name is required.';
    }
    
    if (empty($form_data['email'])) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (emailExists($form_data['email'])) {
        $errors[] = 'An account with this email address already exists.';
    }
    
    if (!empty($form_data['phone']) && !preg_match('/^[\+]?[1-9][\d]{0,15}$/', $form_data['phone'])) {
        $errors[] = 'Please enter a valid phone number.';
    }
    
    if (empty($form_data['password'])) {
        $errors[] = 'Password is required.';
    } elseif (strlen($form_data['password']) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
    }
    
    if ($form_data['password'] !== $form_data['confirm_password']) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (!isset($_POST['terms'])) {
        $errors[] = 'Please accept the terms and conditions.';
    }
    
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    } else {
        // Attempt registration
        $registration_result = registerUser($form_data);
        
        if ($registration_result['success']) {
            redirect('login.php?registered=1');
        } else {
            $error_message = $registration_result['message'];
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="h3 mb-2">Create Account</h2>
                        <p class="text-muted">Join us to start booking amazing events</p>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="registrationForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" 
                                       placeholder="+1234567890">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Password Strength Indicator -->
                        <div class="mb-3">
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="passwordStrengthText" class="form-text text-muted">Password strength will appear here</small>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="../legal/terms.php" target="_blank">Terms and Conditions</a> 
                                and <a href="../legal/privacy.php" target="_blank">Privacy Policy</a> *
                            </label>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
                            <label class="form-check-label" for="newsletter">
                                Subscribe to our newsletter for event updates and special offers
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0">
                            Already have an account? 
                            <a href="login.php" class="text-decoration-none fw-bold">
                                Sign in here
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

// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    const strengthText = document.getElementById('passwordStrengthText');
    
    let strength = 0;
    let strengthLabel = '';
    let strengthColor = '';
    
    if (password.length >= 6) strength += 25;
    if (password.match(/[a-z]+/)) strength += 25;
    if (password.match(/[A-Z]+/)) strength += 25;
    if (password.match(/[0-9]+/)) strength += 12.5;
    if (password.match(/[$@#&!]+/)) strength += 12.5;
    
    if (strength <= 25) {
        strengthLabel = 'Weak';
        strengthColor = 'bg-danger';
    } else if (strength <= 50) {
        strengthLabel = 'Fair';
        strengthColor = 'bg-warning';
    } else if (strength <= 75) {
        strengthLabel = 'Good';
        strengthColor = 'bg-info';
    } else {
        strengthLabel = 'Strong';
        strengthColor = 'bg-success';
    }
    
    strengthBar.style.width = strength + '%';
    strengthBar.className = 'progress-bar ' + strengthColor;
    strengthText.textContent = 'Password strength: ' + strengthLabel;
});

// Confirm password validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include '../includes/footer.php'; ?>