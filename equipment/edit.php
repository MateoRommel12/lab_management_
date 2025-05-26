<?php
// Set page title
$pageTitle = "Edit Equipment";

// Include header
require_once '../includes/header.php';

// Require login
$auth->requireLogin();

// Check if user has permission to manage equipment
if (!$auth->canManageEquipment()) {
    Helpers::redirectWithMessage("../index.php", "You don't have permission to edit equipment.", "danger");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    Helpers::redirectWithMessage("index.php", "Invalid equipment ID", "danger");
    exit;
}

$equipmentId = (int)$_GET['id'];

// Include required models
require_once '../models/Equipment.php';
require_once '../models/Room.php';

// Initialize models
$equipmentModel = new Equipment();
$roomModel = new Room();

// Get equipment details
$equipment = $equipmentModel->getEquipmentById($equipmentId);

if (!$equipment) {
    Helpers::redirectWithMessage("index.php", "Equipment not found", "danger");
    exit;
}

// Get all categories and rooms for dropdown
$categories = $equipmentModel->getAllCategories();
$rooms = $roomModel->getAllRooms();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $updatedEquipment = [
        'equipment_id' => $equipmentId,
        'equipment_name' => trim($_POST['equipment_name']),
        'description' => trim($_POST['description']),
        'model' => trim($_POST['model']),
        'brand' => trim($_POST['brand']),
        'serial_number' => trim($_POST['serial_number']),
        'category_id' => (int)$_POST['category_id'],
        'acquisition_date' => trim($_POST['acquisition_date']),
        'cost' => (float)$_POST['cost'],
        'supplier' => trim($_POST['supplier']),
        'condition_status' => trim($_POST['condition_status']),
        'room_id' => !empty($_POST['room_id']) ? (int)$_POST['room_id'] : null
    ];
    
    // Validation
    $errors = [];
    
    if (empty($updatedEquipment['equipment_name'])) {
        $errors[] = "Equipment name is required";
    }
    
    if (empty($updatedEquipment['serial_number'])) {
        $errors[] = "Serial number is required";
    } elseif ($updatedEquipment['serial_number'] !== $equipment['serial_number'] && 
              $equipmentModel->serialNumberExists($updatedEquipment['serial_number'])) {
        $errors[] = "Serial number already exists";
    }
    
    if (empty($updatedEquipment['category_id'])) {
        $errors[] = "Category is required";
    }
    
    // If room changed, track movement
    $roomChanged = ($updatedEquipment['room_id'] != $equipment['room_id']);
    
    // If no errors, update equipment
    if (empty($errors)) {
        $result = $equipmentModel->updateEquipment($updatedEquipment);
        
        if ($result) {
            // Log the action
            Helpers::logAction("Updated equipment: " . $updatedEquipment['equipment_name']);
            
            // If room changed, record equipment movement
            if ($roomChanged) {
                $movementData = [
                    'equipment_id' => $equipmentId,
                    'from_room_id' => $equipment['room_id'],
                    'to_room_id' => $updatedEquipment['room_id'],
                    'moved_by' => $auth->getUserId(),
                    'reason' => "Updated equipment location via edit form"
                ];
                
                $equipmentModel->recordEquipmentMovement($movementData);
                
                // Log the movement
                Helpers::logAction("Moved equipment: " . $updatedEquipment['equipment_name'] . " to a new location");
            }
            
            // Redirect to equipment view with success message
            Helpers::redirectWithMessage("view.php?id=$equipmentId", "Equipment updated successfully", "success");
            exit;
        } else {
            $errors[] = "Failed to update equipment. Please try again.";
        }
    }
} else {
    // Pre-fill form with existing equipment data
    $updatedEquipment = $equipment;
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>
            <i class="fas fa-edit me-2"></i>Edit Equipment
        </h1>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="view.php?id=<?php echo $equipmentId; ?>" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Equipment Details
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
        <h5 class="card-title mb-0">Equipment Information</h5>
    </div>
    <div class="card-body">
        <form action="edit.php?id=<?php echo $equipmentId; ?>" method="POST" class="row g-3">
            <!-- Basic Information -->
            <div class="col-md-6">
                <label for="equipment_name" class="form-label">Equipment Name *</label>
                <input type="text" class="form-control" id="equipment_name" name="equipment_name" required
                    value="<?php echo htmlspecialchars($updatedEquipment['equipment_name']); ?>">
            </div>
            
            <div class="col-md-6">
                <label for="category_id" class="form-label">Category *</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>" 
                        <?php echo $updatedEquipment['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-6">
                <label for="brand" class="form-label">Brand</label>
                <input type="text" class="form-control" id="brand" name="brand" 
                    value="<?php echo htmlspecialchars($updatedEquipment['brand']); ?>">
            </div>
            
            <div class="col-md-6">
                <label for="model" class="form-label">Model</label>
                <input type="text" class="form-control" id="model" name="model" 
                    value="<?php echo htmlspecialchars($updatedEquipment['model']); ?>">
            </div>
            
            <div class="col-md-6">
                <label for="serial_number" class="form-label">Serial Number *</label>
                <input type="text" class="form-control" id="serial_number" name="serial_number" required
                    value="<?php echo htmlspecialchars($updatedEquipment['serial_number']); ?>">
            </div>
            
            <div class="col-md-6">
                <label for="condition_status" class="form-label">Condition *</label>
                <select class="form-select" id="condition_status" name="condition_status" required>
                    <option value="new" <?php echo $updatedEquipment['condition_status'] == 'new' ? 'selected' : ''; ?>>New</option>
                    <option value="good" <?php echo $updatedEquipment['condition_status'] == 'good' ? 'selected' : ''; ?>>Good</option>
                    <option value="fair" <?php echo $updatedEquipment['condition_status'] == 'fair' ? 'selected' : ''; ?>>Fair</option>
                    <option value="poor" <?php echo $updatedEquipment['condition_status'] == 'poor' ? 'selected' : ''; ?>>Poor</option>
                    <option value="under maintenance" <?php echo $updatedEquipment['condition_status'] == 'under maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                    <option value="disposed" <?php echo $updatedEquipment['condition_status'] == 'disposed' ? 'selected' : ''; ?>>Disposed</option>
                </select>
            </div>
            
            <!-- Procurement Information -->
            <div class="col-md-4">
                <label for="acquisition_date" class="form-label">Acquisition Date</label>
                <input type="date" class="form-control" id="acquisition_date" name="acquisition_date" 
                    value="<?php echo htmlspecialchars($updatedEquipment['acquisition_date']); ?>">
            </div>
            
            <div class="col-md-4">
                <label for="cost" class="form-label">Cost (PHP)</label>
                <input type="number" step="0.01" class="form-control" id="cost" name="cost" 
                    value="<?php echo htmlspecialchars($updatedEquipment['cost']); ?>">
            </div>
            
            <div class="col-md-4">
                <label for="supplier" class="form-label">Supplier</label>
                <input type="text" class="form-control" id="supplier" name="supplier" 
                    value="<?php echo htmlspecialchars($updatedEquipment['supplier']); ?>">
            </div>
            
            <!-- Location Information -->
            <div class="col-md-6">
                <label for="room_id" class="form-label">Assign to Room</label>
                <select class="form-select" id="room_id" name="room_id">
                    <option value="">Not Assigned</option>
                    <?php foreach ($rooms as $room): ?>
                    <option value="<?php echo $room['room_id']; ?>" 
                        <?php echo $updatedEquipment['room_id'] == $room['room_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($room['room_name']); ?> (<?php echo htmlspecialchars($room['building'] . ' - ' . $room['room_number']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($updatedEquipment['room_id'])): ?>
                <div class="form-text text-info">
                    <i class="fas fa-info-circle me-1"></i>
                    Changing the room will record a movement in the equipment's history.
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Description -->
            <div class="col-12">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($updatedEquipment['description']); ?></textarea>
            </div>
            
            <div class="col-12 mt-4">
                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Equipment
                </button>
                <a href="view.php?id=<?php echo $equipmentId; ?>" class="btn btn-outline-secondary ms-2">
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