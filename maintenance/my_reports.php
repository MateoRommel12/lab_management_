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
$pageTitle = "My Maintenance Reports";

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/Maintenance.php';

// Initialize Maintenance model
$maintenanceModel = new Maintenance();

// Get current user's maintenance reports
$reports = $maintenanceModel->getMaintenanceReportsByUser($auth->getUserId());
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>
                <i class="fas fa-tools me-2"></i>My Maintenance Reports
            </h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="../maintenance/report.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Maintenance Report
            </a>
        </div>
    </div>

    <?php if (empty($reports)): ?>
        <div class="alert alert-info">
            You don't have any maintenance reports yet. <a href="../maintenance/report.php">Report an issue</a> to get started.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Report Date</th>
                                <th>Issue Description</th>
                                <th>Status</th>
                                <th>Technician</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($report['equipment_name'] ?? 'Unknown Equipment'); ?></td>
                                    <td><?php echo Helpers::formatDate($report['report_date']); ?></td>
                                    <td><?php echo htmlspecialchars($report['issue_description']); ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = '';
                                        switch ($report['status']) {
                                            case 'pending':
                                                $statusClass = 'warning';
                                                break;
                                            case 'in progress':
                                                $statusClass = 'info';
                                                break;
                                            case 'completed':
                                                $statusClass = 'success';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'danger';
                                                break;
                                            default:
                                                $statusClass = 'secondary';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo ucfirst(htmlspecialchars($report['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($report['technician_name']): ?>
                                            <?php echo htmlspecialchars($report['technician_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $report['maintenance_id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($report['status'] === 'completed'): ?>
                                        <button type="button" class="btn btn-sm btn-danger delete-report" data-id="<?php echo $report['maintenance_id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete Maintenance Report
    const deleteButtons = document.querySelectorAll('.delete-report');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            
            console.log("Delete button clicked for maintenance ID:", id); // Debug logging
            
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
                    window.location.href = "delete.php?id=" + id;
                }
            });
        });
    });
});
</script> 