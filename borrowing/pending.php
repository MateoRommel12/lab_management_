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
$pageTitle = "Pending Borrowing Requests";
$currentPage = 'borrowing';

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/Borrowing.php';

// Initialize models
$borrowingModel = new Borrowing();

// Get pending requests
$pendingRequests = $borrowingModel->getPendingRequests();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>
            <i class="fas fa-clock me-2"></i>Pending Borrowing Requests
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
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Purpose</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRequests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['request_id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($request['user_name'] ?? 'Unknown User'); ?></td>
                        <td><?php echo htmlspecialchars($request['equipment_name'] ?? 'Unknown Equipment'); ?></td>
                        <td><?php echo isset($request['request_date']) ? date('M d, Y', strtotime($request['request_date'])) : 'N/A'; ?></td>
                        <td><?php echo isset($request['borrow_date']) ? date('M d, Y', strtotime($request['borrow_date'])) : 'N/A'; ?></td>
                        <td><?php echo isset($request['expected_return_date']) ? date('M d, Y', strtotime($request['expected_return_date'])) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($request['purpose'] ?? ''); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="approve.php?id=<?php echo $request['request_id']; ?>" 
                                   class="btn btn-sm btn-success" 
                                   data-bs-toggle="tooltip" 
                                   title="Approve">
                                    <i class="fas fa-check"></i>
                                </a>
                                <a href="reject.php?id=<?php echo $request['request_id']; ?>" 
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