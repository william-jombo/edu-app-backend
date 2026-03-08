<?php
// backend/api/students/get_student_id.php
// Updated for Supabase PostgreSQL

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
    
    // Get student_id from students table using user_id
    $stmt = $db->prepare("SELECT id as student_id FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'student_id' => $result['student_id']
        ]);
    } else {
        throw new Exception('Student record not found for this user');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>