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
$pageTitle = "Borrow Equipment";

// Include header
require_once __DIR__ . '/../includes/header.php';

// Require login
$auth->requireLogin();

// Check if user has permission to borrow equipment
if (!$auth->canBorrowEquipment()) {
    Helpers::redirectWithMessage("../index.php", "You don't have permission to borrow equipment.", "danger");
    exit;
}

// Include required models
require_once '../models/Equipment.php';
require_once '../models/Borrowing.php';
require_once '../models/User.php';

// Initialize models
$equipmentModel = new Equipment();
$borrowingModel = new Borrowing();
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
    
    // Check if equipment is available for borrowing
    if ($preSelectedEquipment['condition_status'] === 'under maintenance' || 
        $preSelectedEquipment['condition_status'] === 'disposed') {
        Helpers::redirectWithMessage("../equipment/view.php?id=$equipmentId", 
            "This equipment is currently {$preSelectedEquipment['condition_status']} and cannot be borrowed.", "danger");
        exit;
    }
    
    // Check if equipment is already borrowed
    if ($borrowingModel->isEquipmentCurrentlyBorrowed($equipmentId)) {
        Helpers::redirectWithMessage("../equipment/view.php?id=$equipmentId", 
            "This equipment is currently borrowed by someone else.", "danger");
        exit;
    }
}

