<?php
require_once '../../includes/cors.php';
//C:\Users\BR\Desktop\calmtech\php\htdocs\backend\api\teachers\register_student_with_subjects.php
header('Content-Type: application/json');
include_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$required = ['first_name', 'last_name', 'email', 'password', 'student_number', 'class_id', 'subject_ids'];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
        exit();
    }
}

// Validate minimum 7 subjects
if (!is_array($data['subject_ids']) || count($data['subject_ids']) < 7) {
    echo json_encode(['success' => false, 'message' => 'Please select at least 7 subjects']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if email exists
    $checkEmail = $conn->prepare("SELECT id FROM tbl_users WHERE email = :email");
    $checkEmail->execute(['email' => $data['email']]);
    if ($checkEmail->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }
    
    $conn->beginTransaction();
    
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // 1. Insert into tbl_users
    $userQuery = "INSERT INTO tbl_users (email, password, role, first_name, last_name, phone, status) 
                  VALUES (:email, :password, 'student', :first_name, :last_name, :phone, 'active')";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute([
        'email' => $data['email'],
        'password' => $hashed_password,
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'phone' => $data['phone'] ?? null
    ]);
    
    $user_id = $conn->lastInsertId();
    
    // 2. Insert into tbl_students
    $studentQuery = "INSERT INTO tbl_students (user_id, student_number, first_name, last_name, email, password, class_id, phone, status)
                     VALUES (:user_id, :student_number, :first_name, :last_name, :email, :password, :class_id, :phone, 'active')";
    $studentStmt = $conn->prepare($studentQuery);
    $studentStmt->execute([
        'user_id' => $user_id,
        'student_number' => $data['student_number'],
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'email' => $data['email'],
        'password' => $hashed_password,
        'class_id' => $data['class_id'],
        'phone' => $data['phone'] ?? null
    ]);
    
    $student_id = $conn->lastInsertId();
    
    // 3. Enroll in class
    $enrollQuery = "INSERT INTO tbl_class_enrollment (student_id, class_id, enrollment_date, status)
                    VALUES (:student_id, :class_id, CURDATE(), 'active')";
    $enrollStmt = $conn->prepare($enrollQuery);
    $enrollStmt->execute([
        'student_id' => $student_id,
        'class_id' => $data['class_id']
    ]);
    
    // 4. Enroll in subjects
    $subjectQuery = "INSERT INTO tbl_student_subjects (student_id, subject_id, enrollment_date, status)
                     VALUES (:student_id, :subject_id, CURDATE(), 'active')";
    $subjectStmt = $conn->prepare($subjectQuery);
    
    foreach ($data['subject_ids'] as $subject_id) {
        $subjectStmt->execute([
            'student_id' => $student_id,
            'subject_id' => intval($subject_id)
        ]);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Student registered successfully',
        'student_id' => $student_id
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
