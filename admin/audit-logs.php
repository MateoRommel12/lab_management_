<?php
// Set page title
$pageTitle = "Audit Logs";

// Include header
require_once '../includes/header.php';

// Require admin privileges
$auth->requireAdmin();

// Include required models
require_once '../models/AuditLog.php';
require_once '../models/User.php';

// Initialize models
$auditLogModel = new AuditLog();
$userModel = new User();

// Get filters
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : '';
$actionType = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Default to last 7 days if no date filters
if (empty($startDate) && empty($endDate)) {
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime('-7 days'));
}

// Get audit logs with filters
$logs = $auditLogModel->getLogs($userId, $actionType, $startDate, $endDate);

// Get all users for filter dropdown
$users = $userModel->getAllUsersWithRoles();

// Get distinct action types for filter dropdown
$actionTypes = $auditLogModel->getDistinctActionTypes();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>
            <i class="fas fa-history me-2"></i>Audit Logs
        </h1>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filter Logs</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="audit-logs.php" class="row g-3">
            <div class="col-md-3">
                <label for="user_id" class="form-label">User</label>
                <select name="user_id" id="user_id" class="form-select">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['user_id']; ?>" <?php echo ($userId == $user['user_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['username'] . ' (' . $user['first_name'] . ' ' . $user['last_name'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="action_type" class="form-label">Action Type</label>
                <select name="action_type" id="action_type" class="form-select">
                    <option value="">All Actions</option>
                    <?php foreach ($actionTypes as $action): ?>
                    <option value="<?php echo $action['action_type']; ?>" <?php echo ($actionType == $action['action_type']) ? 'selected' : ''; ?>>
                        <?php echo ucfirst(htmlspecialchars($action['action_type'])); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $startDate; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $endDate; ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Apply Filters
                </button>
                <a href="audit-logs.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Logs List -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Audit Logs</h5>
    </div>
    <div class="card-body">
        <?php if (empty($logs)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No logs found matching your criteria.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Action Type</th>
                        <th>Action Description</th>
                        <th>IP Address</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo $log['log_id']; ?></td>
                        <td><?php echo htmlspecialchars($log['username'] ?? 'Unknown'); ?></td>
                        <td>
                            <span class="badge bg-<?php echo ($log['action_type'] == 'login' || $log['action_type'] == 'registration') ? 'success' : (($log['action_type'] == 'logout') ? 'info' : 'primary'); ?>">
                                <?php echo ucfirst(htmlspecialchars($log['action_type'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($log['action_description']); ?></td>
                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        <td><?php echo Helpers::formatDateTime($log['timestamp']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- DataTables Initialization -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new DataTable('.datatable', {
            responsive: true,
            ordering: true,
            order: [[5, 'desc']], // Sort by timestamp descending
            paging: true,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
        });
    });
</script>

