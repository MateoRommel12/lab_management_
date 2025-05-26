<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../utils/Helpers.php';
require_once __DIR__ . '/../config/Database.php';

// Initialize Auth
$auth = Auth::getInstance();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    $_SESSION['error'] = "You must be logged in to perform this action.";
    header('Location: ../login.php');
    exit;
}

// Get database connection
$db = Database::getInstance();

// Check if request ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No borrowing request specified.";
    header('Location: ../student/dashboard.php');
    exit;
}

$requestId = $_GET['id'];
error_log("Processing direct return for request ID: " . $requestId);

// Get the borrowing request
$query = "SELECT br.*, e.equipment_id FROM borrowing_requests br 
         JOIN equipment e ON br.equipment_id = e.equipment_id 
         WHERE br.request_id = :requestId";
$borrowing = $db->single($query, ['requestId' => $requestId]);

// Check if borrowing exists
if (!$borrowing) {
    $_SESSION['error'] = "Borrowing request not found.";
    header('Location: ../student/dashboard.php');
    exit;
}

// Check if user owns the borrowing or is an admin/technician
$currentUser = $auth->getUser();
if ($borrowing['borrower_id'] != $currentUser['user_id'] && 
    !$auth->isAdmin() && !$auth->isLabTechnician() && !$auth->isFaculty()) {
    $_SESSION['error'] = "You are not authorized to return this equipment.";
    header('Location: ../faculty/dashboard.php');
    exit;
}

// Check if the borrowing is in the correct status
if ($borrowing['status'] !== 'approved' && $borrowing['status'] !== 'borrowed') {
    $_SESSION['error'] = "This item cannot be returned as it is not currently borrowed.";
    header('Location: ../student/dashboard.php');
    exit;
}

// Process the return directly
try {
    // Begin database transaction
    $db->beginTransaction();
    
    // 1. Update borrowing status
    $updateBorrowingQuery = "UPDATE borrowing_requests 
                           SET status = 'returned', 
                               actual_return_date = NOW() 
                           WHERE request_id = :requestId";
    
    $result1 = $db->execute($updateBorrowingQuery, ['requestId' => $requestId]);
    if (!$result1) {
        throw new Exception("Failed to update borrowing status");
    }
    
    // 2. Update equipment status
    $updateEquipmentQuery = "UPDATE equipment 
                           SET status = 'active' 
                           WHERE equipment_id = :equipmentId";
    
    $result2 = $db->execute($updateEquipmentQuery, ['equipmentId' => $borrowing['equipment_id']]);
    if (!$result2) {
        throw new Exception("Failed to update equipment status");
    }
    
    // Commit transaction
    $db->commit();
    
    $_SESSION['success'] = "Equipment has been successfully returned.";
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollback();
    
    error_log("Direct return error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while processing the return: " . $e->getMessage();
}

// Redirect back to dashboard
if ($auth->isFaculty()) {
    header('Location: ../faculty/dashboard.php');
} else if ($auth->isStudentAssistant()) {
    header('Location: ../student/dashboard.php');
} else {
    header('Location: ../admin/dashboard.php');
}
exit;
?> 