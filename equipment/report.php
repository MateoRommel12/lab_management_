<?php
// Set page title
$pageTitle = "Equipment Reports";

// Include header
require_once '../includes/header.php';

// Require login
$auth->requireLogin();

// Check if user has permission to view reports
if (!$auth->canViewReports()) {
    Helpers::redirectWithMessage("../index.php", "You don't have permission to view reports.", "danger");
    exit;
}

// Include required models
require_once '../models/Equipment.php';
require_once '../models/Room.php';

// Initialize models
$equipmentModel = new Equipment();
$roomModel = new Room();

// Get filters
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'all';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$room = isset($_GET['room']) ? $_GET['room'] : '';

// Get categories, rooms and statuses for filters
$categories = $equipmentModel->getAllCategories();
$rooms = $roomModel->getAllRooms();
$statuses = ['new', 'good', 'fair', 'poor', 'under maintenance', 'disposed'];

// Get report data based on type and filters
$reportData = [];
$reportTitle = '';

switch ($reportType) {
    case 'by_category':
        $reportData = $equipmentModel->getEquipmentSummaryByCategory();
        $reportTitle = 'Equipment Summary by Category';
        break;
    case 'by_condition':
        $reportData = $equipmentModel->getEquipmentSummaryByCondition();
        $reportTitle = 'Equipment Summary by Condition';
        break;
    case 'by_room':
        $reportData = $equipmentModel->getEquipmentSummaryByRoom();
        $reportTitle = 'Equipment Summary by Room';
        break;
    case 'maintenance_required':
        $reportData = $equipmentModel->getEquipmentRequiringMaintenance();
        $reportTitle = 'Equipment Requiring Maintenance';
        break;
    case 'recently_added':
        $reportData = $equipmentModel->getRecentlyAddedEquipment();
        $reportTitle = 'Recently Added Equipment';
        break;
    case 'value_summary':
        $reportData = $equipmentModel->getEquipmentValueSummary();
        $reportTitle = 'Equipment Value Summary';
        break;
    default:
        // Default to detailed list with filters
        $reportData = $equipmentModel->getAllEquipment($category, $status, $room);
        $reportTitle = 'Detailed Equipment List';
        break;
}

// Export to CSV if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="equipment_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add report title and date
    fputcsv($output, [$reportTitle]);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []); // Empty line
    
    // Add headers based on report type
    switch ($reportType) {
        case 'by_category':
            fputcsv($output, ['Category', 'Total Items', 'Total Value']);
            foreach ($reportData as $row) {
                fputcsv($output, [
                    $row['category_name'],
                    $row['item_count'],
                    number_format($row['total_value'], 2)
                ]);
            }
            break;
        case 'by_condition':
            fputcsv($output, ['Condition', 'Total Items', 'Percentage']);
            foreach ($reportData as $row) {
                fputcsv($output, [
                    ucfirst($row['condition_status']),
                    $row['item_count'],
                    number_format($row['percentage'], 2) . '%'
                ]);
            }
            break;
        case 'by_room':
            fputcsv($output, ['Room', 'Building', 'Floor', 'Room Number', 'Total Items']);
            foreach ($reportData as $row) {
                fputcsv($output, [
                    $row['room_name'] ?: 'Not Assigned',
                    $row['building'] ?: 'N/A',
                    $row['floor'] ?: 'N/A',
                    $row['room_number'] ?: 'N/A',
                    $row['item_count']
                ]);
            }
            break;
        case 'maintenance_required':
            fputcsv($output, ['Equipment Name', 'Serial Number', 'Category', 'Condition', 'Room', 'Last Maintenance']);
            foreach ($reportData as $row) {
                fputcsv($output, [
                    $row['equipment_name'],
                    $row['serial_number'],
                    $row['category_name'],
                    ucfirst($row['condition_status']),
                    $row['room_name'] ?: 'Not Assigned',
                    $row['last_maintenance'] ?: 'Never'
                ]);
            }
            break;
        case 'recently_added':
            fputcsv($output, ['Equipment Name', 'Serial Number', 'Category', 'Condition', 'Date Added']);
            foreach ($reportData as $row) {
                fputcsv($output, [
                    $row['equipment_name'],
                    $row['serial_number'],
                    $row['category_name'],
                    ucfirst($row['condition_status']),
                    date('Y-m-d', strtotime($row['created_at']))
                ]);
            }
            break;
        case 'value_summary':
            fputcsv($output, ['Category', 'Total Items', 'Total Value', 'Average Value']);
            foreach ($reportData as $row) {
                fputcsv($output, [
                    $row['category_name'],
                    $row['item_count'],
                    number_format($row['total_value'], 2),
                    number_format($row['average_value'], 2)
                ]);
            }
            break;
        default:
            // Default to detailed list
            fputcsv($output, ['ID', 'Name', 'Category', 'Serial Number', 'Room', 'Condition', 'Acquisition Date', 'Cost']);
            foreach ($reportData as $row) {
                fputcsv($output, [
                    $row['equipment_id'],
                    $row['equipment_name'],
                    $row['category_name'],
                    $row['serial_number'],
                    $row['room_name'] ?: 'Not Assigned',
                    ucfirst($row['condition_status']),
                    $row['acquisition_date'] ?: 'N/A',
                    $row['cost'] ? number_format($row['cost'], 2) : 'N/A'
                ]);
            }
            break;
    }
    
    fclose($output);
    exit;
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>
            <i class="fas fa-file-alt me-2"></i>Equipment Reports
        </h1>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Equipment List
        </a>
    </div>
