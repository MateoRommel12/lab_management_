<?php
// Check if the equipment data exists
if (!isset($equipmentData) || empty($equipmentData)) {
    echo '<div class="alert alert-warning">No equipment data found for the selected criteria.</div>';
    return;
}

// Count statuses
$statusCounts = [
    'active' => 0,
    'inactive' => 0,
    'under maintenance' => 0
];

foreach ($equipmentData as $item) {
    if (isset($item['status'])) {
        $statusCounts[$item['status']] = ($statusCounts[$item['status']] ?? 0) + 1;
    }
}

$totalItems = count($equipmentData);
?>

<div class="report-header mb-4">
    <h4>Equipment Status Report</h4>
    <p class="text-muted">
        <?php if (!empty($status)): ?>
            Status: <?php echo ucfirst($status); ?> |
        <?php endif; ?>
        
        <?php if (!empty($category)): ?>
            <?php 
            // Get category name
            require_once __DIR__ . '/../../models/Category.php';
            $categoryModel = new Category();
            $categoryInfo = $categoryModel->getCategoryById($category);
            echo "Category: " . ($categoryInfo['category_name'] ?? 'Unknown') . " | ";
            ?>
        <?php endif; ?>
        
        Total Items: <?php echo $totalItems; ?>
    </p>
</div>

<!-- Status Overview -->
<div class="row mb-4">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Status Overview</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="p-3 border-end">
                            <h3 class="text-success"><?php echo $statusCounts['active']; ?></h3>
                            <p>Active</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 border-end">
                            <h3 class="text-danger"><?php echo $statusCounts['inactive']; ?></h3>
                            <p>Inactive</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3">
                            <h3 class="text-warning"><?php echo $statusCounts['under maintenance']; ?></h3>
                            <p>Maintenance</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Equipment Table -->
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Serial Number</th>
                <th>Status</th>
                <th>Condition</th>
                <th>Location</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($equipmentData as $item): ?>
            <tr>
                <td><?php echo $item['equipment_id']; ?></td>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                <td><?php echo htmlspecialchars($item['serial_number']); ?></td>
                <td>
                    <span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : ($item['status'] === 'inactive' ? 'danger' : 'warning'); ?>">
                        <?php echo ucfirst($item['status']); ?>
                    </span>
                </td>
                <td>
                    <span class="badge bg-<?php 
                        echo match($item['equipment_condition']) {
                            'new' => 'success',
                            'good' => 'info',
                            'fair' => 'warning',
                            'poor' => 'danger',
                            default => 'secondary'
                        };
                    ?>">
                        <?php echo ucfirst($item['equipment_condition']); ?>
                    </span>
                </td>
                <td><?php echo htmlspecialchars($item['location']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div> 