<?php
// //C:\Users\BR\Desktop\calmtech\php\htdocs\backend\config\database.php
// header('Access-Control-Allow-Origin: http://localhost:5173');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');
// header('Access-Control-Allow-Credentials: true');

// // Handle preflight requests
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit();
// }

// class Database {
//     private $host = "localhost";
//     private $db_name = "edu";
//     private $username = "root";
//     private $password = "";
//     public $conn;

//     public function getConnection() {
//         $this->conn = null;

//         try {
//             $this->conn = new PDO(
//                 "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
//                 $this->username,
//                 $this->password
//             );
//             $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//             $this->conn->exec("set names utf8");
//         } catch(PDOException $exception) {
//             echo json_encode([
//                 "success" => false,
//                 "message" => "Connection error: " . $exception->getMessage()
//             ]);
//         }

//         return $this->conn;
//     }
// }
?>







<!-- for supabase only  -->




<?php
//C:\Users\BR\Desktop\calmtech\php\htdocs\backend\config\database.php

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
 

//  private $host = "aws-1-us-east-1.pooler.supabase.com";
//     private $db_name = "postgres";
//     private $username = "postgres.auknbtgbypmisvrowwzu";
//     private $password = "YOUR_PASSWORD_HERE";  // ⚠️ Put your actual password!
//     private $port = "5432";

//WJomBo.W/@Tw2111

class Database {
    // Supabase PostgreSQL Settings
    private $host = "aws-1-us-east-1.pooler.supabase.com";
    private $db_name = "postgres";
    private $username = "postgres.auknbtgbypmisvrowwzu";
    private $password = "WJomBo.W/@Tw2111";  // ⚠️ PASTE YOUR PASSWORD HERE!
    private $port = "5432";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // PostgreSQL connection
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
            echo json_encode([
                "success" => false,
                "message" => "Connection error: " . $exception->getMessage()
            ]);
            exit();
        }

        return $this->conn;
    }
}
?>






<!-- for both  -->







<?php
// //C:\Users\BR\Desktop\calmtech\php\htdocs\backend\config\database.php

// header('Access-Control-Allow-Origin: http://localhost:5173');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');
// header('Access-Control-Allow-Credentials: true');

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit();
// }

// class Database {
//     private $host;
//     private $db_name;
//     private $username;
//     private $password;
//     private $port;
//     private $driver;
//     public $conn;

//     public function __construct() {
//         // Change this to switch between local and Supabase
//         $USE_SUPABASE = true; // ⚠️ Set to true for Supabase, false for local
        
//         if ($USE_SUPABASE) {
//             // Supabase PostgreSQL
//             $this->driver = "pgsql";
//             $this->host = "db.auknbtgbypmisvrowwzu.supabase.co";
//             $this->db_name = "postgres";
//             $this->username = "postgres";
//             $this->password = "WJomBo.W/@Tw2111"; // ⚠️ PASTE HERE!
//             $this->port = "5432";
//         } else {
//             // Local MySQL
//             $this->driver = "mysql";
//             $this->host = "localhost";
//             $this->db_name = "edu";
//             $this->username = "root";
//             $this->password = "";
//             $this->port = "3306";
//         }
//     }

//     public function getConnection() {
//         $this->conn = null;

//         try {
//             if ($this->driver === "pgsql") {
//                 $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->db_name}";
//             } else {
//                 $dsn = "mysql:host={$this->host};dbname={$this->db_name}";
//             }
            
//             $this->conn = new PDO(
//                 $dsn,
//                 $this->username,
//                 $this->password,
//                 [
//                     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//                     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//                 ]
//             );
            
//             if ($this->driver === "mysql") {
//                 $this->conn->exec("SET NAMES utf8");
//             } else {
//                 $this->conn->exec("SET NAMES 'UTF8'");
//             }
            
//         } catch(PDOException $exception) {
//             echo json_encode([
//                 "success" => false,
//                 "message" => "Connection error: " . $exception->getMessage()
//             ]);
//             exit();
//         }

//         return $this->conn;
//     }
// }
?>