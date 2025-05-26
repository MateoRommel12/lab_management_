<?php
// Check if the maintenance data exists
if (!isset($maintenanceData) || empty($maintenanceData)) {
    echo '<div class="alert alert-warning">No maintenance data found for the selected date range.</div>';
    return;
}

// Group by status
$statusCounts = [
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'cancelled' => 0
];

// Initialize arrays for other metrics
$issueTypeCount = [];
$equipmentTypeCount = [];
$technicianCount = [];
$totalDuration = 0;
$countWithDuration = 0;

foreach ($maintenanceData as $item) {
    // Count by status
    if (isset($item['status'])) {
        $statusCounts[$item['status']] = ($statusCounts[$item['status']] ?? 0) + 1;
    }
    
    // Count by issue type
    if (isset($item['issue_type'])) {
        $issueTypeCount[$item['issue_type']] = ($issueTypeCount[$item['issue_type']] ?? 0) + 1;
    }
    
    // Count by equipment category
    if (isset($item['category_name'])) {
        $categoryName = $item['category_name'] ?? 'Uncategorized';
        $equipmentTypeCount[$categoryName] = ($equipmentTypeCount[$categoryName] ?? 0) + 1;
    }
    
    // Count by technician
    if (isset($item['technician_name']) && $item['technician_name']) {
        $technicianName = $item['technician_name'];
        $technicianCount[$technicianName] = ($technicianCount[$technicianName] ?? 0) + 1;
    }
    
    // Calculate average duration if dates are available
    if (isset($item['request_date']) && isset($item['completion_date']) && $item['status'] === 'completed') {
        $requestDate = strtotime($item['request_date']);
        $completionDate = strtotime($item['completion_date']);
        if ($requestDate && $completionDate) {
            $duration = ($completionDate - $requestDate) / (60 * 60 * 24); // in days
            $totalDuration += $duration;
            $countWithDuration++;
        }
    }
}

$totalRequests = count($maintenanceData);
$avgDuration = $countWithDuration > 0 ? $totalDuration / $countWithDuration : 0;

// Format the date range for display
$dateRangeText = '';
if (!empty($startDate) && !empty($endDate)) {
    $dateRangeText = date('M d, Y', strtotime($startDate)) . ' to ' . date('M d, Y', strtotime($endDate));
} elseif (!empty($startDate)) {
    $dateRangeText = 'From ' . date('M d, Y', strtotime($startDate));
} elseif (!empty($endDate)) {
    $dateRangeText = 'Until ' . date('M d, Y', strtotime($endDate));
} else {
    $dateRangeText = 'All Time';
}
?>

<div class="report-header mb-4">
    <h4>Maintenance History Report</h4>
    <p class="text-muted">
        Date Range: <?php echo $dateRangeText; ?> | 
        Total Requests: <?php echo $totalRequests; ?> | 
        Avg. Repair Time: <?php echo number_format($avgDuration, 1); ?> days
    </p>
</div>

<!-- Status Summary -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Request Status Summary</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col">
                        <div class="p-2">
                            <h4 class="text-warning"><?php echo $statusCounts['pending']; ?></h4>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-2">
                            <h4 class="text-primary"><?php echo $statusCounts['in_progress']; ?></h4>
                            <p>In Progress</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-2">
                            <h4 class="text-success"><?php echo $statusCounts['completed']; ?></h4>
                            <p>Completed</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-2">
                            <h4 class="text-danger"><?php echo $statusCounts['cancelled']; ?></h4>
                            <p>Cancelled</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Analysis Charts -->
<div class="row mb-4">
    <!-- Issue Type Distribution -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Issue Type Distribution</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Issue Type</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issueTypeCount as $type => $count): ?>
                            <tr>
                                <td><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($type))); ?></td>
                                <td><?php echo $count; ?></td>
                                <td><?php echo number_format(($count / $totalRequests) * 100, 1); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Technician Workload -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Technician Workload</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Technician</th>
                                <th>Assigned Tasks</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($technicianCount)): ?>
                            <tr>
                                <td colspan="3" class="text-center">No technician assignments found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($technicianCount as $tech => $count): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tech); ?></td>
                                    <td><?php echo $count; ?></td>
                                    <td><?php echo number_format(($count / $totalRequests) * 100, 1); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Details Table -->
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Equipment</th>
                <th>Issue Type</th>
                <th>Description</th>
                <th>Technician</th>
                <th>Request Date</th>
                <th>Completion Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($maintenanceData as $item): ?>
            <tr>
                <td><?php echo $item['request_id']; ?></td>
                <td><?php echo htmlspecialchars($item['equipment_name'] ?? $item['name'] ?? 'Unknown'); ?></td>
                <td><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($item['issue_type'] ?? 'Unknown'))); ?></td>
                <td><?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 50)) . (strlen($item['description'] ?? '') > 50 ? '...' : ''); ?></td>
                <td><?php echo htmlspecialchars($item['technician_name'] ?? 'Not Assigned'); ?></td>
                <td><?php echo isset($item['request_date']) ? date('M d, Y', strtotime($item['request_date'])) : 'N/A'; ?></td>
                <td><?php echo isset($item['completion_date']) && $item['status'] === 'completed' ? date('M d, Y', strtotime($item['completion_date'])) : 'N/A'; ?></td>
                <td>
                    <span class="badge bg-<?php 
                        echo match($item['status']) {
                            'completed' => 'success',
                            'pending' => 'warning',
                            'in_progress' => 'primary',
                            'cancelled' => 'danger',
                            default => 'secondary'
                        };
                    ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div> 