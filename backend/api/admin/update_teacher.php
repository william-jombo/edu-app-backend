<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->id) || empty($data->firstname) || empty($data->lastname) || empty($data->email)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $conn = getDBConnection();
    $conn->beginTransaction();
    
    // Update teachers table
    $stmt = $conn->prepare("
        UPDATE teachers SET 
            firstname = :firstname,
            lastname = :lastname,
            phone = :phone,
            department = :department,
            specialization = :specialization,
            status = :status,
            updated_at = NOW()
        WHERE id = :id
    ");
    
    $stmt->execute([
        'id' => $data->id,
        'firstname' => $data->firstname,
        'lastname' => $data->lastname,
        'phone' => $data->phone,
        'department' => $data->department,
        'specialization' => $data->specialization,
        'status' => $data->status
    ]);
    
    // Get user_id
    $getUserStmt = $conn->prepare("SELECT user_id FROM teachers WHERE id = :teacher_id");
    $getUserStmt->execute(['teacher_id' => $data->id]);
    $teacher = $getUserStmt->fetch(PDO::FETCH_ASSOC);
    
    // Update users table
    $updateUserQuery = "UPDATE users SET email = :email, status = :status";
    if (!empty($data->password)) {
        $updateUserQuery .= ", password = :password";
    }
    $updateUserQuery .= ", updated_at = NOW() WHERE id = :user_id";
    
    $userStmt = $conn->prepare($updateUserQuery);
    $params = [
        'email' => $data->email,
        'status' => $data->status,
        'user_id' => $teacher['user_id']
    ];
    
    if (!empty($data->password)) {
        $params['password'] = password_hash($data->password, PASSWORD_DEFAULT);
    }
    
    $userStmt->execute($params);
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Teacher updated successfully']);
    
} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}