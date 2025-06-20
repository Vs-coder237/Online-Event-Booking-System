<?php
/**
 * Admin Login Page
 * Online Event Booking System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';


// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        // Validate input
        if (empty($username) || empty($password)) {
            $error_message = 'Please enter both username and password.';
        } else {
            // Authenticate admin
            $result = loginAdmin($username, $password);
            if ($result['success']) {
                // Log activity
                logActivity('admin_login', 'Admin logged in successfully', 'admin', $_SESSION['admin_id']);
                
                // Redirect to dashboard
                header('Location: index.php');
                exit();
            } else {
                $error_message = $result['message'];
                // Log failed attempt
                logActivity('admin_login_failed', "Failed login attempt for username: $username", 'admin');
            }
        }
    }
}

/**
 * Login Admin Function
 */
function loginAdmin($username, $password) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Password is correct, set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];

            return [
                'success' => true,
                'admin' => $admin
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Invalid username or password.'
            ];
        }
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database error occurred. Please try again.'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px 15px 0 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .btn-admin {
            background: linear-gradient(45deg, #ff6b6b, #ee5a52);
            border: none;
        }
        
        .btn-admin:hover {
            background: linear-gradient(45deg, #ee5a52, #ff6b6b);
            transform: translateY(-1px);
        }
        
        .text-white-70 {
            color: rgba(255, 255, 255, 0.7) !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card">
                    <div class="card-header login-header text-center py-4">
                        <i class="fas fa-shield-alt fa-3x text-white mb-3"></i>
                        <h4 class="text-white mb-0">Admin Portal</h4>
                        <p class="text-white-70 mb-0 small"><?php echo SITE_NAME; ?></p>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-end-0 text-white-70">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control border-start-0" 
                                           name="username" 
                                           placeholder="Username" 
                                           required
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-end-0 text-white-70">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control border-start-0" 
                                           name="password" 
                                           placeholder="Password" 
                                           required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-admin w-100 py-2 fw-bold">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Login to Dashboard
                            </button>
                        </form>
                    </div>
                    
                    <div class="card-footer bg-transparent border-top-0 text-center">
                        <small class="text-white-70">
                            <a href="<?php echo SITE_URL; ?>" class="text-white text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>
                                Back to Main Site
                            </a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>