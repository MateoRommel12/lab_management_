<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../utils/Helpers.php';

// Initialize Auth
$auth = Auth::getInstance();

// Check if user is lab technician, redirect if not
if (!$auth->isLabTechnician() && !$auth->isAdmin()) {
    header('Location: ../access-denied.php');
    exit;
}

// Get maintenance ID from URL
$maintenanceId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($maintenanceId <= 0) {
    $_SESSION['error_message'] = "Invalid maintenance request ID.";
    header('Location: pending.php');
    exit;
}

// Include required model
require_once __DIR__ . '/../models/Maintenance.php';
$maintenanceModel = new Maintenance();

// Assign maintenance request to current technician
$userId = $auth->getUser()['user_id'];
$result = $maintenanceModel->assignMaintenanceToTechnician($maintenanceId, $userId);

if ($result) {
    $_SESSION['success_message'] = "Maintenance request assigned to you successfully.";
} else {
    $_SESSION['error_message'] = "Failed to assign maintenance request. It may have already been assigned or doesn't exist.";
}

// Redirect back to pending page
header('Location: ' . Helpers::url('maintenance/pending.php'));
exit;
?> 