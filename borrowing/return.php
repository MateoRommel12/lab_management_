<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add debug logging
error_log("Return.php loaded with request method: " . $_SERVER['REQUEST_METHOD']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data: " . print_r($_POST, true));
}
error_log("GET data: " . print_r($_GET, true));

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../utils/Helpers.php';
require_once __DIR__ . '/../config/Database.php'; // Correct path

// Initialize Auth
$auth = Auth::getInstance();

// Check if user is logged in and is a student, redirect if not
if (!$auth->isLoggedIn() || !$auth->isStudentAssistant()) {
    header('Location: ../access-denied.php');
    exit;
}

// Include models
require_once __DIR__ . '/../models/Borrowing.php';
require_once __DIR__ . '/../models/Equipment.php';

// Initialize models
$borrowingModel = new Borrowing();
$equipmentModel = new Equipment();
$db = Database::getInstance(); // Get database instance

// Get current user
$user = $auth->getUser();

// Check if request ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No borrowing request specified.";
    header('Location: ../student/dashboard.php');
    exit;
}

$requestId = $_GET['id'];

// Get borrowing request details
$borrowing = $borrowingModel->getBorrowingById($requestId);

// Verify that the borrowing exists and belongs to the current user
if (!$borrowing) {
    $_SESSION['error'] = "Borrowing request not found.";
    header('Location: ../student/dashboard.php');
    exit;
}

if ($borrowing['borrower_id'] != $user['user_id']) {
    $_SESSION['error'] = "You are not authorized to return this equipment.";
    header('Location: ../student/dashboard.php');
    exit;
}

// Verify that the borrowing is in approved or borrowed status
if ($borrowing['status'] !== 'approved' && $borrowing['status'] !== 'borrowed') {
    $_SESSION['error'] = "This item cannot be returned as it is not currently borrowed.";
    header('Location: ../student/dashboard.php');
    exit;
}

// Process the return
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Processing equipment return for request ID: " . $requestId);
    
    try {
        // Get equipment ID from the borrowing record
        $equipmentId = $borrowing['equipment_id'];
        error_log("Equipment ID for return: " . $equipmentId);
        
        // 1. Update borrowing status to returned
        $updateBorrowingQuery = "UPDATE borrowing_requests 
                               SET status = 'returned', 
                                   actual_return_date = NOW() 
                               WHERE request_id = :requestId";
        
        $params = ['requestId' => $requestId];
        $result1 = $db->execute($updateBorrowingQuery, $params);
        error_log("Borrowing update result: " . ($result1 ? "Success" : "Failed"));
        
        // 2. Update equipment status to active
        $updateEquipmentQuery = "UPDATE equipment 
                               SET status = 'active',
                                   updated_at = NOW()
                               WHERE equipment_id = :equipmentId";
        
        $params2 = ['equipmentId' => $equipmentId];
        $result2 = $db->execute($updateEquipmentQuery, $params2);
        error_log("Equipment update result: " . ($result2 ? "Success" : "Failed"));
        
        if ($result1 && $result2) {
            error_log("Successfully processed equipment return");
            $_SESSION['success'] = "Equipment has been successfully returned.";
            header('Location: ../student/dashboard.php');
            exit;
        } else {
            error_log("Failed to update database records");
            throw new Exception("Failed to update database records");
        }
    } catch (Exception $e) {
        error_log("Return error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while processing the return: " . $e->getMessage();
    }
}

// Set page title
$pageTitle = "Return Equipment";

// Include header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Return Equipment</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        <h5>Equipment Details</h5>
                        <p><strong>Equipment:</strong> <?php echo htmlspecialchars($borrowing['equipment_name']); ?></p>
                        <p><strong>Borrow Date:</strong> <?php echo Helpers::formatDate($borrowing['borrow_date']); ?></p>
                        <p><strong>Expected Return Date:</strong> <?php echo Helpers::formatDate($borrowing['expected_return_date']); ?></p>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Please confirm that you are returning the equipment in good condition.
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="return_equipment" class="btn btn-success">
                                <i class="fas fa-undo me-2"></i>Confirm Return
                            </button>
                            <a href="../student/dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('confirmReturnBtn').addEventListener('click', function() {
        // Log that the button was clicked
        console.log('Return button clicked');
        
        // Create a new POST request
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        // Handle the response
        xhr.onload = function() {
            if (xhr.status === 200) {
                console.log('Request successful');
                window.location.href = '../student/dashboard.php';
            } else {
                console.log('Request failed');
                alert('Failed to process return. Please try again.');
            }
        };
        
        // Send the request with the form data
        xhr.send('request_id=<?php echo $requestId; ?>');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 
