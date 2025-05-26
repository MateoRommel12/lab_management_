<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AuditLog.php';

class Auth {
    private static $instance = null;
    private $user = null;
    
    // Private constructor for singleton pattern
    private function __construct() {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params(SESSION_LIFETIME, SESSION_PATH, $_SERVER['HTTP_HOST'], SESSION_SECURE, SESSION_HTTP_ONLY);
            session_start();
        }
        
        // Check if user is logged in
        if (isset($_SESSION['user_id'])) {
            $userModel = new User();
            $this->user = $userModel->findById($_SESSION['user_id']);
            
            // If user not found or inactive, logout
            if (!$this->user || $this->user['status'] !== 'active') {
                $this->logout();
            }
        }
    }
    
    // Get singleton instance
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Auth();
        }
        return self::$instance;
    }
    
    // Login user
    public function login($username, $password) {
        $userModel = new User();
        $user = $userModel->authenticate($username, $password);
        
        if ($user) {
            // Set session data
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['login_time'] = time();
            
            // Update user object
            $this->user = $user;
            
            // Log successful login action
            $auditLog = new AuditLog();
            $auditLog->logAction(
                $user['user_id'],
                'login',
                'User logged in successfully',
                $this->getIpAddress()
            );
            
            // Determine redirect URL based on role
            $redirect = 'index.php';
            
            // Check user role and set appropriate redirect
            switch ($user['role_id']) {
                case 1: // Administrator
                    $redirect = 'admin/dashboard.php';
                    break;
                case 2: // Faculty
                    $redirect = 'faculty/dashboard.php';
                    break;
                case 3: // Lab Technician
                    $redirect = 'technician/dashboard.php';
                    break;
                case 4: // Student Assistant
                    $redirect = 'student/dashboard.php';
                    break;
                default:
                    $redirect = 'index.php';
            }
            
            return ['success' => true, 'redirect' => $redirect];
        }
        
        // Log failed login attempts without user_id to avoid foreign key violations
        $auditLog = new AuditLog();
        $auditLog->logActionNoUser(
            'login_failed',
            'Failed login attempt for username: ' . $username,
            $this->getIpAddress()
        );
        
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Logout user
    public function logout() {
        error_log("Auth::logout() called");
        
        // Log logout action if user is logged in
        if (isset($_SESSION['user_id'])) {
            error_log("User ID found in session: " . $_SESSION['user_id']);
            $auditLog = new AuditLog();
            $auditLog->logAction(
                $_SESSION['user_id'],
                'logout',
                'User logged out',
                $this->getIpAddress()
            );
            error_log("Logout action logged");
        } else {
            error_log("No user ID found in session");
        }
        
        // Unset session variables
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['role_id']);
        unset($_SESSION['login_time']);
        error_log("Session variables unset");
        
        // Clear user object
        $this->user = null;
        error_log("User object cleared");
        
        return true;
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return $this->user !== null;
    }
    
    // Get current user
    public function getUser() {
        return $this->user;
    }
    
    // Get user ID
    public function getUserId() {
        return $this->user ? $this->user['user_id'] : null;
    }
    
    // Get user role ID
    public function getUserRoleId() {
        return $this->user ? $this->user['role_id'] : null;
    }
    
    // Check if user has a specific role
    public function hasRole($roleId) {
        return $this->user && $this->user['role_id'] == $roleId;
    }
    
    // Check if user is administrator
    public function isAdmin() {
        return $this->hasRole(1); // Administrator role_id = 1
    }
    
    // Check if user is faculty
    public function isFaculty() {
        return $this->hasRole(2); // Faculty role_id = 2
    }
    
    // Check if user is lab technician
    public function isLabTechnician() {
        return $this->hasRole(3); // Lab Technician role_id = 3
    }
    
    // Check if user is student assistant
    public function isStudentAssistant() {
        return $this->hasRole(4); // Student Assistant role_id = 4
    }
    
    // Register a new user
    public function register($userData) {
        $userModel = new User();
        
        // Check if username or email already exists
        if ($userModel->usernameExists($userData['username'])) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        if ($userModel->emailExists($userData['email'])) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Register user
        $result = $userModel->register($userData);
        
        if (isset($result['success']) && $result['success']) {
            // Log registration action if user was created successfully
            $auditLog = new AuditLog();
            $auditLog->logAction(
                $result['user_id'] ?? null,
                'registration',
                'User registered',
                $this->getIpAddress()
            );
            
            return ['success' => true, 'user_id' => $result['user_id'] ?? null];
        }
        
        return ['success' => false, 'message' => $result['message'] ?? 'Failed to register user'];
    }
    
    // Get client IP address
    private function getIpAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    
    // Redirect user if not logged in
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }
    
    // Redirect user if not admin
    public function requireAdmin() {
        $this->requireLogin();
        
        if (!$this->isAdmin()) {
            header('Location: ' . APP_URL . '/access-denied.php');
            exit;
        }
    }
    
    // Redirect user if not lab technician
    public function requireLabTechnician() {
        $this->requireLogin();
        
        if (!$this->isLabTechnician() && !$this->isAdmin()) {
            header('Location: ' . APP_URL . '/access-denied.php');
            exit;
        }
    }
    
    // Check if user has permission to manage equipment
    public function canManageEquipment() {
        return $this->isAdmin() || $this->isLabTechnician();
    }
    
    // Check if user has permission to manage rooms
    public function canManageRooms() {
        return $this->isAdmin();
    }
    
    // Check if user has permission to approve borrowing requests
    public function canApproveBorrowing() {
        return $this->isAdmin() || $this->isLabTechnician();
    }
    
    // Check if user has permission to manage maintenance
    public function canManageMaintenance() {
        return $this->isAdmin() || $this->isLabTechnician();
    }
    
    // Check if user has permission to borrow equipment
    public function canBorrowEquipment() {
        return $this->isLoggedIn(); // All logged in users can request to borrow
    }
    
    // Check if user has permission to view reports
    public function canViewReports() {
        return $this->isAdmin() || $this->isLabTechnician();
    }
    
    // Check if user has permission to manage users
    public function canManageUsers() {
        return $this->isAdmin();
    }
    
    // Check if user has permission to view equipment
    public function canViewEquipment() {
        return $this->isLoggedIn(); // All logged in users can view equipment
    }
    
    // Check if user has permission to view borrowings
    public function canViewBorrowings() {
        return $this->isLoggedIn(); // All logged in users can view borrowing requests
    }
    
    // Check if user has permission to view all borrowings
    public function canViewAllBorrowings() {
        return $this->isAdmin() || $this->isLabTechnician();
    }
    
    // Check if user has permission to view rooms
    public function canViewRooms() {
        return $this->isLoggedIn(); // All logged in users can view rooms
    }
    
    // Check if user has permission to view maintenance 
    public function canViewMaintenance() {
        return $this->isLoggedIn(); // All logged in users can view maintenance requests
    }
    
    // Check if user has permission to view all maintenance requests
    public function canViewAllMaintenance() {
        return $this->isAdmin() || $this->isLabTechnician();
    }
    
    // Check if user has permission to report maintenance
    public function canReportMaintenance() {
        return $this->isLoggedIn(); // All logged in users can report maintenance issues
    }
} 