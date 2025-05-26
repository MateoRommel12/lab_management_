<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once 'config/config.php';
require_once 'utils/Auth.php';
require_once 'utils/Helpers.php';

// Initialize Auth
$auth = Auth::getInstance();

// Check authentication before setting page title - redirect first if not logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// For admins, redirect to admin dashboard
if ($auth->isAdmin()) {
    header('Location: admin/dashboard.php');
    exit;
}

// Set page title
$pageTitle = "Dashboard";

// Include header
require_once 'includes/header.php';

// Include required models
require_once 'models/Equipment.php';
require_once 'models/Room.php';
require_once 'models/Borrowing.php';
require_once 'models/Maintenance.php';

// Get statistics
$equipmentModel = new Equipment();
$roomModel = new Room();
$borrowingModel = new Borrowing();
$maintenanceModel = new Maintenance();

$equipmentStats = $equipmentModel->getEquipmentStatistics();
$roomStats = $roomModel->getRoomStatistics();
$borrowingStats = $borrowingModel->getBorrowingStatistics();
$maintenanceStats = $maintenanceModel->getMaintenanceStatistics();

// Get recent activities based on user role
$userId = $auth->getUserId();
$recentBorrowings = $borrowingModel->getBorrowingByUser($userId);
$recentMaintenance = $maintenanceModel->getMaintenanceByReporter($userId);

// If lab technician, get pending requests for approval
$pendingBorrowings = [];
$pendingMaintenance = [];

if ($auth->canApproveBorrowing()) {
    $pendingBorrowings = $borrowingModel->getPendingRequests();
    $pendingMaintenance = $maintenanceModel->getPendingRequests();
}
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="display-5 mb-4">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </h1>
        <p class="lead">Welcome back, <?php echo $auth->getUser()['first_name'] . ' ' . $auth->getUser()['last_name']; ?>!</p>
    </div>
</div>

