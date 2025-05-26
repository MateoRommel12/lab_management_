<?php
require_once __DIR__ . '/BaseModel.php';

class Borrowing extends BaseModel {
    protected $table = 'borrowing_requests';
    protected $primaryKey = 'request_id';
    protected $allowedFields = [
        'borrower_id', 'equipment_id', 'borrow_date', 'expected_return_date',
        'purpose', 'status', 'approved_by', 'approval_date', 'actual_return_date',
        'condition_before', 'condition_after'
    ];
    
    // Get borrowing request with related information
    public function getBorrowingWithDetails($requestId) {
        $query = "SELECT br.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as borrower_name,
                    u1.email as borrower_email,
                    CONCAT(u2.first_name, ' ', u2.last_name) as approver_name,
                    e.name as equipment_name,
                    e.serial_number,
                    e.model,
                    e.manufacturer,
                    c.category_name,
                    r.room_name,
                    r.building,
                    r.room_number
                 FROM {$this->table} br 
                 JOIN users u1 ON br.borrower_id = u1.user_id 
                 LEFT JOIN users u2 ON br.approved_by = u2.user_id 
                 JOIN equipment e ON br.equipment_id = e.equipment_id 
                 LEFT JOIN equipment_categories c ON e.category_id = c.category_id
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE br.request_id = :requestId";
        
        $result = $this->db->single($query, ['requestId' => $requestId]);
        
        // Ensure equipment_name is set
        if ($result) {
            $result['equipment_name'] = $result['equipment_name'] ?? 'Unknown Equipment';
        }
        
        return $result;
    }
    
    // Get all borrowing requests with related information
    public function getAllBorrowingRequests() {
        $query = "SELECT br.*, 
                    e.name,
                    e.serial_number,
                    c.category_name,
                    CONCAT(borrower.first_name, ' ', borrower.last_name) as borrower_name,
                    CONCAT(approver.first_name, ' ', approver.last_name) as approver_name,
                    r.role_name as borrower_role,
                    rm.room_name,
                    rm.building,
                    rm.room_number
                 FROM borrowing_requests br
                 LEFT JOIN equipment e ON br.equipment_id = e.equipment_id
                 LEFT JOIN equipment_categories c ON e.category_id = c.category_id
                 LEFT JOIN users borrower ON br.borrower_id = borrower.user_id
                 LEFT JOIN users approver ON br.approved_by = approver.user_id
                 LEFT JOIN roles r ON borrower.role_id = r.role_id
                 LEFT JOIN rooms rm ON e.location = rm.room_id
                 ORDER BY br.request_date DESC";
        
        return $this->db->resultSet($query);
    }
    
    // Get pending borrowing requests
    public function getPendingRequests() {
        $query = "SELECT br.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as user_name,
                    e.name as equipment_name,
                    e.serial_number,
                    r.room_name
                 FROM {$this->table} br 
                 JOIN users u1 ON br.borrower_id = u1.user_id 
                 JOIN equipment e ON br.equipment_id = e.equipment_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE br.status = 'pending'
                 ORDER BY br.request_date ASC";
        
        return $this->db->resultSet($query);
    }
    
    // Get borrowing requests by user
    public function getBorrowingByUser($userId) {
        $query = "SELECT br.*, 
                    e.name,
                    e.serial_number,
                    r.room_name,
                    CONCAT(u.first_name, ' ', u.last_name) as approver_name
                 FROM {$this->table} br 
                 JOIN equipment e ON br.equipment_id = e.equipment_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 LEFT JOIN users u ON br.approved_by = u.user_id 
                 WHERE br.borrower_id = :userId
                 ORDER BY br.request_date DESC";
        
        return $this->db->resultSet($query, ['userId' => $userId]);
    }
    
    // Get borrowing requests by equipment
    public function getBorrowingByEquipment($equipmentId) {
        $query = "SELECT br.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as borrower_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as approver_name
                 FROM {$this->table} br 
                 JOIN users u1 ON br.borrower_id = u1.user_id 
                 LEFT JOIN users u2 ON br.approved_by = u2.user_id 
                 WHERE br.equipment_id = :equipmentId
                 ORDER BY br.request_date DESC";
        
        return $this->db->resultSet($query, ['equipmentId' => $equipmentId]);
    }
    
