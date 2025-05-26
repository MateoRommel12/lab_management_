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
$pageTitle = "Inactive Equipment";
$currentPage = 'equipment';

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/Equipment.php';

// Initialize models
$equipmentModel = new Equipment();

// Handle bulk delete
$deleteCount = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all_inactive'])) {
    // Get all inactive equipment
    $inactiveEquipment = $equipmentModel->getEquipmentByStatus('inactive');
    
    // Delete each one
    foreach ($inactiveEquipment as $item) {
        if ($equipmentModel->delete($item['equipment_id'])) {
            $deleteCount++;
        }
    }
    
    // Set notification
    if ($deleteCount > 0) {
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => "Successfully deleted {$deleteCount} inactive equipment items."
        ];
    } else {
        $_SESSION['notification'] = [
            'type' => 'warning',
            'message' => "No inactive equipment was deleted."
        ];
    }
    
    // Redirect to refresh the page
    header('Location: inactive_equipment.php');
    exit;
}

// Get all equipment with inactive status
$inactiveEquipment = $equipmentModel->getEquipmentByStatus('inactive');
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>
            <i class="fas fa-ban me-2"></i>Inactive Equipment
        </h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="equipment.php" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i>Back to All Equipment
        </a>
        <?php if (count($inactiveEquipment) > 0): ?>
        <button type="button" class="btn btn-danger" id="deleteAllBtn">
            <i class="fas fa-trash me-2"></i>Delete All Inactive Equipment
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (count($inactiveEquipment) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover datatable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Serial Number</th>
                            <th>Model</th>
                            <th>Manufacturer</th>
                            <th>Status</th>
                            <th>Condition</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inactiveEquipment as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                            <td><?php echo htmlspecialchars($item['serial_number']); ?></td>
                            <td><?php echo htmlspecialchars($item['model']); ?></td>
                            <td><?php echo htmlspecialchars($item['manufacturer']); ?></td>
                            <td>
                                <span class="badge bg-danger">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($item['equipment_condition']) {
                                        'new' => 'success',
                                        'good' => 'info',
                                        'fair' => 'warning',
                                        'poor' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($item['equipment_condition']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit_equipment.php?id=<?php echo $item['equipment_id']; ?>" 
                                       class="btn btn-sm btn-primary" 
                                       data-bs-toggle="tooltip" 
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0);" 
                                       class="btn btn-sm btn-danger delete-equipment" 
                                       data-id="<?php echo $item['equipment_id']; ?>"
                                       data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                       data-bs-toggle="tooltip" 
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No inactive equipment found.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Form for bulk delete -->
<form id="deleteAllForm" action="inactive_equipment.php" method="POST" style="display: none;">
    <input type="hidden" name="delete_all_inactive" value="1">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Handle individual delete button clicks
    const deleteButtons = document.querySelectorAll('.delete-equipment');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            Swal.fire({
                title: 'Confirm Deletion',
                text: `Are you sure you want to delete the equipment "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create and submit a form to delete_equipment.php
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'delete_equipment.php';
                    form.style.display = 'none';
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'equipment_id';
                    input.value = id;
                    
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
    
    // Handle delete all button click
    const deleteAllBtn = document.getElementById('deleteAllBtn');
    if (deleteAllBtn) {
        deleteAllBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Delete All Inactive Equipment?',
                text: 'Are you sure you want to delete ALL inactive equipment? This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete all!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteAllForm').submit();
                }
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 