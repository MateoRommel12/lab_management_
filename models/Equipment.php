<?php
require_once __DIR__ . '/BaseModel.php';

class Equipment extends BaseModel {
    protected $table = 'equipment';
    protected $primaryKey = 'equipment_id';
    protected $allowedFields = [
        'name', 'description', 'serial_number', 'model', 'manufacturer',
        'purchase_date', 'warranty_expiry', 'status', 'location',
        'equipment_condition', 'notes', 'last_maintenance_date',
        'category_id', 'acquisition_date', 'cost', 'supplier'
    ];
    
    // Get equipment by ID
    public function getEquipmentById($equipmentId) {
        $query = "SELECT e.*, 
                    c.category_name,
                    r.room_name,
                    r.building,
                    r.room_number
                 FROM {$this->table} e 
                 LEFT JOIN equipment_categories c ON e.category_id = c.category_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE e.equipment_id = :equipmentId";
        
        $result = $this->db->single($query, ['equipmentId' => $equipmentId]);
        
        if ($result) {
            // Map the name field to equipment_name for backward compatibility
            $result['equipment_name'] = $result['name'];
            // Map equipment_condition to condition_status for backward compatibility
            $result['condition_status'] = $result['equipment_condition'];
            // Ensure cost is a number
            $result['cost'] = isset($result['cost']) ? (float)$result['cost'] : null;
        }
        
        return $result;
    }
    
