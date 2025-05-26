<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base URL
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../utils/Helpers.php';

// Initialize Auth
$auth = Auth::getInstance();

// Check if user is lab technician, redirect if not
if (!$auth->isLabTechnician() && !$auth->isAdmin()) {
    header('Location: ../access-denied.php');
    exit;
}

// Include required models
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/Borrowing.php';
require_once __DIR__ . '/../models/Maintenance.php';

// Get statistics
$equipmentModel = new Equipment();
$roomModel = new Room();
$borrowingModel = new Borrowing();
$maintenanceModel = new Maintenance();

$equipmentStats = $equipmentModel->getEquipmentStatistics();
$roomStats = $roomModel->getRoomStatistics();
$borrowingStats = $borrowingModel->getBorrowingStatistics();
$maintenanceStats = $maintenanceModel->getMaintenanceStatistics();

// Get recent equipment movements
$recentMovements = $equipmentModel->getRecentMovements(5);

// Get pending maintenance requests
$pendingMaintenance = $maintenanceModel->getPendingRequests();

// Get in-progress maintenance requests for this technician
$inProgressMaintenance = $maintenanceModel->getMaintenanceByTechnician($auth->getUser()['user_id']);

// Get pending borrowing requests
$pendingBorrowings = $borrowingModel->getPendingRequests();

// Set page title
$pageTitle = "Technician Dashboard";
$currentPage = 'dashboard';

// Include header
require_once __DIR__ . '/../includes/header.php';

?>

