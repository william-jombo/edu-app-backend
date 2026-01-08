<?php
// // FILE: backend/api/register_student_with_subjects.php
// // Updated to work with your existing database structure
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');
// header('Access-Control-Allow-Methods: POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');

// // Handle preflight request
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     exit(0);
// }

// require_once  '../../config/database.php';

// try {
//     // Get POST data
//     $data = json_decode(file_get_contents("php://input"), true);
    
//     // Validate required fields
//     $required_fields = ['firstname', 'lastname', 'email', 'password', 'student_number', 'class_id', 'subjects'];
//     foreach ($required_fields as $field) {
//         if (empty($data[$field])) {
//             echo json_encode([
//                 'success' => false,
//                 'message' => "Missing required field: $field"
//             ]);
//             exit;
//         }
//     }
    
//     // Validate subjects count (minimum 7)
//     if (!is_array($data['subjects']) || count($data['subjects']) < 7) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Please select at least 7 subjects'
//         ]);
//         exit;
//     }
    
//     $database = new Database();
//     $conn = $database->getConnection();
    
//     // Start transaction
//     $conn->beginTransaction();
    
//     try {
//         // Check if email already exists in tbl_users
//         $checkEmail = $conn->prepare("SELECT id FROM tbl_users WHERE email = ?");
//         $checkEmail->execute([$data['email']]);
//         if ($checkEmail->fetch()) {
//             throw new Exception('Email already registered');
//         }
        
//         // Check if student number already exists in tbl_students
//         $checkStudentNum = $conn->prepare("SELECT id FROM tbl_students WHERE student_number = ?");
//         $checkStudentNum->execute([$data['student_number']]);
//         if ($checkStudentNum->fetch()) {
//             throw new Exception('Student number already exists');
//         }
        
//         // Hash password
//         $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
//         // STEP 1: Insert into tbl_users (main users table)
//         $insertUser = "INSERT INTO tbl_users 
//                       (email, password, role, first_name, last_name, phone, status, created_at) 
//                       VALUES (?, ?, 'student', ?, ?, ?, 'active', NOW())";
        
//         $stmt = $conn->prepare($insertUser);
//         $stmt->execute([
//             $data['email'],
//             $hashedPassword,
//             $data['firstname'],
//             $data['lastname'],
//             $data['phone'] ?? null
//         ]);
        
//         $user_id = $conn->lastInsertId();
        
//         // STEP 2: Insert into tbl_students (linked to user)
//         $insertStudent = "INSERT INTO tbl_students 
//                          (user_id, student_number, first_name, last_name, email, password, class_id, phone, status, created_at) 
//                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        
//         $stmt = $conn->prepare($insertStudent);
//         $stmt->execute([
//             $user_id,
//             $data['student_number'],
//             $data['firstname'],
//             $data['lastname'],
//             $data['email'],
//             $hashedPassword,
//             $data['class_id'],
//             $data['phone'] ?? null
//         ]);
        
//         $student_id = $conn->lastInsertId();
        
//         // STEP 3: Insert into tbl_student (new table for compatibility)
//         $insertStudentNew = "INSERT INTO tbl_student 
//                             (firstname, lastname, email, password, student_number, phone, class_id, registration_date, status) 
//                             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'active')";
        
//         $stmt = $conn->prepare($insertStudentNew);
//         $stmt->execute([
//             $data['firstname'],
//             $data['lastname'],
//             $data['email'],
//             $hashedPassword,
//             $data['student_number'],
//             $data['phone'] ?? null,
//             $data['class_id']
//         ]);
        
//         $new_student_id = $conn->lastInsertId();
        
//         // STEP 4: Insert student-subject enrollments
//         // Check if table exists first
//         $checkTable = $conn->query("SHOW TABLES LIKE 'tbl_student_subject'");
//         if ($checkTable->rowCount() > 0) {
//             $insertEnrollment = "INSERT INTO tbl_student_subject (student_id, subject_id, enrollment_date, status) 
//                                 VALUES (?, ?, NOW(), 'active')";
//             $enrollStmt = $conn->prepare($insertEnrollment);
            
