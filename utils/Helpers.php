<?php
class Helpers {
    // Format date
    public static function formatDate($date, $format = 'Y-m-d') {
        if (!$date) return '';
        
        $datetime = new DateTime($date);
        return $datetime->format($format);
    }
    
    // Format datetime
    public static function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
        if (!$datetime) return '';
        
        $dt = new DateTime($datetime);
        return $dt->format($format);
    }
    
    // Format currency
    public static function formatCurrency($amount, $symbol = '₱') {
        if (!is_numeric($amount)) return '';
        
        return $symbol . number_format($amount, 2);
    }
    
    // Sanitize input
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::sanitizeInput($value);
            }
            return $input;
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    // Validate email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Generate random string
    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $randomString;
    }
    
    // Get file extension
    public static function getFileExtension($filename) {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }
    
    // Check if file is an image
    public static function isImage($filename) {
        $ext = self::getFileExtension($filename);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        
        return in_array(strtolower($ext), $imageExtensions);
    }
    
    // Calculate date difference in days
    public static function dateDifferenceInDays($date1, $date2) {
        $datetime1 = new DateTime($date1);
        $datetime2 = new DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        
        return $interval->days;
    }
    
    // Get time ago (for displaying relative time)
    public static function timeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $strTime = array("second", "minute", "hour", "day", "month", "year");
        $length = array("60", "60", "24", "30", "12", "10");

        $currentTime = time();
        if ($currentTime >= $timestamp) {
            $diff = $currentTime - $timestamp;
            
            for ($i = 0; $diff >= $length[$i] && $i < count($length) - 1; $i++) {
                $diff = $diff / $length[$i];
            }
            
            $diff = round($diff);
            if ($diff == 1) {
                return $diff . " " . $strTime[$i] . " ago";
            } else {
                return $diff . " " . $strTime[$i] . "s ago";
            }
        }
        
        return "just now";
    }
    
    // Truncate text
    public static function truncateText($text, $length = 100, $ending = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $ending;
    }
    
    // Get status badge HTML
    public static function getStatusBadge($status) {
        $badges = [
            'active' => ['class' => 'bg-success', 'text' => 'Active'],
            'inactive' => ['class' => 'bg-secondary', 'text' => 'Inactive'],
            'maintenance' => ['class' => 'bg-warning', 'text' => 'Under Maintenance'],
            'retired' => ['class' => 'bg-danger', 'text' => 'Retired']
        ];
        
        $status = strtolower($status);
        $badge = $badges[$status] ?? ['class' => 'bg-secondary', 'text' => 'Unknown'];
        
        return sprintf(
            '<span class="badge %s">%s</span>',
            htmlspecialchars($badge['class']),
            htmlspecialchars($badge['text'])
        );
    }
    
    // Generate breadcrumbs HTML
    public static function generateBreadcrumbs($breadcrumbs) {
        $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
        
        foreach ($breadcrumbs as $key => $crumb) {
            if (isset($crumb['url']) && $key < count($breadcrumbs) - 1) {
                $html .= '<li class="breadcrumb-item"><a href="' . $crumb['url'] . '">' . $crumb['label'] . '</a></li>';
            } else {
                $html .= '<li class="breadcrumb-item active" aria-current="page">' . $crumb['label'] . '</li>';
            }
        }
        
        $html .= '</ol></nav>';
        
        return $html;
    }
    
    // Check if string contains a search term
    public static function stringContains($haystack, $needle) {
        return stripos($haystack, $needle) !== false;
    }
    
    // Get pagination HTML
    public static function getPagination($currentPage, $totalPages, $baseUrl) {
        if ($totalPages <= 1) return '';
        
        $html = '<nav aria-label="Page navigation"><ul class="pagination">';
        
        // Previous button
        if ($currentPage > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">&laquo; Previous</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo; Previous</span></li>';
        }
        
        // Page numbers
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        
        if ($startPage > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>';
            if ($startPage > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i == $currentPage) {
                $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
            }
        }
        
        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $totalPages . '">' . $totalPages . '</a></li>';
        }
        
        // Next button
        if ($currentPage < $totalPages) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Next &raquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>';
        }
        
        $html .= '</ul></nav>';
        
        return $html;
    }
    
    // Flash messages handling
    public static function setFlashMessage($type, $message) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    public static function getFlashMessage() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['flash_message'])) {
            $flashMessage = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $flashMessage;
        }
        
        return null;
    }
    
    public static function displayFlashMessage() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['flash_message'])) {
            $type = $_SESSION['flash_message']['type'];
            $message = $_SESSION['flash_message']['message'];
            
            unset($_SESSION['flash_message']);
            
            return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                        ' . $message . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        }
        
        return '';
    }

    // Log user actions
    public static function logAction($action, $userId = null) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Get current user ID if not provided
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        // Get user IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        
        try {
            // Create database connection
            require_once __DIR__ . '/../config/config.php';
            require_once __DIR__ . '/../config/Database.php';
            $db = Database::getInstance();
            
            // Insert log entry
            $query = "INSERT INTO audit_logs (user_id, action_type, action_description, ip_address) 
                     VALUES (:user_id, :action_type, :action_description, :ip_address)";
            
            $params = [
                ':user_id' => $userId,
                ':action_type' => 'User Action',
                ':action_description' => $action,
                ':ip_address' => $ipAddress
            ];
            
            return $db->execute($query, $params);
        } catch (Exception $e) {
            error_log("Error logging action: " . $e->getMessage());
            return false;
        }
    }

    // Redirect with flash message
    public static function redirectWithMessage($url, $message, $type = 'success') {
        self::setFlashMessage($type, $message);
        echo "<script>window.location.href = '" . $url . "';</script>";
        exit;
    }

    // Get condition badge HTML
    public static function getConditionBadge($condition) {
        $badges = [
            'new' => ['class' => 'bg-success', 'text' => 'New'],
            'good' => ['class' => 'bg-info', 'text' => 'Good'],
            'fair' => ['class' => 'bg-warning', 'text' => 'Fair'],
            'poor' => ['class' => 'bg-danger', 'text' => 'Poor'],
            'under maintenance' => ['class' => 'bg-primary', 'text' => 'Under Maintenance'],
            'disposed' => ['class' => 'bg-secondary', 'text' => 'Disposed']
        ];
        
        $condition = strtolower($condition);
        $badge = $badges[$condition] ?? ['class' => 'bg-secondary', 'text' => 'Unknown'];
        
        return sprintf(
            '<span class="badge %s">%s</span>',
            htmlspecialchars($badge['class']),
            htmlspecialchars($badge['text'])
        );
    }
    
    // Generate correct URL for the application
    public static function url($path) {
        // Get the base URL
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        
        // Remove leading slash if present
        if (substr($path, 0, 1) === '/') {
            $path = substr($path, 1);
        }
        
        // Simple solution for lab_management_ folder in XAMPP
        // For localhost development, assuming the project is in htdocs/lab_management_
        return $base_url . '/lab_management_/' . $path;
    }
} 