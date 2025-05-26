<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once 'config/config.php';
require_once 'utils/Auth.php';
require_once 'utils/Helpers.php';

// Initialize Auth
$auth = Auth::getInstance();

// Set page title
$pageTitle = "Reset Password";

// Get token from URL
$token = $_GET['token'] ?? '';

// Initialize variables
$message = '';
$messageType = '';
$validToken = false;
$userId = null;

// Validate token
if (!empty($token)) {
    $userModel = new User();
    $tokenData = $userModel->validateResetToken($token);
    
    if ($tokenData && strtotime($tokenData['expiry']) > time()) {
        $validToken = true;
        $userId = $tokenData['user_id'];
    } else {
        $message = 'Invalid or expired reset token. Please request a new password reset.';
        $messageType = 'danger';
    }
} else {
    $message = 'No reset token provided.';
    $messageType = 'danger';
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirmPassword)) {
        $message = 'Please enter both password fields.';
        $messageType = 'danger';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'danger';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $messageType = 'danger';
    } else {
        // Update password
        if ($userModel->updatePassword($userId, $password)) {
            // Invalidate reset token
            $userModel->invalidateResetToken($token);
            
            $message = 'Your password has been reset successfully. You can now login with your new password.';
            $messageType = 'success';
            
            // Redirect to login after 3 seconds
            header('refresh:3;url=login.php');
        } else {
            $message = 'Failed to reset password. Please try again later.';
            $messageType = 'danger';
        }
    }
}

// Include header
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-key me-2"></i>Reset Password</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($validToken): ?>
                        <form action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        Please enter a password (minimum 8 characters).
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#confirm_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        Please confirm your password.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Reset Password
                                </button>
                                <a href="login.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center">
                            <a href="forgot-password.php" class="btn btn-primary">
                                <i class="fas fa-redo me-2"></i>Request New Reset Link
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.querySelector(targetId);
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});

// Form validation
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php require_once 'includes/footer.php'; ?> 