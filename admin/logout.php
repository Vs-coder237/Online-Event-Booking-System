<?php
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Optional: Clear session cookie if set
if (ini_get("session.use_cookies")) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Redirect to login page or homepage
header("Location: ../index.php");
exit();
