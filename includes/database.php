<?php
/**
 * Database Connection Class
 */
class Database {
    private static $instance = null;
    private $pdo;
    
    // Private constructor to prevent direct creation
    private function __construct() {
        try {
            // Load config if not already loaded
            if (!defined('DB_DSN')) {
                require_once __DIR__ . '/config.php';
            }
            
            $this->pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Set timezone for Ethiopia
            $this->pdo->exec("SET TIME ZONE 'Africa/Addis_Ababa'");
            
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() { }
    
    /**
     * Prevent unserializing
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Optional: Create a global function to get database connection
function getDB() {
    return Database::getInstance()->getConnection();
}
?>