<?php
class Category {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all categories
     * @return array Array of categories
     */
    public function getAllCategories() {
        $sql = "SELECT * FROM equipment_categories ORDER BY category_name ASC";
        return $this->db->resultSet($sql);
    }
    
    /**
     * Get a category by ID
     * @param int $id Category ID
     * @return array|false Category data or false if not found
     */
    public function getCategoryById($id) {
        $sql = "SELECT * FROM equipment_categories WHERE id = :id";
        return $this->db->single($sql, ['id' => $id]);
    }
    
    /**
     * Create a new category
     * @param array $data Category data
     * @return int|false New category ID or false on failure
     */
    public function create($data) {
        $sql = "INSERT INTO equipment_categories (category_name, description) VALUES (:name, :description)";
        $params = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null
        ];
        
        if ($this->db->execute($sql, $params)) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    /**
     * Update a category
     * @param int $id Category ID
     * @param array $data Category data
     * @return bool Success status
     */
    public function update($id, $data) {
        $sql = "UPDATE equipment_categories SET category_name = :name, description = :description WHERE id = :id";
        $params = [
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null
        ];
        
        return $this->db->execute($sql, $params) !== false;
    }
    
    /**
     * Delete a category
     * @param int $id Category ID
     * @return bool Success status
     */
    public function delete($id) {
        // First check if category is in use
        $sql = "SELECT COUNT(*) as count FROM equipment WHERE category_id = :id";
        $result = $this->db->single($sql, ['id' => $id]);
        
        if ($result && $result['count'] > 0) {
            return false; // Category is in use
        }
        
        $sql = "DELETE FROM equipment_categories WHERE id = :id";
        return $this->db->execute($sql, ['id' => $id]) !== false;
    }
    
    /**
     * Check if a category exists
     * @param string $name Category name
     * @param int $excludeId Optional ID to exclude from check
     * @return bool True if category exists
     */
    public function categoryExists($name, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM equipment_categories WHERE category_name = :name";
        $params = ['name' => $name];
        
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $result = $this->db->single($sql, $params);
        return $result && $result['count'] > 0;
    }
} 