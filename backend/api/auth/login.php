<?php
// // backend/api/auth/login.php
// // CORS MUST be first!

// // from chat gpt 


// require_once __DIR__ . '/../../includes/cors.php';
// header("Access-Control-Allow-Origin: https://edu-bv0h58dy3-williams-projects-f21505ba.vercel.app");
// header("Access-Control-Allow-Methods: POST, OPTIONS");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit;
// }

// $input = json_decode(file_get_contents("php://input"), true);

// $email = $input['email'] ?? null;
// $password = $input['password'] ?? null;

// if (!$email || !$password) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Email and password are required'
//     ]);
//     exit;
// }




// session_start();
// require_once '../../includes/cors.php';

// //session_start();

// try {
//     include_once '../../config/database.php';

//     $database = new Database();
//     $db = $database->getConnection();

//     // Get posted data
//     $data = json_decode(file_get_contents("php://input"));

//     if (!empty($data->email) && !empty($data->password)) {
        
//         // Query to get user from users table
//         $query = "SELECT id, email, password, role, status FROM users WHERE email = :email";
        
//         $stmt = $db->prepare($query);
//         $stmt->bindParam(":email", $data->email);
//         $stmt->execute();

//         if ($stmt->rowCount() > 0) {
//             $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
//             // Verify password
//             if (password_verify($data->password, $row['password'])) {
                
//                 // Check if account is active
//                 if (isset($row['status']) && $row['status'] !== 'active') {
//                     http_response_code(403);
//                     echo json_encode([
//                         "success" => false,
//                         "message" => "Account is inactive. Please contact admin."
//                     ]);
//                     exit();
//                 }

//                 // Get additional info based on role
//                 $additional_info = [];
                
//                 if ($row['role'] === 'student') {
//                     // Get student info from students table
//                     $student_query = "SELECT 
//                                         s.id as student_id, 
//                                         s.student_number, 
//                                         s.firstname,
//                                         s.lastname,
//                                         s.class_id,
//                                         c.class_name,
//                                         c.grade_level
//                                       FROM students s 
//                                       LEFT JOIN classes c ON s.class_id = c.id
//                                       WHERE s.user_id = :user_id";
//                     $student_stmt = $db->prepare($student_query);
//                     $student_stmt->bindParam(":user_id", $row['id']);
//                     $student_stmt->execute();
                    
//                     if ($student_stmt->rowCount() > 0) {
//                         $additional_info = $student_stmt->fetch(PDO::FETCH_ASSOC);
//                     }
//                 } 
//                 elseif ($row['role'] === 'teacher') {
//                     // Get teacher info from teachers table
//                     $teacher_query = "SELECT 
//                                         t.id as teacher_id, 
//                                         t.teacher_id,
//                                         t.firstname,
//                                         t.lastname,
//                                         t.department,
//                                         t.specialization
//                                       FROM teachers t 
//                                       WHERE t.user_id = :user_id";
//                     $teacher_stmt = $db->prepare($teacher_query);
//                     $teacher_stmt->bindParam(":user_id", $row['id']);
//                     $teacher_stmt->execute();
                    
//                     if ($teacher_stmt->rowCount() > 0) {
//                         $additional_info = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
//                     }
//                 }
//                 elseif ($row['role'] === 'admin') {
//                     // Admin doesn't need additional info
//                     $additional_info = [
//                         'role_name' => 'Administrator'
//                     ];
//                 }

//                 // Store in session
//                 $_SESSION['user_id'] = $row['id'];
//                 $_SESSION['role'] = $row['role'];
//                 $_SESSION['email'] = $row['email'];

//                 http_response_code(200);
//                 echo json_encode([
//                     "success" => true,
//                     "message" => "Login successful",
//                     "user" => [
//                         "id" => $row['id'],
//                         "email" => $row['email'],
//                         "role" => $row['role'],
//                         "firstname" => $additional_info['firstname'] ?? 'Admin',
//                         "lastname" => $additional_info['lastname'] ?? 'User',
//                         "additional_info" => $additional_info
//                     ]
//                 ]);

//             } else {
//                 http_response_code(401);
//                 echo json_encode([
//                     "success" => false,
//                     "message" => "Invalid password"
//                 ]);
//             }

//         } else {
//             http_response_code(404);
//             echo json_encode([
//                 "success" => false,
//                 "message" => "User not found"
//             ]);
//         }

//     } else {
//         http_response_code(400);
//         echo json_encode([
//             "success" => false,
//             "message" => "Email and password are required"
//         ]);
//     }

// } catch (Exception $e) {
//     http_response_code(500);
//     echo json_encode([
//         "success" => false,
//         "message" => "Server error: " . $e->getMessage()
//     ]);
// }

?>

















<?php
// backend/api/auth/login.php

// 1️⃣ CORS FIRST — ONLY ONCE
require_once __DIR__ . '/../../includes/cors.php';

// 2️⃣ Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 3️⃣ Start session BEFORE output
session_start();

// 4️⃣ Read JSON input ONCE
$input = json_decode(file_get_contents("php://input"), true);

$email = $input['email'] ?? null;
$password = $input['password'] ?? null;

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Email and password are required'
    ]);
    exit;
}

// 5️⃣ Database
require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id, email, password, role, status 
              FROM users 
              WHERE email = :email 
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid password'
        ]);
        exit;
    }

    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Account is inactive'
        ]);
        exit;
    }

    // 6️⃣ Store session (optional but OK)
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}
