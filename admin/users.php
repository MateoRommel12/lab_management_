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
$pageTitle = "User Management";
$currentPage = 'users';

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/User.php';

// Initialize models
$userModel = new User();

// Get all users with their roles
$users = $userModel->getAllUsers();

// Handle user deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    
    // Don't allow deletion of own account
    if ($userId === $auth->getUserId()) {
        Helpers::redirectWithMessage("users.php", "You cannot delete your own account.", "danger");
        exit;
    }
    
    // Get user details for logging
    $user = $userModel->findById($userId);
    
    if ($user && $userModel->delete($userId)) {
        // Log the action
        Helpers::logAction("Deleted user: " . $user['username']);
        Helpers::redirectWithMessage("users.php", "User deleted successfully.", "success");
        exit;
    } else {
        Helpers::redirectWithMessage("users.php", "Failed to delete user.", "danger");
        exit;
    }
}

// Handle user status toggle if requested
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $userId = (int)$_GET['toggle_status'];
    $user = $userModel->findById($userId);
    
    if ($user) {
        $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
        
        if ($userModel->update($userId, ['status' => $newStatus])) {
            // Log the action
            $actionText = $newStatus === 'active' ? "Activated" : "Deactivated";
            Helpers::logAction("$actionText user: " . $user['username']);
            
            Helpers::redirectWithMessage("users.php", "User $actionText successfully.", "success");
            exit;
        } else {
            Helpers::redirectWithMessage("users.php", "Failed to update user status.", "danger");
            exit;
        }
    } else {
        Helpers::redirectWithMessage("users.php", "User not found.", "danger");
        exit;
    }
}
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>
            <i class="fas fa-users me-2"></i>User Management
        </h1>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="add_user.php" class="btn btn-success">
            <i class="fas fa-user-plus me-2"></i>Add New User
        </a>
    </div>
</div>

<!-- Users List -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Users</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $user['role_id'] == 1 ? 'danger' : ($user['role_id'] == 2 ? 'primary' : ($user['role_id'] == 3 ? 'success' : 'info')); ?>">
                                <?php echo htmlspecialchars($user['role_name']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <?php if ($user['user_id'] !== $auth->getUserId()): ?>
                                    <a href="users.php?toggle_status=<?php echo $user['user_id']; ?>" class="btn btn-sm <?php echo $user['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?>" 
                                        onclick="return confirm('Are you sure you want to <?php echo $user['status'] === 'active' ? 'deactivate' : 'activate'; ?> this user?');">
                                        <i class="fas <?php echo $user['status'] === 'active' ? 'fa-ban' : 'fa-check'; ?>"></i>
                                    </a>
                                    
                                    <a href="users.php?delete=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
