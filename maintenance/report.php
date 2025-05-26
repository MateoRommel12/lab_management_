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

// Set page title
$pageTitle = "Report Maintenance Issue";

// Include header
require_once __DIR__ . '/../includes/header.php';

// Require login
$auth->requireLogin();

// Check if user has permission to report maintenance
if (!$auth->canReportMaintenance()) {
    Helpers::redirectWithMessage("../index.php", "You don't have permission to report maintenance issues.", "danger");
    exit;
}

// Include required models
require_once '../models/Equipment.php';
require_once '../models/Maintenance.php';
require_once '../models/User.php';

// Initialize models
$equipmentModel = new Equipment();
$maintenanceModel = new Maintenance();
$userModel = new User();

// Get current user ID
$userId = $auth->getUserId();

// Check if specific equipment is pre-selected
$equipmentId = isset($_GET['equipment_id']) ? (int)$_GET['equipment_id'] : null;
$preSelectedEquipment = null;

if ($equipmentId) {
    $preSelectedEquipment = $equipmentModel->getEquipmentById($equipmentId);
    
    // Check if equipment exists
    if (!$preSelectedEquipment) {
        Helpers::redirectWithMessage("../equipment/index.php", "Equipment not found", "danger");
        exit;
    }
    
    // Check if equipment is already under maintenance
    if ($preSelectedEquipment['condition_status'] === 'under maintenance') {
        // Check if there is an active maintenance request
        $activeRequest = $maintenanceModel->getActiveMaintenanceByEquipment($equipmentId);
        
        if ($activeRequest) {
            Helpers::redirectWithMessage("../equipment/view.php?id=$equipmentId", 
                "This equipment is already under maintenance. View the existing maintenance request for details.", "warning");
            exit;
        }
    }
    
    // Check if equipment is disposed
    if ($preSelectedEquipment['condition_status'] === 'disposed') {
        Helpers::redirectWithMessage("../equipment/view.php?id=$equipmentId", 
            "This equipment is marked as disposed and cannot be maintained.", "danger");
        exit;
    }
}

// Get all equipment for dropdown
$allEquipment = $equipmentModel->getAllEquipment();

