<?php
/**
 * Database Configuration
 * Online Event Booking System
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'event_booking';
    private $username = 'root';
    private $password = 'w@1529djkq#';
    private $charset = 'utf8mb4';
    private $conn;

    /**
     * Get database connection
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
        
        return $this->conn;
    }

    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }
}

// Create global database connection
$database = new Database();
// $db = $database->getConnection();

$pdo = $database->getConnection();
?>