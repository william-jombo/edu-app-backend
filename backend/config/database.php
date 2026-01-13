<?php
// backend/config/database.php
// Database connection for Supabase PostgreSQL

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // Use environment variables for security (set in Fly.io secrets)
        // Fallback to hardcoded values for now
        $this->host = getenv('DB_HOST') ?: 'aws-1-us-east-1.pooler.supabase.com';
        $this->db_name = getenv('DB_NAME') ?: 'postgres';
        $this->username = getenv('DB_USER') ?: 'postgres.auknbtgbypmisvrowwzu';
        $this->password = getenv('DB_PASSWORD') ?: 'WJomBo.W/@Tw2111';
        $this->port = getenv('DB_PORT') ?: '5432';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->db_name}";
            
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            
            $this->conn->exec("SET NAMES 'UTF8'");
            
        } catch(PDOException $exception) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Database connection failed: " . $exception->getMessage()
            ]);
            exit();
        }

        return $this->conn;
    }
}