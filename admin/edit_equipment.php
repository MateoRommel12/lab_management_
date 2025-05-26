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
$pageTitle = "Edit Equipment";
$currentPage = 'equipment';

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/Category.php';

// Initialize models
$equipmentModel = new Equipment();
$categoryModel = new Category();

// Get all categories for the dropdown
$categories = $categoryModel->getAllCategories();

// Get equipment ID from URL
$equipmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get equipment details
$equipment = $equipmentModel->getEquipmentById($equipmentId);

// Check if equipment exists
if (!$equipment) {
    Helpers::redirectWithMessage("equipment.php", "Equipment not found.", "danger");
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $equipmentData = [
        'id' => $equipmentId,
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'serial_number' => trim($_POST['serial_number'] ?? ''),
        'model' => trim($_POST['model'] ?? ''),
        'manufacturer' => trim($_POST['manufacturer'] ?? ''),
        'purchase_date' => $_POST['purchase_date'] ?? null,
        'warranty_expiry' => $_POST['warranty_expiry'] ?? null,
        'status' => $_POST['status'] ?? 'active',
        'location' => trim($_POST['location'] ?? ''),
        'equipment_condition' => $_POST['equipment_condition'] ?? 'new',
        'notes' => trim($_POST['notes'] ?? ''),
        'last_maintenance_date' => $_POST['last_maintenance_date'] ?? null,
        'category_id' => $_POST['category_id'] ?? null
    ];
    
    // Validate form data
    $errors = [];
    
    // Check if name is provided
    if (empty($equipmentData['name'])) {
        $errors[] = "Equipment name is required";
    }
    
    // Check if serial number is provided and unique (excluding current equipment)
    if (empty($equipmentData['serial_number'])) {
        $errors[] = "Serial number is required";
    } elseif ($equipmentModel->serialNumberExists($equipmentData['serial_number'], $equipmentId)) {
        $errors[] = "Serial number already exists";
    }
    
    // Check if model is provided
    if (empty($equipmentData['model'])) {
        $errors[] = "Model is required";
    }
    
    // Check if manufacturer is provided
    if (empty($equipmentData['manufacturer'])) {
        $errors[] = "Manufacturer is required";
    }
    
    // Check if location is provided
    if (empty($equipmentData['location'])) {
        $errors[] = "Location is required";
    }

    // Check if category is provided
    if (empty($equipmentData['category_id'])) {
        $errors[] = "Category is required";
    }
    
    // If no errors, update equipment
    if (empty($errors)) {
        if ($equipmentModel->update($equipmentId, $equipmentData)) {
            // Log the action
            Helpers::logAction("Updated equipment: " . $equipmentData['name']);
            
            Helpers::redirectWithMessage("equipment.php", "Equipment updated successfully.", "success");
            exit;
        } else {
            $errors[] = "Failed to update equipment";
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1>
            <i class="fas fa-edit me-2"></i>Edit Equipment
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
        <h5 class="card-title mb-0">Equipment Information</h5>
    </div>
    <div class="card-body">
        <form action="edit_equipment.php?id=<?php echo $equipmentId; ?>" method="POST" class="row g-3">
            <!-- Name -->
            <div class="col-md-6">
                <label for="name" class="form-label">Equipment Name *</label>
                <input type="text" class="form-control" id="name" name="name" required
                    value="<?php echo htmlspecialchars($equipment['name']); ?>">
            </div>
            
            <!-- Category -->
            <div class="col-md-6">
                <label for="category_id" class="form-label">Category *</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>" 
                            <?php echo ($equipment['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Serial Number -->
            <div class="col-md-6">
                <label for="serial_number" class="form-label">Serial Number *</label>
                <input type="text" class="form-control" id="serial_number" name="serial_number" required
                    value="<?php echo htmlspecialchars($equipment['serial_number']); ?>">
            </div>
            
            <!-- Model -->
            <div class="col-md-6">
                <label for="model" class="form-label">Model *</label>
                <input type="text" class="form-control" id="model" name="model" required
                    value="<?php echo htmlspecialchars($equipment['model']); ?>">
            </div>
            
            <!-- Manufacturer -->
            <div class="col-md-6">
                <label for="manufacturer" class="form-label">Manufacturer *</label>
                <input type="text" class="form-control" id="manufacturer" name="manufacturer" required
                    value="<?php echo htmlspecialchars($equipment['manufacturer']); ?>">
            </div>
            
            <!-- Purchase Date -->
            <div class="col-md-6">
                <label for="purchase_date" class="form-label">Purchase Date</label>
                <input type="date" class="form-control" id="purchase_date" name="purchase_date"
                    value="<?php echo $equipment['purchase_date'] ? date('Y-m-d', strtotime($equipment['purchase_date'])) : ''; ?>">
            </div>
            
            <!-- Warranty Expiry -->
            <div class="col-md-6">
                <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry"
                    value="<?php echo $equipment['warranty_expiry'] ? date('Y-m-d', strtotime($equipment['warranty_expiry'])) : ''; ?>">
            </div>
            
            <!-- Status -->
            <div class="col-md-6">
                <label for="status" class="form-label">Status *</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="active" <?php echo ($equipment['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($equipment['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <!-- Condition -->
            <div class="col-md-6">
                <label for="equipment_condition" class="form-label">Condition *</label>
                <select class="form-select" id="equipment_condition" name="equipment_condition" required>
                    <option value="new" <?php echo ($equipment['equipment_condition'] === 'new') ? 'selected' : ''; ?>>New</option>
                    <option value="good" <?php echo ($equipment['equipment_condition'] === 'good') ? 'selected' : ''; ?>>Good</option>
                    <option value="fair" <?php echo ($equipment['equipment_condition'] === 'fair') ? 'selected' : ''; ?>>Fair</option>
                    <option value="poor" <?php echo ($equipment['equipment_condition'] === 'poor') ? 'selected' : ''; ?>>Poor</option>
                </select>
            </div>
            
            <!-- Location -->
            <div class="col-md-6">
                <label for="location" class="form-label">Location *</label>
                <input type="text" class="form-control" id="location" name="location" required
                    value="<?php echo htmlspecialchars($equipment['location']); ?>">
            </div>
            
            <!-- Last Maintenance Date -->
            <div class="col-md-6">
                <label for="last_maintenance_date" class="form-label">Last Maintenance Date</label>
                <input type="date" class="form-control" id="last_maintenance_date" name="last_maintenance_date"
                    value="<?php echo $equipment['last_maintenance_date'] ? date('Y-m-d', strtotime($equipment['last_maintenance_date'])) : ''; ?>">
            </div>
            
            <!-- Description -->
            <div class="col-md-12">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($equipment['description']); ?></textarea>
            </div>
            
            <!-- Notes -->
            <div class="col-md-12">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($equipment['notes']); ?></textarea>
            </div>
            
            <div class="col-12 mt-4">
                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Equipment
                </button>
                <a href="equipment.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 