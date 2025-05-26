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
$pageTitle = "My Profile";

// Include header
require_once 'includes/header.php';

// Get current user
$currentUser = $auth->getUser();
$roleMap = [
    '1' => ['Administrator', 'fa-user-shield', 'bg-danger'],
    '2' => ['Faculty', 'fa-chalkboard-teacher', 'bg-primary'],
    '3' => ['Lab Technician', 'fa-tools', 'bg-info'],
    '4' => ['Student Assistant', 'fa-user-graduate', 'bg-success']
];
$roleInfo = $roleMap[$currentUser['role_id']] ?? ['Unknown', 'fa-user', 'bg-secondary'];
$statusClass = $currentUser['status'] === 'active' ? 'bg-success' : 'bg-secondary';
?>

<style>
body {
    background: #f4f6fb;
}
.profile-card {
    border-radius: 18px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    background: #fff;
    padding: 2.5rem 2rem 2rem 2rem;
    margin-top: 2rem;
}
.profile-avatar {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: 50%;
    border: 5px solid #e3e6f0;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
}
.profile-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.2rem;
}
.profile-value {
    font-size: 1.1rem;
    color: #212529;
}
.profile-icon {
    width: 1.3em;
    margin-right: 0.5em;
    color: #6c757d;
}
@media (max-width: 767px) {
    .profile-card { padding: 1.2rem 0.5rem; }
}
</style>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="profile-card">
                <div class="text-center">
                    
                    <h2 class="fw-bold mb-1 mt-2">
                        <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>
                    </h2>
                    <span class="badge <?php echo $roleInfo[2]; ?> me-1"><i class="fas <?php echo $roleInfo[1]; ?> me-1"></i><?php echo $roleInfo[0]; ?></span>
                    <span class="badge <?php echo $statusClass; ?>">Status: <?php echo ucfirst(htmlspecialchars($currentUser['status'])); ?></span>
                </div>
                <hr class="my-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="profile-label"><i class="fas fa-user profile-icon"></i>Username</div>
                        <div class="profile-value"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-label"><i class="fas fa-envelope profile-icon"></i>Email</div>
                        <div class="profile-value"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-label"><i class="fas fa-id-card profile-icon"></i>Full Name</div>
                        <div class="profile-value"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-label"><i class="fas fa-clock profile-icon"></i>Last Login</div>
                        <div class="profile-value"><?php echo isset($currentUser['last_login']) && $currentUser['last_login'] ? Helpers::formatDateTime($currentUser['last_login']) : 'Never'; ?></div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-12 text-center">
                        <a href="<?php echo APP_URL; ?>/change-password.php" class="btn btn-warning me-2">
                            <i class="fas fa-key me-2"></i>Change Password
                        </a>
                        <?php
                        $role = $auth->getUser()['role_id'];
                        $dashboardPath = '';
                        switch($role) {
                            case 1: $dashboardPath = 'admin/dashboard.php'; break;
                            case 2: $dashboardPath = 'faculty/dashboard.php'; break;
                            case 3: $dashboardPath = 'technician/dashboard.php'; break;
                            case 4: $dashboardPath = 'student/dashboard.php'; break;
                            default: $dashboardPath = 'index.php';
                        }
                        ?>
                        <a href="<?php echo APP_URL . '/' . $dashboardPath; ?>" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 