// Get technicians for assignment
$technicians = [];
if ($auth->canManageMaintenance()) {
    $technicians = $userModel->getUsersByRole('Lab Technician');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $maintenanceRequest = [
        'equipment_id' => (int)$_POST['equipment_id'],
        'reported_by' => $userId,
        'issue_description' => trim($_POST['issue_description']),
        'priority' => trim($_POST['priority']),
        'status' => 'pending',
        'technician_assigned' => !empty($_POST['technician_assigned']) ? (int)$_POST['technician_assigned'] : null
    ];
    
    // Validation
    $errors = [];
    
    if (empty($maintenanceRequest['equipment_id'])) {
        $errors[] = "Equipment is required";
    } else {
        // Check if equipment exists
        $selectedEquipment = $equipmentModel->getEquipmentById($maintenanceRequest['equipment_id']);
        
        if (!$selectedEquipment) {
            $errors[] = "Selected equipment not found";
        } elseif ($selectedEquipment['condition_status'] === 'disposed') {
            $errors[] = "The selected equipment is disposed and cannot be maintained";
        }
        
        // Check if equipment already has an active maintenance request
        if ($maintenanceModel->hasActiveMaintenanceRequest($maintenanceRequest['equipment_id'])) {
            $errors[] = "This equipment already has an active maintenance request";
        }
    }
    
    if (empty($maintenanceRequest['issue_description'])) {
        $errors[] = "Issue description is required";
    }
    
    if (empty($maintenanceRequest['priority'])) {
        $errors[] = "Priority is required";
    }
    
    // If no errors, add maintenance request
    if (empty($errors)) {
        $result = $maintenanceModel->addMaintenanceRequest($maintenanceRequest);
        
        if ($result) {
            // Update equipment status to "under maintenance"
            $equipmentModel->updateEquipmentStatus($maintenanceRequest['equipment_id'], 'under maintenance');
            
            // Log the action
            $equipmentName = $selectedEquipment['name'];
            Helpers::logAction("Reported maintenance issue for: " . $equipmentName);
            
            // Redirect to maintenance list with success message
            Helpers::redirectWithMessage("index.php", "Maintenance issue reported successfully", "success");
            exit;
        } else {
            $errors[] = "Failed to report maintenance issue. Please try again.";
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>
            <i class="fas fa-tools me-2"></i>Report Maintenance Issue
        </h1>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Maintenance List
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
        <h5 class="card-title mb-0">Maintenance Issue Form</h5>
    </div>
    <div class="card-body">
        <form action="report.php<?php echo $equipmentId ? "?equipment_id=$equipmentId" : ''; ?>" method="POST" class="row g-3">
            <!-- Equipment Selection -->
            <div class="col-md-12">
                <label for="equipment_id" class="form-label">Equipment *</label>
                <select class="form-select" id="equipment_id" name="equipment_id" required <?php echo $preSelectedEquipment ? 'disabled' : ''; ?>>
                    <option value="">Select Equipment</option>
                    <?php foreach ($allEquipment as $equipment): ?>
                        <?php if ($equipment['condition_status'] != 'under maintenance' && $equipment['condition_status'] != 'disposed'): ?>
                        <option value="<?php echo $equipment['equipment_id']; ?>" 
                            <?php echo ($preSelectedEquipment && $preSelectedEquipment['equipment_id'] == $equipment['equipment_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($equipment['name'] ?? 'Unknown Equipment'); ?> 
                            (<?php echo htmlspecialchars($equipment['category_name'] ?? 'Uncategorized'); ?>, 
                            SN: <?php echo htmlspecialchars($equipment['serial_number'] ?? 'N/A'); ?>)
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                
                <?php if ($preSelectedEquipment): ?>
                <!-- Hidden field to pass equipment_id when dropdown is disabled -->
                <input type="hidden" name="equipment_id" value="<?php echo $preSelectedEquipment['equipment_id']; ?>">
                <?php endif; ?>
                
                <div class="form-text">Select the equipment that requires maintenance.</div>
            </div>
            
            <!-- Equipment Details (shown when equipment is selected) -->
            <div class="col-md-12 mb-3" id="equipmentDetails" style="display: <?php echo $preSelectedEquipment ? 'block' : 'none'; ?>">
                <div class="card border-light">
                    <div class="card-body">
                        <h6 class="card-title">Equipment Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Name:</strong> <span id="equipmentName"><?php echo $preSelectedEquipment ? htmlspecialchars($preSelectedEquipment['name']) : ''; ?></span></p>
                                <p class="mb-1"><strong>Category:</strong> <span id="equipmentCategory"><?php echo $preSelectedEquipment ? htmlspecialchars($preSelectedEquipment['category_name']) : ''; ?></span></p>
                                <p class="mb-1"><strong>Serial Number:</strong> <span id="equipmentSerial"><?php echo $preSelectedEquipment ? htmlspecialchars($preSelectedEquipment['serial_number']) : ''; ?></span></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Condition:</strong> <span id="equipmentCondition"><?php echo $preSelectedEquipment ? Helpers::getConditionBadge($preSelectedEquipment['condition_status']) : ''; ?></span></p>
                                <p class="mb-1"><strong>Location:</strong> <span id="equipmentLocation"><?php echo $preSelectedEquipment ? htmlspecialchars($preSelectedEquipment['room_name'] ?: 'Not Assigned') : ''; ?></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Issue Details -->
            <div class="col-12">
                <label for="issue_description" class="form-label">Issue Description *</label>
                <textarea class="form-control" id="issue_description" name="issue_description" rows="3" required
                    placeholder="Please describe the issue in detail..."><?php echo isset($maintenanceRequest['issue_description']) ? htmlspecialchars($maintenanceRequest['issue_description']) : ''; ?></textarea>
                <div class="form-text">Provide a detailed description of the issue. Include when it started and any error messages or symptoms.</div>
            </div>
            
            <div class="col-md-6">
                <label for="priority" class="form-label">Priority *</label>
                <select class="form-select" id="priority" name="priority" required>
                    <option value="">Select Priority</option>
                    <option value="low" <?php echo isset($maintenanceRequest['priority']) && $maintenanceRequest['priority'] == 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo isset($maintenanceRequest['priority']) && $maintenanceRequest['priority'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo isset($maintenanceRequest['priority']) && $maintenanceRequest['priority'] == 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="critical" <?php echo isset($maintenanceRequest['priority']) && $maintenanceRequest['priority'] == 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
                <div class="form-text">Select the urgency level of this maintenance issue.</div>
            </div>
            
            <?php if ($auth->canManageMaintenance() && !empty($technicians)): ?>
            <div class="col-md-6">
                <label for="technician_assigned" class="form-label">Assign Technician</label>
                <select class="form-select" id="technician_assigned" name="technician_assigned">
                    <option value="">Unassigned</option>
                    <?php foreach ($technicians as $tech): ?>
                    <option value="<?php echo $tech['user_id']; ?>" 
                        <?php echo isset($maintenanceRequest['technician_assigned']) && $maintenanceRequest['technician_assigned'] == $tech['user_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Optionally assign a technician to this maintenance request.</div>
            </div>
            <?php endif; ?>
            
            <!-- Reporter Information -->
            <div class="col-md-12">
                <h6 class="form-label">Reporter Information</h6>
                <div class="card border-light">
                    <div class="card-body">
                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($auth->getUser()['first_name'] . ' ' . $auth->getUser()['last_name']); ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($auth->getUser()['email']); ?></p>
                        <p class="mb-1"><strong>Role:</strong> <?php 
                            // Get role name from the database
                            $userWithRole = $userModel->getUserWithRole($auth->getUserId());
                            echo htmlspecialchars($userWithRole['role_name'] ?? 'User'); 
                        ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-12 mt-4">
                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Submit Maintenance Request
                </button>
                <a href="<?php echo $equipmentId ? "../equipment/view.php?id=$equipmentId" : 'index.php'; ?>" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript for Equipment Selection -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const equipmentSelect = document.getElementById('equipment_id');
    const equipmentDetails = document.getElementById('equipmentDetails');
    const equipmentName = document.getElementById('equipmentName');
    const equipmentCategory = document.getElementById('equipmentCategory');
    const equipmentSerial = document.getElementById('equipmentSerial');
    const equipmentCondition = document.getElementById('equipmentCondition');
    const equipmentLocation = document.getElementById('equipmentLocation');
    
    // Equipment data
    const equipmentData = <?php echo json_encode($allEquipment); ?>;
    
    // Function to update equipment details
    function updateEquipmentDetails() {
        const selectedId = parseInt(equipmentSelect.value);
        
        if (selectedId) {
            // Find selected equipment
            const selected = equipmentData.find(item => item.equipment_id === selectedId);
            
            if (selected) {
                // Update display
                equipmentName.textContent = selected.name;
                equipmentCategory.textContent = selected.category_name;
                equipmentSerial.textContent = selected.serial_number;
                
                // Update condition with badge
                let conditionClass = '';
                switch(selected.condition_status) {
                    case 'new': conditionClass = 'success'; break;
                    case 'good': conditionClass = 'info'; break;
                    case 'fair': conditionClass = 'primary'; break;
                    case 'poor': conditionClass = 'warning'; break;
                    default: conditionClass = 'secondary';
                }
                
                equipmentCondition.innerHTML = `<span class="badge bg-${conditionClass}">${selected.condition_status.charAt(0).toUpperCase() + selected.condition_status.slice(1)}</span>`;
                
                // Update location
                equipmentLocation.textContent = selected.room_name || 'Not Assigned';
                
                // Show details
                equipmentDetails.style.display = 'block';
            }
        } else {
            // Hide details when nothing selected
            equipmentDetails.style.display = 'none';
        }
    }
    
    // Add change event listener to equipment select
    equipmentSelect.addEventListener('change', updateEquipmentDetails);
    
    // Initial update in case equipment is pre-selected
    updateEquipmentDetails();
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?> 