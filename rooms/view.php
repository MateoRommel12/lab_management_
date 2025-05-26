<?php
// Set page title
$pageTitle = "View Room";

// Include header
require_once '../includes/header.php';

// Require login
$auth->requireLogin();

// Check if user has permission to view rooms
if (!$auth->canViewRooms()) {
    Helpers::redirectWithMessage("../index.php", "You don't have permission to view rooms.", "danger");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    Helpers::redirectWithMessage("index.php", "Invalid room ID", "danger");
    exit;
}

$roomId = (int)$_GET['id'];

// Include required models
require_once '../models/Room.php';
require_once '../models/Equipment.php';
require_once '../models/User.php';

// Initialize models
$roomModel = new Room();
$equipmentModel = new Equipment();
$userModel = new User();

// Get room details
$room = $roomModel->getRoomById($roomId);

if (!$room) {
    Helpers::redirectWithMessage("index.php", "Room not found", "danger");
    exit;
}

// Get equipment in this room
$equipment = $equipmentModel->getEquipmentByRoom($roomId);

// Get lab technician details if assigned
$technician = null;
if (!empty($room['lab_technician_id'])) {
    $technician = $userModel->getUserById($room['lab_technician_id']);
}

// Get equipment stats
$equipmentStats = [
    'total' => count($equipment),
    'by_condition' => [
        'new' => 0,
        'good' => 0,
        'fair' => 0,
        'poor' => 0,
        'under maintenance' => 0,
        'disposed' => 0
    ],
    'by_category' => []
];

// Calculate statistics
foreach ($equipment as $item) {
    // Count by condition
    if (isset($equipmentStats['by_condition'][$item['condition_status']])) {
        $equipmentStats['by_condition'][$item['condition_status']]++;
    }
    
    // Count by category
    if (!isset($equipmentStats['by_category'][$item['category_name']])) {
        $equipmentStats['by_category'][$item['category_name']] = 0;
    }
    $equipmentStats['by_category'][$item['category_name']]++;
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>
            <i class="fas fa-door-open me-2"></i><?php echo htmlspecialchars($room['room_name']); ?>
        </h1>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Room List
        </a>
        <?php if ($auth->canManageRooms()): ?>
        <a href="edit.php?id=<?php echo $roomId; ?>" class="btn btn-primary ms-2">
            <i class="fas fa-edit me-2"></i>Edit
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Room Details -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Room Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h3 class="text-primary mb-3"><?php echo htmlspecialchars($room['room_name']); ?></h3>
                    
                    <p class="mb-2">
                        <span class="fw-bold">Building:</span> 
                        <?php echo htmlspecialchars($room['building']); ?>
                    </p>
                    
                    <p class="mb-2">
                        <span class="fw-bold">Floor:</span> 
                        <?php echo htmlspecialchars($room['floor']); ?>
                    </p>
                    
                    <p class="mb-2">
                        <span class="fw-bold">Room Number:</span> 
                        <?php echo htmlspecialchars($room['room_number']); ?>
                    </p>
                    
                    <p class="mb-2">
                        <span class="fw-bold">Capacity:</span> 
                        <?php echo $room['capacity'] ? htmlspecialchars($room['capacity']) . ' people' : 'Not specified'; ?>
                    </p>
                    
                    <p class="mb-2">
                        <span class="fw-bold">Status:</span> 
                        <?php 
                        $statusClass = [
                            'active' => 'success',
                            'inactive' => 'danger',
                            'under maintenance' => 'warning'
                        ];
                        $class = $statusClass[$room['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $class; ?>">
                            <?php echo ucfirst($room['status']); ?>
                        </span>
                    </p>
                    
                    <?php if ($room['status'] === 'under maintenance'): ?>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This room is currently under maintenance and may not be available for use.
                    </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h5 class="text-secondary">Lab Technician</h5>
                    <?php if ($technician): ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-3">
                            <div class="avatar-initial rounded-circle bg-primary">
                                <?php echo strtoupper(substr($technician['first_name'], 0, 1) . substr($technician['last_name'], 0, 1)); ?>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($technician['first_name'] . ' ' . $technician['last_name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($technician['email']); ?></small>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No lab technician assigned to this room.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Equipment Statistics -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Equipment Statistics</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <h4 class="display-5"><?php echo $equipmentStats['total']; ?></h4>
                    <p class="text-muted">Total Equipment Items</p>
                    
                    <a href="../equipment/index.php?room=<?php echo $roomId; ?>" class="btn btn-primary">
                        <i class="fas fa-laptop me-2"></i>View All Equipment
                    </a>
                </div>
                
                <?php if ($equipmentStats['total'] > 0): ?>
                <!-- Condition Breakdown -->
                <div class="mb-4">
                    <h6 class="fw-bold">Equipment by Condition</h6>
                    
                    <?php foreach ($equipmentStats['by_condition'] as $condition => $count): ?>
                        <?php if ($count > 0): ?>
                        <div class="d-flex align-items-center mb-2">
                            <?php
                            $conditionClasses = [
                                'new' => 'success',
                                'good' => 'info',
                                'fair' => 'primary',
                                'poor' => 'warning',
                                'under maintenance' => 'danger',
                                'disposed' => 'secondary'
                            ];
                            $conditionClass = $conditionClasses[$condition] ?? 'secondary';
                            $percentage = ($count / $equipmentStats['total']) * 100;
                            ?>
                            <span class="badge bg-<?php echo $conditionClass; ?> me-2"><?php echo ucfirst($condition); ?></span>
                            <div class="progress flex-grow-1" style="height: 10px;">
                                <div class="progress-bar bg-<?php echo $conditionClass; ?>" role="progressbar" 
                                    style="width: <?php echo $percentage; ?>%" 
                                    aria-valuenow="<?php echo $percentage; ?>" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100"></div>
                            </div>
                            <span class="ms-2"><?php echo $count; ?></span>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <!-- Category Breakdown -->
                <div>
                    <h6 class="fw-bold">Equipment by Category</h6>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tbody>
                                <?php foreach ($equipmentStats['by_category'] as $category => $count): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td class="text-end"><?php echo $count; ?> items</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No equipment is currently assigned to this room. 
                    <a href="../equipment/index.php" class="alert-link">Browse equipment</a> to assign items to this room.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Equipment List -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Equipment in this Room</h5>
    </div>
    <div class="card-body">
        <?php if (empty($equipment)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No equipment is currently assigned to this room.
        </div>
        
        <?php if ($auth->canManageEquipment()): ?>
        <p>Would you like to assign equipment to this room?</p>
        <a href="../equipment/index.php" class="btn btn-primary">
            <i class="fas fa-laptop me-2"></i>Browse Equipment
        </a>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Equipment Name</th>
                        <th>Category</th>
                        <th>Serial Number</th>
                        <th>Condition</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipment as $item): ?>
                    <tr>
                        <td><?php echo $item['equipment_id']; ?></td>
                        <td><?php echo htmlspecialchars($item['equipment_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['serial_number']); ?></td>
                        <td>
                            <?php echo Helpers::getConditionBadge($item['condition_status']); ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="../equipment/view.php?id=<?php echo $item['equipment_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($auth->canManageEquipment()): ?>
                                <a href="../equipment/edit.php?id=<?php echo $item['equipment_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../equipment/move.php?id=<?php echo $item['equipment_id']; ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-exchange-alt"></i>
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

<?php
// Include footer
require_once '../includes/footer.php';
?>