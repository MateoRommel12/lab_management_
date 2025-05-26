<?php
// Set page title
$pageTitle = "Add Room";

// Include header
require_once '../includes/header.php';

// Require login
$auth->requireLogin();

// Check if user has permission to manage rooms
if (!$auth->canManageRooms()) {
    Helpers::redirectWithMessage("../index.php", "You don't have permission to add rooms.", "danger");
    exit;
}

// Include required models
require_once '../models/Room.php';
require_once '../models/User.php';

// Initialize models
$roomModel = new Room();
$userModel = new User();

// Get all lab technicians for dropdown
$technicians = $userModel->getUsersByRole('Lab Technician');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $room = [
        'room_name' => trim($_POST['room_name']),
        'building' => trim($_POST['building']),
        'floor' => trim($_POST['floor']),
        'room_number' => trim($_POST['room_number']),
        'capacity' => !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null,
        'lab_technician_id' => !empty($_POST['lab_technician_id']) ? (int)$_POST['lab_technician_id'] : null,
        'status' => trim($_POST['status'])
    ];
    
    // Validation
    $errors = [];
    
    if (empty($room['room_name'])) {
        $errors[] = "Room name is required";
    }
    
    if (empty($room['building'])) {
        $errors[] = "Building is required";
    }
    
    if (empty($room['floor'])) {
        $errors[] = "Floor is required";
    }
    
    if (empty($room['room_number'])) {
        $errors[] = "Room number is required";
    } elseif ($roomModel->roomNumberExists($room['building'], $room['room_number'])) {
        $errors[] = "Room number already exists in this building";
    }
    
    if (empty($room['status'])) {
        $errors[] = "Status is required";
    }
    
    // If no errors, add room
    if (empty($errors)) {
        $result = $roomModel->addRoom($room);
        
        if ($result) {
            // Log the action
            Helpers::logAction("Added new room: " . $room['room_name']);
            
            // Redirect to room list with success message
            Helpers::redirectWithMessage("index.php", "Room added successfully", "success");
            exit;
        } else {
            $errors[] = "Failed to add room. Please try again.";
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>
            <i class="fas fa-plus-circle me-2"></i>Add Room
        </h1>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Room List
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo $error; ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Room Information</h5>
    </div>
    <div class="card-body">
        <form action="add.php" method="POST" class="row g-3">
            <!-- Basic Information -->
            <div class="col-md-6">
                <label for="room_name" class="form-label">Room Name *</label>
                <input type="text" class="form-control" id="room_name" name="room_name" required
                    value="<?php echo isset($room['room_name']) ? htmlspecialchars($room['room_name']) : ''; ?>">
                <div class="form-text">E.g., Computer Lab A, Physics Laboratory, etc.</div>
            </div>
            
            <div class="col-md-6">
                <label for="building" class="form-label">Building *</label>
                <input type="text" class="form-control" id="building" name="building" required
                    value="<?php echo isset($room['building']) ? htmlspecialchars($room['building']) : ''; ?>">
                <div class="form-text">E.g., Science Building, Main Building, etc.</div>
            </div>
            
            <div class="col-md-6">
                <label for="floor" class="form-label">Floor *</label>
                <input type="text" class="form-control" id="floor" name="floor" required
                    value="<?php echo isset($room['floor']) ? htmlspecialchars($room['floor']) : ''; ?>">
                <div class="form-text">E.g., Ground Floor, 1st Floor, Basement, etc.</div>
            </div>
            
            <div class="col-md-6">
                <label for="room_number" class="form-label">Room Number *</label>
                <input type="text" class="form-control" id="room_number" name="room_number" required
                    value="<?php echo isset($room['room_number']) ? htmlspecialchars($room['room_number']) : ''; ?>">
                <div class="form-text">E.g., 101, A-201, etc.</div>
            </div>
            
            <div class="col-md-6">
                <label for="capacity" class="form-label">Capacity</label>
                <input type="number" class="form-control" id="capacity" name="capacity" min="1"
                    value="<?php echo isset($room['capacity']) ? htmlspecialchars($room['capacity']) : ''; ?>">
                <div class="form-text">Number of people the room can accommodate (optional)</div>
            </div>
            
            <div class="col-md-6">
                <label for="status" class="form-label">Status *</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="">Select Status</option>
                    <option value="active" <?php echo isset($room['status']) && $room['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo isset($room['status']) && $room['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="under maintenance" <?php echo isset($room['status']) && $room['status'] == 'under maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                </select>
            </div>
            
            <div class="col-md-6">
                <label for="lab_technician_id" class="form-label">Lab Technician</label>
                <select class="form-select" id="lab_technician_id" name="lab_technician_id">
                    <option value="">Select Lab Technician (Optional)</option>
                    <?php foreach ($technicians as $tech): ?>
                    <option value="<?php echo $tech['user_id']; ?>" 
                        <?php echo isset($room['lab_technician_id']) && $room['lab_technician_id'] == $tech['user_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-12 mt-4">
                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Room
                </button>
                <a href="index.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>