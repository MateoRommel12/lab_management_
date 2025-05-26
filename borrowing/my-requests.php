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

// Require login
$auth->requireLogin();

// Set page title
$pageTitle = "My Borrowing Requests";

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/Borrowing.php';

// Initialize Borrowing model
$borrowingModel = new Borrowing();

// Get current user's borrowing requests
$borrowings = $borrowingModel->getBorrowingsByUser($auth->getUserId());
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>
                <i class="fas fa-history me-2"></i>My Borrowing Requests
            </h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="../borrowing/borrow.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Borrowing Request
            </a>
        </div>
    </div>

    <?php if (empty($borrowings)): ?>
        <div class="alert alert-info">
            You don't have any borrowing requests yet. <a href="../borrowing/borrow.php">Request to borrow equipment</a> to get started.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Request Date</th>
                                <th>Borrow Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowings as $borrowing): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($borrowing['equipment_name'] ?? 'Unknown Equipment'); ?></td>
                                    <td><?php echo Helpers::formatDate($borrowing['request_date']); ?></td>
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
                                        <a href="view.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 