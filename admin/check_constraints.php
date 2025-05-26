<?php
// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

// Get database instance
$db = Database::getInstance();

// Check for any foreign key constraints on equipment table
echo "Checking foreign key constraints for equipment table:\n\n";

// Query to get all tables that might reference equipment
$tables = [
    'borrowing_requests' => 'equipment_id',
    'maintenance_requests' => 'equipment_id',
    'equipment_movements' => 'equipment_id'
];

foreach ($tables as $table => $column) {
    try {
        echo "Table: $table, Column: $column\n";
        
        // Check the count of references
        $query = "SELECT COUNT(*) as count FROM $table WHERE $column = 1";
        $stmt = $db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "References to equipment_id=1: " . $result['count'] . "\n";
        
        // Check if the column has a foreign key constraint
        $query = "SHOW CREATE TABLE $table";
        $stmt = $db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (isset($result['Create Table'])) {
            echo "CREATE TABLE statement: \n" . $result['Create Table'] . "\n";
            
            // Check if the statement contains a foreign key constraint on the equipment column
            if (strpos($result['Create Table'], "FOREIGN KEY (`$column`)") !== false) {
                echo "Foreign key constraint found on $column in table $table\n";
                
                // Check if the constraint has ON DELETE restrictions
                if (strpos($result['Create Table'], "ON DELETE RESTRICT") !== false) {
                    echo "DELETE RESTRICT constraint found - this prevents deletion of referenced equipment\n";
                }
                if (strpos($result['Create Table'], "ON DELETE CASCADE") !== false) {
                    echo "DELETE CASCADE constraint found - this allows deletion of referenced equipment\n";
                }
                if (strpos($result['Create Table'], "ON DELETE SET NULL") !== false) {
                    echo "DELETE SET NULL constraint found - this sets NULL on deletion of referenced equipment\n";
                }
            } else {
                echo "No foreign key constraint found on $column in table $table\n";
            }
        }
    } catch (Exception $e) {
        echo "Error checking table $table: " . $e->getMessage() . "\n";
    }
    
    echo "\n---\n\n";
}

// Output to a file
file_put_contents(__DIR__ . '/constraints_check.txt', ob_get_contents());
?> 