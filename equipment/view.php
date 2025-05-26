<?php
require_once '../config/config.php';
require_once '../utils/Helpers.php';

// Set page title
$pageTitle = "View Equipment";

// Include header
require_once '../includes/header.php';

// Require login
$auth->requireLogin();

// Check if user has permission to view equipment
if (!$auth->canViewEquipment()) {
    Helpers::redirectWithMessage("../index.php", "You don't have permission to view equipment.", "danger");
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
require_once '../models/Borrowing.php';
require_once '../models/Maintenance.php';

// Initialize models
$equipmentModel = new Equipment();
$borrowingModel = new Borrowing();
$maintenanceModel = new Maintenance();

// Get equipment details
$equipment = $equipmentModel->getEquipmentById($equipmentId);

if (!$equipment) {
    Helpers::redirectWithMessage("index.php", "Equipment not found", "danger");
    exit;
}

// Get borrowing history
$borrowingHistory = $borrowingModel->getBorrowingHistoryByEquipment($equipmentId);

// Get maintenance history
$maintenanceHistory = $maintenanceModel->getMaintenanceHistoryByEquipment($equipmentId);

// Get movement history
$movementHistory = $equipmentModel->getEquipmentMovementHistory($equipmentId);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>
            <i class="fas fa-laptop me-2"></i><?php echo htmlspecialchars($equipment['name'] ?? 'Unknown Equipment'); ?>
        </h1>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Equipment List
        </a>
        <?php if ($auth->canManageEquipment()): ?>
        <a href="edit.php?id=<?php echo $equipmentId; ?>" class="btn btn-primary ms-2">
            <i class="fas fa-edit me-2"></i>Edit
        </a>
        <?php endif; ?>
        <?php if ($auth->canBorrowEquipment()): ?>
        <a href="../borrowing/borrow.php?equipment_id=<?php echo $equipmentId; ?>" class="btn btn-warning ms-2">
            <i class="fas fa-hand-holding me-2"></i>Borrow
        </a>
        <?php endif; ?>
        <?php if ($auth->canReportMaintenance()): ?>
        <a href="../maintenance/report.php?equipment_id=<?php echo $equipmentId; ?>" class="btn btn-danger ms-2">
            <i class="fas fa-tools me-2"></i>Report Issue
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Equipment Details -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Equipment Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h3 class="text-primary mb-3"><?php echo htmlspecialchars($equipment['name'] ?? 'Unknown Equipment'); ?></h3>
                    <p class="mb-2">
                        <span class="fw-bold">Category:</span> 
                        <?php echo htmlspecialchars($equipment['category_name'] ?? 'N/A'); ?>
                    </p>
                    <p class="mb-2">
                        <span class="fw-bold">Serial Number:</span> 
                        <?php echo htmlspecialchars($equipment['serial_number'] ?? 'N/A'); ?>
                    </p>
                    <p class="mb-2">
                        <span class="fw-bold">Brand:</span> 
                        <?php echo htmlspecialchars($equipment['manufacturer'] ?? 'N/A'); ?>
                    </p>
                    <p class="mb-2">
                        <span class="fw-bold">Model:</span> 
                        <?php echo htmlspecialchars($equipment['model'] ?? 'N/A'); ?>
                    </p>
                    <p class="mb-2">
                        <span class="fw-bold">Description:</span> 
                        <?php echo htmlspecialchars($equipment['description'] ?? 'No description available'); ?>
                    </p>
                </div>
                
                <div class="mb-4">
                    <h5 class="text-secondary">Procurement Information</h5>
                    <p class="mb-2">
                        <span class="fw-bold">Acquisition Date:</span> 
                        <?php echo $equipment['purchase_date'] ? Helpers::formatDate($equipment['purchase_date']) : 'N/A'; ?>
                    </p>
                    <p class="mb-2">
                        <span class="fw-bold">Cost:</span> 
                        <?php 
                        if (isset($equipment['cost']) && is_numeric($equipment['cost'])) {
                            echo Helpers::formatCurrency($equipment['cost']);
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </p>
                    <p class="mb-2">
                        <span class="fw-bold">Supplier:</span> 
                        <?php echo htmlspecialchars($equipment['supplier'] ?? 'N/A'); ?>
                    </p>
                </div>
                
                <div>
                    <h5 class="text-secondary">Status Information</h5>
                    <p class="mb-2">
                        <span class="fw-bold">Current Location:</span> 
                        <?php 
                        if (!empty($equipment['room_name'])) {
                            echo htmlspecialchars($equipment['room_name']);
                            if (!empty($equipment['building'])) {
                                echo ' - ' . htmlspecialchars($equipment['building']);
                            }
                            if (!empty($equipment['room_number'])) {
                                echo ' (Room ' . htmlspecialchars($equipment['room_number']) . ')';
                            }
                        } else {
                            echo 'Not Assigned';
                        }
                        ?>
                    </p>
                    <p class="mb-2">
                        <span class="fw-bold">Condition:</span> 
                        <?php echo Helpers::getConditionBadge($equipment['equipment_condition'] ?? 'unknown'); ?>
                    </p>
                    <?php if (($equipment['equipment_condition'] ?? '') === 'under maintenance'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This equipment is currently under maintenance and not available for use.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Current Status & QR Code -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Current Status</h5>
            </div>
            <div class="card-body">
                <?php
                // Check if equipment is currently borrowed
                $currentBorrowing = $borrowingModel->getCurrentBorrowingByEquipment($equipmentId);
                if ($currentBorrowing):
                ?>
                <div class="alert alert-info mb-4">
                    <h5><i class="fas fa-info-circle me-2"></i>This Equipment is Currently Borrowed</h5>
                    <p class="mb-1">
                        <strong>Borrowed By:</strong> <?php echo htmlspecialchars($currentBorrowing['borrower_name']); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Borrow Date:</strong> <?php echo Helpers::formatDateTime($currentBorrowing['borrow_date']); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Expected Return:</strong> <?php echo Helpers::formatDateTime($currentBorrowing['expected_return_date']); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Purpose:</strong> <?php echo htmlspecialchars($currentBorrowing['purpose']); ?>
                    </p>
                    
                    <?php if ($auth->canApproveBorrowing()): ?>
                    <div class="mt-3">
                        <a href="../borrowing/return.php?id=<?php echo $currentBorrowing['request_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-undo me-2"></i>Process Return
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php
                // Check if equipment has active maintenance
                $activeMaintenance = $maintenanceModel->getActiveMaintenanceByEquipment($equipmentId);
                if ($activeMaintenance):
                ?>
                <div class="alert alert-warning mb-4">
                    <h5><i class="fas fa-tools me-2"></i>This Equipment is Currently Under Maintenance</h5>
                    <p class="mb-1">
                        <strong>Reported By:</strong> <?php echo htmlspecialchars($activeMaintenance['reporter_name']); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Reported On:</strong> <?php echo Helpers::formatDateTime($activeMaintenance['report_date']); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Issue:</strong> <?php echo htmlspecialchars($activeMaintenance['issue_description']); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Status:</strong> <?php echo ucfirst($activeMaintenance['status']); ?>
                    </p>
                    
                    <?php if ($auth->canManageMaintenance()): ?>
                    <div class="mt-3">
                        <a href="../maintenance/update.php?id=<?php echo $activeMaintenance['maintenance_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-wrench me-2"></i>Update Maintenance
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!$currentBorrowing && !$activeMaintenance && $equipment['equipment_condition'] !== 'disposed'): ?>
                <div class="alert alert-success mb-4">
                    <h5><i class="fas fa-check-circle me-2"></i>This Equipment is Available</h5>
                    <p>This equipment is currently not borrowed and not under maintenance.</p>
                    
                    <?php if ($auth->canBorrowEquipment()): ?>
                    <div class="mt-3">
                        <a href="../borrowing/borrow.php?equipment_id=<?php echo $equipmentId; ?>" class="btn btn-warning">
                            <i class="fas fa-hand-holding me-2"></i>Borrow This Equipment
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- QR Code (Placeholder) -->
                <div class="mt-4 text-center">
                    <h5 class="mb-3">Equipment QR Code</h5>
                    <div class="mb-3 qr-code-placeholder" style="height: 200px; width: 200px; margin: 0 auto; background-color: #f5f5f5; display: flex; align-items: center; justify-content: center;">
                        <span class="text-muted">QR Code</span>
                    </div>
                    <p class="text-muted">Scan this QR code to quickly access equipment details</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Equipment History Tabs -->
<div class="card mb-4">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="equipmentHistoryTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="borrowing-tab" data-bs-toggle="tab" data-bs-target="#borrowing" type="button" role="tab" aria-controls="borrowing" aria-selected="true">
                    <i class="fas fa-exchange-alt me-2"></i>Borrowing History
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab" aria-controls="maintenance" aria-selected="false">
                    <i class="fas fa-tools me-2"></i>Maintenance History
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="movement-tab" data-bs-toggle="tab" data-bs-target="#movement" type="button" role="tab" aria-controls="movement" aria-selected="false">
                    <i class="fas fa-map-marker-alt me-2"></i>Movement History
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="equipmentHistoryTabContent">
            <!-- Borrowing History Tab -->
            <div class="tab-pane fade show active" id="borrowing" role="tabpanel" aria-labelledby="borrowing-tab">
                <?php if (empty($borrowingHistory)): ?>
                <p class="text-muted">No borrowing history for this equipment.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Borrower</th>
                                <th>Borrow Date</th>
                                <th>Return Date</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowingHistory as $borrowing): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($borrowing['borrower_name']); ?></td>
                                <td><?php echo Helpers::formatDateTime($borrowing['borrow_date']); ?></td>
                                <td>
                                    <?php 
                                    if ($borrowing['actual_return_date']) {
                                        echo Helpers::formatDateTime($borrowing['actual_return_date']);
                                    } elseif ($borrowing['status'] === 'borrowed' || $borrowing['status'] === 'overdue') {
                                        echo '<span class="text-warning">Not Returned Yet</span>';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($borrowing['actual_return_date']) {
                                        $borrow = new DateTime($borrowing['borrow_date']);
                                        $return = new DateTime($borrowing['actual_return_date']);
                                        $diff = $borrow->diff($return);
                                        
                                        if ($diff->days > 0) {
                                            echo $diff->days . ' days';
                                        } else {
                                            echo $diff->h . ' hours';
                                        }
                                    } elseif ($borrowing['status'] === 'borrowed' || $borrowing['status'] === 'overdue') {
                                        $borrow = new DateTime($borrowing['borrow_date']);
                                        $now = new DateTime();
                                        $diff = $borrow->diff($now);
                                        
                                        if ($diff->days > 0) {
                                            echo $diff->days . ' days (ongoing)';
                                        } else {
                                            echo $diff->h . ' hours (ongoing)';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo Helpers::getStatusBadge($borrowing['status']); ?></td>
                                <td>
                                    <a href="../borrowing/view.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Maintenance History Tab -->
            <div class="tab-pane fade" id="maintenance" role="tabpanel" aria-labelledby="maintenance-tab">
                <?php if (empty($maintenanceHistory)): ?>
                <p class="text-muted">No maintenance history for this equipment.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Reported By</th>
                                <th>Issue</th>
                                <th>Report Date</th>
                                <th>Completion Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($maintenanceHistory as $maintenance): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($maintenance['reporter_name']); ?></td>
                                <td><?php echo Helpers::truncateText($maintenance['issue_description'], 50); ?></td>
                                <td><?php echo Helpers::formatDateTime($maintenance['report_date']); ?></td>
                                <td>
                                    <?php echo $maintenance['completion_date'] ? Helpers::formatDateTime($maintenance['completion_date']) : '-'; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'pending' => 'warning',
                                        'in progress' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'secondary'
                                    ];
                                    $statusClass = $statusClasses[$maintenance['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($maintenance['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../maintenance/view.php?id=<?php echo $maintenance['maintenance_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Movement History Tab -->
            <div class="tab-pane fade" id="movement" role="tabpanel" aria-labelledby="movement-tab">
                <?php if (empty($movementHistory)): ?>
                <p class="text-muted">No movement history for this equipment.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Moved By</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movementHistory as $movement): ?>
                            <tr>
                                <td><?php echo Helpers::formatDateTime($movement['movement_date']); ?></td>
                                <td>
                                    <?php echo $movement['from_room_name'] ? htmlspecialchars($movement['from_room_name']) : '<span class="text-muted">Not Assigned</span>'; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($movement['to_room_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($movement['moved_by_name']); ?></td>
                                <td><?php echo htmlspecialchars($movement['reason'] ?: 'No reason provided'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

