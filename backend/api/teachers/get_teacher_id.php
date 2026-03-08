<?php
//C:\Users\BR\Desktop\calmtech\php\htdocs\edu-app-backend\backend\api\teachers\get_teacher_id.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once '../../config/database.php';

try {
    $user_id = $_GET['user_id'] ?? null;
    
    if (!$user_id) {
        throw new Exception('User ID required');
    }
    
    $db = getDBConnection();
    
    // Get teacher.id from teachers table using user_id
    $query = "SELECT id, teacher_id as teacher_number, firstname, lastname, department 
              FROM teachers 
              WHERE user_id = ? AND status = 'active'";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        throw new Exception('Teacher profile not found');
    }
    
    echo json_encode([
        'success' => true,
        'teacher_id' => $teacher['id'], // This is the teachers.id we need
        'teacher_number' => $teacher['teacher_number'],
        'firstname' => $teacher['firstname'],
        'lastname' => $teacher['lastname'],
        'department' => $teacher['department']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>