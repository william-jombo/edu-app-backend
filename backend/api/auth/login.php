<?php
// session_start();
// header('Content-Type: application/json');

// try {
//     include_once '../../config/database.php';

//     $database = new Database();
//     $db = $database->getConnection();

//     // Get posted data
//     $data = json_decode(file_get_contents("php://input"));

//     if (!empty($data->email) && !empty($data->password)) {
        
//         // Query to get user
//         $query = "SELECT id, email, password, role, first_name, last_name, status FROM tbl_users WHERE email = :email";
        
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
//                     // Fixed query - removed the JOIN with tbl_class
//                     $student_query = "SELECT s.id as student_id, s.student_number, s.class_id 
//                                       FROM tbl_students s 
//                                       WHERE s.user_id = :user_id";
//                     $student_stmt = $db->prepare($student_query);
//                     $student_stmt->bindParam(":user_id", $row['id']);
//                     $student_stmt->execute();
                    
//                     if ($student_stmt->rowCount() > 0) {
//                         $additional_info = $student_stmt->fetch(PDO::FETCH_ASSOC);
//                     }
//                 } 
//                 elseif ($row['role'] === 'teacher') {
//     $teacher_query = "SELECT t.id as teacher_id, t.teacher_id 
//                       FROM tbl_teacher t 
//                       WHERE t.user_id = :user_id";
//     $teacher_stmt = $db->prepare($teacher_query);
//     $teacher_stmt->bindParam(":user_id", $row['id']);
//     $teacher_stmt->execute();
    
//     if ($teacher_stmt->rowCount() > 0) {
//         $additional_info = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
//     }
// }elseif ($row['role'] === 'admin') {
//                         // Admin doesn't need additional info from other tables
//                         $additional_info = [];
//                     }

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
//                         "first_name" => $row['first_name'],
//                         "last_name" => $row['last_name'],
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
// Updated for new database structure

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    include_once '../../config/database.php';

    $database = new Database();
    $db = $database->getConnection();

    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->email) && !empty($data->password)) {
        
        // Query to get user from users table
        $query = "SELECT id, email, password, role, status FROM users WHERE email = :email";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $data->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if (password_verify($data->password, $row['password'])) {
                
                // Check if account is active
                if (isset($row['status']) && $row['status'] !== 'active') {
                    http_response_code(403);
                    echo json_encode([
                        "success" => false,
                        "message" => "Account is inactive. Please contact admin."
                    ]);
                    exit();
                }

                // Get additional info based on role
                $additional_info = [];
                
                if ($row['role'] === 'student') {
                    // Get student info from students table
                    $student_query = "SELECT 
                                        s.id as student_id, 
                                        s.student_number, 
                                        s.firstname,
                                        s.lastname,
                                        s.class_id,
                                        c.class_name,
                                        c.grade_level
                                      FROM students s 
                                      LEFT JOIN classes c ON s.class_id = c.id
                                      WHERE s.user_id = :user_id";
                    $student_stmt = $db->prepare($student_query);
                    $student_stmt->bindParam(":user_id", $row['id']);
                    $student_stmt->execute();
                    
                    if ($student_stmt->rowCount() > 0) {
                        $additional_info = $student_stmt->fetch(PDO::FETCH_ASSOC);
                    }
                } 
                elseif ($row['role'] === 'teacher') {
                    // Get teacher info from teachers table
                    $teacher_query = "SELECT 
                                        t.id as teacher_id, 
                                        t.teacher_id,
                                        t.firstname,
                                        t.lastname,
                                        t.department,
                                        t.specialization
                                      FROM teachers t 
                                      WHERE t.user_id = :user_id";
                    $teacher_stmt = $db->prepare($teacher_query);
                    $teacher_stmt->bindParam(":user_id", $row['id']);
                    $teacher_stmt->execute();
                    
                    if ($teacher_stmt->rowCount() > 0) {
                        $additional_info = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
                    }
                }
                elseif ($row['role'] === 'admin') {
                    // Admin doesn't need additional info
                    $additional_info = [
                        'role_name' => 'Administrator'
                    ];
                }

                // Store in session
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['email'] = $row['email'];

                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Login successful",
                    "user" => [
                        "id" => $row['id'],
                        "email" => $row['email'],
                        "role" => $row['role'],
                        "firstname" => $additional_info['firstname'] ?? 'Admin',
                        "lastname" => $additional_info['lastname'] ?? 'User',
                        "additional_info" => $additional_info
                    ]
                ]);

            } else {
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid password"
                ]);
            }

        } else {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "User not found"
            ]);
        }

    } else {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email and password are required"
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>