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

// Check authentication before setting page title - redirect if not logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Set page title
$pageTitle = "Change Password";

// Process password change if submitted
$passwordMessage = '';
$passwordMessageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $passwordMessage = 'All fields are required';
        $passwordMessageType = 'danger';
    } else if ($newPassword !== $confirmPassword) {
        $passwordMessage = 'New passwords do not match';
        $passwordMessageType = 'danger';
    } else if (strlen($newPassword) < 8) {
        $passwordMessage = 'New password must be at least 8 characters long';
        $passwordMessageType = 'danger';
    } else {
        // Verify current password
        if ($auth->verifyPassword($currentPassword)) {
            // Update password
            if ($auth->updatePassword($newPassword)) {
                $passwordMessage = 'Password has been updated successfully';
                $passwordMessageType = 'success';
            } else {
                $passwordMessage = 'Failed to update password. Please try again.';
                $passwordMessageType = 'danger';
            }
        } else {
            $passwordMessage = 'Current password is incorrect';
            $passwordMessageType = 'danger';
        }
    }
}

// Include header
require_once 'includes/header.php';

// Get current user
$currentUser = $auth->getUser();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1 class="display-5 mb-4">
            <i class="fas fa-key me-2"></i>Change Password
        </h1>
        <p class="lead">Update your account password below.</p>
    </div>
</div>

<!-- Change Password Section -->
<div class="row mb-4">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0"><i class="fas fa-key me-2"></i>Change Your Password</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($passwordMessage)): ?>
                    <div class="alert alert-<?php echo $passwordMessageType; ?> alert-dismissible fade show">
                        <?php echo $passwordMessage; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo APP_URL; ?>/change-password.php" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#current_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#new_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#confirm_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-2"></i>Update Password
                            </button>
                            <a href="<?php echo APP_URL; ?>/profile.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Profile
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide password toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(button => {
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
});
</script>

<?php require_once 'includes/footer.php'; ?> 