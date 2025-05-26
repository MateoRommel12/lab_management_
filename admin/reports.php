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
$pageTitle = "Report Generation";
$currentPage = 'reports';

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/Borrowing.php';
require_once __DIR__ . '/../models/Maintenance.php';

// Initialize models
$equipmentModel = new Equipment();
$roomModel = new Room();
$borrowingModel = new Borrowing();
$maintenanceModel = new Maintenance();

// Process report generation if requested
$reportData = null;
$reportType = '';
$startDate = '';
$endDate = '';
$category = '';
$status = '';
$generateReport = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $reportType = $_POST['report_type'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $category = $_POST['category'] ?? '';
    $status = $_POST['status'] ?? '';
    $generateReport = true;
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>
            <i class="fas fa-file-alt me-2"></i>Report Generation
        </h1>
    </div>
</div>

<div class="row">
    <!-- Report Selection Form -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Generate Report</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="">Select a report type</option>
                            <option value="equipment_status" <?php echo $reportType === 'equipment_status' ? 'selected' : ''; ?>>Equipment Status</option>
                            <option value="equipment_inventory" <?php echo $reportType === 'equipment_inventory' ? 'selected' : ''; ?>>Equipment Inventory</option>
                            <option value="borrowing_history" <?php echo $reportType === 'borrowing_history' ? 'selected' : ''; ?>>Borrowing History</option>
                            <option value="maintenance_history" <?php echo $reportType === 'maintenance_history' ? 'selected' : ''; ?>>Maintenance History</option>
                            <option value="room_usage" <?php echo $reportType === 'room_usage' ? 'selected' : ''; ?>>Room Usage</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    
                    <div class="mb-3 category-filter" style="display: none;">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php
                            // Get equipment categories
                            require_once __DIR__ . '/../models/Category.php';
                            $categoryModel = new Category();
                            $categories = $categoryModel->getAllCategories();
                            
                            foreach ($categories as $cat) {
                                $selected = $category == $cat['category_id'] ? 'selected' : '';
                                echo "<option value='{$cat['category_id']}' $selected>{$cat['category_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3 status-filter" style="display: none;">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="under maintenance" <?php echo $status === 'under maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                        </select>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="generate_report" class="btn btn-primary">
                            <i class="fas fa-file-export me-2"></i>Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Report Display Area -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Report Results</h5>
                <?php if ($generateReport): ?>
                <div>
                    <button id="printBtn" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                    <button id="exportPdfBtn" class="btn btn-sm btn-outline-danger me-2">
                        <i class="fas fa-file-pdf me-2"></i>Export PDF
                    </button>
                    <button id="exportExcelBtn" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-excel me-2"></i>Export Excel
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$generateReport): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Select report parameters and click Generate Report</h4>
                    </div>
                <?php else: ?>
                    <div id="reportContent">
                        <?php
                        // Generate the appropriate report based on type
                        if ($reportType === 'equipment_status') {
                            // Equipment Status Report
                            $equipmentData = $equipmentModel->getEquipmentReport($status, $category);
                            require_once __DIR__ . '/reports/equipment_status_report.php';
                        } elseif ($reportType === 'equipment_inventory') {
                            // Equipment Inventory Report
                            $inventoryData = $equipmentModel->getInventoryReport($category);
                            require_once __DIR__ . '/reports/equipment_inventory_report.php';
                        } elseif ($reportType === 'borrowing_history') {
                            // Borrowing History Report
                            $borrowingData = $borrowingModel->getBorrowingReport($startDate, $endDate);
                            require_once __DIR__ . '/reports/borrowing_history_report.php';
                        } elseif ($reportType === 'maintenance_history') {
                            // Maintenance History Report
                            $maintenanceData = $maintenanceModel->getMaintenanceReport($startDate, $endDate);
                            require_once __DIR__ . '/reports/maintenance_history_report.php';
                        } elseif ($reportType === 'room_usage') {
                            // Room Usage Report
                            $roomData = $roomModel->getRoomUsageReport();
                            require_once __DIR__ . '/reports/room_usage_report.php';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Print functionality
    document.getElementById('printBtn')?.addEventListener('click', function() {
        window.print();
    });

    // PDF Export functionality
    document.getElementById('exportPdfBtn')?.addEventListener('click', function() {
        const reportContent = document.getElementById('reportContent').innerHTML;
        const reportTitle = document.querySelector('.card-header h5').textContent;
        
        // Create a form and submit it to generate PDF
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'generate_pdf.php';
        
        const contentInput = document.createElement('input');
        contentInput.type = 'hidden';
        contentInput.name = 'content';
        contentInput.value = reportContent;
        
        const titleInput = document.createElement('input');
        titleInput.type = 'hidden';
        titleInput.name = 'title';
        titleInput.value = reportTitle;
        
        form.appendChild(contentInput);
        form.appendChild(titleInput);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    // Excel Export functionality
    document.getElementById('exportExcelBtn')?.addEventListener('click', function() {
        const reportContent = document.getElementById('reportContent').innerHTML;
        const reportTitle = document.querySelector('.card-header h5').textContent;
        
        // Create a form and submit it to generate Excel
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'generate_excel.php';
        
        const contentInput = document.createElement('input');
        contentInput.type = 'hidden';
        contentInput.name = 'content';
        contentInput.value = reportContent;
        
        const titleInput = document.createElement('input');
        titleInput.type = 'hidden';
        titleInput.name = 'title';
        titleInput.value = reportTitle;
        
        form.appendChild(contentInput);
        form.appendChild(titleInput);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });
});
</script> 