<?php
// Set page title
$pageTitle = "Add Equipment";

// Include header
require_once '../includes/header.php';

// Require login
$auth->requireLogin();

// Check if user has permission to manage equipment
if (!$auth->canManageEquipment()) {
    Helpers::redirectWithMessage("../index.php", "You don't have permission to add equipment.", "danger");
    exit;
}

// Include required models
require_once '../models/Equipment.php';
require_once '../models/Room.php';

// Initialize models
$equipmentModel = new Equipment();
$roomModel = new Room();

// Get all categories and rooms for dropdown
$categories = $equipmentModel->getAllCategories();
$rooms = $roomModel->getAllRooms();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $equipment = [
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
    
    if (empty($equipment['equipment_name'])) {
        $errors[] = "Equipment name is required";
    }
    
    if (empty($equipment['serial_number'])) {
        $errors[] = "Serial number is required";
    } elseif ($equipmentModel->serialNumberExists($equipment['serial_number'])) {
        $errors[] = "Serial number already exists";
    }
    
    if (empty($equipment['category_id'])) {
        $errors[] = "Category is required";
    }
    
    // If no errors, add equipment
    if (empty($errors)) {
        $result = $equipmentModel->addEquipment($equipment);
        
        if ($result) {
            // Log the action
            Helpers::logAction("Added new equipment: " . $equipment['equipment_name']);
            
            // Redirect to equipment list with success message
            Helpers::redirectWithMessage("index.php", "Equipment added successfully", "success");
            exit;
        } else {
            $errors[] = "Failed to add equipment. Please try again.";
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>
            <i class="fas fa-plus-circle me-2"></i>Add Equipment
        </h1>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Equipment List
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
        <form action="add.php" method="POST" class="row g-3">
            <!-- Basic Information -->
            <div class="col-md-6">
                <label for="equipment_name" class="form-label">Equipment Name *</label>
                <input type="text" class="form-control" id="equipment_name" name="equipment_name" required
                    value="<?php echo isset($equipment['equipment_name']) ? htmlspecialchars($equipment['equipment_name']) : ''; ?>">
            </div>
            
            <div class="col-md-6">
                <label for="category_id" class="form-label">Category *</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>" 
                        <?php echo isset($equipment['category_id']) && $equipment['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-6">
                <label for="brand" class="form-label">Brand</label>
                <input type="text" class="form-control" id="brand" name="brand" 
                    value="<?php echo isset($equipment['brand']) ? htmlspecialchars($equipment['brand']) : ''; ?>">
            </div>
            
            <div class="col-md-6">
                <label for="model" class="form-label">Model</label>
                <input type="text" class="form-control" id="model" name="model" 
                    value="<?php echo isset($equipment['model']) ? htmlspecialchars($equipment['model']) : ''; ?>">
            </div>
            
            <div class="col-md-6">
                <label for="serial_number" class="form-label">Serial Number *</label>
                <input type="text" class="form-control" id="serial_number" name="serial_number" required
                    value="<?php echo isset($equipment['serial_number']) ? htmlspecialchars($equipment['serial_number']) : ''; ?>">
            </div>
            
            <div class="col-md-6">
                <label for="condition_status" class="form-label">Condition *</label>
                <select class="form-select" id="condition_status" name="condition_status" required>
                    <option value="new" <?php echo isset($equipment['condition_status']) && $equipment['condition_status'] == 'new' ? 'selected' : ''; ?>>New</option>
                    <option value="good" <?php echo isset($equipment['condition_status']) && $equipment['condition_status'] == 'good' ? 'selected' : ''; ?>>Good</option>
                    <option value="fair" <?php echo isset($equipment['condition_status']) && $equipment['condition_status'] == 'fair' ? 'selected' : ''; ?>>Fair</option>
                    <option value="poor" <?php echo isset($equipment['condition_status']) && $equipment['condition_status'] == 'poor' ? 'selected' : ''; ?>>Poor</option>
                </select>
            </div>
            
            <!-- Procurement Information -->
            <div class="col-md-4">
                <label for="acquisition_date" class="form-label">Acquisition Date</label>
                <input type="date" class="form-control" id="acquisition_date" name="acquisition_date" 
                    value="<?php echo isset($equipment['acquisition_date']) ? htmlspecialchars($equipment['acquisition_date']) : ''; ?>">
            </div>
            
            <div class="col-md-4">
                <label for="cost" class="form-label">Cost (PHP)</label>
                <input type="number" step="0.01" class="form-control" id="cost" name="cost" 
                    value="<?php echo isset($equipment['cost']) ? htmlspecialchars($equipment['cost']) : ''; ?>">
            </div>
            
            <div class="col-md-4">
                <label for="supplier" class="form-label">Supplier</label>
                <input type="text" class="form-control" id="supplier" name="supplier" 
                    value="<?php echo isset($equipment['supplier']) ? htmlspecialchars($equipment['supplier']) : ''; ?>">
            </div>
            
            <!-- Location Information -->
            <div class="col-md-6">
                <label for="room_id" class="form-label">Assign to Room</label>
                <select class="form-select" id="room_id" name="room_id">
                    <option value="">Not Assigned</option>
                    <?php foreach ($rooms as $room): ?>
                    <option value="<?php echo $room['room_id']; ?>" 
                        <?php echo isset($equipment['room_id']) && $equipment['room_id'] == $room['room_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($room['room_name']); ?> (<?php echo htmlspecialchars($room['building'] . ' - ' . $room['room_number']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Description -->
            <div class="col-12">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($equipment['description']) ? htmlspecialchars($equipment['description']) : ''; ?></textarea>
            </div>
            
            <div class="col-12 mt-4">
                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Equipment
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