    // Get all equipment with optional filters
    public function getAllEquipment($category = '', $status = '', $room = '', $search = '', $limit = null, $offset = null) {
        $query = "SELECT e.equipment_id, e.name, e.description, e.serial_number, e.model, 
                        e.manufacturer, e.purchase_date, e.warranty_expiry, e.status, 
                        e.location, e.equipment_condition, e.notes, e.last_maintenance_date, 
                        e.category_id, e.created_at, e.updated_at, c.category_name, r.room_name
                 FROM {$this->table} e 
                 LEFT JOIN equipment_categories c ON e.category_id = c.category_id 
                 LEFT JOIN rooms r ON e.location = r.room_id";
        
        $params = [];
        $conditions = [];
        
        // Add category filter
        if (!empty($category)) {
            $conditions[] = "e.category_id = :categoryId";
            $params['categoryId'] = $category;
        }
        
        // Add status filter
        if (!empty($status)) {
            $conditions[] = "e.status = :status";
            $params['status'] = $status;
        }
        
        // Add room filter
        if (!empty($room)) {
            $conditions[] = "e.location = :roomId";
            $params['roomId'] = $room;
        }
        
        // Add search filter
        if (!empty($search)) {
            $conditions[] = "(e.name LIKE :search 
                           OR e.description LIKE :search 
                           OR e.serial_number LIKE :search 
                           OR e.model LIKE :search 
                           OR e.manufacturer LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        // Add conditions to query if any exist
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY e.name";
        
        // Add pagination if limit is provided
        if ($limit !== null) {
            $query .= " LIMIT :limit";
            $params['limit'] = (int)$limit;
            
            if ($offset !== null) {
                $query .= " OFFSET :offset";
                $params['offset'] = (int)$offset;
            }
        }
        
        return $this->db->resultSet($query, $params);
    }
    
    // Get equipment statistics
    public function getEquipmentStatistics() {
        $stats = [];
        
        // Total equipment count
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db->single($query);
        $stats['total'] = $result['total'];
        
        // Equipment by status
        $query = "SELECT status, COUNT(*) as count 
                 FROM {$this->table} 
                 GROUP BY status";
        $result = $this->db->resultSet($query);
        $stats['by_status'] = [];
        foreach ($result as $row) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        
        // Equipment by condition
        $query = "SELECT equipment_condition, COUNT(*) as count 
                 FROM {$this->table} 
                 GROUP BY equipment_condition";
        $result = $this->db->resultSet($query);
        $stats['by_condition'] = [];
        foreach ($result as $row) {
            $stats['by_condition'][$row['equipment_condition']] = $row['count'];
        }
        
        // Equipment by category
        $query = "SELECT c.category_name, COUNT(*) as count 
                 FROM {$this->table} e 
                 JOIN equipment_categories c ON e.category_id = c.category_id 
                 GROUP BY c.category_id, c.category_name";
        $result = $this->db->resultSet($query);
        $stats['by_category'] = [];
        foreach ($result as $row) {
            $stats['by_category'][$row['category_name']] = $row['count'];
        }
        
        // Equipment requiring maintenance (warranty expired or last maintenance > 6 months ago)
        $query = "SELECT COUNT(*) as count 
                 FROM {$this->table} 
                 WHERE warranty_expiry < CURDATE() 
                 OR last_maintenance_date < DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
                 OR last_maintenance_date IS NULL";
        $result = $this->db->single($query);
        $stats['needs_maintenance'] = $result['count'];
        
        return $stats;
    }
    
    // Get equipment with category information
    public function getEquipmentWithCategory($equipmentId) {
        $query = "SELECT e.equipment_id, e.name, e.description, e.serial_number, e.model, 
                        e.manufacturer, e.purchase_date, e.warranty_expiry, e.status, 
                        e.location, e.equipment_condition, e.notes, e.last_maintenance_date, 
                        e.category_id, e.created_at, e.updated_at, c.category_name 
                 FROM {$this->table} e 
                 LEFT JOIN equipment_categories c ON e.category_id = c.category_id 
                 WHERE e.equipment_id = :equipmentId";
        
        return $this->db->single($query, ['equipmentId' => $equipmentId]);
    }
    
    // Get all equipment with their categories
    public function getAllEquipmentWithCategories() {
        $query = "SELECT e.equipment_id, e.name, e.description, e.serial_number, e.model, 
                        e.manufacturer, e.purchase_date, e.warranty_expiry, e.status, 
                        e.location, e.equipment_condition, e.notes, e.last_maintenance_date, 
                        e.category_id, e.created_at, e.updated_at, c.category_name 
                 FROM {$this->table} e 
                 LEFT JOIN equipment_categories c ON e.category_id = c.category_id 
                 ORDER BY e.name";
        
        return $this->db->resultSet($query);
    }
    
    // Get equipment by category
    public function getEquipmentByCategory($categoryId) {
        $query = "SELECT equipment_id, name, description, serial_number, model, 
                        manufacturer, purchase_date, warranty_expiry, status, 
                        location, equipment_condition, notes, last_maintenance_date, 
                        category_id, created_at, updated_at 
                 FROM {$this->table} 
                 WHERE category_id = :categoryId 
                 ORDER BY name";
        return $this->db->resultSet($query, ['categoryId' => $categoryId]);
    }
    
    // Get equipment by status
    public function getEquipmentByStatus($status) {
        $query = "SELECT equipment_id, name, description, serial_number, model, 
                        manufacturer, purchase_date, warranty_expiry, status, 
                        location, equipment_condition, notes, last_maintenance_date, 
                        category_id, created_at, updated_at 
                 FROM {$this->table} 
                 WHERE status = :status 
                 ORDER BY name";
        return $this->db->resultSet($query, ['status' => $status]);
    }
    
    // Get equipment by condition
    public function getEquipmentByCondition($condition) {
        $query = "SELECT equipment_id, name, description, serial_number, model, 
                        manufacturer, purchase_date, warranty_expiry, status, 
                        location, equipment_condition, notes, last_maintenance_date, 
                        category_id, created_at, updated_at 
                 FROM {$this->table} 
                 WHERE equipment_condition = :condition 
                 ORDER BY name";
        return $this->db->resultSet($query, ['condition' => $condition]);
    }
    
    // Get all categories
    public function getAllCategories() {
        $query = "SELECT * FROM equipment_categories ORDER BY category_name";
        return $this->db->resultSet($query);
    }
    
    // Check if serial number exists
    public function serialNumberExists($serialNumber, $excludeEquipmentId = null) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE serial_number = :serialNumber";
        $params = ['serialNumber' => $serialNumber];
        
        if ($excludeEquipmentId) {
            $query .= " AND equipment_id != :excludeEquipmentId";
            $params['excludeEquipmentId'] = $excludeEquipmentId;
        }
        
        $result = $this->db->single($query, $params);
        return $result['count'] > 0;
    }
    
    // Get equipment movement history
    public function getEquipmentMovementHistory($equipmentId) {
        $query = "SELECT m.*, 
                    CONCAT(u.first_name, ' ', u.last_name) as moved_by_name,
                    r1.room_name as from_room,
                    r2.room_name as to_room
                 FROM equipment_movements m 
                 LEFT JOIN users u ON m.moved_by = u.user_id 
                 LEFT JOIN rooms r1 ON m.from_room_id = r1.room_id 
                 LEFT JOIN rooms r2 ON m.to_room_id = r2.room_id 
                 WHERE m.equipment_id = :equipmentId 
                 ORDER BY m.movement_date DESC";
        
        return $this->db->resultSet($query, ['equipmentId' => $equipmentId]);
    }
    
