<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Auth.php';
require_once __DIR__ . '/../../utils/Helpers.php';

// Initialize Auth
$auth = Auth::getInstance();

// Check if user is admin, redirect if not
if (!$auth->isAdmin()) {
    header('Location: ../../access-denied.php');
    exit;
}

// Set page title
$pageTitle = "Assign Maintenance Tasks";
$currentPage = 'maintenance';

// Include header
require_once __DIR__ . '/../../includes/header.php';

// Include required models
require_once __DIR__ . '/../../models/Maintenance.php';
require_once __DIR__ . '/../../models/User.php';

// Initialize models
$maintenanceModel = new Maintenance();
$userModel = new User();

// Get pending maintenance requests
$pendingRequests = $maintenanceModel->getPendingRequests();

// Get available technicians
$technicians = $userModel->getTechnicians();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_technician'])) {
    $maintenanceId = $_POST['maintenance_id'];
    $technicianId = $_POST['technician_id'];
    
    if ($maintenanceModel->assignTechnician($maintenanceId, $technicianId)) {
        $_SESSION['success_message'] = "Technician assigned successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to assign technician. Please try again.";
    }
    
    // Redirect to prevent form resubmission
    header('Location: assign.php');
    exit;
}
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="display-5 mb-4">
            <i class="fas fa-tools me-2"></i>Assign Maintenance Tasks
        </h1>
    </div>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Pending Maintenance Requests</h5>
            </div>
            <div class="card-body">
                <?php if (empty($pendingRequests)): ?>
                    <div class="alert alert-success">
                        No pending maintenance requests at this time.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Equipment</th>
                                    <th>Issue Description</th>
                                    <th>Reported By</th>
                                    <th>Date Reported</th>
                                    <th>Assign Technician</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingRequests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['issue_description']); ?></td>
                                    <td><?php echo htmlspecialchars($request['reporter_name']); ?></td>
                                    <td><?php echo Helpers::formatDateTime($request['report_date']); ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="maintenance_id" value="<?php echo $request['maintenance_id']; ?>">
                                            <select name="technician_id" class="form-select form-select-sm d-inline-block w-auto" required>
                                                <option value="">Select Technician</option>
                                                <?php foreach ($technicians as $technician): ?>
                                                    <option value="<?php echo $technician['user_id']; ?>">
                                                        <?php echo htmlspecialchars($technician['first_name'] . ' ' . $technician['last_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_technician" class="btn btn-sm btn-primary">
                                                <i class="fas fa-user-check"></i> Assign
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 