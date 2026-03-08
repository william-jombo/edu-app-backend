<?php
// backend/config/database.php
// Database configuration for Supabase PostgreSQL

// =============================================================================
// DATABASE CLASS
// =============================================================================

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // Always read from environment variables
        // Works for both local (.env loaded externally) and production (Fly.io secrets)
        $this->host     = getenv('DB_HOST')     ?: 'aws-0-eu-west-1.pooler.supabase.com';
        $this->port     = getenv('DB_PORT')     ?: '6543';
        $this->db_name  = getenv('DB_NAME')     ?: 'postgres';
        $this->username = getenv('DB_USERNAME') ?: 'postgres.axcjumdnwtgngxkanfzz';
        $this->password = getenv('DB_PASSWORD') ?: '';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            // Build DSN for PostgreSQL
            $dsn = sprintf(
                "pgsql:host=%s;port=%s;dbname=%s;sslmode=require",
                $this->host,
                $this->port,
                $this->db_name
            );

            // Create PDO connection
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT         => false,
                    PDO::ATTR_TIMEOUT            => 30
                ]
            );

            // Set character encoding
            $this->conn->exec("SET NAMES 'utf8'");

            // Set timezone
            $timezone = getenv('TIMEZONE') ?: 'Africa/Blantyre';
            $this->conn->exec("SET TIME ZONE '{$timezone}'");

        } catch (PDOException $exception) {
            error_log("Database Connection Error: " . $exception->getMessage());

            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => getenv('ENVIRONMENT') === 'production'
                    ? 'Database connection failed'
                    : 'Database error: ' . $exception->getMessage()
            ]);
            exit(1);
        }

        return $this->conn;
    }
}

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Get database connection
 */
function getDBConnection() {
    $database = new Database();
    return $database->getConnection();
}

/**
 * Set JSON response headers
 */
function setJSONHeaders() {
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * Send JSON response
 */
function sendJSON($data, $status_code = 200) {
    http_response_code($status_code);
    setJSONHeaders();
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit(0);
}

/**
 * Send error response
 */
function sendError($message, $status_code = 400) {
    sendJSON([
        'success' => false,
        'message' => $message
    ], $status_code);
}

/**
 * Send success response
 */
function sendSuccess($message, $data = []) {
    sendJSON(array_merge([
        'success' => true,
        'message' => $message
    ], $data), 200);
}

// =============================================================================
// INCLUDE ADDITIONAL CONFIGS
// =============================================================================

$supabase_config = __DIR__ . '/supabase.php';
if (file_exists($supabase_config)) {
    require_once $supabase_config;
}