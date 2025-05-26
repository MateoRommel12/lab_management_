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
$pageTitle = "Equipment Management";
$currentPage = 'equipment';

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/Equipment.php';

// Initialize models
$equipmentModel = new Equipment();

// Get all equipment
$equipment = $equipmentModel->getAllEquipmentWithCategories();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>
            <i class="fas fa-microscope me-2"></i>Equipment Management
        </h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="inactive_equipment.php" class="btn btn-danger me-2">
            <i class="fas fa-trash me-2"></i>Manage Inactive Equipment
        </a>
        <a href="add_equipment.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Equipment
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
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
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipment as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                        <td><?php echo htmlspecialchars($item['serial_number']); ?></td>
                        <td><?php echo htmlspecialchars($item['model']); ?></td>
                        <td><?php echo htmlspecialchars($item['manufacturer']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : 'danger'; ?>">
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
                        <td><?php echo htmlspecialchars($item['location']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="edit_equipment.php?id=<?php echo $item['equipment_id']; ?>" 
                                   class="btn btn-sm btn-primary" 
                                   data-bs-toggle="tooltip" 
                                   title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="javascript:void(0);" 
                                   class="btn btn-sm btn-danger force-delete-equipment" 
                                   data-id="<?php echo $item['equipment_id']; ?>"
                                   data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                   data-bs-toggle="tooltip" 
                                   title="Delete Equipment">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Handle delete button clicks (which now use force delete)
    const deleteButtons = document.querySelectorAll('.force-delete-equipment');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            Swal.fire({
                title: 'Delete Equipment',
                html: `<div class="text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <p>Are you sure you want to delete the equipment "${name}"?</p>
                    <p>This will delete ALL related records including:</p>
                    <ul class="text-start">
                        <li>Borrowing Requests</li>
                        <li>Maintenance Records</li>
                        <li>Movement History</li>
                    </ul>
                    <p>This action cannot be undone!</p>
                </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create and submit a form to force_delete_equipment.php
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'force_delete_equipment.php';
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
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>