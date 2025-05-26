<?php
require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    protected $allowedFields = [
        'username', 'password', 'email', 'first_name', 'last_name', 
        'role_id', 'status'
    ];
    
    // Authenticate user
    public function authenticate($username, $password) {
        $query = "SELECT * FROM {$this->table} WHERE username = :username AND status = 'active'";
        $user = $this->db->single($query, ['username' => $username]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Remove password before returning user data
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    // Register new user
    public function register($userData) {
        try {
            // Hash password
            if (isset($userData['password'])) {
                $userData['password'] = password_hash($userData['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
            }
            
            $userId = $this->create($userData);
            
            if ($userId) {
                return [
                    'success' => true,
                    'message' => 'User registered successfully',
                    'user_id' => $userId
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to register user'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error registering user: ' . $e->getMessage()
            ];
        }
    }
    
    // Get user with role information
    public function getUserWithRole($userId) {
        $query = "SELECT u.*, r.role_name, r.description as role_description 
                 FROM {$this->table} u 
                 JOIN roles r ON u.role_id = r.role_id 
                 WHERE u.user_id = :userId";
        
        return $this->db->single($query, ['userId' => $userId]);
    }
    
    // Get all users with their roles
    public function getAllUsers() {
        $query = "SELECT u.*, 
                    r.role_name,
                    CONCAT(u.first_name, ' ', u.last_name) as full_name
                 FROM users u
                 LEFT JOIN roles r ON u.role_id = r.role_id
                 ORDER BY u.first_name, u.last_name";
        
        return $this->db->resultSet($query);
    }
    
    // Update user password
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        
        $query = "UPDATE {$this->table} SET password = :password WHERE user_id = :userId";
        
        return $this->db->execute($query, [
            'password' => $hashedPassword,
            'userId' => $userId
        ]) ? true : false;
    }
    
    // Check if username exists
    public function usernameExists($username, $excludeUserId = null) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = :username";
        $params = ['username' => $username];
        
        if ($excludeUserId) {
            $query .= " AND user_id != :excludeUserId";
            $params['excludeUserId'] = $excludeUserId;
        }
        
        $result = $this->db->single($query, $params);
        return $result['count'] > 0;
    }
    
    // Check if email exists
    public function emailExists($email, $excludeUserId = null) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = :email";
        $params = ['email' => $email];
        
        if ($excludeUserId) {
            $query .= " AND user_id != :excludeUserId";
            $params['excludeUserId'] = $excludeUserId;
        }
        
        $result = $this->db->single($query, $params);
        return $result['count'] > 0;
    }
    
    // Get users by role
    public function getUsersByRole($roleId) {
        return $this->findBy('role_id', $roleId);
    }
    
    // Override update to hash password if it's being updated
    public function update($id, $data) {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        } else {
            // Don't update password if empty
            unset($data['password']);
        }
        
        return parent::update($id, $data);
    }

    // Get all roles
    public function getAllRoles() {
        $query = "SELECT * FROM roles ORDER BY role_name";
        return $this->db->resultSet($query);
    }

    // Get user statistics
    public function getUserStatistics() {
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'by_role' => []
        ];

        // Get total users count
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db->single($query);
        $stats['total'] = $result['total'];

        // Get active/inactive counts
        $query = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
        $results = $this->db->resultSet($query);
        foreach ($results as $row) {
            $stats[$row['status']] = $row['count'];
        }

        // Get counts by role
        $query = "SELECT r.role_name, COUNT(u.user_id) as count 
                 FROM {$this->table} u 
                 JOIN roles r ON u.role_id = r.role_id 
                 GROUP BY r.role_id, r.role_name";
        $results = $this->db->resultSet($query);
        foreach ($results as $row) {
            $stats['by_role'][$row['role_name']] = $row['count'];
        }

        return $stats;
    }

    // Check if user exists by username (for login attempts)
    public function getUserByUsername($username) {
        $query = "SELECT user_id, username, status FROM {$this->table} WHERE username = :username";
        return $this->db->single($query, ['username' => $username]);
    }

    // Get all technicians
    public function getTechnicians() {
        $query = "SELECT u.* 
                 FROM {$this->table} u 
                 JOIN roles r ON u.role_id = r.role_id 
                 WHERE r.role_name = 'Lab Technician' 
                 AND u.status = 'active' 
                 ORDER BY u.first_name, u.last_name";
        
        return $this->db->resultSet($query);
    }

    // Get user by email
    public function getUserByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE email = :email AND status = 'active'";
        return $this->db->single($query, ['email' => $email]);
    }

    // Save reset token
    public function saveResetToken($userId, $token, $expiry) {
        // First, invalidate any existing tokens for this user
        $this->invalidateResetTokens($userId);
        
        // Insert new token
        $query = "INSERT INTO password_resets (user_id, token, expiry) VALUES (:userId, :token, :expiry)";
        return $this->db->execute($query, [
            'userId' => $userId,
            'token' => $token,
            'expiry' => $expiry
        ]);
    }

    // Validate reset token
    public function validateResetToken($token) {
        $query = "SELECT * FROM password_resets WHERE token = :token AND used = 0 AND expiry > NOW()";
        return $this->db->single($query, ['token' => $token]);
    }

    // Invalidate reset token
    public function invalidateResetToken($token) {
        $query = "UPDATE password_resets SET used = 1 WHERE token = :token";
        return $this->db->execute($query, ['token' => $token]);
    }

    // Invalidate all reset tokens for a user
    public function invalidateResetTokens($userId) {
        $query = "UPDATE password_resets SET used = 1 WHERE user_id = :userId AND used = 0";
        return $this->db->execute($query, ['userId' => $userId]);
    }
} 