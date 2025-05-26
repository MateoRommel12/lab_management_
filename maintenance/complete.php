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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resolutionNotes = trim($_POST['resolution_notes']);
    if ($maintenanceModel->completeMaintenance($maintenanceId, $resolutionNotes)) {
        echo "<script>alert('Maintenance marked as complete!'); window.location='../technician/dashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to complete maintenance.'); window.location='../technician/dashboard.php';</script>";
    }
    exit;
}

$pageTitle = 'Complete Maintenance';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container mt-4">
    <h2>Complete Maintenance for: <?php echo htmlspecialchars($maintenance['name'] ?? 'Unknown Equipment'); ?></h2>
    <form method="POST">
        <div class="mb-3">
            <label for="resolution_notes" class="form-label">Resolution Notes</label>
            <textarea name="resolution_notes" id="resolution_notes" class="form-control" rows="5" required><?php echo htmlspecialchars($maintenance['resolution_notes'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-success">Mark as Complete</button>
        <a href="../technician/dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?> 