<?php if ($auth->canViewReports()): ?>
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card dashboard-card text-center">
                <div class="card-body">
                    <div class="dashboard-icon text-primary">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h5 class="card-title">Equipment</h5>
                    <p class="stats-value"><?php echo $equipmentStats['total']; ?></p>
                    <p class="stats-label">Total Items</p>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?php echo APP_URL; ?>/equipment/index.php" class="btn btn-sm btn-primary">View All</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card dashboard-card text-center">
                <div class="card-body">
                    <div class="dashboard-icon text-success">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <h5 class="card-title">Rooms</h5>
                    <p class="stats-value"><?php echo $roomStats['total']; ?></p>
                    <p class="stats-label">Total Rooms</p>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?php echo APP_URL; ?>/rooms/index.php" class="btn btn-sm btn-success">View All</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card dashboard-card text-center">
                <div class="card-body">
                    <div class="dashboard-icon text-info">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h5 class="card-title">Borrowings</h5>
                    <p class="stats-value"><?php echo $borrowingStats['active_count'] ?? 0; ?></p>
                    <p class="stats-label">Active Borrowings</p>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?php echo APP_URL; ?>/borrowing/active.php" class="btn btn-sm btn-info">View All</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
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
                    <a href="<?php echo APP_URL; ?>/maintenance/pending.php" class="btn btn-sm btn-warning">View All</a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Quick Access Links for Students -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Quick Access</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <a href="<?php echo APP_URL; ?>/equipment/index.php" class="btn btn-outline-primary btn-lg w-100">
                                <i class="fas fa-laptop me-2"></i>Browse Equipment
                            </a>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <a href="<?php echo APP_URL; ?>/rooms/index.php" class="btn btn-outline-success btn-lg w-100">
                                <i class="fas fa-door-open me-2"></i>View Rooms
                            </a>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <a href="<?php echo APP_URL; ?>/borrowing/borrow.php" class="btn btn-outline-info btn-lg w-100">
                                <i class="fas fa-exchange-alt me-2"></i>Borrow Equipment
                            </a>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <a href="<?php echo APP_URL; ?>/maintenance/report.php" class="btn btn-outline-warning btn-lg w-100">
                                <i class="fas fa-tools me-2"></i>Report Issue
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Recent Borrowing Requests -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-exchange-alt me-2"></i>My Recent Borrowing Requests
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentBorrowings)): ?>
                    <p class="text-muted">You don't have any borrowing requests yet.</p>
                    <div class="text-center mt-3">
                        <a href="<?php echo APP_URL; ?>/borrowing/borrow.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Borrow Equipment
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Equipment</th>
                                    <th>Borrow Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recentBorrowings, 0, 5) as $borrowing): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($borrowing['name'] ?? 'Unknown Equipment'); ?></td>
                                        <td><?php echo Helpers::formatDate($borrowing['borrow_date']); ?></td>
                                        <td><?php echo Helpers::getStatusBadge($borrowing['status']); ?></td>
                                        <td>
                                            <a href="<?php echo APP_URL; ?>/borrowing/view.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?php echo APP_URL; ?>/borrowing/my-requests.php" class="btn btn-outline-primary">
                            View All My Requests
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Maintenance Reports -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-tools me-2"></i>My Recent Maintenance Reports
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentMaintenance)): ?>
                    <p class="text-muted">You haven't reported any maintenance issues yet.</p>
                    <div class="text-center mt-3">
                        <a href="<?php echo APP_URL; ?>/maintenance/report.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Report an Issue
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Equipment</th>
                                    <th>Date Reported</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recentMaintenance, 0, 5) as $maintenance): ?>
                                    <tr>
                                        <td><?php echo $maintenance['name']; ?></td>
                                        <td><?php echo Helpers::formatDate($maintenance['report_date']); ?></td>
                                        <td><?php echo Helpers::getStatusBadge($maintenance['status']); ?></td>
                                        <td>
                                            <a href="<?php echo APP_URL; ?>/maintenance/view.php?id=<?php echo $maintenance['maintenance_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?php echo APP_URL; ?>/maintenance/my-reports.php" class="btn btn-outline-primary">
                            View All My Reports
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($auth->canApproveBorrowing()): ?>
    <div class="row">
        <!-- Pending Borrowing Requests -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title">
                        <i class="fas fa-clock me-2"></i>Pending Borrowing Requests
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingBorrowings)): ?>
                        <p class="text-muted">No pending borrowing requests at this time.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Borrower</th>
                                        <th>Equipment</th>
                                        <th>Date Requested</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($pendingBorrowings, 0, 5) as $borrowing): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($borrowing['borrower_name']); ?></td>
                                            <td><?php echo htmlspecialchars($borrowing['name'] ?? 'Unknown Equipment'); ?></td>
                                            <td><?php echo Helpers::formatDate($borrowing['request_date']); ?></td>
                                            <td>
                                                <a href="<?php echo APP_URL; ?>/borrowing/approve.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>/borrowing/reject.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo APP_URL; ?>/borrowing/pending.php" class="btn btn-outline-warning">
                                View All Pending Requests
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Pending Maintenance Requests -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title">
                        <i class="fas fa-clock me-2"></i>Pending Maintenance Requests
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingMaintenance)): ?>
                        <p class="text-muted">No pending maintenance requests at this time.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Reporter</th>
                                        <th>Equipment</th>
                                        <th>Date Reported</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($pendingMaintenance, 0, 5) as $maintenance): ?>
                                        <tr>
                                            <td><?php echo $maintenance['reporter_name']; ?></td>
                                            <td><?php echo $maintenance['equipment_name']; ?></td>
                                            <td><?php echo Helpers::formatDate($maintenance['report_date']); ?></td>
                                            <td>
                                                <a href="<?php echo APP_URL; ?>/maintenance/assign.php?id=<?php echo $maintenance['maintenance_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-user-plus"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>/maintenance/view.php?id=<?php echo $maintenance['maintenance_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo APP_URL; ?>/maintenance/pending.php" class="btn btn-outline-warning">
                                View All Pending Requests
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>



