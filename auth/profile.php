<?php
/**
 * User Profile Page
 * Online Event Booking System
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirect if not logged in
requireLogin();

$page_title = 'My Profile - ' . SITE_NAME;
$error_message = '';
$success_message = '';

// Get current user data
$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update profile
    if (isset($_POST['update_profile'])) {
        $form_data = [
            'first_name' => sanitizeInput($_POST['first_name']),
            'last_name' => sanitizeInput($_POST['last_name']),
            'phone' => sanitizeInput($_POST['phone']),
            'newsletter' => isset($_POST['newsletter']) ? 1 : 0
        ];
        
        // Validation
        $errors = [];
        
        if (empty($form_data['first_name'])) {
            $errors[] = 'First name is required.';
        }
        
        if (empty($form_data['last_name'])) {
            $errors[] = 'Last name is required.';
        }
        
        if (!empty($form_data['phone']) && !preg_match('/^[\+]?[1-9][\d]{0,15}$/', $form_data['phone'])) {
            $errors[] = 'Please enter a valid phone number.';
        }
        
        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
        } else {
            // Update user profile
            $update_result = updateUserProfile($user['id'], $form_data);
            
            if ($update_result['success']) {
                $success_message = 'Profile updated successfully.';
                $user = getCurrentUser(); // Refresh user data
            } else {
                $error_message = $update_result['message'];
            }
        }
    }
    
    // Change password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        $errors = [];
        
        if (empty($current_password)) {
            $errors[] = 'Current password is required.';
        }
        
        if (empty($new_password)) {
            $errors[] = 'New password is required.';
        } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        }
        
        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
        } else {
            // Change password
            $password_result = changeUserPassword($user['id'], $current_password, $new_password);
            
            if ($password_result['success']) {
                $success_message = 'Password changed successfully.';
            } else {
                $error_message = $password_result['message'];
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-circle mb-3">
                            <span class="avatar-initials">
                                <?php echo substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1); ?>
                            </span>
                        </div>
                        <h5 class="mb-0"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <a href="#profile-info" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                            <i class="fas fa-user me-2"></i>Profile Information
                        </a>
                        <a href="#change-password" class="list-group-item list-group-item-action" data-bs-toggle="list">
                            <i class="fas fa-lock me-2"></i>Change Password
                        </a>
                        <a href="<?php echo SITE_URL; ?>/booking/booking-history.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-history me-2"></i>Booking History
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
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
            
            <div class="tab-content">
                <!-- Profile Information -->
                <div class="tab-pane fade show active" id="profile-info">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                    <div class="form-text">Email address cannot be changed</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                           placeholder="+1234567890">
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter" 
                                           <?php echo (isset($user['newsletter']) && $user['newsletter']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="newsletter">
                                        Subscribe to our newsletter for event updates and special offers
                                    </label>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="tab-pane fade" id="change-password">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="new_password" name="new_password" 
                                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password *</label>
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
                                
                                <!-- Password Strength Indicator -->
                                <div class="mb-3">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small id="passwordStrengthText" class="form-text text-muted">Password strength will appear here</small>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 80px;
    height: 80px;
    background-color: #007bff;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0 auto;
}

.avatar-initials {
    color: white;
    font-size: 32px;
    font-weight: bold;
}
</style>

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
document.getElementById('new_password').addEventListener('input', function() {
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
    const password = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include '../includes/footer.php'; ?>