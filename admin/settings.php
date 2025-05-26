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
$pageTitle = "System Settings";
$currentPage = 'settings';

// Include header
require_once __DIR__ . '/../includes/header.php';

// Initialize settings array
$settings = [
    'app_name' => APP_NAME,
    'app_email' => 'admin@example.com',
    'max_borrow_days' => 14,
    'maintenance_notification' => 'enabled',
    'overdue_notification' => 'enabled',
    'user_registration' => 'enabled',
    'email_notifications' => 'disabled',
    'system_theme' => 'light',
    'default_language' => 'english',
    'maintenance_mode' => 'disabled'
];

// Initialize message
$message = '';
$messageType = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $newSettings = [
        'app_name' => trim($_POST['app_name']),
        'app_email' => trim($_POST['app_email']),
        'max_borrow_days' => (int)$_POST['max_borrow_days'],
        'maintenance_notification' => $_POST['maintenance_notification'],
        'overdue_notification' => $_POST['overdue_notification'],
        'user_registration' => $_POST['user_registration'],
        'email_notifications' => $_POST['email_notifications'],
        'system_theme' => $_POST['system_theme'],
        'default_language' => $_POST['default_language'],
        'maintenance_mode' => $_POST['maintenance_mode']
    ];
    
    // Validate form data
    $errors = [];
    
    if (empty($newSettings['app_name'])) {
        $errors[] = "Application name is required";
    }
    
    if (empty($newSettings['app_email']) || !filter_var($newSettings['app_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid application email is required";
    }
    
    if ($newSettings['max_borrow_days'] < 1 || $newSettings['max_borrow_days'] > 180) {
        $errors[] = "Maximum borrow days must be between 1 and 180";
    }
    
    // If no errors, update settings
    if (empty($errors)) {
        // In a real implementation, you would save these settings to a database or configuration file
        // For this example, we'll just show a success message
        
        // Log the action
        Helpers::logAction("Updated system settings");
        
        $message = "Settings saved successfully.";
        $messageType = "success";
        
        // Update local settings array to reflect changes
        $settings = $newSettings;
    } else {
        $message = implode("<br>", $errors);
        $messageType = "danger";
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1>
            <i class="fas fa-cogs me-2"></i>System Settings
        </h1>
    </div>
</div>

<?php if (!empty($message)): ?>
<div class="alert alert-<?php echo $messageType; ?>">
    <?php echo $message; ?>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">General Settings</h5>
            </div>
            <div class="card-body">
                <form action="settings.php" method="POST" class="row g-3">
                    <!-- General Settings -->
                    <div class="col-md-6">
                        <label for="app_name" class="form-label">Application Name *</label>
                        <input type="text" class="form-control" id="app_name" name="app_name" required
                            value="<?php echo htmlspecialchars($settings['app_name']); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="app_email" class="form-label">Application Email *</label>
                        <input type="email" class="form-control" id="app_email" name="app_email" required
                            value="<?php echo htmlspecialchars($settings['app_email']); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="max_borrow_days" class="form-label">Maximum Borrow Days *</label>
                        <input type="number" class="form-control" id="max_borrow_days" name="max_borrow_days" required
                            min="1" max="180" value="<?php echo $settings['max_borrow_days']; ?>">
                        <div class="form-text">Maximum number of days equipment can be borrowed</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="system_theme" class="form-label">System Theme</label>
                        <select class="form-select" id="system_theme" name="system_theme">
                            <option value="light" <?php echo $settings['system_theme'] === 'light' ? 'selected' : ''; ?>>Light</option>
                            <option value="dark" <?php echo $settings['system_theme'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="default_language" class="form-label">Default Language</label>
                        <select class="form-select" id="default_language" name="default_language">
                            <option value="english" <?php echo $settings['default_language'] === 'english' ? 'selected' : ''; ?>>English</option>
                            <option value="spanish" <?php echo $settings['default_language'] === 'spanish' ? 'selected' : ''; ?>>Spanish</option>
                            <option value="french" <?php echo $settings['default_language'] === 'french' ? 'selected' : ''; ?>>French</option>
                        </select>
                    </div>
                    
                    <!-- Feature Settings -->
                    <div class="col-12 mt-4">
                        <h5>Features</h5>
                        <hr>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="user_registration" class="form-label">User Registration</label>
                        <select class="form-select" id="user_registration" name="user_registration">
                            <option value="enabled" <?php echo $settings['user_registration'] === 'enabled' ? 'selected' : ''; ?>>Enabled</option>
                            <option value="disabled" <?php echo $settings['user_registration'] === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                        </select>
                        <div class="form-text">Allow new users to register</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="maintenance_notification" class="form-label">Maintenance Notifications</label>
                        <select class="form-select" id="maintenance_notification" name="maintenance_notification">
                            <option value="enabled" <?php echo $settings['maintenance_notification'] === 'enabled' ? 'selected' : ''; ?>>Enabled</option>
                            <option value="disabled" <?php echo $settings['maintenance_notification'] === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                        </select>
                        <div class="form-text">Notify technicians of new maintenance requests</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="overdue_notification" class="form-label">Overdue Notifications</label>
                        <select class="form-select" id="overdue_notification" name="overdue_notification">
                            <option value="enabled" <?php echo $settings['overdue_notification'] === 'enabled' ? 'selected' : ''; ?>>Enabled</option>
                            <option value="disabled" <?php echo $settings['overdue_notification'] === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                        </select>
                        <div class="form-text">Notify users and admins of overdue equipment</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email_notifications" class="form-label">Email Notifications</label>
                        <select class="form-select" id="email_notifications" name="email_notifications">
                            <option value="enabled" <?php echo $settings['email_notifications'] === 'enabled' ? 'selected' : ''; ?>>Enabled</option>
                            <option value="disabled" <?php echo $settings['email_notifications'] === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                        </select>
                        <div class="form-text">Send email notifications for various events</div>
                    </div>
                    
                    <!-- System Settings -->
                    <div class="col-12 mt-4">
                        <h5>System</h5>
                        <hr>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="maintenance_mode" class="form-label">Maintenance Mode</label>
                        <select class="form-select" id="maintenance_mode" name="maintenance_mode">
                            <option value="enabled" <?php echo $settings['maintenance_mode'] === 'enabled' ? 'selected' : ''; ?>>Enabled</option>
                            <option value="disabled" <?php echo $settings['maintenance_mode'] === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                        </select>
                        <div class="form-text">Put the system in maintenance mode (only admins can access)</div>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <hr>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Settings
                        </button>
                        <a href="../index.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

