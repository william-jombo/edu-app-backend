<?php
// backend/api/common/register_student_with_subjects.php
// Updated for Supabase PostgreSQL

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once '../../config/database.php';

try {
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
    
    $db = getDBConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Check if email already exists
        $checkEmail = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->execute([$data['email']]);
        if ($checkEmail->fetch()) {
            throw new Exception('This email is already registered. Please use a different email or login.');
        }
        
        // Check if student number already exists
        $checkStudentNum = $db->prepare("SELECT id FROM students WHERE student_number = ?");
        $checkStudentNum->execute([$data['student_number']]);
        if ($checkStudentNum->fetch()) {
            throw new Exception('This student number is already registered.');
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // 1. Insert into users table
        $insertUser = "INSERT INTO users 
                      (email, password, role, status, created_at) 
                      VALUES (?, ?, 'student', 'active', CURRENT_TIMESTAMP)";
        
        $stmt = $db->prepare($insertUser);
        $stmt->execute([$data['email'], $hashedPassword]);
        $user_id = $db->lastInsertId();
        
        // 2. Insert into students table
        $insertStudent = "INSERT INTO students 
                         (user_id, student_number, firstname, lastname, phone, class_id, enrollment_date, status, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE, 'active', CURRENT_TIMESTAMP)";
        
        $stmt = $db->prepare($insertStudent);
        $stmt->execute([
            $user_id,
            $data['student_number'],
            $data['firstname'],
            $data['lastname'],
            $data['phone'] ?? null,
            $data['class_id']
        ]);
        $student_id = $db->lastInsertId();
        
        // 3. Insert into class_enrollments
        $insertClassEnrollment = "INSERT INTO class_enrollments 
                                 (student_id, class_id, enrollment_date, status, created_at) 
                                 VALUES (?, ?, CURRENT_DATE, 'active', CURRENT_TIMESTAMP)";
        
        $stmt = $db->prepare($insertClassEnrollment);
        $stmt->execute([$student_id, $data['class_id']]);
        
        // 4. Insert into subject_enrollments
        $insertSubjectEnrollment = "INSERT INTO subject_enrollments 
                                   (student_id, subject_id, enrollment_date, status, created_at) 
                                   VALUES (?, ?, CURRENT_DATE, 'active', CURRENT_TIMESTAMP)";
        
        $enrollStmt = $db->prepare($insertSubjectEnrollment);
        foreach ($data['subjects'] as $subject_id) {
            $enrollStmt->execute([$student_id, $subject_id]);
        }
        
        // Commit transaction
        $db->commit();
        
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
        $db->rollBack();
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