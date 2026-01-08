


<?php
// ============================================================================
// FILE: backend/api/admin/update_student.php
// ============================================================================
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->id) || empty($data->firstname) || empty($data->lastname) || empty($data->email)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Update student query
    $query = "UPDATE students SET 
              firstname = :firstname,
              lastname = :lastname,
              student_number = :student_number,
              phone = :phone,
              class_id = :class_id,
              date_of_birth = :date_of_birth,
              gender = :gender,
              address = :address,
              guardian_name = :guardian_name,
              guardian_phone = :guardian_phone,
              status = :status
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':firstname', $data->firstname);
    $stmt->bindParam(':lastname', $data->lastname);
    $stmt->bindParam(':student_number', $data->student_number);
    $stmt->bindParam(':phone', $data->phone);
    $stmt->bindParam(':class_id', $data->class_id);
    $stmt->bindParam(':date_of_birth', $data->date_of_birth);
    $stmt->bindParam(':gender', $data->gender);
    $stmt->bindParam(':address', $data->address);
    $stmt->bindParam(':guardian_name', $data->guardian_name);
    $stmt->bindParam(':guardian_phone', $data->guardian_phone);
    $stmt->bindParam(':status', $data->status);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update student');
    }
    
    // Get user_id
    $getUserQuery = "SELECT user_id FROM students WHERE id = :student_id";
    $getUserStmt = $db->prepare($getUserQuery);
    $getUserStmt->bindParam(':student_id', $data->id);
    $getUserStmt->execute();
    $student = $getUserStmt->fetch(PDO::FETCH_ASSOC);
    
    // Update users table
    $updateUserQuery = "UPDATE users SET 
                       email = :email,
                       status = :status";
    
    // Add password if provided
    if (!empty($data->password)) {
        $updateUserQuery .= ", password = :password";
    }
    
    $updateUserQuery .= " WHERE id = :user_id";
    
    $userStmt = $db->prepare($updateUserQuery);
    $userStmt->bindParam(':email', $data->email);
    $userStmt->bindParam(':status', $data->status);
    
    if (!empty($data->password)) {
        $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT);
        $userStmt->bindParam(':password', $hashedPassword);
    }
    
    $userStmt->bindParam(':user_id', $student['user_id']);
    $userStmt->execute();
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Student updated successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>