</div>

<!-- Report Controls -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Report Options</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="report.php" class="row g-3">
            <div class="col-md-4">
                <label for="report_type" class="form-label">Report Type</label>
                <select name="report_type" id="report_type" class="form-select">
                    <option value="all" <?php echo $reportType == 'all' ? 'selected' : ''; ?>>Detailed Equipment List</option>
                    <option value="by_category" <?php echo $reportType == 'by_category' ? 'selected' : ''; ?>>Summary by Category</option>
                    <option value="by_condition" <?php echo $reportType == 'by_condition' ? 'selected' : ''; ?>>Summary by Condition</option>
                    <option value="by_room" <?php echo $reportType == 'by_room' ? 'selected' : ''; ?>>Summary by Room</option>
                    <option value="maintenance_required" <?php echo $reportType == 'maintenance_required' ? 'selected' : ''; ?>>Equipment Requiring Maintenance</option>
                    <option value="recently_added" <?php echo $reportType == 'recently_added' ? 'selected' : ''; ?>>Recently Added Equipment</option>
                    <option value="value_summary" <?php echo $reportType == 'value_summary' ? 'selected' : ''; ?>>Equipment Value Summary</option>
                </select>
            </div>
            
            <div id="detailedFilters" class="row g-3 <?php echo $reportType == 'all' ? '' : 'd-none'; ?>">
                <div class="col-md-4">
                    <label for="category" class="form-label">Category</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo ($category == $cat['category_id']) ? 'selected' : ''; ?>>
                            <?php echo $cat['category_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="status" class="form-label">Condition</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Conditions</option>
                        <?php foreach ($statuses as $stat): ?>
                        <option value="<?php echo $stat; ?>" <?php echo ($status == $stat) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($stat); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="room" class="form-label">Room</label>
                    <select name="room" id="room" class="form-select">
                        <option value="">All Rooms</option>
                        <?php foreach ($rooms as $r): ?>
                        <option value="<?php echo $r['room_id']; ?>" <?php echo ($room == $r['room_id']) ? 'selected' : ''; ?>>
                            <?php echo $r['room_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Generate Report
                </button>
                
                <button type="submit" name="export" value="csv" class="btn btn-success ms-2">
                    <i class="fas fa-file-csv me-2"></i>Export to CSV
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Report Display -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><?php echo $reportTitle; ?></h5>
        <span class="text-muted">Generated on: <?php echo date('Y-m-d H:i:s'); ?></span>
    </div>
    <div class="card-body">
        <?php if (empty($reportData)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No data found for the selected report criteria.
        </div>
        <?php else: ?>
        
        <?php if ($reportType === 'by_category'): ?>
        <!-- Category Summary Report -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total Items</th>
                        <th>Total Value</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo $row['item_count']; ?></td>
                        <td>PHP <?php echo number_format($row['total_value'], 2); ?></td>
                        <td>
                            <a href="index.php?category=<?php echo $row['category_id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View Items
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-primary">
                        <th>Total</th>
                        <th>
                            <?php
                            $totalItems = array_sum(array_column($reportData, 'item_count'));
                            echo $totalItems;
                            ?>
                        </th>
                        <th>
                            PHP <?php
                            $totalValue = array_sum(array_column($reportData, 'total_value'));
                            echo number_format($totalValue, 2);
                            ?>
                        </th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Chart for Category Summary -->
        <div class="mt-4">
            <h5>Distribution by Category</h5>
            <div class="chart-container" style="position: relative; height:300px; width:100%">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            const categoryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [<?php echo "'" . implode("', '", array_column($reportData, 'category_name')) . "'"; ?>],
                    datasets: [{
                        label: 'Number of Items',
                        data: [<?php echo implode(', ', array_column($reportData, 'item_count')); ?>],
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
        </script>
        
        <?php elseif ($reportType === 'by_condition'): ?>
        <!-- Condition Summary Report -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Condition</th>
                        <th>Total Items</th>
                        <th>Percentage</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td>
                            <?php echo Helpers::getConditionBadge($row['condition_status']); ?>
                        </td>
                        <td><?php echo $row['item_count']; ?></td>
                        <td><?php echo number_format($row['percentage'], 2); ?>%</td>
                        <td>
                            <a href="index.php?status=<?php echo $row['condition_status']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View Items
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-primary">
                        <th>Total</th>
                        <th>
                            <?php
                            $totalItems = array_sum(array_column($reportData, 'item_count'));
                            echo $totalItems;
                            ?>
                        </th>
                        <th>100%</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Chart for Condition Summary -->
        <div class="mt-4">
            <h5>Distribution by Condition</h5>
            <div class="chart-container" style="position: relative; height:300px; width:100%">
                <canvas id="conditionChart"></canvas>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('conditionChart').getContext('2d');
            const conditionChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: [<?php 
                        $labels = array_map(function($item) {
                            return ucfirst($item['condition_status']);
                        }, $reportData);
                        echo "'" . implode("', '", $labels) . "'";
                    ?>],
                    datasets: [{
                        data: [<?php echo implode(', ', array_column($reportData, 'item_count')); ?>],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)', // Blue
                            'rgba(75, 192, 192, 0.7)', // Green
                            'rgba(255, 206, 86, 0.7)', // Yellow
                            'rgba(255, 99, 132, 0.7)',  // Red
                            'rgba(153, 102, 255, 0.7)', // Purple
                            'rgba(201, 203, 207, 0.7)'  // Grey
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
        </script>
        
        <?php elseif ($reportType === 'by_room'): ?>
        <!-- Room Summary Report -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Building</th>
                        <th>Room Number</th>
                        <th>Total Items</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td><?php echo $row['room_name'] ? htmlspecialchars($row['room_name']) : '<span class="text-muted">Not Assigned</span>'; ?></td>
                        <td><?php echo $row['building'] ? htmlspecialchars($row['building']) : '-'; ?></td>
                        <td><?php echo $row['room_number'] ? htmlspecialchars($row['room_number']) : '-'; ?></td>
                        <td><?php echo $row['item_count']; ?></td>
                        <td>
                            <?php if ($row['room_id']): ?>
                            <a href="index.php?room=<?php echo $row['room_id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View Items
                            </a>
                            <?php else: ?>
                            <a href="index.php?room=unassigned" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View Items
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-primary">
                        <th colspan="3">Total</th>
                        <th>
                            <?php
                            $totalItems = array_sum(array_column($reportData, 'item_count'));
                            echo $totalItems;
                            ?>
                        </th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php elseif ($reportType === 'maintenance_required' || $reportType === 'recently_added'): ?>
        <!-- Maintenance Required or Recently Added Report -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Equipment Name</th>
                        <th>Serial Number</th>
                        <th>Category</th>
                        <th>Condition</th>
                        <th>Room</th>
                        <?php if ($reportType === 'maintenance_required'): ?>
                        <th>Last Maintenance</th>
                        <?php else: ?>
                        <th>Date Added</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['serial_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo Helpers::getConditionBadge($row['condition_status']); ?></td>
                        <td><?php echo $row['room_name'] ? htmlspecialchars($row['room_name']) : '<span class="text-muted">Not Assigned</span>'; ?></td>
                        <?php if ($reportType === 'maintenance_required'): ?>
                        <td><?php echo $row['last_maintenance'] ? Helpers::formatDate($row['last_maintenance']) : '<span class="text-muted">Never</span>'; ?></td>
                        <?php else: ?>
                        <td><?php echo Helpers::formatDate($row['created_at']); ?></td>
                        <?php endif; ?>
                        <td>
                            <a href="view.php?id=<?php echo $row['equipment_id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <?php if ($reportType === 'maintenance_required' && $auth->canReportMaintenance()): ?>
                            <a href="../maintenance/report.php?equipment_id=<?php echo $row['equipment_id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-tools"></i> Report Issue
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php elseif ($reportType === 'value_summary'): ?>
        <!-- Value Summary Report -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total Items</th>
                        <th>Total Value</th>
                        <th>Average Value</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo $row['item_count']; ?></td>
                        <td>PHP <?php echo number_format($row['total_value'], 2); ?></td>
                        <td>PHP <?php echo number_format($row['average_value'], 2); ?></td>
                        <td>
                            <a href="index.php?category=<?php echo $row['category_id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View Items
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-primary">
                        <th>Total</th>
                        <th>
                            <?php
                            $totalItems = array_sum(array_column($reportData, 'item_count'));
                            echo $totalItems;
                            ?>
                        </th>
                        <th>
                            PHP <?php
                            $totalValue = array_sum(array_column($reportData, 'total_value'));
                            echo number_format($totalValue, 2);
                            ?>
                        </th>
                        <th>
                            PHP <?php
                            echo $totalItems > 0 ? number_format($totalValue / $totalItems, 2) : '0.00';
                            ?>
                        </th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Chart for Value Summary -->
        <div class="mt-4">
            <h5>Value Distribution by Category</h5>
            <div class="chart-container" style="position: relative; height:300px; width:100%">
                <canvas id="valueChart"></canvas>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('valueChart').getContext('2d');
            const valueChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [<?php echo "'" . implode("', '", array_column($reportData, 'category_name')) . "'"; ?>],
                    datasets: [{
                        label: 'Total Value (PHP)',
                        data: [<?php echo implode(', ', array_column($reportData, 'total_value')); ?>],
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
        </script>
        
        <?php else: ?>
        <!-- Default Detailed Equipment List -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Serial Number</th>
                        <th>Room</th>
                        <th>Condition</th>
                        <th>Acquisition Date</th>
                        <th>Cost</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td><?php echo $row['equipment_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['serial_number']); ?></td>
                        <td><?php echo $row['room_name'] ? htmlspecialchars($row['room_name']) : '<span class="text-muted">Not Assigned</span>'; ?></td>
                        <td><?php echo Helpers::getConditionBadge($row['condition_status']); ?></td>
                        <td><?php echo $row['acquisition_date'] ? Helpers::formatDate($row['acquisition_date']) : '-'; ?></td>
                        <td><?php echo $row['cost'] ? 'PHP ' . number_format($row['cost'], 2) : '-'; ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $row['equipment_id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
</div>

<script>
// Toggle detailed filters based on report type
document.addEventListener('DOMContentLoaded', function() {
    const reportType = document.getElementById('report_type');
    const detailedFilters = document.getElementById('detailedFilters');
    
    reportType.addEventListener('change', function() {
        if (this.value === 'all') {
            detailedFilters.classList.remove('d-none');
        } else {
            detailedFilters.classList.add('d-none');
        }
    });
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?> 