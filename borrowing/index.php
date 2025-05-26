<?php
// Set page title
$pageTitle = "Borrowing Management";

// Include header
require_once '../includes/header.php';

// Require login
$auth->requireLogin();

// Check if user has permission to view borrowings
if (!$auth->canViewBorrowings()) {
    Helpers::redirectWithMessage("../index.php", "You don't have permission to view borrowing requests.", "danger");
    exit;
}

// Include required models
require_once '../models/Borrowing.php';
require_once '../models/Equipment.php';
require_once '../models/User.php';

// Initialize models
$borrowingModel = new Borrowing();
$equipmentModel = new Equipment();
$userModel = new User();

// Get filters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$borrower = isset($_GET['borrower']) ? $_GET['borrower'] : '';
$equipment = isset($_GET['equipment']) ? $_GET['equipment'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Determine if viewing all or just user's requests
$viewingAll = $auth->canViewAllBorrowings() && !isset($_GET['my_requests']);
$userId = $auth->getUserId();

// Get borrowing requests based on filters and permissions
if ($viewingAll) {
    $borrowings = $borrowingModel->getAllBorrowingRequests($status, $borrower, $equipment, $date_from, $date_to);
} else {
    $borrowings = $borrowingModel->getBorrowingByUser($userId, $status, $equipment, $date_from, $date_to);
}

// Get data for filters
$statuses = ['pending', 'approved', 'rejected', 'borrowed', 'returned', 'overdue'];
$borrowers = $viewingAll ? $userModel->getAllUsers() : [];
$equipmentList = $equipmentModel->getAllEquipment();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>
            <i class="fas fa-exchange-alt me-2"></i>
            <?php echo $viewingAll ? 'Borrowing Management' : 'My Borrowing Requests'; ?>
        </h1>
    </div>
    <div class="col-md-6 text-md-end">
        <div class="btn-group mb-2">
            <?php if ($auth->canBorrowEquipment()): ?>
            <a href="borrow.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Borrowing Request
            </a>
            <?php endif; ?>
            
            <?php if ($auth->canViewAllBorrowings()): ?>
            <a href="index.php<?php echo isset($_GET['my_requests']) ? '' : '?my_requests=1'; ?>" class="btn btn-outline-primary">
                <i class="fas fa-exchange-alt me-2"></i>
                <?php echo isset($_GET['my_requests']) ? 'View All Requests' : 'View My Requests'; ?>
            </a>
            <?php endif; ?>
        </div>
        
        <?php if ($auth->canApproveBorrowing()): ?>
        <div class="btn-group mb-2 ms-2">
            <a href="pending.php" class="btn btn-warning">
                <i class="fas fa-clock me-2"></i>Pending Approvals
            </a>
            <a href="active.php" class="btn btn-info">
                <i class="fas fa-hand-holding me-2"></i>Active Borrows
            </a>
            <a href="overdue.php" class="btn btn-danger">
                <i class="fas fa-exclamation-circle me-2"></i>Overdue
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filter Borrowing Requests</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-3">
            <?php if (isset($_GET['my_requests'])): ?>
            <input type="hidden" name="my_requests" value="1">
            <?php endif; ?>
            
            <div class="col-md-3">
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
            <div class="col-md-3">
                <label for="borrower" class="form-label">Borrower</label>
                <select name="borrower" id="borrower" class="form-select">
                    <option value="">All Borrowers</option>
                    <?php foreach ($borrowers as $user): ?>
                    <option value="<?php echo $user['user_id']; ?>" <?php echo ($borrower == $user['user_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-3">
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
            
            <div class="col-md-3">
                <label for="date_from" class="form-label">Borrow Date From</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            
            <div class="col-md-3">
                <label for="date_to" class="form-label">Borrow Date To</label>
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

<!-- Borrowing Requests List -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <?php echo $viewingAll ? 'All Borrowing Requests' : 'My Borrowing Requests'; ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($borrowings)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No borrowing requests found matching your criteria.
        </div>
        
        <?php if ($auth->canBorrowEquipment()): ?>
        <p>Would you like to make a new borrowing request?</p>
        <a href="borrow.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>New Borrowing Request
        </a>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <?php if ($viewingAll): ?>
                        <th>Borrower</th>
                        <?php endif; ?>
                        <th>Equipment</th>
                        <th>Request Date</th>
                        <th>Borrow Date</th>
                        <th>Expected Return</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($borrowings as $borrowing): ?>
                    <tr>
                        <td><?php echo $borrowing['request_id']; ?></td>
                        
                        <?php if ($viewingAll): ?>
                        <td><?php echo htmlspecialchars($borrowing['borrower_name']); ?></td>
                        <?php endif; ?>
                        
                        <td>
                            <?php echo htmlspecialchars($borrowing['equipment_name'] ?? 'Unknown Equipment'); ?>
                            <?php if (!empty($borrowing['serial_number'])): ?>
                                <br>
                                <small class="text-muted">SN: <?php echo htmlspecialchars($borrowing['serial_number']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo Helpers::formatDate($borrowing['request_date']); ?></td>
                        <td><?php echo Helpers::formatDate($borrowing['borrow_date']); ?></td>
                        <td><?php echo Helpers::formatDate($borrowing['expected_return_date']); ?></td>
                        <td><?php echo Helpers::getStatusBadge($borrowing['status']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="view.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if ($auth->canApproveBorrowing() && $borrowing['status'] === 'pending'): ?>
                                <a href="approve.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i>
                                </a>
                                <a href="reject.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-danger">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($auth->canApproveBorrowing() && $borrowing['status'] === 'approved'): ?>
                                <a href="checkout.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($auth->canApproveBorrowing() && ($borrowing['status'] === 'borrowed' || $borrowing['status'] === 'overdue')): ?>
                                <a href="return.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-undo"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (($borrowing['status'] === 'pending' && $borrowing['borrower_id'] == $userId) || $auth->isAdmin()): ?>
                                <a href="cancel.php?id=<?php echo $borrowing['request_id']; ?>" class="btn btn-sm btn-secondary">
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

