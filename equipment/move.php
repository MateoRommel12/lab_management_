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

// Require login
$auth->requireLogin();

// Check if user has permission to manage equipment
if (!$auth->canManageEquipment()) {
    Helpers::redirectWithMessage("index.php", "You don't have permission to move equipment.", "danger");
    exit;
}

// Include required models
require_once '../models/Equipment.php';
require_once '../models/Room.php';

// Initialize models
$equipmentModel = new Equipment();
$roomModel = new Room();

// GET request shows the form
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $equipmentId = (int)$_GET['id'];
    
    // Get equipment details
    $equipment = $equipmentModel->getEquipmentById($equipmentId);
    if (!$equipment) {
        Helpers::redirectWithMessage("index.php", "Equipment not found.", "danger");
        exit;
    }
    
    // Get all rooms for dropdown
    $rooms = $roomModel->getAllRooms();
    
    // Set page title
    $pageTitle = "Move Equipment";
    
    // Include header
    require_once '../includes/header.php';
    ?>
    
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>
                <i class="fas fa-exchange-alt me-2"></i>Move Equipment
            </h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Equipment List
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Move Equipment Form</h5>
        </div>
        <div class="card-body">
            <form action="move.php" method="POST" class="row g-3">
                <input type="hidden" name="equipment_id" value="<?php echo $equipmentId; ?>">
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Equipment</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($equipment['name']); ?>" readonly>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Current Location</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($equipment['location'] ?? 'Not Assigned'); ?>" readonly>
                    </div>
                </div>
                
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="to_room_id" class="form-label">New Location *</label>
                        <select class="form-select" id="to_room_id" name="to_room_id" required>
                            <option value="">Select Room</option>
                            <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['room_id']; ?>">
                                <?php echo htmlspecialchars($room['room_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Movement *</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Move Equipment
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php
    // Include footer
    require_once '../includes/footer.php';
    exit;
}
// POST request processes the form
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipmentId = $_POST['equipment_id'] ?? null;
    $toRoomId = $_POST['to_room_id'] ?? null;
    $movedBy = $_POST['moved_by'] ?? ($_POST['fallback_moved_by'] ?? null);
    $movementDate = $_POST['movement_date'] ?? null;
    $reason = $_POST['reason'] ?? '';
    
    // Debug output
    error_log("Move Equipment Request Data: " . print_r($_POST, true));

    if (!$equipmentId || !$toRoomId) {
        Helpers::redirectWithMessage("index.php", "Missing required information (Equipment ID or Room ID).", "danger");
        exit;
    }
    
    if (!$movedBy) {
        // If moved_by is still not set, default to current user
        $movedBy = $auth->getUserId();
        if (!$movedBy) {
            Helpers::redirectWithMessage("index.php", "Cannot determine who is moving the equipment. Please try again.", "danger");
            exit;
        }
    }

    // Get current equipment location
    $equipment = $equipmentModel->getEquipmentById($equipmentId);
    if (!$equipment) {
        Helpers::redirectWithMessage("index.php", "Equipment not found.", "danger");
        exit;
    }

    // Handle from_room_id properly - make sure it's a valid integer or null
    $fromRoomId = null;
    if (isset($equipment['location']) && is_numeric($equipment['location']) && $equipment['location'] > 0) {
        $fromRoomId = (int)$equipment['location'];
    }

    // Prepare movement data
    $movementData = [
        'equipment_id' => $equipmentId,
        'from_room_id' => $fromRoomId,
        'to_room_id' => $toRoomId,
        'moved_by' => $movedBy,
        'movement_date' => $movementDate ?: date('Y-m-d H:i:s'),
        'reason' => $reason
    ];
    
    error_log("Movement Data: " . print_r($movementData, true));

    // Move the equipment
    if ($equipmentModel->moveEquipment($equipmentId, $toRoomId, $movementData)) {
        Helpers::redirectWithMessage("index.php", "Equipment moved successfully.", "success");
    } else {
        Helpers::redirectWithMessage("index.php", "Failed to move equipment. Check logs for details.", "danger");
    }
    exit;
}
// If not GET with ID or POST, redirect to index
else {
    Helpers::redirectWithMessage("index.php", "Invalid request method.", "danger");
    exit;
}
?> 