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

// Check if user is logged in and is a student, redirect if not
if (!$auth->isLoggedIn() || !$auth->isStudentAssistant()) {
    header('Location: ../access-denied.php');
    exit;
}

// Set page title and current page
$pageTitle = "Student Dashboard";
$currentPage = 'dashboard';

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include models
require_once __DIR__ . '/../models/Borrowing.php';
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/Maintenance.php';

// Get current user
$user = $auth->getUser();

// Initialize models
$borrowingModel = new Borrowing();
$equipmentModel = new Equipment();
$maintenanceModel = new Maintenance();

// Get student's borrowing requests
$borrowings = $borrowingModel->getBorrowingsByUser($user['user_id']);

// Get student's pending borrowing requests
$pendingBorrowings = $borrowingModel->getBorrowingsByUserAndStatus($user['user_id'], 'pending');

// Get student's active borrowings
$activeBorrowings = $borrowingModel->getBorrowingsByUserAndStatus($user['user_id'], 'approved');

// Get student's maintenance reports
$maintenanceReports = $maintenanceModel->getMaintenanceReportsByUser($user['user_id']);

// Get recent equipment movements
$recentMovements = $equipmentModel->getRecentMovements(5);
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="display-5 mb-4">
                <i class="fas fa-tachometer-alt me-2"></i>Student Dashboard
            </h1>
            <p class="lead">Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</p>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-body text-center">
                    <div class="dashboard-icon text-primary">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h5 class="card-title">Pending Requests</h5>
                    <p class="stats-value"><?php echo count($pendingBorrowings); ?></p>
                    <p class="stats-label">Awaiting Approval</p>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="../borrowing/my-requests.php" class="btn btn-sm btn-primary">View My Requests</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-body text-center">
                    <div class="dashboard-icon text-success">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h5 class="card-title">Current Borrowings</h5>
                    <p class="stats-value"><?php echo count($activeBorrowings); ?></p>
                    <p class="stats-label">Items Currently Borrowed</p>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="../borrowing/active.php" class="btn btn-sm btn-success">View Borrowed Items</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-body text-center">
                    <div class="dashboard-icon text-warning">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h5 class="card-title">Maintenance Reports</h5>
                    <p class="stats-value"><?php echo count($maintenanceReports); ?></p>
                    <p class="stats-label">Issues Reported</p>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="../maintenance/my_reports.php" class="btn btn-sm btn-warning">View My Reports</a>
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
                        <div class="col-md-4 mb-3">
                            <a href="../borrowing/borrow.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-hand-holding me-2"></i>Borrow Equipment
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="../maintenance/report.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-exclamation-triangle me-2"></i>Report Issue
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="../equipment/index.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-search me-2"></i>Browse Equipment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Borrowings -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Borrowing Activity</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($borrowings)): ?>
                        <div class="alert alert-info">
                            You don't have any borrowing history yet. <a href="../borrowing/borrow.php">Borrow equipment</a> to get started.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Equipment</th>
                                        <th>Borrow Date</th>
                                        <th>Return Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Get the 5 most recent borrowings
                                    $recentBorrowings = array_slice($borrowings, 0, 5);
                                    
                                    foreach ($recentBorrowings as $borrowing): 
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($borrowing['equipment_name'] ?? 'Unknown Equipment'); ?></td>
                                            <td><?php echo Helpers::formatDate($borrowing['borrow_date']); ?></td>
                                            <td>
                                                <?php 
                                                if ($borrowing['status'] === 'returned') {
                                                    echo Helpers::formatDate($borrowing['actual_return_date']);
                                                } else {
                                                    echo Helpers::formatDate($borrowing['expected_return_date']) . ' (Expected)';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusClass = '';
                                                switch ($borrowing['status']) {
                                                    case 'pending':
                                                        $statusClass = 'warning';
                                                        break;
                                                    case 'approved':
                                                        $statusClass = 'success';
                                                        break;
                                                    case 'returned':
                                                        $statusClass = 'info';
                                                        break;
                                                    case 'rejected':
                                                        $statusClass = 'danger';
                                                        break;
                                                    default:
                                                        $statusClass = 'secondary';
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($borrowing['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="../borrowing/view.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($borrowing['status'] === 'approved' || $borrowing['status'] === 'borrowed'): ?>
                                                <a href="../borrowing/direct_return.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-undo"></i> Return
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($borrowing['status'] === 'rejected' || $borrowing['status'] === 'returned'): ?>
                                                <button type="button" class="btn btn-sm btn-danger delete-borrowing" data-id="<?php echo $borrowing['request_id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="../borrowing/my-requests.php" class="btn btn-outline-primary">
                                View All Borrowing History
                            </a>
                        </div>
                    <?php endif; ?>
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
                            <a href="../equipment/movements.php" class="btn btn-outline-primary">
                                View All Movements
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete Borrowing Request
    const deleteButtons = document.querySelectorAll('.delete-borrowing');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            
            console.log("Delete button clicked for ID:", id); // Debug logging
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log("Confirmed, redirecting to delete.php with ID:", id); // Debug logging
                    window.location.href = "../borrowing/delete.php?id=" + id;
                }
            });
        });
    });
});
</script> 