// Get all available equipment for dropdown (not under maintenance, not disposed, not currently borrowed)
$availableEquipment = $equipmentModel->getAvailableEquipmentForBorrowing();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $borrowRequest = [
        'borrower_id' => $userId,
        'equipment_id' => (int)$_POST['equipment_id'],
        'borrow_date' => trim($_POST['borrow_date']),
        'expected_return_date' => trim($_POST['expected_return_date']),
        'purpose' => trim($_POST['purpose']),
        'status' => 'pending'
    ];
    
    // Validation
    $errors = [];
    
    if (empty($borrowRequest['equipment_id'])) {
        $errors[] = "Equipment is required";
    } else {
        // Check if equipment is available
        $selectedEquipment = $equipmentModel->getEquipmentById($borrowRequest['equipment_id']);
        
        if (!$selectedEquipment) {
            $errors[] = "Selected equipment not found";
        } elseif ($selectedEquipment['condition_status'] === 'under maintenance' || 
                  $selectedEquipment['condition_status'] === 'disposed') {
            $errors[] = "The selected equipment is {$selectedEquipment['condition_status']} and cannot be borrowed";
        }
        
        // Check if equipment is already borrowed
        if ($borrowingModel->isEquipmentCurrentlyBorrowed($borrowRequest['equipment_id'])) {
            $errors[] = "The selected equipment is currently borrowed by someone else";
        }
    }
    
    if (empty($borrowRequest['borrow_date'])) {
        $errors[] = "Borrow date is required";
    } else {
        // Check if borrow date is in the future
        $borrowDate = new DateTime($borrowRequest['borrow_date']);
        $today = new DateTime();
        
        if ($borrowDate < $today) {
            $errors[] = "Borrow date cannot be in the past";
        }
    }
    
    if (empty($borrowRequest['expected_return_date'])) {
        $errors[] = "Expected return date is required";
    } else {
        // Check if return date is after borrow date
        $borrowDate = new DateTime($borrowRequest['borrow_date']);
        $returnDate = new DateTime($borrowRequest['expected_return_date']);
        
        if ($returnDate <= $borrowDate) {
            $errors[] = "Return date must be after borrow date";
        }
    }
    
    if (empty($borrowRequest['purpose'])) {
        $errors[] = "Purpose is required";
    }
    
    // If no errors, add borrowing request
    if (empty($errors)) {
        $result = $borrowingModel->addBorrowingRequest($borrowRequest);
        
        if ($result) {
            // Log the action
            $equipmentName = $selectedEquipment['equipment_name'];
            Helpers::logAction("Submitted borrowing request for: " . $equipmentName);
            
            // Redirect to borrowing list with success message
            Helpers::redirectWithMessage("index.php", "Borrowing request submitted successfully. Please wait for approval.", "success");
            exit;
        } else {
            $errors[] = "Failed to submit borrowing request. Please try again.";
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>
            <i class="fas fa-hand-holding me-2"></i>Borrow Equipment
        </h1>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Borrowing List
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
        <h5 class="card-title mb-0">Borrowing Request Form</h5>
    </div>
    <div class="card-body">
        <form action="borrow.php<?php echo $equipmentId ? "?equipment_id=$equipmentId" : ''; ?>" method="POST" class="row g-3">
            <!-- Equipment Selection -->
            <div class="col-md-12">
                <div class="form-group">
                    <label for="equipment_id">Equipment *</label>
                    <select name="equipment_id" id="equipment_id" class="form-control" required>
                        <option value="">Select Equipment</option>
                        <?php foreach ($availableEquipment as $equipment): ?>
                        <option value="<?php echo $equipment['equipment_id']; ?>">
                            <?php echo htmlspecialchars($equipment['equipment_name'] ?? 'Unknown Equipment'); ?>
                            (SN: <?php echo htmlspecialchars($equipment['serial_number'] ?? 'N/A'); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Select the equipment you want to borrow. Only available equipment is shown.</small>
                </div>
                
                <?php if ($preSelectedEquipment): ?>
                <!-- Hidden field to pass equipment_id when dropdown is disabled -->
                <input type="hidden" name="equipment_id" value="<?php echo $preSelectedEquipment['equipment_id']; ?>">
                <?php endif; ?>
            </div>
            
            <!-- Equipment Details (shown when equipment is selected) -->
            <div class="col-md-12 mb-3" id="equipmentDetails" style="display: <?php echo $preSelectedEquipment ? 'block' : 'none'; ?>">
                <div class="card border-light">
                    <div class="card-body">
                        <h6 class="card-title">Equipment Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Name:</strong> <span id="equipmentName"><?php echo $preSelectedEquipment ? htmlspecialchars($preSelectedEquipment['equipment_name']) : ''; ?></span></p>
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
            
            <!-- Borrowing Details -->
            <div class="col-md-6">
                <label for="borrow_date" class="form-label">Borrow Date *</label>
                <input type="date" class="form-control" id="borrow_date" name="borrow_date" required
                    value="<?php echo isset($borrowRequest['borrow_date']) ? htmlspecialchars($borrowRequest['borrow_date']) : date('Y-m-d'); ?>"
                    min="<?php echo date('Y-m-d'); ?>">
                <div class="form-text">The date when you need the equipment</div>
            </div>
            
            <div class="col-md-6">
                <label for="expected_return_date" class="form-label">Expected Return Date *</label>
                <input type="date" class="form-control" id="expected_return_date" name="expected_return_date" required
                    value="<?php echo isset($borrowRequest['expected_return_date']) ? htmlspecialchars($borrowRequest['expected_return_date']) : date('Y-m-d', strtotime('+7 days')); ?>"
                    min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                <div class="form-text">The date when you plan to return the equipment</div>
            </div>
            
            <div class="col-12">
                <label for="purpose" class="form-label">Purpose of Borrowing *</label>
                <textarea class="form-control" id="purpose" name="purpose" rows="3" required
                    placeholder="Please explain why you need this equipment..."><?php echo isset($borrowRequest['purpose']) ? htmlspecialchars($borrowRequest['purpose']) : ''; ?></textarea>
                <div class="form-text">Provide a detailed explanation of why you need to borrow this equipment</div>
            </div>
            
            <!-- Borrower Information -->
            <div class="col-md-12">
                <h6 class="form-label">Borrower Information</h6>
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
            
            <!-- Agreement -->
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="agreement" required>
                    <label class="form-check-label" for="agreement">
                        I agree to handle the equipment with care and return it by the expected return date. 
                        I understand that I am responsible for any damage or loss during the borrowing period.
                    </label>
                </div>
            </div>
            
            <div class="col-12 mt-4">
                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Submit Borrowing Request
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
    const equipmentData = <?php echo json_encode($availableEquipment); ?>;
    
    // Function to update equipment details
    function updateEquipmentDetails() {
        const selectedId = parseInt(equipmentSelect.value);
        
        if (selectedId) {
            // Find selected equipment
            const selected = equipmentData.find(item => item.equipment_id === selectedId);
            
            if (selected) {
                // Update display
                equipmentName.textContent = selected.equipment_name;
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

require_once __DIR__ . '/../includes/footer.php';
?>
