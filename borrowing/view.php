<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../utils/Helpers.php';
require_once __DIR__ . '/../models/Borrowing.php';

// Initialize Auth
$auth = Auth::getInstance();

// Get borrowing ID from URL
$borrowingId = $_GET['id'] ?? null;

if (!$borrowingId) {
    Helpers::setFlashMessage('error', 'Invalid borrowing request.');
    header('Location: index.php');
    exit;
}

// Initialize Borrowing model
$borrowing = new Borrowing();

// Get borrowing details
$borrowingDetails = $borrowing->getBorrowingWithDetails($borrowingId);

if (!$borrowingDetails) {
    Helpers::setFlashMessage('error', 'Borrowing request not found.');
    header('Location: index.php');
    exit;
}

// Set page title
$pageTitle = "View Borrowing Request";

// Include header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Borrowing Request Details</h4>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2">Equipment Information</h5>
                                <p><strong>Equipment:</strong> <?php echo htmlspecialchars($borrowingDetails['equipment_name'] ?? 'Unknown Equipment'); ?></p>
                                <p><strong>Serial Number:</strong> <?php echo htmlspecialchars($borrowingDetails['serial_number'] ?? 'N/A'); ?></p>
                                <p><strong>Category:</strong> <?php echo htmlspecialchars($borrowingDetails['category_name'] ?? 'Uncategorized'); ?></p>
                                <p><strong>Location:</strong> 
                                    <?php 
                                    if (!empty($borrowingDetails['room_name'])) {
                                        echo htmlspecialchars($borrowingDetails['room_name']);
                                        if (!empty($borrowingDetails['building'])) {
                                            echo ' - ' . htmlspecialchars($borrowingDetails['building']);
                                        }
                                        if (!empty($borrowingDetails['room_number'])) {
                                            echo ' (Room ' . htmlspecialchars($borrowingDetails['room_number']) . ')';
                                        }
                                    } else {
                                        echo 'Not assigned';
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2">Request Information</h5>
                                <p><strong>Borrower:</strong> <?php echo htmlspecialchars($borrowingDetails['borrower_name'] ?? 'Unknown'); ?></p>
                                <p><strong>Request Date:</strong> <?php echo date('M d, Y', strtotime($borrowingDetails['request_date'])); ?></p>
                                <p><strong>Status:</strong> 
                                    <?php
                                    $statusClass = [
                                        'pending' => 'bg-warning',
                                        'approved' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        'returned' => 'bg-info'
                                    ];
                                    $statusText = ucfirst($borrowingDetails['status'] ?? 'unknown');
                                    $statusClass = $statusClass[$borrowingDetails['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </p>
                                <?php if ($borrowingDetails['status'] === 'approved'): ?>
                                    <p><strong>Approved By:</strong> <?php echo htmlspecialchars($borrowingDetails['approver_name'] ?? 'Unknown'); ?></p>
                                    <p><strong>Approval Date:</strong> <?php echo date('M d, Y', strtotime($borrowingDetails['approval_date'])); ?></p>
                                    <p><strong>Expected Return:</strong> <?php echo date('M d, Y', strtotime($borrowingDetails['expected_return_date'])); ?></p>
                                <?php endif; ?>
                                <?php if ($borrowingDetails['status'] === 'returned'): ?>
                                    <p><strong>Return Date:</strong> <?php echo date('M d, Y', strtotime($borrowingDetails['actual_return_date'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Additional Information</h5>
                                <p><strong>Purpose:</strong> <?php echo nl2br(htmlspecialchars($borrowingDetails['purpose'] ?? 'No purpose specified')); ?></p>
                                <?php if (!empty($borrowingDetails['notes'])): ?>
                                    <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($borrowingDetails['notes'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                        <?php if ($borrowingDetails['status'] === 'pending' && $auth->canApproveBorrowing()): ?>
                            <div>
                                <a href="approve.php?id=<?php echo $borrowingId; ?>" class="btn btn-success me-2">
                                    <i class="fas fa-check me-2"></i>Approve
                                </a>
                                <a href="reject.php?id=<?php echo $borrowingId; ?>" class="btn btn-danger">
                                    <i class="fas fa-times me-2"></i>Reject
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 