<div class="row">
    <div class="col-md-12">
        <h1 class="display-5 mb-4">
            <i class="fas fa-tools me-2"></i>Technician Dashboard
        </h1>
        <p class="lead">Welcome back, <?php echo $auth->getUser()['first_name'] . ' ' . $auth->getUser()['last_name']; ?>!</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card dashboard-card text-center">
            <div class="card-body">
                <div class="dashboard-icon text-success">
                    <i class="fas fa-laptop"></i>
                </div>
                <h5 class="card-title">Equipment</h5>
                <p class="stats-value"><?php echo $equipmentStats['total']; ?></p>
                <p class="stats-label">Total Items</p>
            </div>
            <div class="card-footer bg-transparent">
                <a href="../equipment/index.php" class="btn btn-sm btn-success">Manage Equipment</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card dashboard-card text-center">
            <div class="card-body">
                <div class="dashboard-icon text-warning">
                    <i class="fas fa-tools"></i>
                </div>
                <h5 class="card-title">Maintenance</h5>
                <p class="stats-value"><?php echo $maintenanceStats['pending_count'] ?? 0; ?></p>
                <p class="stats-label">Pending Requests</p>
            </div>
            <div class="card-footer bg-transparent">
                <a href="/maintenance/pending.php" class="btn btn-sm btn-warning">View Requests</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card dashboard-card text-center">
            <div class="card-body">
                <div class="dashboard-icon text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h5 class="card-title">Issues</h5>
                <p class="stats-value"><?php echo isset($equipmentStats['by_condition']['poor']) ? $equipmentStats['by_condition']['poor'] : 0; ?></p>
                <p class="stats-label">Equipment in Poor Condition</p>
            </div>
            <div class="card-footer bg-transparent">
                <a href="../equipment/index.php?condition=poor" class="btn btn-sm btn-danger">View Equipment</a>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo Helpers::url('maintenance/report.php'); ?>" class="btn btn-outline-warning w-100">
                            <i class="fas fa-wrench me-2"></i>Report Maintenance
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo Helpers::url('equipment/move.php'); ?>" class="btn btn-outline-primary w-100">
                            <i class="fas fa-exchange-alt me-2"></i>Move Equipment
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo Helpers::url('equipment/index.php'); ?>" class="btn btn-outline-info w-100">
                            <i class="fas fa-chart-bar me-2"></i>All Equipment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Equipment Movements -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Equipment Movements</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentMovements)): ?>
                    <div class="alert alert-info">
                        No recent equipment movements to display.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Equipment</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Moved By</th>
                                    <th>Date</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentMovements as $movement): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($movement['equipment_name']); ?></td>
                                    <td>
                                        <?php echo $movement['from_room'] ? htmlspecialchars($movement['from_room']) : '<span class="text-muted">Not Assigned</span>'; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($movement['to_room']); ?></td>
                                    <td><?php echo htmlspecialchars($movement['moved_by_name']); ?></td>
                                    <td><?php echo Helpers::formatDateTime($movement['movement_date']); ?></td>
                                    <td><?php echo htmlspecialchars($movement['reason'] ?: 'No reason provided'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="/equipment/movements.php" class="btn btn-outline-primary">
                            View All Movements
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Pending Maintenance Requests -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Pending Maintenance Requests</h5>
            </div>
            <div class="card-body">
                <?php if (empty($pendingMaintenance)): ?>
                    <div class="alert alert-success">
                        No pending maintenance requests. All equipment is functioning properly!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Equipment</th>
                                    <th>Issue</th>
                                    <th>Reported By</th>
                                    <th>Date Reported</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingMaintenance as $maintenance): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($maintenance['name'] ?? 'Unknown Equipment'); ?></td>
                                    <td><?php echo htmlspecialchars($maintenance['issue_description']); ?></td>
                                    <td><?php echo htmlspecialchars($maintenance['reporter_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo Helpers::formatDateTime($maintenance['report_date']); ?></td>
                                    <td>
                                        <?php if ($maintenance['status'] === 'pending'): ?>
                                            <a href="<?php echo Helpers::url('maintenance/assign.php?id=' . $maintenance['maintenance_id']); ?>" class="btn btn-sm btn-success" title="Assign to me">
                                                <i class="fas fa-user-check"></i>
                                            </a>
                                        <?php elseif ($maintenance['status'] === 'in progress' && $maintenance['technician_assigned'] === $auth->getUser()['user_id']): ?>
                                            <a href="<?php echo Helpers::url('maintenance/update.php?id=' . $maintenance['maintenance_id']); ?>" class="btn btn-sm btn-primary" title="Update Progress">
                                                <i class="fas fa-tasks"></i>
                                            </a>

                                        <?php endif; ?>
                                        <a href="<?php echo Helpers::url('maintenance/view.php?id=' . $maintenance['maintenance_id']); ?>" class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?php echo Helpers::url('maintenance/index.php'); ?>" class="btn btn-outline-warning">
                            View All Maintenance Requests
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- In Progress Maintenance Requests -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">In Progress Maintenance</h5>
            </div>
            <div class="card-body">
                <?php if (empty($inProgressMaintenance)): ?>
                    <div class="alert alert-info">
                        You have no in-progress maintenance tasks.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Equipment</th>
                                    <th>Issue</th>
                                    <th>Reported By</th>
                                    <th>Date Reported</th>
                                    <th>Maintenance ID</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inProgressMaintenance as $maintenance): ?>
                                <?php if ($maintenance['status'] === 'in progress'): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($maintenance['name'] ?? 'Unknown Equipment'); ?></td>
                                    <td><?php echo htmlspecialchars($maintenance['issue_description']); ?></td>
                                    <td><?php echo htmlspecialchars($maintenance['reporter_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo Helpers::formatDateTime($maintenance['report_date']); ?></td>
                                    <td><?php echo htmlspecialchars($maintenance['maintenance_id']); ?></td>
                                    <td>
                                        <a href="<?php echo Helpers::url('maintenance/update.php?id=' . $maintenance['maintenance_id']); ?>" class="btn btn-sm btn-primary" title="Update Progress">
                                            <i class="fas fa-tasks"></i>
                                        </a>
                                        <a href="<?php echo Helpers::url('maintenance/complete.php?id=' . $maintenance['maintenance_id']); ?>" class="btn btn-sm btn-success" title="Mark as Complete">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="<?php echo Helpers::url('maintenance/view.php?id=' . $maintenance['maintenance_id']); ?>" class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 
