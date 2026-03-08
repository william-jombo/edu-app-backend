<?php
// // backend/api/auth/login.php

// require_once '../../config/database.php';

// // ============================================
// // CORS HEADERS - reads from environment variable
// // ============================================
// $allowedOrigins = getenv('ALLOWED_ORIGINS') ?: 'http://localhost:5173';
// $allowedOriginsArray = array_map('trim', explode(',', $allowedOrigins));
// $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// if (in_array($origin, $allowedOriginsArray)) {
//     header("Access-Control-Allow-Origin: $origin");
//     header('Access-Control-Allow-Credentials: true');
//     header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
//     header('Access-Control-Allow-Headers: Content-Type, Authorization');
// } else {
//     http_response_code(403);
//     header('Content-Type: application/json');
//     echo json_encode([
//         'success' => false,
//         'message' => 'CORS origin not allowed'
//     ]);
//     exit();
// }

// header('Content-Type: application/json');

// // Handle preflight OPTIONS request BEFORE session_start
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit();
// }

// // ============================================
// // START SESSION
// // ============================================
// session_start();

// // ============================================
// // MAIN LOGIN LOGIC
// // ============================================
// try {
//     $db = getDBConnection();

//     // Get posted data
//     $data = json_decode(file_get_contents("php://input"));

//     // Validate required fields
//     if (empty($data->email) || empty($data->password)) {
//         http_response_code(400);
//         echo json_encode([
//             "success" => false,
//             "message" => "Email and password are required"
//         ]);
//         exit();
//     }

//     // Query user from users table
//     $query = "SELECT id, email, password, role, status FROM users WHERE email = :email";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(":email", $data->email);
//     $stmt->execute();

//     if ($stmt->rowCount() === 0) {
//         http_response_code(404);
//         echo json_encode([
//             "success" => false,
//             "message" => "No account found with that email"
//         ]);
//         exit();
//     }

//     $row = $stmt->fetch(PDO::FETCH_ASSOC);

//     // Verify password
//     if (!password_verify($data->password, $row['password'])) {
//         http_response_code(401);
//         echo json_encode([
//             "success" => false,
//             "message" => "Incorrect password"
//         ]);
//         exit();
//     }

//     // Check account status
//     if (isset($row['status']) && $row['status'] !== 'active') {
//         http_response_code(403);
//         echo json_encode([
//             "success" => false,
//             "message" => "Your account is inactive. Please contact admin."
//         ]);
//         exit();
//     }

//     // ============================================
//     // FETCH ROLE-SPECIFIC INFO
//     // ============================================
//     $additional_info = [];

//     if ($row['role'] === 'student') {
//         $student_query = "SELECT 
//                             s.id as student_id, 
//                             s.student_number, 
//                             s.firstname,
//                             s.lastname,
//                             s.class_id,
//                             c.class_name,
//                             c.grade_level
//                           FROM students s 
//                           LEFT JOIN classes c ON s.class_id = c.id
//                           WHERE s.user_id = :user_id";

//         $student_stmt = $db->prepare($student_query);
//         $student_stmt->bindParam(":user_id", $row['id']);
//         $student_stmt->execute();

//         if ($student_stmt->rowCount() > 0) {
//             $additional_info = $student_stmt->fetch(PDO::FETCH_ASSOC);
//         }

//     } elseif ($row['role'] === 'teacher') {
//         // ✅ FIX: Changed 't.id as teacher_id' to just 't.id'
//         // and 't.teacher_id' to 't.teacher_id as teacher_number'
//         $teacher_query = "SELECT 
//                             t.id,
//                             t.teacher_id as teacher_number,
//                             t.firstname,
//                             t.lastname,
//                             t.department,
//                             t.specialization
//                           FROM teachers t 
//                           WHERE t.user_id = :user_id";

//         $teacher_stmt = $db->prepare($teacher_query);
//         $teacher_stmt->bindParam(":user_id", $row['id']);
//         $teacher_stmt->execute();

//         if ($teacher_stmt->rowCount() > 0) {
//             $additional_info = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
//         }

//     } elseif ($row['role'] === 'admin') {
//         $additional_info = [
//             'firstname' => 'Admin',
//             'lastname'  => 'User',
//             'role_name' => 'Administrator'
//         ];
//     }

//     // ============================================
//     // STORE IN SESSION
//     // ============================================
//     $_SESSION['user_id'] = $row['id'];
//     $_SESSION['role']    = $row['role'];
//     $_SESSION['email']   = $row['email'];

//     // ✅ FIX: Changed from 'teacher_id' to 'id'
//     if ($row['role'] === 'teacher' && isset($additional_info['id'])) {
//         $_SESSION['teacher_id'] = $additional_info['id'];
//     }

