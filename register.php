<?php
// Set page title
$pageTitle = "Register";

// Include header
require_once 'includes/header.php';

// Check if user is already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Get user roles for dropdown
require_once 'models/BaseModel.php';
require_once 'models/User.php';

$userModel = new User();
$db = Database::getInstance();
$roles = $db->resultSet("SELECT * FROM roles");

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $roleId = $_POST['role_id'] ?? 2; // Default to Faculty role
    
    // Validate input
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 4) {
        $errors[] = 'Username must be at least 4 characters long.';
    } elseif ($userModel->usernameExists($username)) {
        $errors[] = 'Username already exists.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!Helpers::validateEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif ($userModel->emailExists($email)) {
        $errors[] = 'Email already exists.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($firstName)) {
        $errors[] = 'First name is required.';
    }
    
    if (empty($lastName)) {
        $errors[] = 'Last name is required.';
    }
    
    // If there are no errors, register the user
    if (empty($errors)) {
        $userData = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role_id' => $roleId,
            'status' => 'active'
        ];
        
        $result = $auth->register($userData);
        
        if ($result['success']) {
            // Store role_id in session for login redirect
            $_SESSION['register_role_id'] = $roleId;
            
            Helpers::setFlashMessage('success', 'Registration successful! You can now log in.');
            header('Location: login.php');
            exit;
        } else {
            Helpers::setFlashMessage('error', 'Registration failed: ' . $result['message']);
        }
    } else {
        // Show errors
        $errorMessage = implode('<br>', $errors);
        Helpers::setFlashMessage('error', $errorMessage);
    }
}
?>

<div class="register-container">
    <div class="card register-card">
        <div class="card-header bg-primary text-white text-center py-3">
            <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Register</h4>
        </div>
        <div class="card-body p-4">
            <form action="register.php" method="post" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $_POST['first_name'] ?? ''; ?>" required>
                        <div class="invalid-feedback">
                            Please enter your first name.
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $_POST['last_name'] ?? ''; ?>" required>
                        <div class="invalid-feedback">
                            Please enter your last name.
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                        <div class="invalid-feedback">
                            Please enter a valid email address.
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo $_POST['username'] ?? ''; ?>" required>
                        <div class="invalid-feedback">
                            Please enter a username (at least 4 characters).
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="invalid-feedback">
                            Please enter a password (at least 6 characters).
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#confirm_password">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="invalid-feedback">
                            Please confirm your password.
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="role_id" class="form-label">Role</label>
                    <select class="form-select" id="role_id" name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['role_id']; ?>" <?php echo (isset($_POST['role_id']) && $_POST['role_id'] == $role['role_id']) ? 'selected' : ''; ?>>
                                <?php echo $role['role_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">
                        Please select a role.
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </button>
                </div>
            </form>
            
            <div class="mt-3 text-center">
                <p>
                    Already have an account? <a href="login.php">Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 