    // Get available equipment for borrowing
    public function getAvailableEquipmentForBorrowing() {
        $query = "SELECT e.*, 
                    e.name as equipment_name,
                    c.category_name,
                    r.room_name,
                    r.building,
                    r.room_number
                 FROM {$this->table} e 
                 LEFT JOIN equipment_categories c ON e.category_id = c.category_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE e.status = 'active' 
                 AND e.equipment_condition NOT IN ('under maintenance', 'disposed')
                 AND NOT EXISTS (
                     SELECT 1 FROM borrowing_requests br 
                     WHERE br.equipment_id = e.equipment_id 
                     AND br.status IN ('approved', 'borrowed')
                 )
                 AND NOT EXISTS (
                     SELECT 1 FROM maintenance_requests mr 
                     WHERE mr.equipment_id = e.equipment_id 
                     AND mr.status IN ('pending', 'in progress')
                 )
                 ORDER BY e.name";
        
        $results = $this->db->resultSet($query);
        
        // Ensure equipment_name is set for each result
        foreach ($results as &$result) {
            $result['equipment_name'] = $result['name'] ?? 'Unknown Equipment';
        }
        
        return $results;
    }
    
    // Update equipment status
    public function updateEquipmentStatus($equipmentId, $status) {
        $query = "UPDATE {$this->table} 
                 SET status = :status,
                     updated_at = NOW()
                 WHERE equipment_id = :equipmentId";
        
        return $this->db->execute($query, [
            'equipmentId' => $equipmentId,
            'status' => $status
        ]) ? true : false;
    }

    // Update equipment status (alias for updateEquipmentStatus for consistency)
    public function updateStatus($equipmentId, $status) {
        return $this->updateEquipmentStatus($equipmentId, $status);
    }
    