    // Get active borrowings (approved or borrowed status)
    public function getActiveBorrowings() {
        $query = "SELECT br.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as borrower_name,
                    e.name,
                    e.serial_number,
                    r.room_name
                 FROM {$this->table} br 
                 JOIN users u1 ON br.borrower_id = u1.user_id 
                 JOIN equipment e ON br.equipment_id = e.equipment_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE br.status IN ('approved', 'borrowed')
                 ORDER BY br.borrow_date ASC";
        
        return $this->db->resultSet($query);
    }
    
    // Get overdue borrowings
    public function getOverdueBorrowings() {
        $query = "SELECT br.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as borrower_name,
                    u1.email as borrower_email,
                    e.name,
                    e.serial_number,
                    r.room_name
                 FROM {$this->table} br 
                 JOIN users u1 ON br.borrower_id = u1.user_id 
                 JOIN equipment e ON br.equipment_id = e.equipment_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE (br.status = 'borrowed' AND br.expected_return_date < NOW())
                    OR br.status = 'overdue'
                 ORDER BY br.expected_return_date ASC";
        
        return $this->db->resultSet($query);
    }
    
    // Approve borrowing request
    public function approveBorrowing($requestId, $approverId) {
        $query = "UPDATE {$this->table} 
                 SET status = 'approved', 
                     approved_by = :approverId, 
                     approval_date = NOW() 
                 WHERE request_id = :requestId AND status = 'pending'";
        
        return $this->db->execute($query, [
            'approverId' => $approverId,
            'requestId' => $requestId
        ]) ? true : false;
    }
    
    // Reject borrowing request
    public function rejectBorrowing($requestId, $approverId) {
        $query = "UPDATE {$this->table} 
                 SET status = 'rejected', 
                     approved_by = :approverId, 
                     approval_date = NOW() 
                 WHERE request_id = :requestId AND status = 'pending'";
        
        return $this->db->execute($query, [
            'approverId' => $approverId,
            'requestId' => $requestId
        ]) ? true : false;
    }
    
    // Mark as borrowed
    public function markAsBorrowed($requestId, $conditionBefore) {
        $query = "UPDATE {$this->table} 
                 SET status = 'borrowed', 
                     condition_before = :conditionBefore 
                 WHERE request_id = :requestId AND status = 'approved'";
        
        return $this->db->execute($query, [
            'conditionBefore' => $conditionBefore,
            'requestId' => $requestId
        ]) ? true : false;
    }
    
    // Mark as returned
    public function markAsReturned($requestId, $conditionAfter) {
        $query = "UPDATE {$this->table} 
                 SET status = 'returned', 
                     actual_return_date = NOW(),
                     condition_after = :conditionAfter 
                 WHERE request_id = :requestId AND status IN ('borrowed', 'overdue')";
        
        return $this->db->execute($query, [
            'conditionAfter' => $conditionAfter,
            'requestId' => $requestId
        ]) ? true : false;
    }
    
    // Mark as overdue
    public function markAsOverdue($requestId) {
        $query = "UPDATE {$this->table} 
                 SET status = 'overdue'
                 WHERE request_id = :requestId AND status = 'borrowed' 
                 AND expected_return_date < NOW()";
        
        return $this->db->execute($query, [
            'requestId' => $requestId
        ]) ? true : false;
    }
    
    // Update overdue status for all borrowings
    public function updateOverdueStatus() {
        $query = "UPDATE {$this->table} 
                 SET status = 'overdue'
                 WHERE status = 'borrowed' AND expected_return_date < NOW()";
        
        return $this->db->execute($query) ? true : false;
    }
    
    // Check if equipment is available for borrowing
    public function isEquipmentAvailable($equipmentId, $borrowDate, $returnDate, $excludeRequestId = null) {
        $query = "SELECT COUNT(*) as count 
                 FROM {$this->table} 
                 WHERE equipment_id = :equipmentId 
                 AND status IN ('approved', 'borrowed') 
                 AND (
                     (borrow_date <= :borrowDate AND expected_return_date >= :borrowDate) OR
                     (borrow_date <= :returnDate AND expected_return_date >= :returnDate) OR
                     (borrow_date >= :borrowDate AND expected_return_date <= :returnDate)
                 )";
        
        $params = [
            'equipmentId' => $equipmentId,
            'borrowDate' => $borrowDate,
            'returnDate' => $returnDate
        ];
        
        if ($excludeRequestId) {
            $query .= " AND request_id != :excludeRequestId";
            $params['excludeRequestId'] = $excludeRequestId;
        }
        
        $result = $this->db->single($query, $params);
        return $result['count'] == 0;
    }
    
