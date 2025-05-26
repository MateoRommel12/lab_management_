<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include bootstrap file for authentication
require_once __DIR__ . '/includes/bootstrap.php';

// Debug information
error_log("Logout process started");

// First, log the logout action through Auth class
$auth->logout();
error_log("Auth logout completed");

// Clear all session data
$_SESSION = array();
error_log("Session array cleared");

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
    error_log("Session cookie destroyed");
}

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
    error_log("Remember me cookie cleared");
}

// Destroy the session
session_destroy();
error_log("Session destroyed");

// Set success message in a new session
session_start();
$_SESSION['flash_message'] = [
    'type' => 'success',
    'message' => 'You have been successfully logged out.'
];
error_log("Flash message set");

// Redirect to login page
header('Location: ' . APP_URL . '/login.php');
error_log("Redirecting to login page");
exit;
?>   