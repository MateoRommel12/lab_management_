<?php
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $conn;
    private $error;
    private static $instance = null;
    
    // Private constructor to implement Singleton pattern
    private function __construct() {
        // Set DSN (Data Source Name)
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        
        // Set options
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        );
        
        // Create PDO instance
        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database Connection Error: " . $this->error);
            // For development environment only - remove in production
            echo "Connection Error: " . $this->error;
        }
    }
    
    // Get singleton instance
    public static function getInstance() {
        if(!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    // Get connection
    public function getConnection() {
        return $this->conn;
    }
    
    // Execute prepared statement
    public function execute($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);
            
            // For debugging
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("SQL Error: " . $errorInfo[2] . " in query: " . $query);
            }
            
            return $stmt;
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Query Execution Error: " . $this->error . " in query: " . $query);
            // For development environment only - remove in production
            echo "Query Error: " . $this->error;
            return false;
        }
    }
    
    // Return a single record
    public function single($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    // Return multiple records
    public function resultSet($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt ? $stmt->fetchAll() : false;
    }
    
    // Row count
    public function rowCount($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt ? $stmt->rowCount() : false;
    }
    
    // Get last inserted ID
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->conn->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->conn->rollBack();
    }
} 