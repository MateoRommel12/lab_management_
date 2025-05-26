<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Auth.php';

// Initialize Auth
$auth = Auth::getInstance();

// Check if user is admin
if (!$auth->isAdmin()) {
    header('Location: ../access-denied.php');
    exit;
}

// Check if content and title are provided
if (!isset($_POST['content']) || !isset($_POST['title'])) {
    die('Missing required parameters');
}

$title = $_POST['title'];
$content = $_POST['content'];

// Check if we can use PHPSpreadsheet
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    // Try to use PHPSpreadsheet
    try {
        require_once $autoloadPath;
        generateExcel($title, $content);
    } catch (Exception $e) {
        // If any error occurs with PHPSpreadsheet, fall back to CSV
        generateCSV($title, $content);
    }
} else {
    // Fall back to CSV if vendor/autoload.php doesn't exist
    generateCSV($title, $content);
}

/**
 * Generate Excel file using PHPSpreadsheet
 */
function generateExcel($title, $content) {
    // Create new Spreadsheet object
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('Lab Management System')
        ->setLastModifiedBy('Lab Management System')
        ->setTitle($title)
        ->setSubject($title)
        ->setDescription('Generated on: ' . date('Y-m-d H:i:s'));

    // Set title
    $sheet->setCellValue('A1', $title);
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Set generation date
    $sheet->setCellValue('A2', 'Generated on: ' . date('Y-m-d H:i:s'));
    $sheet->mergeCells('A2:H2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Add a blank row
    $sheet->setCellValue('A3', '');

    // Parse HTML content and convert to Excel
    $dom = new DOMDocument();
    @$dom->loadHTML($content);
    $tables = $dom->getElementsByTagName('table');

    $row = 4;
    foreach ($tables as $table) {
        $rows = $table->getElementsByTagName('tr');
        foreach ($rows as $tableRow) {
            $cells = $tableRow->getElementsByTagName('td');
            $headers = $tableRow->getElementsByTagName('th');
            
            $col = 1;
            // Process headers
            foreach ($headers as $header) {
                $sheet->setCellValueByColumnAndRow($col, $row, trim($header->textContent));
                $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setBold(true);
                $col++;
            }
            
            // Process cells
            foreach ($cells as $cell) {
                $sheet->setCellValueByColumnAndRow($col, $row, trim($cell->textContent));
                $col++;
            }
            $row++;
        }
        $row += 2; // Add spacing between tables
    }

    // Auto-size columns
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set borders
    $lastRow = $row - 1;
    $sheet->getStyle('A1:H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    // Create Excel file
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $title . '_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    // Save file to PHP output
    $writer->save('php://output');
    exit;
}

/**
 * Generate CSV file as fallback
 */
function generateCSV($title, $content) {
    // Parse HTML content and convert to CSV
    $dom = new DOMDocument();
    @$dom->loadHTML($content);
    $tables = $dom->getElementsByTagName('table');
    
    // Prepare CSV content
    $csvContent = $title . "\n";
    $csvContent .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $rows = $table->getElementsByTagName('tr');
        foreach ($rows as $tableRow) {
            $cells = $tableRow->getElementsByTagName('td');
            $headers = $tableRow->getElementsByTagName('th');
            
            $rowData = [];
            
            // Process headers
            foreach ($headers as $header) {
                $rowData[] = '"' . str_replace('"', '""', trim($header->textContent)) . '"';
            }
            
            // Process cells
            foreach ($cells as $cell) {
                $rowData[] = '"' . str_replace('"', '""', trim($cell->textContent)) . '"';
            }
            
            $csvContent .= implode(",", $rowData) . "\n";
        }
        $csvContent .= "\n";
    }
    
    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $title . '_' . date('Y-m-d') . '.csv"');
    
    // Output CSV content
    echo $csvContent;
    exit;
} 