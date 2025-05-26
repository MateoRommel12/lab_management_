<?php
require_once __DIR__ . '/BaseModel.php';

class AuditLog extends BaseModel {
    protected $table = 'audit_logs';
    protected $primaryKey = 'log_id';
    protected $allowedFields = [
        'user_id', 'action_type', 'action_description', 'ip_address'
    ];
    
    // Log an action
    public function logAction($userId, $actionType, $actionDescription, $ipAddress) {
        // If userId is null (e.g., for failed login attempts), set it to NULL in the database
        // This will only work if the foreign key constraint allows NULL values
        
        $data = [
            'user_id' => $userId,
            'action_type' => $actionType,
            'action_description' => $actionDescription,
            'ip_address' => $ipAddress
        ];
        
        return $this->create($data);
    }
    
    // Log an action without requiring a user ID (for failed logins, etc.)
    public function logActionNoUser($actionType, $actionDescription, $ipAddress) {
        try {
            $query = "INSERT INTO {$this->table} (action_type, action_description, ip_address, action_date) 
                     VALUES (:action_type, :action_description, :ip_address, NOW())";
            
            return $this->db->execute($query, [
                'action_type' => $actionType,
                'action_description' => $actionDescription,
                'ip_address' => $ipAddress
            ]);
        } catch (Exception $e) {
            // Silently fail - logging should never prevent the application from functioning
            return false;
        }
    }
    
    // Get all logs with user information
    public function getAllLogsWithUsers() {
        $query = "SELECT l.*, u.username, u.first_name, u.last_name 
                 FROM {$this->table} l 
                 LEFT JOIN users u ON l.user_id = u.user_id 
                 ORDER BY l.action_date DESC";
        
        return $this->db->resultSet($query);
    }
    
    // Get logs by user
    public function getLogsByUser($userId) {
        $query = "SELECT l.*, u.username, u.first_name, u.last_name 
                 FROM {$this->table} l 
                 LEFT JOIN users u ON l.user_id = u.user_id 
                 WHERE l.user_id = :userId
                 ORDER BY l.action_date DESC";
        
        return $this->db->resultSet($query, ['userId' => $userId]);
    }
    
    // Get logs by action type
    public function getLogsByActionType($actionType) {
        $query = "SELECT l.*, u.username, u.first_name, u.last_name 
                 FROM {$this->table} l 
                 LEFT JOIN users u ON l.user_id = u.user_id 
                 WHERE l.action_type = :actionType
                 ORDER BY l.action_date DESC";
        
        return $this->db->resultSet($query, ['actionType' => $actionType]);
    }
    
    // Get logs by date range
    public function getLogsByDateRange($startDate, $endDate) {
        $query = "SELECT l.*, u.username, u.first_name, u.last_name 
                 FROM {$this->table} l 
                 LEFT JOIN users u ON l.user_id = u.user_id 
                 WHERE l.action_date >= :startDate 
                   AND l.action_date <= :endDate
                 ORDER BY l.action_date DESC";
        
        return $this->db->resultSet($query, [
            'startDate' => $startDate . ' 00:00:00',
            'endDate' => $endDate . ' 23:59:59'
        ]);
    }
    
    // Get distinct action types for filtering
    public function getDistinctActionTypes() {
        $query = "SELECT DISTINCT action_type FROM {$this->table} ORDER BY action_type";
        
        return $this->db->resultSet($query);
    }
    
    // Get filtered logs based on criteria
    public function getFilteredLogs($userId = null, $actionType = null, $startDate = null, $endDate = null) {
        $query = "SELECT l.*, u.username, u.first_name, u.last_name 
                 FROM {$this->table} l 
                 LEFT JOIN users u ON l.user_id = u.user_id 
                 WHERE 1=1";
        
        $params = [];
        
        if ($userId) {
            $query .= " AND l.user_id = :userId";
            $params['userId'] = $userId;
        }
        
        if ($actionType) {
            $query .= " AND l.action_type = :actionType";
            $params['actionType'] = $actionType;
        }
        
        if ($startDate) {
            $query .= " AND l.action_date >= :startDate";
            $params['startDate'] = $startDate . ' 00:00:00';
        }
        
        if ($endDate) {
            $query .= " AND l.action_date <= :endDate";
            $params['endDate'] = $endDate . ' 23:59:59';
        }
        
        $query .= " ORDER BY l.action_date DESC";
        
        return $this->db->resultSet($query, $params);
    }
    
    // Get user login history
    public function getUserLoginHistory($userId) {
        $query = "SELECT l.* 
                 FROM {$this->table} l 
                 WHERE l.user_id = :userId 
                   AND l.action_type IN ('login', 'logout')
                 ORDER BY l.action_date DESC";
        
        return $this->db->resultSet($query, ['userId' => $userId]);
    }

    // Get recent logs with user information
    public function getRecentLogs($limit = 10) {
        $query = "SELECT l.*, u.username, u.first_name, u.last_name 
                 FROM {$this->table} l 
                 LEFT JOIN users u ON l.user_id = u.user_id 
                 ORDER BY l.action_date DESC 
                 LIMIT :limit";
        
        return $this->db->resultSet($query, ['limit' => $limit]);
    }
} 