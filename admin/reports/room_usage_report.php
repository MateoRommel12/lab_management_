<?php
// Check if the room data exists
if (!isset($roomData) || empty($roomData)) {
    echo '<div class="alert alert-warning">No room data found.</div>';
    return;
}

// Count by status and building
$statusCounts = [
    'active' => 0,
    'inactive' => 0,
    'under maintenance' => 0
];

$buildingCounts = [];
$roomsWithoutTechnician = 0;
$totalEquipment = 0;
$roomsByEquipmentCount = [];

foreach ($roomData as $room) {
    // Count by status
    if (isset($room['status'])) {
        $statusCounts[$room['status']] = ($statusCounts[$room['status']] ?? 0) + 1;
    }
    
    // Count by building
    if (isset($room['building'])) {
        $buildingCounts[$room['building']] = ($buildingCounts[$room['building']] ?? 0) + 1;
    }
    
    // Count rooms without technician
    if (empty($room['lab_technician_id'])) {
        $roomsWithoutTechnician++;
    }
    
    // Count equipment
    $equipmentCount = $room['equipment_count'] ?? 0;
    $totalEquipment += $equipmentCount;
    
    // Group rooms by equipment count range
    if ($equipmentCount == 0) {
        $roomsByEquipmentCount['0'] = ($roomsByEquipmentCount['0'] ?? 0) + 1;
    } elseif ($equipmentCount < 5) {
        $roomsByEquipmentCount['1-4'] = ($roomsByEquipmentCount['1-4'] ?? 0) + 1;
    } elseif ($equipmentCount < 10) {
        $roomsByEquipmentCount['5-9'] = ($roomsByEquipmentCount['5-9'] ?? 0) + 1;
    } else {
        $roomsByEquipmentCount['10+'] = ($roomsByEquipmentCount['10+'] ?? 0) + 1;
    }
}

$totalRooms = count($roomData);
$roomsWithTechnician = $totalRooms - $roomsWithoutTechnician;
$avgEquipmentPerRoom = $totalRooms > 0 ? $totalEquipment / $totalRooms : 0;
?>

<div class="report-header mb-4">
    <h4>Room Usage Report</h4>
    <p class="text-muted">
        Total Rooms: <?php echo $totalRooms; ?> | 
        Total Equipment: <?php echo $totalEquipment; ?> |
        Avg. Equipment per Room: <?php echo number_format($avgEquipmentPerRoom, 1); ?>
    </p>
</div>

<!-- Room Summary -->
<div class="row mb-4">
    <!-- Room Status -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Room Status</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="p-2">
                            <h4 class="text-success"><?php echo $statusCounts['active']; ?></h4>
                            <p>Active</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2">
                            <h4 class="text-danger"><?php echo $statusCounts['inactive']; ?></h4>
                            <p>Inactive</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2">
                            <h4 class="text-warning"><?php echo $statusCounts['under maintenance']; ?></h4>
                            <p>Maintenance</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Technician Assignment -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Technician Assignment</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="p-2 border-end">
                            <h4 class="text-success"><?php echo $roomsWithTechnician; ?></h4>
                            <p>Assigned</p>
                            <span class="text-muted"><?php echo number_format(($roomsWithTechnician / $totalRooms) * 100, 1); ?>%</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2">
                            <h4 class="text-danger"><?php echo $roomsWithoutTechnician; ?></h4>
                            <p>Unassigned</p>
                            <span class="text-muted"><?php echo number_format(($roomsWithoutTechnician / $totalRooms) * 100, 1); ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Equipment Distribution -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Equipment Distribution</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Equipment Count</th>
                                <th>Rooms</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roomsByEquipmentCount as $range => $count): ?>
                            <tr>
                                <td><?php echo $range; ?></td>
                                <td><?php echo $count; ?></td>
                                <td><?php echo number_format(($count / $totalRooms) * 100, 1); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Building Distribution -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Rooms by Building</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Building</th>
                                <th>Room Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($buildingCounts as $building => $count): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($building); ?></td>
                                <td><?php echo $count; ?></td>
                                <td><?php echo number_format(($count / $totalRooms) * 100, 1); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Room Details Table -->
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Room ID</th>
                <th>Room Name</th>
                <th>Building</th>
                <th>Room Number</th>
                <th>Capacity</th>
                <th>Equipment Count</th>
                <th>Technician</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roomData as $room): ?>
            <tr>
                <td><?php echo $room['room_id']; ?></td>
                <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                <td><?php echo htmlspecialchars($room['building']); ?></td>
                <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                <td><?php echo $room['capacity']; ?></td>
                <td><?php echo $room['equipment_count'] ?? 0; ?></td>
                <td>
                    <?php if (!empty($room['technician_name'])): ?>
                        <?php echo htmlspecialchars($room['technician_name']); ?>
                    <?php else: ?>
                        <span class="text-danger">Not Assigned</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge bg-<?php 
                        echo match($room['status']) {
                            'active' => 'success',
                            'inactive' => 'danger',
                            'under maintenance' => 'warning',
                            default => 'secondary'
                        };
                    ?>">
                        <?php echo ucfirst($room['status']); ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div> 