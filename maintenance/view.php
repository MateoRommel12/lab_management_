<?php
// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Auth.php';
require_once __DIR__ . '/../utils/Helpers.php';
require_once __DIR__ . '/../models/Maintenance.php';

// Initialize authentication
$auth = Auth::getInstance();

$maintenanceModel = new Maintenance();

// Get maintenance record
if (!isset($_GET['id'])) {
    echo "Maintenance ID is required.";
    exit;
}

$maintenanceId = $_GET['id'];
$maintenance = $maintenanceModel->getMaintenanceWithDetails($maintenanceId);

if (!$maintenance) {
    echo "Maintenance record not found.";
    exit;
}

$pageTitle = 'View Maintenance Details';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container mt-4">
    <h2>Maintenance Details for: <?php echo htmlspecialchars($maintenance['name'] ?? 'Unknown Equipment'); ?></h2>
    
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Equipment Information</h5>
            <p><strong>Equipment:</strong> <?php echo htmlspecialchars($maintenance['name'] ?? 'Unknown'); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($maintenance['status'] ?? 'Unknown'); ?></p>
            
            <h5 class="card-title mt-4">Issue Details</h5>
            <p><strong>Reported By:</strong> <?php echo htmlspecialchars($maintenance['reporter_name'] ?? 'Unknown'); ?></p>
            <p><strong>Date Reported:</strong> <?php echo Helpers::formatDateTime($maintenance['report_date']); ?></p>
            <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($maintenance['issue_description'])); ?></p>
            
            <h5 class="card-title mt-4">Progress Notes</h5>
            <p><?php echo nl2br(htmlspecialchars($maintenance['resolution_notes'] ?? 'No progress notes yet.')); ?></p>
            
            <h5 class="card-title mt-4">Resolution Notes</h5>
            <p><?php echo nl2br(htmlspecialchars($maintenance['resolution_notes'] ?? 'Not resolved yet.')); ?></p>
        </div>
    </div>
    
    <a href="../technician/dashboard.php" class="btn btn-primary">Back to Dashboard</a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?> 