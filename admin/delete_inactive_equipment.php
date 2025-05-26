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

// Check if user is admin, redirect if not
if (!$auth->isAdmin()) {
    header('Location: ../access-denied.php');
    exit;
}

// Required for CSRF protection
if (!isset($_POST['confirm_delete']) || $_POST['confirm_delete'] !== 'yes') {
    $_SESSION['notification'] = [
        'type' => 'danger',
        'message' => "Invalid request. Please use the proper form to delete inactive equipment."
    ];
    header('Location: equipment.php');
    exit;
}

// Include required models
require_once __DIR__ . '/../models/Equipment.php';

// Initialize models
$equipmentModel = new Equipment();

// Get all inactive equipment
$inactiveEquipment = $equipmentModel->getEquipmentByStatus('inactive');

// Counter for deleted items
$deleteCount = 0;

// Delete each inactive equipment
foreach ($inactiveEquipment as $item) {
    if ($equipmentModel->delete($item['equipment_id'])) {
        $deleteCount++;
    }
}

// Set notification based on results
if ($deleteCount > 0) {
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => "Successfully deleted {$deleteCount} inactive equipment items."
    ];
} else if (count($inactiveEquipment) === 0) {
    $_SESSION['notification'] = [
        'type' => 'info',
        'message' => "No inactive equipment found to delete."
    ];
} else {
    $_SESSION['notification'] = [
        'type' => 'warning',
        'message' => "No equipment was deleted. There might be an issue with the deletion process."
    ];
}

// Redirect back to equipment page
header('Location: equipment.php');
exit; 