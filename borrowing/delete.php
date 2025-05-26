<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../utils/Helpers.php';
require_once __DIR__ . '/../models/Borrowing.php';
require_once __DIR__ . '/../config/Database.php';

// Initialize Auth
$auth = Auth::getInstance();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    $_SESSION['error'] = "You must be logged in to perform this action.";
    header('Location: ../login.php');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid request ID.";
    header('Location: ../faculty/dashboard.php');
    exit;
}

$requestId = (int)$_GET['id'];

// Initialize Borrowing model
$borrowingModel = new Borrowing();
$db = Database::getInstance();

try {
    // Get the borrowing request
    $borrowing = $borrowingModel->getBorrowingById($requestId);
    
    // Debug information
    error_log("Attempting to delete borrowing request ID: " . $requestId);
    error_log("Borrowing data: " . print_r($borrowing, true));

    // Check if borrowing request exists
    if (!$borrowing) {
        throw new Exception("Borrowing request not found.");
    }

    // Check if user has permission to delete this request
    if ($borrowing['borrower_id'] !== $auth->getUser()['user_id'] && 
        !in_array($auth->getUser()['role_id'], ['1', '2', '3'])) {
        throw new Exception("You don't have permission to delete this request.");
    }

    // Check if the request can be deleted (allow pending, rejected, and returned requests)
    if (!in_array($borrowing['status'], ['pending', 'rejected', 'returned'])) {
        throw new Exception("Only pending, rejected, or returned requests can be deleted.");
    }

    // Try direct database delete for more reliability
    $query = "DELETE FROM borrowing_requests WHERE request_id = :id";
    $result = $db->execute($query, ['id' => $requestId]);
    
    if ($result) {
        error_log("Successfully deleted borrowing request ID: " . $requestId);
        $_SESSION['success'] = "Borrowing request has been deleted successfully.";
    } else {
        error_log("Failed to delete borrowing request ID: " . $requestId);
        throw new Exception("Failed to delete the borrowing request.");
    }
} catch (Exception $e) {
    error_log("Error deleting borrowing request: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

// Redirect back to dashboard
if ($auth->isFaculty()) {
    header('Location: ../faculty/dashboard.php');
} else if ($auth->isStudentAssistant()) {
    header('Location: ../student/dashboard.php');
} else if ($auth->isAdmin()) {
    header('Location: ../admin/dashboard.php');
} else {
    header('Location: ../index.php');
}
exit;