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
$pageTitle = "Admin Dashboard";
$currentPage = 'dashboard';

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/Borrowing.php';
require_once __DIR__ . '/../models/Maintenance.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AuditLog.php';

// Get statistics
$equipmentModel = new Equipment();
$roomModel = new Room();
$borrowingModel = new Borrowing();
$maintenanceModel = new Maintenance();
$userModel = new User();
$auditLogModel = new AuditLog();

$equipmentStats = $equipmentModel->getEquipmentStatistics();
$roomStats = $roomModel->getRoomStatistics();
$borrowingStats = $borrowingModel->getBorrowingStatistics();
$maintenanceStats = $maintenanceModel->getMaintenanceStatistics();
$userStats = $userModel->getUserStatistics();
$recentLogs = $auditLogModel->getRecentLogs(10);
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="display-5 mb-4">
            <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
        </h1>
        <p class="lead">Welcome back, Administrator <?php echo $auth->getUser()['first_name'] . ' ' . $auth->getUser()['last_name']; ?>!</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card text-center">
            <div class="card-body">
                <div class="dashboard-icon text-primary">
                    <i class="fas fa-users"></i>
                </div>
                <h5 class="card-title">Users</h5>
                <p class="stats-value"><?php echo $userStats['total'] ?? 0; ?></p>
                <p class="stats-label">Total Users</p>
            </div>
            <div class="card-footer bg-transparent">
                <a href="users.php" class="btn btn-sm btn-primary">Manage Users</a>
            </div>
        </div>
    </div>
    
    
    <div class="col-md-3 mb-3">
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
                <a href="equipment.php" class="btn btn-sm btn-success">Manage Equipment</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card text-center">
            <div class="card-body">
                <div class="dashboard-icon text-primary">
                    <i class="fas fa-door-open"></i>
                </div>
                <h5 class="card-title">Rooms</h5>
                <p class="stats-value"><?php echo $roomStats['total'] ?? 0; ?></p>
                <p class="stats-label">Total Rooms</p>
            </div>
            <div class="card-footer bg-transparent">
                <a href="rooms.php" class="btn btn-sm btn-primary">Manage Rooms</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card text-center">
            <div class="card-body">
                <div class="dashboard-icon text-info">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h5 class="card-title">Borrowings</h5>
                <p class="stats-value"><?php echo $borrowingStats['pending_count'] ?? 0; ?></p>
                <p class="stats-label">Pending Approvals</p>
            </div>
            <div class="card-footer bg-transparent">
                <a href="../borrowing/pending.php" class="btn btn-sm btn-info">View Requests</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
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
                <a href="maintenance/assign.php" class="btn btn-sm btn-warning">Manage Maintenance</a>
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
                        <a href="add_user.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-user-plus me-2"></i>Add New User
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="add_equipment.php" class="btn btn-outline-success w-100">
                            <i class="fas fa-plus-circle me-2"></i>Add Equipment
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="maintenance/assign.php" class="btn btn-outline-warning w-100">
                            <i class="fas fa-tools me-2"></i>Assign Maintenance
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="add_room.php" class="btn btn-outline-info w-100">
                            <i class="fas fa-door-open me-2"></i>Add Room
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="../equipment/movements.php" class="btn btn-outline-warning w-100">
                            <i class="fas fa-exchange-alt me-2"></i>Equipment Movements
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="settings.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-cogs me-2"></i>System Settings
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="reports.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-chart-bar me-2"></i>Generate Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 

<!-- Recent Activity -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent System Activity</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentLogs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No recent activity found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentLogs as $log): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($log['action_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                        <td><?php echo htmlspecialchars($log['action_description'] ?? 'Unknown action'); ?></td>
                                        <td><?php echo htmlspecialchars($log['ip_address'] ?? 'Unknown'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="audit-logs.php" class="btn btn-outline-primary">
                        View All Activity Logs
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 