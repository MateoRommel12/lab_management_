<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'PHPMailer-master/src/Exception.php';
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set page title
$pageTitle = "Forgot Password";

// Process form submission
$message = '';
$messageType = '';

$user = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'danger';
    } else {
        $userData = $user->getUserByEmail($email);
        
        if ($userData) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save token to database
            if ($user->saveResetToken($userData['user_id'], $token, $expiry)) {
                // Create reset link
                $resetLink = APP_URL . '/reset-password.php?token=' . $token;
                
                // Initialize PHPMailer
                $mail = new PHPMailer(true);
                
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'rvnmatter24@gmail.com'; // Replace with your Gmail
                    $mail->Password = 'gdkhlpfwalbbqevi'; // Replace with your Gmail App Password (no spaces)
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    
                    // Recipients   
                    $mail->setFrom('rvnmatter24@gmail.com', APP_NAME);
                    $mail->addAddress($email, $userData['first_name'] . ' ' . $userData['last_name']);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request';
                    $mail->Body = "
                        <h2>Password Reset Request</h2>
                        <p>Hello {$userData['first_name']},</p>
                        <p>We received a request to reset your password. Click the link below to reset your password:</p>
                        <p><a href='{$resetLink}'>{$resetLink}</a></p>
                        <p>This link will expire in 1 hour.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                        <p>Best regards,<br>" . APP_NAME . "</p>
                    ";
                    
                    $mail->send();
                    $message = 'Password reset instructions have been sent to your email.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    $messageType = 'danger';
                }
            } else {
                $message = 'Failed to process password reset request';
                $messageType = 'danger';
            }
        } else {
            // Don't reveal if email exists or not for security
            $message = 'If your email is registered, you will receive password reset instructions.';
            $messageType = 'info';
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
                    <h4 class="mb-0"><i class="fas fa-key me-2"></i>Forgot Password</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="forgot-password.php" method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">
                                    Please enter your email address.
                                </div>
                            </div>
                            <div class="form-text">
                                Enter your registered email address to receive password reset instructions.
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Send Reset Instructions
                            </button>
                            <a href="login.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 