<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../config/config.php';
require_once '../utils/Auth.php';
require_once '../utils/Helpers.php';

// Initialize Auth
$auth = Auth::getInstance();

// Check if user is admin, redirect if not
if (!$auth->isAdmin()) {
    header('Location: ../access-denied.php');
    exit;
}

// Set page title
$pageTitle = "Edit User";

// Include header
require_once '../includes/header.php';

// Include required models
require_once '../models/User.php';

// Initialize models
$userModel = new User();

// Check for user ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    Helpers::redirectWithMessage("users.php", "Invalid user ID.", "danger");
    exit;
}

$userId = (int)$_GET['id'];
$user = $userModel->getUserWithRole($userId);

if (!$user) {
    Helpers::redirectWithMessage("users.php", "User not found.", "danger");
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $userData = [
        'username' => trim($_POST['username']),
        'email' => trim($_POST['email']),
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'role_id' => (int)$_POST['role_id'],
        'status' => $_POST['status']
    ];
    
    // Only update password if provided
    if (!empty(trim($_POST['password']))) {
        $userData['password'] = trim($_POST['password']);
    }
    
    // Validate form data
    $errors = [];
    
    // Check if username is provided and unique
    if (empty($userData['username'])) {
        $errors[] = "Username is required";
    } elseif ($userData['username'] !== $user['username'] && $userModel->usernameExists($userData['username'])) {
        $errors[] = "Username already exists";
    }
    
    // Check if password meets requirements if provided
    if (isset($userData['password']) && strlen($userData['password']) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Check if email is provided and valid
    if (empty($userData['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } elseif ($userData['email'] !== $user['email'] && $userModel->emailExists($userData['email'])) {
        $errors[] = "Email already exists";
    }
    
    // Check if first name is provided
    if (empty($userData['first_name'])) {
        $errors[] = "First name is required";
    }
    
    // Check if last name is provided
    if (empty($userData['last_name'])) {
        $errors[] = "Last name is required";
    }
    
    // Check if role is provided
    if (empty($userData['role_id'])) {
        $errors[] = "Role is required";
    }
    
    // If no errors, update user
    if (empty($errors)) {
        if ($userModel->update($userId, $userData)) {
            // Log the action
            Helpers::logAction("Updated user: " . $userData['username']);
            
            Helpers::redirectWithMessage("users.php", "User updated successfully.", "success");
            exit;
        } else {
            $errors[] = "Failed to update user";
        }
    }
}

// Get all roles for dropdown
$roles = $userModel->getAllRoles();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1>
            <i class="fas fa-user-edit me-2"></i>Edit User
        </h1>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo $error; ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">User Information</h5>
    </div>
    <div class="card-body">
        <form action="edit_user.php?id=<?php echo $userId; ?>" method="POST" class="row g-3">
            <!-- Username -->
            <div class="col-md-6">
                <label for="username" class="form-label">Username *</label>
                <input type="text" class="form-control" id="username" name="username" required
                    value="<?php echo htmlspecialchars($user['username']); ?>">
            </div>
            
            <!-- Password (optional) -->
            <div class="col-md-6">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password"
                    minlength="8">
                <div class="form-text">Leave blank to keep current password. New password must be at least 8 characters long.</div>
            </div>
            
            <!-- Email -->
            <div class="col-md-6">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email" required
                    value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            
            <!-- Role -->
            <div class="col-md-6">
                <label for="role_id" class="form-label">Role *</label>
                <select class="form-select" id="role_id" name="role_id" required>
                    <option value="">Select Role</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['role_id']; ?>" <?php echo $user['role_id'] == $role['role_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($role['role_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- First Name -->
            <div class="col-md-6">
                <label for="first_name" class="form-label">First Name *</label>
                <input type="text" class="form-control" id="first_name" name="first_name" required
                    value="<?php echo htmlspecialchars($user['first_name']); ?>">
            </div>
            
            <!-- Last Name -->
            <div class="col-md-6">
                <label for="last_name" class="form-label">Last Name *</label>
                <input type="text" class="form-control" id="last_name" name="last_name" required
                    value="<?php echo htmlspecialchars($user['last_name']); ?>">
            </div>
            
            <!-- Status -->
            <div class="col-md-6">
                <label for="status" class="form-label">Status *</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="col-12 mt-4">
                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update User
                </button>
                <a href="users.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?> 