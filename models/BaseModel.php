<?php
require_once __DIR__ . '/../config/Database.php';

abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $allowedFields = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Find all records
    public function findAll() {
        $query = "SELECT * FROM {$this->table}";
        return $this->db->resultSet($query);
    }
    
    // Find by ID
    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->single($query, ['id' => $id]);
    }
    
    // Find by condition
    public function findBy($field, $value) {
        $query = "SELECT * FROM {$this->table} WHERE {$field} = :value";
        return $this->db->resultSet($query, ['value' => $value]);
    }
    
    // Find one by condition
    public function findOneBy($field, $value) {
        $query = "SELECT * FROM {$this->table} WHERE {$field} = :value LIMIT 1";
        return $this->db->single($query, ['value' => $value]);
    }
    
    // Create record
    public function create($data) {
        // Filter data to only include allowed fields
        $filteredData = array_intersect_key($data, array_flip($this->allowedFields));
        
        if (empty($filteredData)) {
            return false;
        }
        
        $fields = array_keys($filteredData);
        $placeholders = array_map(function($field) {
            return ":{$field}";
        }, $fields);
        
        $fieldStr = implode(', ', $fields);
        $placeholderStr = implode(', ', $placeholders);
        
        $query = "INSERT INTO {$this->table} ({$fieldStr}) VALUES ({$placeholderStr})";
        
        if ($this->db->execute($query, $filteredData)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    // Update record
    public function update($id, $data) {
        // Filter data to only include allowed fields
        $filteredData = array_intersect_key($data, array_flip($this->allowedFields));
        
        if (empty($filteredData)) {
            return false;
        }
        
        $setStatements = array_map(function($field) {
            return "{$field} = :{$field}";
        }, array_keys($filteredData));
        
        $setStr = implode(', ', $setStatements);
        
        $query = "UPDATE {$this->table} SET {$setStr} WHERE {$this->primaryKey} = :id";
        
        $filteredData['id'] = $id;
        
        return $this->db->execute($query, $filteredData) ? true : false;
    }
    
    // Delete record
    public function delete($id) {
        try {
            error_log("Attempting to delete record with ID {$id} from table {$this->table}");
            $query = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->execute($query, ['id' => $id]);
            
            if (!$stmt) {
                error_log("Failed to delete record with ID {$id} from table {$this->table}");
                return false;
            }
            
            $rowCount = $stmt->rowCount();
            error_log("Deleted {$rowCount} records from {$this->table} with ID {$id}");
            
            return $rowCount > 0;
        } catch (Exception $e) {
            error_log("Exception in delete method: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Count records
    public function count() {
        $query = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->single($query);
        return $result ? $result['count'] : 0;
    }
    
    // Count by condition
    public function countBy($field, $value) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$field} = :value";
        $result = $this->db->single($query, ['value' => $value]);
        return $result ? $result['count'] : 0;
    }
    
    // Custom query
    public function query($query, $params = []) {
        return $this->db->resultSet($query, $params);
    }
    
    // Custom query - single result
    public function querySingle($query, $params = []) {
        return $this->db->single($query, $params);
    }
} 