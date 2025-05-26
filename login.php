<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files - note: only include what's not in bootstrap.php
require_once 'config/config.php';
require_once 'utils/Auth.php';
require_once 'utils/Helpers.php';

// Initialize Auth
$auth = Auth::getInstance();

// Check if already logged in
if ($auth->isLoggedIn()) {
    // Redirect based on role
    switch ($auth->getUserRoleId()) {
        case 1: // Administrator
            header('Location: admin/dashboard.php');
            break;
        case 2: // Faculty
            header('Location: faculty/dashboard.php');
            break;
        case 3: // Lab Technician
            header('Location: technician/dashboard.php');
            break;
        case 4: // Student Assistant
            header('Location: student/dashboard.php');
            break;
        default:
            header('Location: index.php');
    }
    exit;
}

// Process form submission
$loginMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $loginMessage = 'Please provide both username and password.';
    } else {
        // Perform login
        $result = $auth->login($username, $password);
        
        if (is_array($result) && isset($result['success']) && $result['success']) {
            // Set remember cookie if requested
            if ($remember) {
                setcookie('remember_user', $username, time() + 30 * 24 * 60 * 60, '/');
            }
            
            // Redirect to appropriate page
            header('Location: ' . $result['redirect']);
            exit;
        } else {
            $loginMessage = is_array($result) && isset($result['message']) ? 
                $result['message'] : 'Invalid username or password.';
        }
    }
}

// Get remembered username from cookie
$rememberedUsername = $_COOKIE['remember_user'] ?? '';

// Set page title
$pageTitle = "Login";

// Include header - only AFTER all potential redirects
require_once 'includes/header.php';

// Display any login message
if (!empty($loginMessage)) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($loginMessage) . '</div>';
}
?>

<div class="login-container">
    <div class="card login-card">
        <div class="card-header bg-primary text-white text-center py-3">
            <h4 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Login</h4>
        </div>
        <div class="card-body p-4">
            <form action="login.php" method="post" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($rememberedUsername); ?>" required>
                        <div class="invalid-feedback">
                            Please enter your username.
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
                            Please enter your password.
                        </div>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </div>
            </form>
            
            <div class="mt-3 text-center">
                <p class="mb-0">
                    <a href="forgot-password.php">Forgot Password?</a>
                </p>
                <p class="mt-2">
                    Don't have an account? <a href="register.php">Register</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

    