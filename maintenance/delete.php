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
require_once __DIR__ . '/../models/Maintenance.php';
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
    $_SESSION['error'] = "Invalid maintenance report ID.";
    header('Location: ../maintenance/my_reports.php');
    exit;
}

$maintenanceId = (int)$_GET['id'];

// Initialize Maintenance model
$maintenanceModel = new Maintenance();
$db = Database::getInstance();

try {
    // Get the maintenance report
    $report = $maintenanceModel->getMaintenanceWithDetails($maintenanceId);
    
    // Debug information
    error_log("Attempting to delete maintenance report ID: " . $maintenanceId);
    error_log("Maintenance data: " . print_r($report, true));

    // Check if maintenance report exists
    if (!$report) {
        throw new Exception("Maintenance report not found.");
    }

    // Check if user has permission to delete this report
    if ($report['reported_by'] !== $auth->getUser()['user_id'] && 
        !in_array($auth->getUser()['role_id'], ['1', '2', '3'])) {
        throw new Exception("You don't have permission to delete this report.");
    }

    // Check if the report can be deleted (only completed reports can be deleted)
    if ($report['status'] !== 'completed') {
        throw new Exception("Only completed maintenance reports can be deleted.");
    }

    // Try direct database delete for more reliability
    $query = "DELETE FROM maintenance_requests WHERE maintenance_id = :id";
    $result = $db->execute($query, ['id' => $maintenanceId]);
    
    if ($result) {
        error_log("Successfully deleted maintenance report ID: " . $maintenanceId);
        $_SESSION['success'] = "Maintenance report has been deleted successfully.";
    } else {
        error_log("Failed to delete maintenance report ID: " . $maintenanceId);
        throw new Exception("Failed to delete the maintenance report.");
    }
} catch (Exception $e) {
    error_log("Error deleting maintenance report: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

// Redirect back to maintenance reports
header('Location: ../maintenance/my_reports.php');
exit; 