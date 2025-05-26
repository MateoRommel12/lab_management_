<?php
require_once __DIR__ . '/BaseModel.php';

class Room extends BaseModel {
    protected $table = 'rooms';
    protected $primaryKey = 'room_id';
    protected $allowedFields = [
        'room_name', 'building', 'floor', 'room_number', 
        'capacity', 'lab_technician_id', 'status'
    ];
    
    // Get room with assigned technician details
    public function getRoomWithTechnician($roomId) {
        $query = "SELECT r.*, u.first_name, u.last_name, u.email 
                 FROM {$this->table} r 
                 LEFT JOIN users u ON r.lab_technician_id = u.user_id 
                 WHERE r.room_id = :roomId";
        
        return $this->db->single($query, ['roomId' => $roomId]);
    }
    
    // Get all rooms
    public function getAllRooms() {
        $query = "SELECT * FROM {$this->table} ORDER BY building, floor, room_number";
        return $this->db->resultSet($query);
    }
    
    // Get all rooms with their assigned technicians
    public function getAllRoomsWithTechnicians() {
        $query = "SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as technician_name 
                 FROM {$this->table} r 
                 LEFT JOIN users u ON r.lab_technician_id = u.user_id 
                 ORDER BY r.building, r.floor, r.room_number";
        
        return $this->db->resultSet($query);
    }
    
    // Get rooms by building
    public function getRoomsByBuilding($building) {
        return $this->findBy('building', $building);
    }
    
    // Get rooms by status
    public function getRoomsByStatus($status) {
        return $this->findBy('status', $status);
    }
    
    // Get rooms by technician
    public function getRoomsByTechnician($technicianId) {
        return $this->findBy('lab_technician_id', $technicianId);
    }
    
    // Get equipment count in room
    public function getEquipmentCount($roomId) {
        $query = "SELECT COUNT(*) as count FROM equipment WHERE room_id = :roomId";
        $result = $this->db->single($query, ['roomId' => $roomId]);
        return $result ? $result['count'] : 0;
    }
    
    // Get equipment in room
    public function getEquipment($roomId) {
        $query = "SELECT e.*, c.category_name 
                 FROM equipment e 
                 JOIN equipment_categories c ON e.category_id = c.category_id 
                 WHERE e.room_id = :roomId 
                 ORDER BY e.name";
        
        return $this->db->resultSet($query, ['roomId' => $roomId]);
    }
    
    // Update room status
    public function updateStatus($roomId, $status) {
        $query = "UPDATE {$this->table} SET status = :status WHERE room_id = :roomId";
        
        return $this->db->execute($query, [
            'status' => $status,
            'roomId' => $roomId
        ]) ? true : false;
    }
    
    // Update room technician
    public function updateTechnician($roomId, $technicianId) {
        $query = "UPDATE {$this->table} SET lab_technician_id = :technicianId WHERE room_id = :roomId";
        
        return $this->db->execute($query, [
            'technicianId' => $technicianId,
            'roomId' => $roomId
        ]) ? true : false;
    }
    
    // Search rooms by keyword
    public function searchRooms($keyword) {
        $keyword = "%{$keyword}%";
        
        $query = "SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as technician_name 
                 FROM {$this->table} r 
                 LEFT JOIN users u ON r.lab_technician_id = u.user_id 
                 WHERE r.room_name LIKE :keyword 
                 OR r.building LIKE :keyword 
                 OR r.room_number LIKE :keyword 
                 ORDER BY r.building, r.floor, r.room_number";
        
        return $this->db->resultSet($query, ['keyword' => $keyword]);
    }
    
    // Get list of all buildings
    public function getAllBuildings() {
        $query = "SELECT DISTINCT building FROM {$this->table} ORDER BY building";
        return $this->db->resultSet($query);
    }
    
    // Get room statistics
    public function getRoomStatistics() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
                    SUM(CASE WHEN status = 'under maintenance' THEN 1 ELSE 0 END) as maintenance_count,
                    COUNT(DISTINCT building) as building_count
                  FROM {$this->table}";
        
        return $this->db->single($query);
    }

    // Check if room number exists
    public function roomNumberExists($roomNumber, $excludeId = null) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE room_number = :roomNumber";
        $params = ['roomNumber' => $roomNumber];
        
        if ($excludeId !== null) {
            $query .= " AND room_id != :excludeId";
            $params['excludeId'] = $excludeId;
        }
        
        $result = $this->db->single($query, $params);
        return $result && $result['count'] > 0;
    }

    // Get room usage report with additional equipment count
    public function getRoomUsageReport() {
        $query = "SELECT r.*, 
                    CONCAT(u.first_name, ' ', u.last_name) as technician_name,
                    (SELECT COUNT(*) FROM equipment WHERE room_id = r.room_id) as equipment_count
                 FROM {$this->table} r 
                 LEFT JOIN users u ON r.lab_technician_id = u.user_id 
                 ORDER BY r.building, r.floor, r.room_number";
        
        return $this->db->resultSet($query);
    }
} 