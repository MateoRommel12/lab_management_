<?php
// Set page title
$pageTitle = "Maintenance Management";

// Include header
require_once '../includes/header.php';

// Require login
$auth->requireLogin();

// Check if user has permission to view maintenance 
if (!$auth->canViewMaintenance()) {
    Helpers::redirectWithMessage("../index.php", "You don't have permission to view maintenance requests.", "danger");
    exit;
}

// Include required models
require_once '../models/Maintenance.php';
require_once '../models/Equipment.php';
require_once '../models/User.php';

// Initialize models
$maintenanceModel = new Maintenance();
$equipmentModel = new Equipment();
$userModel = new User();

// Get filters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$reporter = isset($_GET['reporter']) ? $_GET['reporter'] : '';
$equipment = isset($_GET['equipment']) ? $_GET['equipment'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$technician = isset($_GET['technician']) ? $_GET['technician'] : '';

// Determine if viewing all or just user's requests
$viewingAll = $auth->canViewAllMaintenance() && !isset($_GET['my_requests']);
$userId = $auth->getUserId();

// Get maintenance requests based on filters and permissions
if ($viewingAll) {
    $maintenanceRequests = $maintenanceModel->getAllMaintenanceRequests($status, $reporter, $equipment, $date_from, $date_to, $technician);
} else {
    $maintenanceRequests = $maintenanceModel->getMaintenanceByReporter($userId, $status, $equipment, $date_from, $date_to);
}

// Get data for filters
$statuses = ['pending', 'in progress', 'completed', 'cancelled'];
$reporters = $viewingAll ? $userModel->getAllUsers() : [];
$technicians = $viewingAll ? $userModel->getUsersByRole('Lab Technician') : [];
$equipmentList = $equipmentModel->getAllEquipment();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>
            <i class="fas fa-tools me-2"></i>
            <?php echo $viewingAll ? 'Maintenance Management' : 'My Maintenance Requests'; ?>
        </h1>
    </div>
    <div class="col-md-6 text-md-end">
        <div class="btn-group mb-2">
            <?php if ($auth->canReportMaintenance()): ?>
            <a href="report.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Report Maintenance Issue
            </a>
            <?php endif; ?>
            
            <?php if ($auth->canViewAllMaintenance()): ?>
            <a href="index.php<?php echo isset($_GET['my_requests']) ? '' : '?my_requests=1'; ?>" class="btn btn-outline-primary">
                <i class="fas fa-tools me-2"></i>
                <?php echo isset($_GET['my_requests']) ? 'View All Requests' : 'View My Requests'; ?>
            </a>
            <?php endif; ?>
        </div>
        
        <?php if ($auth->canManageMaintenance()): ?>
        <div class="btn-group mb-2 ms-2">
            <a href="pending.php" class="btn btn-warning">
                <i class="fas fa-clock me-2"></i>Pending
            </a>
            <a href="in_progress.php" class="btn btn-info">
                <i class="fas fa-spinner me-2"></i>In Progress
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filter Maintenance Requests</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-3">
            <?php if (isset($_GET['my_requests'])): ?>
            <input type="hidden" name="my_requests" value="1">
            <?php endif; ?>
            
            <div class="col-md-<?php echo $viewingAll ? '2' : '3'; ?>">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $stat): ?>
                    <option value="<?php echo $stat; ?>" <?php echo ($status == $stat) ? 'selected' : ''; ?>>
                        <?php echo ucfirst($stat); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($viewingAll): ?>
            <div class="col-md-2">
                <label for="reporter" class="form-label">Reported By</label>
                <select name="reporter" id="reporter" class="form-select">
                    <option value="">All Reporters</option>
                    <?php foreach ($reporters as $user): ?>
                    <option value="<?php echo $user['user_id']; ?>" <?php echo ($reporter == $user['user_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="technician" class="form-label">Assigned To</label>
                <select name="technician" id="technician" class="form-select">
                    <option value="">All Technicians</option>
                    <option value="unassigned" <?php echo ($technician == 'unassigned') ? 'selected' : ''; ?>>Unassigned</option>
                    <?php foreach ($technicians as $tech): ?>
                    <option value="<?php echo $tech['user_id']; ?>" <?php echo ($technician == $tech['user_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-<?php echo $viewingAll ? '2' : '3'; ?>">
                <label for="equipment" class="form-label">Equipment</label>
                <select name="equipment" id="equipment" class="form-select">
                    <option value="">All Equipment</option>
                    <?php foreach ($equipmentList as $item): ?>
                    <option value="<?php echo $item['equipment_id']; ?>" <?php echo ($equipment == $item['equipment_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($item['equipment_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-<?php echo $viewingAll ? '2' : '3'; ?>">
                <label for="date_from" class="form-label">Report Date From</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            
            <div class="col-md-<?php echo $viewingAll ? '2' : '3'; ?>">
                <label for="date_to" class="form-label">Report Date To</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Apply Filters
                </button>
                <a href="<?php echo $viewingAll ? 'index.php' : 'index.php?my_requests=1'; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Maintenance Requests List -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <?php echo $viewingAll ? 'All Maintenance Requests' : 'My Maintenance Requests'; ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($maintenanceRequests)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No maintenance requests found matching your criteria.
        </div>
        
        <?php if ($auth->canReportMaintenance()): ?>
        <p>Would you like to report a new maintenance issue?</p>
        <a href="report.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Report Maintenance Issue
        </a>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Equipment</th>
                        <?php if ($viewingAll): ?>
                        <th>Reported By</th>
                        <?php endif; ?>
                        <th>Issue</th>
                        <th>Report Date</th>
                        <?php if ($viewingAll): ?>
                        <th>Assigned To</th>
                        <?php endif; ?>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenanceRequests as $request): ?>
                    <tr>
                        <td><?php echo $request['maintenance_id']; ?></td>
                        <td><?php echo htmlspecialchars($request['equipment_name']); ?></td>
                        
                        <?php if ($viewingAll): ?>
                        <td><?php echo htmlspecialchars($request['reporter_name']); ?></td>
                        <?php endif; ?>
                        
                        <td><?php echo Helpers::truncateText($request['issue_description'], 50); ?></td>
                        <td><?php echo Helpers::formatDate($request['report_date']); ?></td>
                        
                        <?php if ($viewingAll): ?>
                        <td>
                            <?php if ($request['technician_assigned']): ?>
                                <?php echo htmlspecialchars($request['technician_name']); ?>
                            <?php else: ?>
                                <span class="badge bg-secondary">Not Assigned</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        
                        <td>
                            <?php
                            $statusClasses = [
                                'pending' => 'warning',
                                'in progress' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'secondary'
                            ];
                            $statusClass = $statusClasses[$request['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $statusClass; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="view.php?id=<?php echo $request['maintenance_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if ($auth->canManageMaintenance() && ($request['status'] === 'pending' || $request['status'] === 'in progress')): ?>
                                <a href="update.php?id=<?php echo $request['maintenance_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (($request['status'] === 'pending' && $request['reported_by'] == $userId) || $auth->isAdmin()): ?>
                                <a href="cancel.php?id=<?php echo $request['maintenance_id']; ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-ban"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?> 