<?php
// Check if the inventory data exists
if (!isset($inventoryData) || empty($inventoryData)) {
    echo '<div class="alert alert-warning">No inventory data found for the selected criteria.</div>';
    return;
}

// Calculate count by category
$categoryCount = [];
$conditionCount = [
    'new' => 0,
    'good' => 0,
    'fair' => 0,
    'poor' => 0
];

foreach ($inventoryData as $item) {
    // Count by category
    $categoryName = $item['category_name'] ?? 'Uncategorized';
    $categoryCount[$categoryName] = ($categoryCount[$categoryName] ?? 0) + 1;
    
    // Count by condition
    if (isset($item['equipment_condition'])) {
        $conditionCount[$item['equipment_condition']] = ($conditionCount[$item['equipment_condition']] ?? 0) + 1;
    }
}

$totalItems = count($inventoryData);
?>

<div class="report-header mb-4">
    <h4>Equipment Inventory Report</h4>
    <p class="text-muted">
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

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Equipment by Category</h5>
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
                            <?php foreach ($categoryCount as $category => $count): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category); ?></td>
                                <td><?php echo $count; ?></td>
                                <td><?php echo number_format(($count / $totalItems) * 100, 1); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Equipment by Condition</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="p-2">
                            <h4 class="text-success"><?php echo $conditionCount['new']; ?></h4>
                            <p>New</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2">
                            <h4 class="text-info"><?php echo $conditionCount['good']; ?></h4>
                            <p>Good</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2">
                            <h4 class="text-warning"><?php echo $conditionCount['fair']; ?></h4>
                            <p>Fair</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2">
                            <h4 class="text-danger"><?php echo $conditionCount['poor']; ?></h4>
                            <p>Poor</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Table -->
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Serial Number</th>
                <th>Condition</th>
                <th>Current Location</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inventoryData as $item): ?>
            <tr>
                <td><?php echo $item['equipment_id']; ?></td>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                <td><?php echo htmlspecialchars($item['serial_number']); ?></td>
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