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

// Set page title
$pageTitle = "Equipment Movements";

// Include header
require_once '../includes/header.php';

// Require login
$auth->requireLogin();

// Check if user has permission to view equipment
if (!$auth->canViewEquipment()) {
    Helpers::redirectWithMessage("../index.php", "You don't have permission to view equipment movements.", "danger");
    exit;
}

// Include required models
require_once '../models/Equipment.php';
require_once '../models/Room.php';

// Initialize models
$equipmentModel = new Equipment();
$roomModel = new Room();

// Get filter parameters
$equipmentId = isset($_GET['equipment_id']) ? (int)$_GET['equipment_id'] : null;
$roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : null;
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;

// Get all equipment and rooms for filters
$equipmentList = $equipmentModel->getAllEquipment();
$rooms = $roomModel->getAllRooms();

// Get movements with filters
$filters = [
    'equipment_id' => $equipmentId,
    'room_id' => $roomId,
    'date_from' => $dateFrom,
    'date_to' => $dateTo
];

$movements = $equipmentModel->getEquipmentMovements($filters);
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>
            <i class="fas fa-exchange-alt me-2"></i>Equipment Movements
        </h1>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Equipment List
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filter Movements</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="equipment_id" class="form-label">Equipment</label>
                <select class="form-select" id="equipment_id" name="equipment_id">
                    <option value="">All Equipment</option>
                    <?php foreach ($equipmentList as $equipment): ?>
                    <option value="<?php echo $equipment['equipment_id']; ?>" 
                            <?php echo $equipmentId == $equipment['equipment_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($equipment['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="room_id" class="form-label">Room</label>
                <select class="form-select" id="room_id" name="room_id">
                    <option value="">All Rooms</option>
                    <?php foreach ($rooms as $room): ?>
                    <option value="<?php echo $room['room_id']; ?>"
                            <?php echo $roomId == $room['room_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($room['room_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?php echo $dateFrom; ?>">
            </div>
            
            <div class="col-md-3">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?php echo $dateTo; ?>">
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Apply Filters
                </button>
                <a href="movements.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-times me-2"></i>Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Movements Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Movement History</h5>
    </div>
    <div class="card-body">
        <?php if (empty($movements)): ?>
        <div class="alert alert-info">
            No equipment movements found matching the selected criteria.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Equipment</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Moved By</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $movement): ?>
                    <tr>
                        <td><?php echo Helpers::formatDateTime($movement['movement_date']); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $movement['equipment_id']; ?>">
                                <?php echo htmlspecialchars($movement['equipment_name']); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo $movement['from_room'] ? htmlspecialchars($movement['from_room']) : '<span class="text-muted">Not Assigned</span>'; ?>
                        </td>
                        <td><?php echo htmlspecialchars($movement['to_room']); ?></td>
                        <td><?php echo htmlspecialchars($movement['moved_by_name']); ?></td>
                        <td><?php echo htmlspecialchars($movement['reason'] ?: 'No reason provided'); ?></td>
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