    // Get equipment count by room
    public function getEquipmentCountByRoom($roomId) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE location = :roomId";
        $result = $this->db->single($query, ['roomId' => $roomId]);
        return $result ? $result['count'] : 0;
    }

    // Move equipment to a new room
    public function moveEquipment($equipmentId, $toRoomId, $movementData) {
        try {
            // Start transaction
            $this->db->beginTransaction();
            error_log("Starting equipment movement transaction for equipment ID: " . $equipmentId);

            // Update equipment location
            $query = "UPDATE {$this->table} 
                     SET location = :roomId,
                         updated_at = NOW()
                     WHERE equipment_id = :equipmentId";
            
            $result = $this->db->execute($query, [
                'roomId' => $toRoomId,
                'equipmentId' => $equipmentId
            ]);

            if (!$result) {
                error_log("Failed to update equipment location. Equipment ID: " . $equipmentId . ", Room ID: " . $toRoomId);
                throw new Exception("Failed to update equipment location");
            }
            error_log("Successfully updated equipment location");

            // Record the movement
            $movementQuery = "INSERT INTO equipment_movements 
                            (equipment_id, from_room_id, to_room_id, moved_by, movement_date, reason) 
                            VALUES 
                            (:equipment_id, :from_room_id, :to_room_id, :moved_by, :movement_date, :reason)";
            
            // Ensure movement_date is set to now if not provided
            if (!isset($movementData['movement_date'])) {
                $movementData['movement_date'] = date('Y-m-d H:i:s');
            }
            
            error_log("Attempting to record movement with data: " . print_r($movementData, true));
            $movementResult = $this->db->execute($movementQuery, $movementData);

            if (!$movementResult) {
                error_log("Failed to record equipment movement. Movement data: " . print_r($movementData, true));
                throw new Exception("Failed to record equipment movement");
            }
            error_log("Successfully recorded equipment movement");

            // Commit transaction
            $this->db->commit();
            error_log("Successfully committed equipment movement transaction");
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            error_log("Error in moveEquipment: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    // Record equipment movement
    public function recordEquipmentMovement($movementData) {
        $query = "INSERT INTO equipment_movements 
                 (equipment_id, from_room_id, to_room_id, moved_by, movement_date, reason) 
                 VALUES 
                 (:equipment_id, :from_room_id, :to_room_id, :moved_by, :movement_date, :reason)";
        
        // Ensure movement_date is set to now if not provided
        if (!isset($movementData['movement_date'])) {
            $movementData['movement_date'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->execute($query, $movementData) ? true : false;
    }

    // Get recent equipment movements
    public function getRecentMovements($limit = 5) {
        try {
            // First check if we have any movements at all
            $checkQuery = "SELECT COUNT(*) as count FROM equipment_movements";
            $count = $this->db->single($checkQuery);
            error_log("Total movements in database: " . ($count ? $count['count'] : 0));

            // Modified query to be more lenient with JOINs
            $query = "SELECT m.*, 
                        e.name as equipment_name,
                        CONCAT(u.first_name, ' ', u.last_name) as moved_by_name,
                        r1.room_name as from_room,
                        r2.room_name as to_room
                     FROM equipment_movements m 
                     LEFT JOIN equipment e ON m.equipment_id = e.equipment_id
                     LEFT JOIN users u ON m.moved_by = u.user_id 
                     LEFT JOIN rooms r1 ON m.from_room_id = r1.room_id 
                     LEFT JOIN rooms r2 ON m.to_room_id = r2.room_id 
                     ORDER BY m.movement_date DESC 
                     LIMIT :limit";
            
            $result = $this->db->resultSet($query, ['limit' => $limit]);
            error_log("Found " . count($result) . " recent movements");
            
            return $result;
        } catch (Exception $e) {
            error_log("Error in getRecentMovements: " . $e->getMessage());
            return [];
        }
    }

    public function getEquipmentMovements($filters = []) {
        $query = "SELECT m.*, 
                    e.name as equipment_name,
                    CONCAT(u.first_name, ' ', u.last_name) as moved_by_name,
                    r1.room_name as from_room,
                    r2.room_name as to_room
                 FROM equipment_movements m 
                 LEFT JOIN equipment e ON m.equipment_id = e.equipment_id
                 LEFT JOIN users u ON m.moved_by = u.user_id 
                 LEFT JOIN rooms r1 ON m.from_room_id = r1.room_id 
                 LEFT JOIN rooms r2 ON m.to_room_id = r2.room_id 
                 WHERE 1=1";

        $params = [];

        if (!empty($filters['equipment_id'])) {
            $query .= " AND m.equipment_id = :equipment_id";
            $params['equipment_id'] = $filters['equipment_id'];
        }

        if (!empty($filters['room_id'])) {
            $query .= " AND (m.from_room_id = :room_id OR m.to_room_id = :room_id)";
            $params['room_id'] = $filters['room_id'];
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND m.movement_date >= :date_from";
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND m.movement_date <= :date_to";
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $query .= " ORDER BY m.movement_date DESC";

        return $this->db->resultSet($query, $params);
    }

    // Get the last movement for an equipment
    public function getLastMovement($equipmentId) {
        $query = "SELECT m.*, 
                    CONCAT(u.first_name, ' ', u.last_name) as moved_by_name
                 FROM equipment_movements m 
                 LEFT JOIN users u ON m.moved_by = u.user_id 
                 WHERE m.equipment_id = :equipmentId 
                 ORDER BY m.movement_date DESC 
                 LIMIT 1";
        
        return $this->db->single($query, ['equipmentId' => $equipmentId]);
    }

    // Get equipment report by status and category
    public function getEquipmentReport($status = '', $category = '') {
        $query = "SELECT e.*, c.category_name 
                  FROM {$this->table} e 
                  LEFT JOIN equipment_categories c ON e.category_id = c.category_id 
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($status)) {
            $query .= " AND e.status = :status";
            $params['status'] = $status;
        }
        
        if (!empty($category)) {
            $query .= " AND e.category_id = :category";
            $params['category'] = $category;
        }
        
        $query .= " ORDER BY e.name";
        
        return $this->db->resultSet($query, $params);
    }

    // Get inventory report by category
    public function getInventoryReport($category = '') {
        $query = "SELECT e.*, c.category_name 
                  FROM {$this->table} e 
                  LEFT JOIN equipment_categories c ON e.category_id = c.category_id 
                  WHERE e.status = 'active'";
        
        $params = [];
        
        if (!empty($category)) {
            $query .= " AND e.category_id = :category";
            $params['category'] = $category;
        }
        
        $query .= " ORDER BY c.category_name, e.name";
        
        return $this->db->resultSet($query, $params);
    }
} 