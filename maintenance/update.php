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
    $progressNotes = trim($_POST['progress_notes']);
    if ($maintenanceModel->updateProgress($maintenanceId, $progressNotes)) {
        echo "<script>alert('Progress updated successfully!'); window.location='../technician/dashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to update progress.'); window.location='../technician/dashboard.php';</script>";
    }
    exit;
}

$pageTitle = 'Update Maintenance Progress';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container mt-4">
    <h2>Update Progress for: <?php echo htmlspecialchars($maintenance['name'] ?? 'Unknown Equipment'); ?></h2>
    <form method="POST">
        <div class="mb-3">
            <label for="progress_notes" class="form-label">Progress Notes</label>
            <textarea name="progress_notes" id="progress_notes" class="form-control" rows="5" required><?php echo htmlspecialchars($maintenance['resolution_notes'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Update Progress</button>
        <a href="../technician/dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?> 