    // Check if equipment is currently borrowed
    public function isEquipmentCurrentlyBorrowed($equipmentId) {
        $query = "SELECT COUNT(*) as count 
                 FROM {$this->table} 
                 WHERE equipment_id = :equipmentId 
                 AND status IN ('approved', 'borrowed')";
        
        $result = $this->db->single($query, ['equipmentId' => $equipmentId]);
        return $result['count'] > 0;
    }
    
    // Get borrowing statistics
    public function getBorrowingStatistics() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed_count,
                    SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned_count,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count
                  FROM {$this->table}";
        
        return $this->db->single($query);
    }
    
    // Add a new borrowing request
    public function addBorrowingRequest($borrowRequest) {
        // Set request date to current timestamp
        $borrowRequest['request_date'] = date('Y-m-d H:i:s');
        
        // Create borrowing request
        return $this->create($borrowRequest);
    }
    
    // Get borrowing history for a specific equipment
    public function getBorrowingHistoryByEquipment($equipmentId) {
        $query = "SELECT br.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as borrower_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as approver_name,
                    e.name as equipment_name
                 FROM {$this->table} br 
                 JOIN users u1 ON br.borrower_id = u1.user_id 
                 LEFT JOIN users u2 ON br.approved_by = u2.user_id 
                 JOIN equipment e ON br.equipment_id = e.equipment_id 
                 WHERE br.equipment_id = :equipmentId 
                 ORDER BY br.request_date DESC";
        
        return $this->db->resultSet($query, ['equipmentId' => $equipmentId]);
    }
    
    // Get active borrowings for a specific equipment
    public function getActiveBorrowing($equipmentId) {
        $query = "SELECT b.*, u.username, u.full_name 
                 FROM {$this->table} b 
                 LEFT JOIN users u ON b.user_id = u.user_id 
                 WHERE b.equipment_id = :equipmentId 
                 AND b.status = 'active' 
                 LIMIT 1";
        
        return $this->db->single($query, ['equipmentId' => $equipmentId]);
    }
    
    // Add new borrowing record
    public function addBorrowing($data) {
        return $this->db->insert($this->table, $data);
    }
    
    // Update borrowing record
    public function updateBorrowing($borrowingId, $data) {
        return $this->db->update($this->table, $data, ['borrowing_id' => $borrowingId]);
    }
    
    // Get borrowing by ID
    public function getBorrowingById($borrowingId) {
        $query = "SELECT br.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as borrower_name,
                    e.name as equipment_name,
                    e.serial_number
                 FROM {$this->table} br 
                 JOIN users u1 ON br.borrower_id = u1.user_id 
                 JOIN equipment e ON br.equipment_id = e.equipment_id 
                 WHERE br.request_id = :borrowingId";
        
        return $this->db->single($query, ['borrowingId' => $borrowingId]);
    }
    
    // Get all borrowings with optional filters
    public function getAllBorrowings($status = '', $userId = '', $search = '') {
        $query = "SELECT b.*, u.username, u.full_name, e.name as equipment_name 
                 FROM {$this->table} b 
                 LEFT JOIN users u ON b.user_id = u.user_id 
                 LEFT JOIN equipment e ON b.equipment_id = e.equipment_id";
        
        $params = [];
        $conditions = [];
        
        if (!empty($status)) {
            $conditions[] = "b.status = :status";
            $params['status'] = $status;
        }
        
        if (!empty($userId)) {
            $conditions[] = "b.user_id = :userId";
            $params['userId'] = $userId;
        }
        
        if (!empty($search)) {
            $conditions[] = "(e.name LIKE :search 
                           OR u.username LIKE :search 
                           OR u.full_name LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY b.borrow_date DESC";
        
        return $this->db->resultSet($query, $params);
    }
    
    // Get current borrowing for a specific equipment
    public function getCurrentBorrowingByEquipment($equipmentId) {
        $query = "SELECT br.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as borrower_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as approver_name
                 FROM {$this->table} br 
                 JOIN users u1 ON br.borrower_id = u1.user_id 
                 LEFT JOIN users u2 ON br.approved_by = u2.user_id 
                 WHERE br.equipment_id = :equipmentId 
                 AND br.status IN ('approved', 'borrowed')
                 ORDER BY br.borrow_date DESC 
                 LIMIT 1";
        
        return $this->db->single($query, ['equipmentId' => $equipmentId]);
    }
    
    // Get borrowings by user and status
    public function getBorrowingsByUserAndStatus($userId, $status) {
        $query = "SELECT br.*, 
                    e.name as equipment_name,
                    e.serial_number,
                    c.category_name
                FROM {$this->table} br 
                JOIN equipment e ON br.equipment_id = e.equipment_id 
                LEFT JOIN equipment_categories c ON e.category_id = c.category_id
                WHERE br.borrower_id = :userId
                AND br.status = :status
                ORDER BY br.request_date DESC";
        
        return $this->db->resultSet($query, [
            'userId' => $userId,
            'status' => $status
        ]);
    }
    
    // Get borrowings by user (alias for getBorrowingByUser for consistency)
    public function getBorrowingsByUser($userId) {
        $query = "SELECT br.*, 
                    e.name as equipment_name,
                    e.serial_number,
                    c.category_name
                FROM {$this->table} br 
                JOIN equipment e ON br.equipment_id = e.equipment_id 
                LEFT JOIN equipment_categories c ON e.category_id = c.category_id
                WHERE br.borrower_id = :userId
                ORDER BY br.request_date DESC";
        
        return $this->db->resultSet($query, ['userId' => $userId]);
    }
    
    // Return equipment
    public function returnEquipment($requestId) {
        try {
            error_log("Starting returnEquipment for request ID: " . $requestId);
            
            // First get the borrowing request to verify it exists and is in the correct status
            $query = "SELECT * FROM {$this->table} WHERE request_id = :requestId";
            $borrowing = $this->db->single($query, ['requestId' => $requestId]);
            
            if (!$borrowing) {
                error_log("Borrowing request not found for ID: " . $requestId);
                throw new Exception("Borrowing request not found");
            }

            error_log("Current borrowing status: " . $borrowing['status']);

            if ($borrowing['status'] !== 'approved' && $borrowing['status'] !== 'borrowed') {
                error_log("Invalid status for return: " . $borrowing['status'] . " for request ID: " . $requestId);
                throw new Exception("This item cannot be returned as it is not currently borrowed");
            }

            // Update the borrowing request status
            $query = "UPDATE {$this->table} 
                     SET status = 'returned',
                         actual_return_date = NOW()
                     WHERE request_id = :requestId 
                     AND status IN ('approved', 'borrowed')";
            
            $result = $this->db->execute($query, ['requestId' => $requestId]);
            
            if (!$result) {
                error_log("Failed to update borrowing status for request ID: " . $requestId);
                throw new Exception("Failed to update borrowing status");
            }

            error_log("Successfully returned equipment for request ID: " . $requestId);
            return true;
        } catch (Exception $e) {
            error_log("Error in returnEquipment: " . $e->getMessage());
            throw $e;
        }
    }

    // Delete borrowing request
    public function deleteBorrowing($requestId) {
        try {
            // First check if the borrowing exists and can be deleted
            $borrowing = $this->getBorrowingById($requestId);
            if (!$borrowing) {
                throw new Exception("Borrowing request not found");
            }

            // Check if the request can be deleted (allow pending, rejected, and returned requests)
            if (!in_array($borrowing['status'], ['pending', 'rejected', 'returned'])) {
                throw new Exception("Only pending, rejected, or returned requests can be deleted");
            }

            // Use the BaseModel's delete method
            return $this->delete($requestId);
        } catch (Exception $e) {
            error_log("Error in deleteBorrowing: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Get borrowing report for specified date range
    public function getBorrowingReport($startDate = '', $endDate = '') {
        $query = "SELECT br.*, 
                    e.name AS equipment_name, 
                    e.serial_number,
                    c.category_name,
                    CONCAT(u1.first_name, ' ', u1.last_name) AS borrower_name,
                    r.role_name AS role
                FROM {$this->table} br
                JOIN equipment e ON br.equipment_id = e.equipment_id
                LEFT JOIN equipment_categories c ON e.category_id = c.category_id
                JOIN users u1 ON br.borrower_id = u1.user_id
                LEFT JOIN roles r ON u1.role_id = r.role_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($startDate)) {
            $query .= " AND (br.borrow_date >= :startDate1 OR br.request_date >= :startDate2)";
            $params['startDate1'] = $startDate;
            $params['startDate2'] = $startDate;
        }
        
        if (!empty($endDate)) {
            $query .= " AND (br.expected_return_date <= :endDate1 OR br.request_date <= :endDate2)";
            $params['endDate1'] = $endDate;
            $params['endDate2'] = $endDate;
        }
        
        $query .= " ORDER BY br.request_date DESC";
        
        return $this->db->resultSet($query, $params);
    }
} 