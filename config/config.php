<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lab_inventory_system');

// Application configuration
define('APP_NAME', 'Lab Equipment Inventory System');
define('APP_URL', 'http://192.168.1.10/lab_management_');
define('APP_ROOT', dirname(dirname(__FILE__)));
define('APP_VERSION', '1.0.0');
define('APP_EMAIL', 'noreply@labmanagement.com'); // System email address

// Session configuration
define('SESSION_NAME', 'lab_inventory');
define('SESSION_LIFETIME', 86400); // 24 hours
define('SESSION_PATH', '/');
define('SESSION_SECURE', false);
define('SESSION_HTTP_ONLY', true);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Manila'); // For Philippines

// Hash cost for password encryption
define('PASSWORD_COST', 12); 