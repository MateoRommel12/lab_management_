<?php
require_once __DIR__ . '/BaseModel.php';

class Maintenance extends BaseModel {
    protected $table = 'maintenance_requests';
    protected $primaryKey = 'maintenance_id';
    protected $allowedFields = [
        'equipment_id', 'reported_by', 'issue_description', 'technician_assigned',
        'status', 'start_date', 'completion_date', 'resolution_notes'
    ];
    
    // Get maintenance request with related information
    public function getMaintenanceWithDetails($maintenanceId) {
        $query = "SELECT mr.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as reporter_name,
                    u1.email as reporter_email,
                    CONCAT(u2.first_name, ' ', u2.last_name) as technician_name,
                    u2.email as technician_email,
                    e.name, e.serial_number, e.model,
                    r.room_name, r.building, r.room_number
                 FROM {$this->table} mr 
                 JOIN users u1 ON mr.reported_by = u1.user_id 
                 LEFT JOIN users u2 ON mr.technician_assigned = u2.user_id 
                 JOIN equipment e ON mr.equipment_id = e.equipment_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE mr.maintenance_id = :maintenanceId";
        
        return $this->db->single($query, ['maintenanceId' => $maintenanceId]);
    }
    
    // Get all maintenance requests with related information
    public function getAllMaintenanceWithDetails() {
        $query = "SELECT mr.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as reporter_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as technician_name,
                    e.name, e.serial_number,
                    r.room_name
                 FROM {$this->table} mr 
                 JOIN users u1 ON mr.reported_by = u1.user_id 
                 LEFT JOIN users u2 ON mr.technician_assigned = u2.user_id 
                 JOIN equipment e ON mr.equipment_id = e.equipment_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 ORDER BY mr.report_date DESC";
        
        return $this->db->resultSet($query);
    }
    
    // Get pending maintenance requests
    public function getPendingRequests() {
        $query = "SELECT mr.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as reporter_name,
                    e.name, e.serial_number,
                    r.room_name
                 FROM {$this->table} mr 
                 JOIN users u1 ON mr.reported_by = u1.user_id 
                 JOIN equipment e ON mr.equipment_id = e.equipment_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE mr.status = 'pending'
                 ORDER BY mr.report_date ASC";
        
        return $this->db->resultSet($query);
    }
    
    // Get in progress maintenance requests
    public function getInProgressRequests() {
        $query = "SELECT mr.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as reporter_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as technician_name,
                    e.name, e.serial_number,
                    r.room_name
                 FROM {$this->table} mr 
                 JOIN users u1 ON mr.reported_by = u1.user_id 
                 LEFT JOIN users u2 ON mr.technician_assigned = u2.user_id 
                 JOIN equipment e ON mr.equipment_id = e.equipment_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE mr.status = 'in progress'
                 ORDER BY mr.start_date ASC";
        
        return $this->db->resultSet($query);
    }
    
    // Get maintenance requests by reporter
    public function getMaintenanceByReporter($userId) {
        $query = "SELECT mr.*, 
                    CONCAT(u.first_name, ' ', u.last_name) as technician_name,
                    e.name, e.serial_number,
                    r.room_name
                 FROM {$this->table} mr 
                 LEFT JOIN users u ON mr.technician_assigned = u.user_id 
                 JOIN equipment e ON mr.equipment_id = e.equipment_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE mr.reported_by = :userId
                 ORDER BY mr.report_date DESC";
        
        return $this->db->resultSet($query, ['userId' => $userId]);
    }
    
    // Get maintenance requests by technician
    public function getMaintenanceByTechnician($technicianId) {
        $query = "SELECT mr.*, 
                    CONCAT(u.first_name, ' ', u.last_name) as reporter_name,
                    e.name, e.serial_number,
                    r.room_name
                 FROM {$this->table} mr 
                 JOIN users u ON mr.reported_by = u.user_id 
                 JOIN equipment e ON mr.equipment_id = e.equipment_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE mr.technician_assigned = :technicianId
                    AND mr.status IN ('in progress', 'pending')
                 ORDER BY mr.report_date ASC";
        
        return $this->db->resultSet($query, ['technicianId' => $technicianId]);
    }
    
    // Get maintenance requests by equipment
    public function getMaintenanceByEquipment($equipmentId) {
        $query = "SELECT mr.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as reporter_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as technician_name
                 FROM {$this->table} mr 
                 JOIN users u1 ON mr.reported_by = u1.user_id 
                 LEFT JOIN users u2 ON mr.technician_assigned = u2.user_id 
                 WHERE mr.equipment_id = :equipmentId
                 ORDER BY mr.report_date DESC";
        
        return $this->db->resultSet($query, ['equipmentId' => $equipmentId]);
    }
    
