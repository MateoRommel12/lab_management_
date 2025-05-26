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
$pageTitle = "Add User";
$currentPage = 'users';

// Include header
require_once __DIR__ . '/../includes/header.php';

// Include required models
require_once __DIR__ . '/../models/User.php';

// Initialize models
$userModel = new User();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $userData = [
        'username' => trim($_POST['username']),
        'password' => trim($_POST['password']),
        'email' => trim($_POST['email']),
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'role_id' => (int)$_POST['role_id'],
        'status' => 'active'
    ];
    
    // Validate form data
    $errors = [];
    
    // Check if username is provided and unique
    if (empty($userData['username'])) {
        $errors[] = "Username is required";
    } elseif ($userModel->usernameExists($userData['username'])) {
        $errors[] = "Username already exists";
    }
    
    // Check if password is provided and meets requirements
    if (empty($userData['password'])) {
        $errors[] = "Password is required";
    } elseif (strlen($userData['password']) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Check if email is provided and valid
    if (empty($userData['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } elseif ($userModel->emailExists($userData['email'])) {
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
    
    // If no errors, add user
    if (empty($errors)) {
        $result = $userModel->register($userData);
        
        if ($result['success']) {
            // Log the action
            Helpers::logAction("Added new user: " . $userData['username']);
            
            Helpers::redirectWithMessage("users.php", "User added successfully.", "success");
            exit;
        } else {
            $errors[] = $result['message'] ?? "Failed to add user";
        }
    }
}

// Get all roles for dropdown
$roles = $userModel->getAllRoles();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1>
            <i class="fas fa-user-plus me-2"></i>Add New User
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
        <form action="add_user.php" method="POST" class="row g-3">
            <!-- Username -->
            <div class="col-md-6">
                <label for="username" class="form-label">Username *</label>
                <input type="text" class="form-control" id="username" name="username" required
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <!-- Password -->
            <div class="col-md-6">
                <label for="password" class="form-label">Password *</label>
                <input type="password" class="form-control" id="password" name="password" required
                    minlength="8">
                <div class="form-text">Password must be at least 8 characters long</div>
            </div>
            
            <!-- Email -->
            <div class="col-md-6">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email" required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <!-- Role -->
            <div class="col-md-6">
                <label for="role_id" class="form-label">Role *</label>
                <select class="form-select" id="role_id" name="role_id" required>
                    <option value="">Select Role</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['role_id']; ?>" <?php echo isset($_POST['role_id']) && $_POST['role_id'] == $role['role_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($role['role_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- First Name -->
            <div class="col-md-6">
                <label for="first_name" class="form-label">First Name *</label>
                <input type="text" class="form-control" id="first_name" name="first_name" required
                    value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
            </div>
            
            <!-- Last Name -->
            <div class="col-md-6">
                <label for="last_name" class="form-label">Last Name *</label>
                <input type="text" class="form-control" id="last_name" name="last_name" required
                    value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
            </div>
            
            <div class="col-12 mt-4">
                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Add User
                </button>
                <a href="users.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

