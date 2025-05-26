<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../utils/Helpers.php';
require_once __DIR__ . '/../models/Borrowing.php';

// Initialize Auth
$auth = Auth::getInstance();

// Require login and approval permission
$auth->requireLogin();
if (!$auth->canApproveBorrowing()) {
    Helpers::redirectWithMessage("../index.php", "You don't have permission to reject borrowing requests.", "danger");
    exit;
}

// Get request ID from URL
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get borrowing request details
$borrowingModel = new Borrowing();
$request = $borrowingModel->getBorrowingWithDetails($requestId);

// Check if request exists and is pending
if (!$request || $request['status'] !== 'pending') {
    Helpers::redirectWithMessage("index.php", "Invalid or non-pending borrowing request.", "danger");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Reject the borrowing request
        if ($borrowingModel->rejectBorrowing($requestId, $auth->getUserId())) {
            Helpers::redirectWithMessage("index.php", "Borrowing request has been rejected successfully.", "success");
            exit;
        } else {
            throw new Exception("Failed to reject borrowing request.");
        }
    } catch (Exception $e) {
        Helpers::redirectWithMessage("reject.php?id=" . $requestId, $e->getMessage(), "danger");
        exit;
    }
}

// Set page title
$pageTitle = "Reject Borrowing Request";

// Include header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-times-circle me-2"></i>Reject Borrowing Request
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Are you sure you want to reject this borrowing request?
                    </div>

                    <div class="mb-4">
                        <h6>Request Details:</h6>
                        <table class="table table-bordered">
                            <tr>
                                <th>Borrower:</th>
                                <td><?php echo htmlspecialchars($request['borrower_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Equipment:</th>
                                <td><?php echo htmlspecialchars($request['equipment_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Serial Number:</th>
                                <td><?php echo htmlspecialchars($request['serial_number']); ?></td>
                            </tr>
                            <tr>
                                <th>Borrow Date:</th>
                                <td><?php echo date('M d, Y', strtotime($request['borrow_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Expected Return Date:</th>
                                <td><?php echo date('M d, Y', strtotime($request['expected_return_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Purpose:</th>
                                <td><?php echo htmlspecialchars($request['purpose']); ?></td>
                            </tr>
                        </table>
                    </div>

                    <form method="POST" class="d-inline">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Reject Request
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 