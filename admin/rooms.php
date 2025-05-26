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

// Check if user is admin, redirect if not
if (!$auth->isAdmin()) {
    header('Location: ../access-denied.php');
    exit;
}

// Set page title
$pageTitle = "Room Management";
$currentPage = 'rooms';

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/Equipment.php';

// Initialize models
$roomModel = new Room();
$equipmentModel = new Equipment();

// Get filters
$building = isset($_GET['building']) ? $_GET['building'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get rooms with filters
$rooms = $roomModel->getAllRoomsWithTechnicians();

// Get buildings for filter
$buildings = $roomModel->getAllBuildings();
$statuses = ['active', 'inactive', 'under maintenance'];

// Get room statistics
$stats = $roomModel->getRoomStatistics();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>
            <i class="fas fa-door-open me-2"></i>Room Management
        </h1>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="add_room.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Room
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Rooms</h5>
                <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Active Rooms</h5>
                <h2 class="mb-0"><?php echo $stats['active_count']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Under Maintenance</h5>
                <h2 class="mb-0"><?php echo $stats['maintenance_count']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Buildings</h5>
                <h2 class="mb-0"><?php echo $stats['building_count']; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filter Rooms</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="rooms.php" class="row g-3">
            <div class="col-md-4">
                <label for="building" class="form-label">Building</label>
                <select name="building" id="building" class="form-select">
                    <option value="">All Buildings</option>
                    <?php foreach ($buildings as $b): ?>
                    <option value="<?php echo htmlspecialchars($b['building']); ?>" <?php echo ($building == $b['building']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($b['building']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
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
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Room name, room number..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Apply Filters
                </button>
                <a href="rooms.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-times me-2"></i>Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Rooms Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($rooms)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No rooms found.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Room Name</th>
                        <th>Building</th>
                        <th>Floor</th>
                        <th>Room Number</th>
                        <th>Capacity</th>
                        <th>Equipment Count</th>
                        <th>Technician</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                    <?php
                    // Get equipment count for room
                    $equipmentCount = $equipmentModel->getEquipmentCountByRoom($room['room_id']);
                    ?>
                    <tr>
                        <td><?php echo $room['room_id']; ?></td>
                        <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                        <td><?php echo htmlspecialchars($room['building']); ?></td>
                        <td><?php echo htmlspecialchars($room['floor']); ?></td>
                        <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                        <td><?php echo $room['capacity'] ?: '-'; ?></td>
                        <td>
                            <?php if ($equipmentCount > 0): ?>
                            <a href="../equipment/index.php?room=<?php echo $room['room_id']; ?>" class="badge bg-info text-decoration-none">
                                <?php echo $equipmentCount; ?> items
                            </a>
                            <?php else: ?>
                            <span class="badge bg-secondary">0 items</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($room['technician_name']): ?>
                            <?php echo htmlspecialchars($room['technician_name']); ?>
                            <?php else: ?>
                            <span class="text-muted">Not Assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $statusClass = [
                                'active' => 'success',
                                'inactive' => 'danger',
                                'under maintenance' => 'warning'
                            ];
                            $class = $statusClass[$room['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $class; ?>">
                                <?php echo ucfirst($room['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="../rooms/view.php?id=<?php echo $room['room_id']; ?>" class="btn btn-sm btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_room.php?id=<?php echo $room['room_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../equipment/index.php?room=<?php echo $room['room_id']; ?>" class="btn btn-sm btn-success" title="View Equipment">
                                    <i class="fas fa-laptop"></i>
                                </a>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 