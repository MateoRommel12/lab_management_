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
$pageTitle = "My Active Borrowings";

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/Borrowing.php';

// Initialize Borrowing model
$borrowingModel = new Borrowing();

// Get current user's active borrowings
$activeBorrowings = $borrowingModel->getBorrowingsByUserAndStatus($auth->getUserId(), 'approved');
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>
                <i class="fas fa-exchange-alt me-2"></i>My Active Borrowings
            </h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="../borrowing/borrow.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Borrowing Request
            </a>
        </div>
    </div>

    <?php if (empty($activeBorrowings)): ?>
        <div class="alert alert-info">
            You don't have any active borrowings. <a href="../borrowing/borrow.php">Request to borrow equipment</a> to get started.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Borrow Date</th>
                                <th>Expected Return Date</th>
                                <th>Days Remaining</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeBorrowings as $borrowing): 
                                $returnDate = new DateTime($borrowing['expected_return_date']);
                                $today = new DateTime();
                                $daysRemaining = $today->diff($returnDate)->days;
                                $isOverdue = $today > $returnDate;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($borrowing['equipment_name'] ?? 'Unknown Equipment'); ?></td>
                                    <td><?php echo Helpers::formatDate($borrowing['borrow_date']); ?></td>
                                    <td><?php echo Helpers::formatDate($borrowing['expected_return_date']); ?></td>
                                    <td>
                                        <?php if ($isOverdue): ?>
                                            <span class="badge bg-danger">Overdue by <?php echo $daysRemaining; ?> days</span>
                                        <?php else: ?>
                                            <span class="badge bg-info"><?php echo $daysRemaining; ?> days remaining</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../borrowing/return.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-undo"></i> Return
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