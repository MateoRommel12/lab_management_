<?php
// Check if the borrowing data exists
if (!isset($borrowingData) || empty($borrowingData)) {
    echo '<div class="alert alert-warning">No borrowing data found for the selected date range.</div>';
    return;
}

// Group by status
$statusCounts = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'returned' => 0,
    'overdue' => 0
];

// Initialize arrays for other metrics
$borrowerTypeCount = [];
$equipmentTypeCount = [];
$totalDuration = 0;
$countWithDuration = 0;

foreach ($borrowingData as $item) {
    // Count by status
    if (isset($item['status'])) {
        $statusCounts[$item['status']] = ($statusCounts[$item['status']] ?? 0) + 1;
    }
    
    // Count by borrower role
    if (isset($item['role'])) {
        $borrowerTypeCount[$item['role']] = ($borrowerTypeCount[$item['role']] ?? 0) + 1;
    }
    
    // Count by equipment category
    if (isset($item['category_name'])) {
        $categoryName = $item['category_name'] ?? 'Uncategorized';
        $equipmentTypeCount[$categoryName] = ($equipmentTypeCount[$categoryName] ?? 0) + 1;
    }
    
    // Calculate average duration if dates are available
    if (isset($item['borrow_date']) && isset($item['return_date'])) {
        $borrowDate = strtotime($item['borrow_date']);
        $returnDate = strtotime($item['return_date']);
        if ($borrowDate && $returnDate) {
            $duration = ($returnDate - $borrowDate) / (60 * 60 * 24); // in days
            $totalDuration += $duration;
            $countWithDuration++;
        }
    }
}

$totalRequests = count($borrowingData);
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
    <h4>Borrowing History Report</h4>
    <p class="text-muted">
        Date Range: <?php echo $dateRangeText; ?> | 
        Total Requests: <?php echo $totalRequests; ?> | 
        Avg. Duration: <?php echo number_format($avgDuration, 1); ?> days
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
                            <h4 class="text-success"><?php echo $statusCounts['approved']; ?></h4>
                            <p>Approved</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-2">
                            <h4 class="text-danger"><?php echo $statusCounts['rejected']; ?></h4>
                            <p>Rejected</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-2">
                            <h4 class="text-info"><?php echo $statusCounts['returned']; ?></h4>
                            <p>Returned</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-2">
                            <h4 class="text-danger"><?php echo $statusCounts['overdue']; ?></h4>
                            <p>Overdue</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Analysis Charts -->
<div class="row mb-4">
    <!-- Borrower Type Distribution -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Borrower Distribution</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>User Type</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowerTypeCount as $role => $count): ?>
                            <tr>
                                <td><?php echo ucfirst(htmlspecialchars($role)); ?></td>
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
    
    <!-- Equipment Type Distribution -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Equipment Category Distribution</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipmentTypeCount as $category => $count): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category); ?></td>
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
</div>

<!-- Borrowing Details Table -->
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Borrower</th>
                <th>Role</th>
                <th>Equipment</th>
                <th>Category</th>
                <th>Borrow Date</th>
                <th>Return Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($borrowingData as $item): ?>
            <tr>
                <td><?php echo $item['request_id']; ?></td>
                <td><?php echo htmlspecialchars($item['borrower_name'] ?? ($item['first_name'] . ' ' . $item['last_name'])); ?></td>
                <td><?php echo ucfirst(htmlspecialchars($item['role'] ?? 'Unknown')); ?></td>
                <td><?php echo htmlspecialchars($item['equipment_name'] ?? $item['name'] ?? 'Unknown'); ?></td>
                <td><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                <td><?php echo isset($item['borrow_date']) ? date('M d, Y', strtotime($item['borrow_date'])) : 'N/A'; ?></td>
                <td><?php echo isset($item['return_date']) ? date('M d, Y', strtotime($item['return_date'])) : 'N/A'; ?></td>
                <td>
                    <span class="badge bg-<?php 
                        echo match($item['status']) {
                            'approved' => 'success',
                            'pending' => 'warning',
                            'rejected' => 'danger',
                            'returned' => 'info',
                            'overdue' => 'danger',
                            default => 'secondary'
                        };
                    ?>">
                        <?php echo ucfirst($item['status']); ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div> 