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

// Set page title
$pageTitle = "Add Room";
$currentPage = 'rooms';

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/Room.php';

// Initialize models
$roomModel = new Room();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $roomData = [
        'room_name' => trim($_POST['room_name'] ?? ''),
        'room_number' => trim($_POST['room_number'] ?? ''),
        'building' => trim($_POST['building'] ?? ''),
        'floor' => trim($_POST['floor'] ?? ''),
        'capacity' => (int)($_POST['capacity'] ?? 0),
        'room_type' => trim($_POST['room_type'] ?? ''),
        'status' => $_POST['status'] ?? 'active',
        'description' => trim($_POST['description'] ?? '')
    ];
    
    // Validate form data
    $errors = [];
    
    // Check if room name is provided
    if (empty($roomData['room_name'])) {
        $errors[] = "Room name is required";
    }
    
    // Check if room number is provided and unique
    if (empty($roomData['room_number'])) {
        $errors[] = "Room number is required";
    } elseif ($roomModel->roomNumberExists($roomData['room_number'])) {
        $errors[] = "Room number already exists";
    }
    
    // Check if building is provided
    if (empty($roomData['building'])) {
        $errors[] = "Building is required";
    }
    
    // Check if floor is provided
    if (empty($roomData['floor'])) {
        $errors[] = "Floor is required";
    }
    
    // If no errors, create room
    if (empty($errors)) {
        if ($roomModel->create($roomData)) {
            // Log the action
            Helpers::logAction("Added new room: " . $roomData['room_name']);
            
            Helpers::redirectWithMessage("rooms.php", "Room added successfully.", "success");
            exit;
        } else {
            $errors[] = "Failed to add room";
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1>
            <i class="fas fa-plus me-2"></i>Add New Room
        </h1>
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
        <form action="add_room.php" method="POST" class="row g-3">
            <!-- Room Name -->
            <div class="col-md-6">
                <label for="room_name" class="form-label">Room Name *</label>
                <input type="text" class="form-control" id="room_name" name="room_name" required
                    value="<?php echo isset($_POST['room_name']) ? htmlspecialchars($_POST['room_name']) : ''; ?>">
            </div>
            
            <!-- Room Number -->
            <div class="col-md-6">
                <label for="room_number" class="form-label">Room Number *</label>
                <input type="text" class="form-control" id="room_number" name="room_number" required
                    value="<?php echo isset($_POST['room_number']) ? htmlspecialchars($_POST['room_number']) : ''; ?>">
            </div>
            
            <!-- Building -->
            <div class="col-md-6">
                <label for="building" class="form-label">Building *</label>
                <input type="text" class="form-control" id="building" name="building" required
                    value="<?php echo isset($_POST['building']) ? htmlspecialchars($_POST['building']) : ''; ?>">
            </div>
            
            <!-- Floor -->
            <div class="col-md-6">
                <label for="floor" class="form-label">Floor *</label>
                <input type="text" class="form-control" id="floor" name="floor" required
                    value="<?php echo isset($_POST['floor']) ? htmlspecialchars($_POST['floor']) : ''; ?>">
            </div>
            
            <!-- Capacity -->
            <div class="col-md-6">
                <label for="capacity" class="form-label">Capacity</label>
                <input type="number" class="form-control" id="capacity" name="capacity" min="0"
                    value="<?php echo isset($_POST['capacity']) ? htmlspecialchars($_POST['capacity']) : ''; ?>">
            </div>
            
            <!-- Room Type -->
            <div class="col-md-6">
                <label for="room_type" class="form-label">Room Type</label>
                <select class="form-select" id="room_type" name="room_type">
                    <option value="classroom" <?php echo (isset($_POST['room_type']) && $_POST['room_type'] === 'classroom') ? 'selected' : ''; ?>>Classroom</option>
                    <option value="laboratory" <?php echo (isset($_POST['room_type']) && $_POST['room_type'] === 'laboratory') ? 'selected' : ''; ?>>Laboratory</option>
                    <option value="office" <?php echo (isset($_POST['room_type']) && $_POST['room_type'] === 'office') ? 'selected' : ''; ?>>Office</option>
                    <option value="conference" <?php echo (isset($_POST['room_type']) && $_POST['room_type'] === 'conference') ? 'selected' : ''; ?>>Conference Room</option>
                    <option value="other" <?php echo (isset($_POST['room_type']) && $_POST['room_type'] === 'other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <!-- Status -->
            <div class="col-md-6">
                <label for="status" class="form-label">Status *</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <!-- Description -->
            <div class="col-md-12">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>
            
            <div class="col-12 mt-4">
                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Add Room
                </button>
                <a href="rooms.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 