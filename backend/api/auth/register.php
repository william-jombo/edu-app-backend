




<?php
// backend/api/common/register_student_with_subjects.php
// Updated for new database structure

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (
    empty($data->firstname) ||
    empty($data->lastname) ||
    empty($data->email) ||
    empty($data->password) ||
    empty($data->student_number) ||
    empty($data->class_id)
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Please fill in all required fields (firstname, lastname, email, password, student_number, class_id)"
    ]);
    exit();
}

// Validate subjects (minimum 7 required)
if (empty($data->subjects) || !is_array($data->subjects) || count($data->subjects) < 7) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Please select at least 7 subjects"
    ]);
    exit();
}

// Check if email already exists
$check_query = "SELECT id FROM users WHERE email = :email";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(":email", $data->email);
$check_stmt->execute();

if ($check_stmt->rowCount() > 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "This email is already registered. Please use a different email or login."
    ]);
    exit();
}

// Check if student number already exists
$check_student_query = "SELECT id FROM students WHERE student_number = :student_number";
$check_student_stmt = $db->prepare($check_student_query);
$check_student_stmt->bindParam(":student_number", $data->student_number);
$check_student_stmt->execute();

if ($check_student_stmt->rowCount() > 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "This student number is already registered."
    ]);
    exit();
}

// Hash password
$hashed_password = password_hash($data->password, PASSWORD_DEFAULT);

try {
    // Start transaction
    $db->beginTransaction();

    // 1. Insert into users table (authentication)
    $user_query = "INSERT INTO users (email, password, role, status) 
                   VALUES (:email, :password, 'student', 'active')";
    
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(":email", $data->email);
    $user_stmt->bindParam(":password", $hashed_password);
    $user_stmt->execute();
    
    $user_id = $db->lastInsertId();

    // 2. Insert into students table (student-specific info)
    $student_query = "INSERT INTO students 
                      (user_id, student_number, firstname, lastname, phone, class_id, enrollment_date, status) 
                      VALUES 
                      (:user_id, :student_number, :firstname, :lastname, :phone, :class_id, CURDATE(), 'active')";
    
    $student_stmt = $db->prepare($student_query);
    $student_stmt->bindParam(":user_id", $user_id);
    $student_stmt->bindParam(":student_number", $data->student_number);
    $student_stmt->bindParam(":firstname", $data->firstname);
    $student_stmt->bindParam(":lastname", $data->lastname);
    $phone = $data->phone ?? null;
    $student_stmt->bindParam(":phone", $phone);
    $student_stmt->bindParam(":class_id", $data->class_id);
    $student_stmt->execute();
    
    $student_id = $db->lastInsertId();

    // 3. Enroll student in class
    $class_enrollment_query = "INSERT INTO class_enrollments 
                               (student_id, class_id, enrollment_date, status) 
                               VALUES 
                               (:student_id, :class_id, CURDATE(), 'active')";
    
    $class_enrollment_stmt = $db->prepare($class_enrollment_query);
    $class_enrollment_stmt->bindParam(":student_id", $student_id);
    $class_enrollment_stmt->bindParam(":class_id", $data->class_id);
    $class_enrollment_stmt->execute();

    // 4. Enroll student in selected subjects
    $subject_enrollment_query = "INSERT INTO subject_enrollments 
                                 (student_id, subject_id, enrollment_date, status) 
                                 VALUES 
                                 (:student_id, :subject_id, CURDATE(), 'active')";
    
    $subject_enrollment_stmt = $db->prepare($subject_enrollment_query);
    
    foreach ($data->subjects as $subject_id) {
        $subject_enrollment_stmt->bindParam(":student_id", $student_id);
        $subject_enrollment_stmt->bindParam(":subject_id", $subject_id);
        $subject_enrollment_stmt->execute();
    }

    // Commit transaction
    $db->commit();

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Registration successful! You can now login.",
        "data" => [
            "user_id" => $user_id,
            "student_id" => $student_id,
            "student_number" => $data->student_number
        ]
    ]);

} catch(PDOException $e) {
    // Rollback on error
    $db->rollBack();
    
    // Check for specific errors
    $error_message = "Registration failed: " . $e->getMessage();
    
    if ($e->getCode() == 23000) {
        // Duplicate entry error
        if (strpos($e->getMessage(), 'email') !== false) {
            $error_message = "This email is already registered.";
        } elseif (strpos($e->getMessage(), 'student_number') !== false) {
            $error_message = "This student number is already in use.";
        } else {
            $error_message = "Duplicate entry detected. Please check your information.";
        }
    }
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $error_message
    ]);
} catch(Exception $e) {
    // Rollback on any other error
    $db->rollBack();
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "An unexpected error occurred: " . $e->getMessage()
    ]);
}
?>