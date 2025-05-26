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

// Check if user is admin, redirect if not
if (!$auth->isAdmin()) {
    header('Location: ../access-denied.php');
    exit;
}

// Include required models
require_once __DIR__ . '/../models/Equipment.php';

// Initialize models
$equipmentModel = new Equipment();
$db = Database::getInstance();

// Initialize variables for messages
$messages = [];
$success = false;

// Check for POST request with equipment_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipment_id'])) {
    $equipmentId = $_POST['equipment_id'];
    
    // Log deletion attempt for debugging
    error_log("Attempting to force delete equipment with ID: " . $equipmentId);
    
    // Get equipment details before deletion (for notification)
    $equipment = $equipmentModel->getEquipmentById($equipmentId);
    
    if ($equipment) {
        error_log("Found equipment: " . $equipment['name'] . " with ID: " . $equipmentId);
        
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Check and delete related records from borrowing_requests
            $query = "SELECT COUNT(*) as count FROM borrowing_requests WHERE equipment_id = :equipment_id";
            $borrowingCount = $db->single($query, ['equipment_id' => $equipmentId]);
            
            if ($borrowingCount && $borrowingCount['count'] > 0) {
                error_log("Found {$borrowingCount['count']} borrowing requests for equipment ID {$equipmentId}");
                $messages[] = "Found {$borrowingCount['count']} borrowing requests referencing this equipment.";
                
                $deleteQuery = "DELETE FROM borrowing_requests WHERE equipment_id = :equipment_id";
                $result = $db->execute($deleteQuery, ['equipment_id' => $equipmentId]);
                
                if ($result) {
                    error_log("Deleted borrowing requests for equipment ID {$equipmentId}");
                    $messages[] = "Successfully deleted related borrowing requests.";
                } else {
                    error_log("Failed to delete borrowing requests for equipment ID {$equipmentId}");
                    $messages[] = "Failed to delete related borrowing requests.";
                    throw new Exception("Failed to delete related borrowing requests");
                }
            }
            
            // Check and delete related records from maintenance_requests
            $query = "SELECT COUNT(*) as count FROM maintenance_requests WHERE equipment_id = :equipment_id";
            $maintenanceCount = $db->single($query, ['equipment_id' => $equipmentId]);
            
            if ($maintenanceCount && $maintenanceCount['count'] > 0) {
                error_log("Found {$maintenanceCount['count']} maintenance requests for equipment ID {$equipmentId}");
                $messages[] = "Found {$maintenanceCount['count']} maintenance requests referencing this equipment.";
                
                $deleteQuery = "DELETE FROM maintenance_requests WHERE equipment_id = :equipment_id";
                $result = $db->execute($deleteQuery, ['equipment_id' => $equipmentId]);
                
                if ($result) {
                    error_log("Deleted maintenance requests for equipment ID {$equipmentId}");
                    $messages[] = "Successfully deleted related maintenance requests.";
                } else {
                    error_log("Failed to delete maintenance requests for equipment ID {$equipmentId}");
                    $messages[] = "Failed to delete related maintenance requests.";
                    throw new Exception("Failed to delete related maintenance requests");
                }
            }
            
            // Check and delete related records from equipment_movements
            $query = "SELECT COUNT(*) as count FROM equipment_movements WHERE equipment_id = :equipment_id";
            $movementCount = $db->single($query, ['equipment_id' => $equipmentId]);
            
            if ($movementCount && $movementCount['count'] > 0) {
                error_log("Found {$movementCount['count']} movement records for equipment ID {$equipmentId}");
                $messages[] = "Found {$movementCount['count']} movement records referencing this equipment.";
                
                $deleteQuery = "DELETE FROM equipment_movements WHERE equipment_id = :equipment_id";
                $result = $db->execute($deleteQuery, ['equipment_id' => $equipmentId]);
                
                if ($result) {
                    error_log("Deleted movement records for equipment ID {$equipmentId}");
                    $messages[] = "Successfully deleted related movement records.";
                } else {
                    error_log("Failed to delete movement records for equipment ID {$equipmentId}");
                    $messages[] = "Failed to delete related movement records.";
                    throw new Exception("Failed to delete related movement records");
                }
            }
            
            // Now delete the equipment itself
            $query = "DELETE FROM equipment WHERE equipment_id = :equipment_id";
            $result = $db->execute($query, ['equipment_id' => $equipmentId]);
            
            if ($result) {
                error_log("Successfully deleted equipment with ID: " . $equipmentId);
                $messages[] = "Successfully deleted the equipment.";
                $success = true;
                
                // Commit transaction
                $db->commit();
                
                // Set success notification
                $_SESSION['notification'] = [
                    'type' => 'success',
                    'message' => "Equipment '{$equipment['name']}' has been deleted successfully. " . implode(" ", $messages)
                ];
            } else {
                error_log("Failed to delete equipment with ID: " . $equipmentId);
                $messages[] = "Failed to delete the equipment itself.";
                throw new Exception("Failed to delete the equipment itself");
            }
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            
            error_log("Exception when force deleting equipment: " . $e->getMessage());
            $_SESSION['notification'] = [
                'type' => 'danger',
                'message' => "Error deleting equipment: " . $e->getMessage() . " Details: " . implode(" ", $messages)
            ];
        }
    } else {
        error_log("Equipment not found with ID: " . $equipmentId);
        // Equipment not found - Set notification
        $_SESSION['notification'] = [
            'type' => 'warning',
            'message' => "Equipment not found."
        ];
    }
    
    // Redirect back to equipment page
    header('Location: equipment.php');
    exit;
} else {
    // Invalid request - Redirect to equipment page
    $_SESSION['notification'] = [
        'type' => 'danger',
        'message' => "Invalid request. Equipment ID is required."
    ];
    header('Location: equipment.php');
    exit;
} 