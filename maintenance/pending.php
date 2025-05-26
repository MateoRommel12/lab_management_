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
$pageTitle = "Pending Maintenance Requests";
$currentPage = 'maintenance';

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/Maintenance.php';

// Initialize models
$maintenanceModel = new Maintenance();

// Get pending requests
$pendingRequests = $maintenanceModel->getPendingRequests();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>
            <i class="fas fa-tools me-2"></i>Pending Maintenance Requests
        </h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover datatable">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>User</th>
                        <th>Equipment</th>
                        <th>Request Date</th>
                        <th>Issue Description</th>
                        <th>Priority</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRequests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['request_id']); ?></td>
                        <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['equipment_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                        <td><?php echo htmlspecialchars($request['issue_description']); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo match($request['priority']) {
                                    'high' => 'danger',
                                    'medium' => 'warning',
                                    'low' => 'info',
                                    default => 'secondary'
                                };
                            ?>">
                                <?php echo ucfirst($request['priority']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="approve_request.php?id=<?php echo $request['request_id']; ?>" 
                                   class="btn btn-sm btn-success" 
                                   data-bs-toggle="tooltip" 
                                   title="Approve">
                                    <i class="fas fa-check"></i>
                                </a>
                                <a href="reject_request.php?id=<?php echo $request['request_id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   data-bs-toggle="tooltip" 
                                   title="Reject">
                                    <i class="fas fa-times"></i>
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
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 