//             foreach ($data['subjects'] as $subject_id) {
//                 $enrollStmt->execute([$new_student_id, $subject_id]);
//             }
//         }
        
//         // Commit transaction
//         $conn->commit();
        
//         echo json_encode([
//             'success' => true,
//             'message' => 'Registration successful! You can now login.',
//             'user_id' => $user_id,
//             'student_id' => $student_id
//         ]);
        
//     } catch (Exception $e) {
//         // Rollback transaction on error
//         $conn->rollBack();
//         throw $e;
//     }
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => $e->getMessage()
//     ]);
// }
?>






<?php
// FILE: backend/api/common/register_student_with_subjects.php
// Updated for NEW clean database structure (users, students, classes, subjects, etc.)

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once '../../config/database.php';

try {
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields
    $required_fields = ['firstname', 'lastname', 'email', 'password', 'student_number', 'class_id', 'subjects'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: $field"
            ]);
            exit;
        }
    }
    
    // Validate subjects count (minimum 7)
    if (!is_array($data['subjects']) || count($data['subjects']) < 7) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Please select at least 7 subjects'
        ]);
        exit;
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // ===================================================================
        // STEP 1: Check if email already exists (in users table only!)
        // ===================================================================
        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->execute([$data['email']]);
        if ($checkEmail->fetch()) {
            throw new Exception('This email is already registered. Please use a different email or login.');
        }
        
        // ===================================================================
        // STEP 2: Check if student number already exists
        // ===================================================================
        $checkStudentNum = $conn->prepare("SELECT id FROM students WHERE student_number = ?");
        $checkStudentNum->execute([$data['student_number']]);
        if ($checkStudentNum->fetch()) {
            throw new Exception('This student number is already registered.');
        }
        
        // ===================================================================
        // STEP 3: Hash password
        // ===================================================================
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // ===================================================================
        // STEP 4: Insert into users table (authentication only)
        // ===================================================================
        $insertUser = "INSERT INTO users 
                      (email, password, role, status, created_at) 
                      VALUES (?, ?, 'student', 'active', NOW())";
        
        $stmt = $conn->prepare($insertUser);
        $stmt->execute([
            $data['email'],
            $hashedPassword
        ]);
        
        $user_id = $conn->lastInsertId();
        
        // ===================================================================
        // STEP 5: Insert into students table (student info)
        // ===================================================================
        $insertStudent = "INSERT INTO students 
                         (user_id, student_number, firstname, lastname, phone, class_id, enrollment_date, status, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 'active', NOW())";
        
        $stmt = $conn->prepare($insertStudent);
        $stmt->execute([
            $user_id,
            $data['student_number'],
            $data['firstname'],
            $data['lastname'],
            $data['phone'] ?? null,
            $data['class_id']
        ]);
        
        $student_id = $conn->lastInsertId();
        
        // ===================================================================
        // STEP 6: Insert into class_enrollments (student enrolled in class)
        // ===================================================================
        $insertClassEnrollment = "INSERT INTO class_enrollments 
                                 (student_id, class_id, enrollment_date, status, created_at) 
                                 VALUES (?, ?, CURDATE(), 'active', NOW())";
        
        $stmt = $conn->prepare($insertClassEnrollment);
        $stmt->execute([
            $student_id,
            $data['class_id']
        ]);
        
        // ===================================================================
        // STEP 7: Insert into subject_enrollments (student enrolled in subjects)
        // ===================================================================
        $insertSubjectEnrollment = "INSERT INTO subject_enrollments 
                                   (student_id, subject_id, enrollment_date, status, created_at) 
                                   VALUES (?, ?, CURDATE(), 'active', NOW())";
        
        $enrollStmt = $conn->prepare($insertSubjectEnrollment);
        
        foreach ($data['subjects'] as $subject_id) {
            $enrollStmt->execute([$student_id, $subject_id]);
        }
        
        // ===================================================================
        // COMMIT TRANSACTION
        // ===================================================================
        $conn->commit();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! You can now login.',
            'data' => [
                'user_id' => $user_id,
                'student_id' => $student_id,
                'student_number' => $data['student_number']
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>