    // Get maintenance reports by user
    public function getMaintenanceReportsByUser($userId) {
        $query = "SELECT mr.*, 
                    CONCAT(u.first_name, ' ', u.last_name) as technician_name,
                    e.name as equipment_name, 
                    e.serial_number,
                    r.room_name
                 FROM {$this->table} mr 
                 LEFT JOIN users u ON mr.technician_assigned = u.user_id 
                 JOIN equipment e ON mr.equipment_id = e.equipment_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE mr.reported_by = :userId
                 ORDER BY mr.report_date DESC";
        
        return $this->db->resultSet($query, ['userId' => $userId]);
    }
    
    // Check if equipment has an active maintenance request
    public function hasActiveMaintenanceRequest($equipmentId) {
        $query = "SELECT COUNT(*) as count 
                 FROM {$this->table} 
                 WHERE equipment_id = :equipmentId 
                 AND status IN ('pending', 'in progress')";
        
        $result = $this->db->single($query, ['equipmentId' => $equipmentId]);
        return $result['count'] > 0;
    }

    // Add new maintenance request
    public function addMaintenanceRequest($data) {
        // Add report_date to the data
        $data['report_date'] = date('Y-m-d H:i:s');
        
        // Ensure status is set to pending for new requests
        $data['status'] = 'pending';
        
        // Insert the maintenance request
        $query = "INSERT INTO {$this->table} 
                 (equipment_id, reported_by, issue_description, technician_assigned, 
                  status, report_date) 
                 VALUES 
                 (:equipment_id, :reported_by, :issue_description, :technician_assigned, 
                  :status, :report_date)";
        
        $params = [
            'equipment_id' => $data['equipment_id'],
            'reported_by' => $data['reported_by'],
            'issue_description' => $data['issue_description'],
            'technician_assigned' => $data['technician_assigned'] ?? null,
            'status' => $data['status'],
            'report_date' => $data['report_date']
        ];
        
        return $this->db->execute($query, $params) ? $this->db->lastInsertId() : false;
    }

    // Assign technician to maintenance request
    public function assignTechnician($maintenanceId, $technicianId) {
        try {
            $query = "UPDATE {$this->table} 
                     SET technician_assigned = :technicianId,
                         status = 'in progress',
                         start_date = NOW()
                     WHERE maintenance_id = :maintenanceId 
                     AND status = 'pending'";
            
            return $this->db->execute($query, [
                'technicianId' => $technicianId,
                'maintenanceId' => $maintenanceId
            ]) ? true : false;
        } catch (Exception $e) {
            error_log("Error assigning technician: " . $e->getMessage());
            return false;
        }
    }
    
    // Complete maintenance request
    public function completeMaintenance($maintenanceId, $resolutionNotes) {
        $query = "UPDATE {$this->table} 
                 SET status = 'completed',
                     completion_date = NOW(),
                     resolution_notes = :resolutionNotes 
                 WHERE maintenance_id = :maintenanceId AND status = 'in progress'";
        
        return $this->db->execute($query, [
            'resolutionNotes' => $resolutionNotes,
            'maintenanceId' => $maintenanceId
        ]) ? true : false;
    }
    
    // Cancel maintenance request
    public function cancelRequest($maintenanceId) {
        $query = "UPDATE {$this->table} 
                 SET status = 'cancelled'
                 WHERE maintenance_id = :maintenanceId AND status IN ('pending', 'in progress')";
        
        return $this->db->execute($query, [
            'maintenanceId' => $maintenanceId
        ]) ? true : false;
    }
    
    // Search maintenance requests by keyword
    public function searchMaintenance($keyword) {
        $keyword = "%{$keyword}%";
        
        $query = "SELECT mr.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as reporter_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as technician_name,
                    e.name, e.serial_number,
                    r.room_name
                 FROM {$this->table} mr 
                 JOIN users u1 ON mr.reported_by = u1.user_id 
                 LEFT JOIN users u2 ON mr.technician_assigned = u2.user_id 
                 JOIN equipment e ON mr.equipment_id = e.equipment_id 
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE mr.issue_description LIKE :keyword 
                    OR mr.resolution_notes LIKE :keyword 
                    OR e.name LIKE :keyword 
                    OR e.serial_number LIKE :keyword 
                    OR r.room_name LIKE :keyword
                    OR CONCAT(u1.first_name, ' ', u1.last_name) LIKE :keyword
                    OR CONCAT(u2.first_name, ' ', u2.last_name) LIKE :keyword
                 ORDER BY mr.report_date DESC";
        
        return $this->db->resultSet($query, ['keyword' => $keyword]);
    }
    
