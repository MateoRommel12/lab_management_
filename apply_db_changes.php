<?php
// Include the database configuration
require_once 'config/config.php';
require_once 'config/Database.php';

// Connect to database
$db = Database::getInstance();

try {
    echo "Modifying audit_logs table structure...<br>";
    
    // Drop the foreign key constraint
    $sql1 = "ALTER TABLE audit_logs DROP FOREIGN KEY audit_logs_ibfk_1";
    $db->execute($sql1);
    echo "Foreign key constraint dropped.<br>";
    
    // Modify the column to allow NULL values
    $sql2 = "ALTER TABLE audit_logs MODIFY user_id INT NULL";
    $db->execute($sql2);
    echo "Column modified to allow NULL values.<br>";
    
    // Add the foreign key constraint back with ON DELETE SET NULL
    $sql3 = "ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_ibfk_1 
             FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL";
    $db->execute($sql3);
    echo "Foreign key constraint added with ON DELETE SET NULL.<br>";
    
    echo "Database structure updated successfully!<br>";
    echo "<a href='login.php'>Go to login page</a>";
    
} catch (Exception $e) {
    echo "Error updating database structure: " . $e->getMessage() . "<br>";
}
?> 