//     if ($row['role'] === 'student' && isset($additional_info['student_id'])) {
//         $_SESSION['student_id'] = $additional_info['student_id'];
//     }

//     // ============================================
//     // SUCCESS RESPONSE
//     // ============================================
//     http_response_code(200);
//     echo json_encode([
//         "success"  => true,
//         "message"  => "Login successful",
//         "user"     => [
//             "id"              => $row['id'],
//             "email"           => $row['email'],
//             "role"            => $row['role'],
//             "firstname"       => $additional_info['firstname'] ?? 'Admin',
//             "lastname"        => $additional_info['lastname']  ?? 'User',
//             "additional_info" => $additional_info
//         ]
//     ]);

// } catch (Exception $e) {
//     http_response_code(500);
//     echo json_encode([
//         "success" => false,
//         "message" => getenv('ENVIRONMENT') === 'production'
//             ? "Server error. Please try again later."
//             : "Server error: " . $e->getMessage()
//     ]);
// }






// backend/api/auth/login.php

require_once '../../config/database.php';

// ============================================
// CORS HEADERS
// ============================================
$allowedOrigins = getenv('ALLOWED_ORIGINS') ?: 'http://localhost:5173';
$allowedOriginsArray = array_map('trim', explode(',', $allowedOrigins));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOriginsArray)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
} else {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'CORS origin not allowed']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

try {
    $db = getDBConnection();
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->email) || empty($data->password)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Email and password are required"]);
        exit();
    }

    $query = "SELECT id, email, password, role, status FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "No account found with that email"]);
        exit();
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify($data->password, $row['password'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Incorrect password"]);
        exit();
    }

    if (isset($row['status']) && $row['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Your account is inactive. Please contact admin."]);
        exit();
    }

    // ============================================
    // FETCH ROLE-SPECIFIC INFO
    // ============================================
    $additional_info = [];
    $teacher_id = null;
    $student_id = null;

    if ($row['role'] === 'student') {
        $student_stmt = $db->prepare("
            SELECT s.id as student_id, s.student_number, s.firstname, s.lastname,
                   s.class_id, c.class_name, c.grade_level
            FROM students s
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE s.user_id = :user_id
        ");
        $student_stmt->bindParam(":user_id", $row['id']);
        $student_stmt->execute();
        if ($student_stmt->rowCount() > 0) {
            $additional_info = $student_stmt->fetch(PDO::FETCH_ASSOC);
            $student_id = $additional_info['student_id'];
        }

    } elseif ($row['role'] === 'teacher') {
        $teacher_stmt = $db->prepare("
            SELECT t.id, t.teacher_id as teacher_number, t.firstname, t.lastname,
                   t.department, t.specialization
            FROM teachers t
            WHERE t.user_id = :user_id
        ");
        $teacher_stmt->bindParam(":user_id", $row['id']);
        $teacher_stmt->execute();
        if ($teacher_stmt->rowCount() > 0) {
            $additional_info = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
            $teacher_id = $additional_info['id']; // ✅ Grab teacher_id clearly
        }

    } elseif ($row['role'] === 'admin') {
        $additional_info = ['firstname' => 'Admin', 'lastname' => 'User', 'role_name' => 'Administrator'];
    }

    // ============================================
    // STORE IN SESSION (keep for backward compat)
    // ============================================
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['role']    = $row['role'];
    $_SESSION['email']   = $row['email'];
    if ($teacher_id) $_SESSION['teacher_id'] = $teacher_id;
    if ($student_id) $_SESSION['student_id'] = $student_id;

    // ============================================
    // SUCCESS RESPONSE - now includes teacher_id clearly
    // ============================================
    http_response_code(200);
    echo json_encode([
        "success"    => true,
        "message"    => "Login successful",
        "teacher_id" => $teacher_id, // ✅ TOP LEVEL - easy to grab
        "student_id" => $student_id, // ✅ TOP LEVEL - easy to grab
        "user"       => [
            "id"              => $row['id'],
            "email"           => $row['email'],
            "role"            => $row['role'],
            "teacher_id"      => $teacher_id, // ✅ ALSO IN USER OBJECT
            "student_id"      => $student_id, // ✅ ALSO IN USER OBJECT
            "firstname"       => $additional_info['firstname'] ?? 'Admin',
            "lastname"        => $additional_info['lastname']  ?? 'User',
            "additional_info" => $additional_info
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => getenv('ENVIRONMENT') === 'production'
            ? "Server error. Please try again later."
            : "Server error: " . $e->getMessage()
    ]);
}

?>