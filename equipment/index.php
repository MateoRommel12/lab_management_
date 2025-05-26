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
$pageTitle = "Equipment Management";

// Include header
require_once __DIR__ . '/../includes/header.php';

// Require login
$auth->requireLogin();

// Check if user has permission to view equipment
if (!$auth->canViewEquipment()) {
    Helpers::redirectWithMessage("../index.php", "You don't have permission to view equipment.", "danger");
    exit;
}

// Include required models
require_once '../models/Equipment.php';
require_once '../models/Room.php';

// Initialize equipment model
$equipmentModel = new Equipment();
$roomModel = new Room();

// Get filters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$room = isset($_GET['room']) ? $_GET['room'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get equipment with filters
$equipment = $equipmentModel->getAllEquipment($category, $status, $room, $search);

// Get all categories and rooms for filters
$categories = $equipmentModel->getAllCategories();
$rooms = $roomModel->getAllRooms();
$statuses = ['active', 'inactive', 'maintenance', 'retired'];
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>
            <i class="fas fa-microscope me-2"></i>Equipment Management
        </h1>
    </div>
    <div class="col-md-4 text-md-end">
        <?php if ($auth->canManageEquipment()): ?>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Equipment
        </a>
        <?php endif; ?>
        <a href="./movements.php" class="btn btn-info ms-2">
            <i class="fas fa-exchange-alt me-2"></i>View Movements
        </a>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>" 
                            <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $stat): ?>
                    <option value="<?php echo $stat; ?>" 
                            <?php echo $status == $stat ? 'selected' : ''; ?>>
                        <?php echo ucfirst($stat); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="room" class="form-label">Room</label>
                <select class="form-select" id="room" name="room">
                    <option value="">All Rooms</option>
                    <?php foreach ($rooms as $r): ?>
                    <option value="<?php echo $r['room_id']; ?>" 
                            <?php echo $room == $r['room_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r['room_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search equipment...">
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Apply Filters
                </button>
                <a href="index.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-times me-2"></i>Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Equipment List -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Equipment List</h5>
    </div>
    <div class="card-body">
        <?php if (empty($equipment)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No equipment found matching your criteria.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Serial Number</th>
                        <th>Room</th>
                        <th>Condition</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipment as $item): ?>
                    <tr>
                        <td><?php echo $item['equipment_id']; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name'] ?? 'Not Assigned'); ?></td>
                        <td><?php echo htmlspecialchars($item['serial_number']); ?></td>
                        <td><?php echo htmlspecialchars($item['location'] ?? 'Not Assigned'); ?></td>
                        <td>
                            <?php
                            $conditionClass = '';
                            switch($item['equipment_condition']) {
                                case 'new': $conditionClass = 'success'; break;
                                case 'good': $conditionClass = 'info'; break;
                                case 'fair': $conditionClass = 'primary'; break;
                                case 'poor': $conditionClass = 'warning'; break;
                                default: $conditionClass = 'secondary';
                            }
                            ?>
                            <span class="badge bg-<?php echo $conditionClass; ?>">
                                <?php echo ucfirst($item['equipment_condition']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="view.php?id=<?php echo $item['equipment_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($auth->canManageEquipment()): ?>
                                <a href="edit.php?id=<?php echo $item['equipment_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                

                                <!-- Direct fallback link in case modal doesn't work -->
                                <a href="move.php?id=<?php echo $item['equipment_id']; ?>" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-arrow-right"></i> 
                                </a>
                                <?php endif; ?>
                                <?php if ($auth->canBorrowEquipment()): ?>
                                <a href="../borrowing/borrow.php?equipment_id=<?php echo $item['equipment_id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-hand-holding"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Move Equipment Modal -->
<div class="modal fade" id="moveEquipmentModal" tabindex="-1" aria-labelledby="moveEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moveEquipmentModalLabel">Move Equipment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="move.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="equipment_id" id="moveEquipmentId">
                    <div class="mb-3">
                        <label class="form-label">Equipment</label>
                        <input type="text" class="form-control" id="moveEquipmentName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Location</label>
                        <input type="text" class="form-control" id="currentRoom" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Moved</label>
                        <input type="text" class="form-control" id="lastMoved" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Moved By</label>
                        <input type="text" class="form-control" id="lastMovedBy" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="to_room_id" class="form-label">New Location</label>
                        <select class="form-select" id="to_room_id" name="to_room_id" required>
                            <option value="">Select Room</option>
                            <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['room_id']; ?>">
                                <?php echo htmlspecialchars($room['room_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="moved_by" class="form-label">Moved By</label>
                        <select class="form-select" id="moved_by" name="moved_by" required>
                            <option value="">Select User</option>
                            <?php 
                            // Get all users who can manage equipment
                            $users = $auth->getUsersByRole(['admin', 'lab_technician']);
                            $currentUserId = $auth->getUserId();
                            $currentUserSelected = false;
                            
                            foreach ($users as $user): 
                                $isCurrentUser = ($user['user_id'] == $currentUserId);
                                if ($isCurrentUser) {
                                    $currentUserSelected = true;
                                }
                            ?>
                            <option value="<?php echo $user['user_id']; ?>" <?php echo $isCurrentUser ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                <?php echo $isCurrentUser ? ' (You)' : ''; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <!-- Fallback hidden input in case the select doesn't work -->
                        <input type="hidden" name="fallback_moved_by" value="<?php echo $currentUserId; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="movement_date" class="form-label">Movement Date</label>
                        <input type="datetime-local" class="form-control" id="movement_date" name="movement_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Movement</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Move Equipment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Direct function to open modal with debugging
function openMoveModal(buttonElement, equipmentId, equipmentName, currentRoom) {
    console.log("openMoveModal called with equipment ID:", equipmentId);
    
    // Set the values in the modal
    document.getElementById('moveEquipmentId').value = equipmentId;
    document.getElementById('moveEquipmentName').value = equipmentName;
    document.getElementById('currentRoom').value = currentRoom;
    
    // Get the last moved info from data attributes
    const lastMoved = buttonElement.getAttribute('data-last-moved');
    const lastMovedBy = buttonElement.getAttribute('data-last-moved-by');
    document.getElementById('lastMoved').value = lastMoved || 'Never';
    document.getElementById('lastMovedBy').value = lastMovedBy || 'N/A';
    
    // Set current date and time
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    
    const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
    document.getElementById('movement_date').value = formattedDateTime;
    
    // Set the current user in the moved_by dropdown
    const movedBySelect = document.getElementById('moved_by');
    if (movedBySelect && movedBySelect.options.length > 0) {
        // Set to first option if available (skip the empty placeholder)
        if (movedBySelect.options.length > 1) {
            movedBySelect.selectedIndex = 1;
        }
    }
    
    try {
        console.log("Attempting to open modal with Bootstrap...");
        // Try using Bootstrap's modal
        var moveModal = new bootstrap.Modal(document.getElementById('moveEquipmentModal'));
        moveModal.show();
    } catch (e) {
        console.error("Bootstrap modal error:", e);
        try {
            // Fallback to jQuery
            console.log("Falling back to jQuery...");
            $('#moveEquipmentModal').modal('show');
        } catch (e2) {
            console.error("jQuery modal error:", e2);
            // Ultimate fallback - change location
            if(confirm("Would you like to move equipment ID " + equipmentId + "?")) {
                window.location.href = "move.php?id=" + equipmentId;
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM fully loaded");
    
    // Check if Bootstrap is loaded
    if (typeof bootstrap !== 'undefined') {
        console.log("Bootstrap is loaded");
    } else {
        console.error("Bootstrap is NOT loaded!");
    }
    
    // Check if jQuery is loaded
    if (typeof jQuery !== 'undefined') {
        console.log("jQuery is loaded");
    } else {
        console.error("jQuery is NOT loaded!");
    }
    
    // Initialize all modals
    var modals = document.querySelectorAll('.modal');
    console.log("Found", modals.length, "modals");
    
    if(modals.length > 0) {
        Array.from(modals).forEach(function(modal) {
            try {
                new bootstrap.Modal(modal);
                console.log("Initialized modal:", modal.id);
            } catch (e) {
                console.error("Error initializing modal:", e);
            }
        });
    }

    const moveEquipmentModal = document.getElementById('moveEquipmentModal');
    if (moveEquipmentModal) {
        console.log("Found moveEquipmentModal");
        moveEquipmentModal.addEventListener('show.bs.modal', function(event) {
            console.log("show.bs.modal event triggered");
            const button = event.relatedTarget;
            const equipmentId = button.getAttribute('data-equipment-id');
            const equipmentName = button.getAttribute('data-equipment-name');
            const currentRoom = button.getAttribute('data-current-room');
            const lastMoved = button.getAttribute('data-last-moved');
            const lastMovedBy = button.getAttribute('data-last-moved-by');
            
            document.getElementById('moveEquipmentId').value = equipmentId;
            document.getElementById('moveEquipmentName').value = equipmentName;
            document.getElementById('currentRoom').value = currentRoom;
            document.getElementById('lastMoved').value = lastMoved || 'Never';
            document.getElementById('lastMovedBy').value = lastMovedBy || 'N/A';
            
            // Set current date and time for movement_date
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            document.getElementById('movement_date').value = formattedDateTime;
            
            // Set the current user in the moved_by dropdown
            const movedBySelect = document.getElementById('moved_by');
            if (movedBySelect && movedBySelect.options.length > 0) {
                // Set to first option if available (skip the empty placeholder)
                if (movedBySelect.options.length > 1) {
                    movedBySelect.selectedIndex = 1;
                }
            }
        });
    } else {
        console.error("moveEquipmentModal not found in DOM");
    }
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?> 