    // Get maintenance statistics
    public function getMaintenanceStatistics() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'in progress' THEN 1 ELSE 0 END) as in_progress_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                    AVG(TIMESTAMPDIFF(HOUR, report_date, IFNULL(completion_date, NOW()))) as avg_resolution_time
                  FROM {$this->table}";
        
        return $this->db->single($query);
    }
    
    // Get maintenance history for a specific equipment
    public function getMaintenanceHistoryByEquipment($equipmentId) {
        $query = "SELECT mr.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as reporter_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as technician_name,
                    e.name as equipment_name
                 FROM {$this->table} mr 
                 JOIN users u1 ON mr.reported_by = u1.user_id 
                 LEFT JOIN users u2 ON mr.technician_assigned = u2.user_id 
                 JOIN equipment e ON mr.equipment_id = e.equipment_id 
                 WHERE mr.equipment_id = :equipmentId 
                 ORDER BY mr.report_date DESC";
        
        return $this->db->resultSet($query, ['equipmentId' => $equipmentId]);
    }
    
    // Get active maintenance for a specific equipment
    public function getActiveMaintenanceByEquipment($equipmentId) {
        $query = "SELECT mr.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as reporter_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as technician_name,
                    e.name as equipment_name
                 FROM {$this->table} mr 
                 JOIN users u1 ON mr.reported_by = u1.user_id 
                 LEFT JOIN users u2 ON mr.technician_assigned = u2.user_id 
                 JOIN equipment e ON mr.equipment_id = e.equipment_id 
                 WHERE mr.equipment_id = :equipmentId 
                 AND mr.status IN ('pending', 'in progress')
                 ORDER BY mr.report_date DESC 
                 LIMIT 1";
        
        return $this->db->single($query, ['equipmentId' => $equipmentId]);
    }
    
    // Assign maintenance to technician
    public function assignMaintenanceToTechnician($maintenanceId, $technicianId) {
        return $this->assignTechnician($maintenanceId, $technicianId);
    }
    
    // Update maintenance progress
    public function updateProgress($maintenanceId, $progressNotes) {
        $query = "UPDATE {$this->table} 
                 SET resolution_notes = :progressNotes 
                 WHERE maintenance_id = :maintenanceId 
                 AND status = 'in progress'";
        
        return $this->db->execute($query, [
            'progressNotes' => $progressNotes,
            'maintenanceId' => $maintenanceId
        ]) ? true : false;
    }
    
    // Delete maintenance report
    public function deleteMaintenanceReport($maintenanceId) {
        try {
            // First check if the maintenance report exists and can be deleted
            $query = "SELECT * FROM {$this->table} WHERE maintenance_id = :maintenanceId";
            $report = $this->db->single($query, ['maintenanceId' => $maintenanceId]);
            
            if (!$report) {
                throw new Exception("Maintenance report not found");
            }

            // Check if the report can be deleted (only completed reports can be deleted)
            if ($report['status'] !== 'completed') {
                throw new Exception("Only completed maintenance reports can be deleted");
            }

            // Use the BaseModel's delete method
            return $this->delete($maintenanceId);
        } catch (Exception $e) {
            error_log("Error in deleteMaintenanceReport: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Get maintenance report with detailed info for specified date range
    public function getMaintenanceReport($startDate = '', $endDate = '') {
        $query = "SELECT mr.*, 
                    CONCAT(u1.first_name, ' ', u1.last_name) as reporter_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as technician_name,
                    e.name as equipment_name, 
                    e.serial_number,
                    c.category_name,
                    r.room_name, 
                    r.building
                 FROM {$this->table} mr 
                 JOIN users u1 ON mr.reported_by = u1.user_id 
                 LEFT JOIN users u2 ON mr.technician_assigned = u2.user_id 
                 JOIN equipment e ON mr.equipment_id = e.equipment_id 
                 LEFT JOIN equipment_categories c ON e.category_id = c.category_id
                 LEFT JOIN rooms r ON e.location = r.room_id 
                 WHERE 1=1";
        
        $params = [];
        
        if (!empty($startDate)) {
            $query .= " AND mr.report_date >= :startDate";
            $params['startDate'] = $startDate;
        }
        
        if (!empty($endDate)) {
            $query .= " AND (mr.completion_date <= :endDate OR mr.report_date <= :endDate)";
            $params['endDate'] = $endDate;
        }
        
        $query .= " ORDER BY mr.report_date DESC";
        
        return $this->db->resultSet($query, $params);
    }
} 