<?php
// FILE: backend/api/students/mark_lesson_viewed.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/Database.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $student_id = $data['student_id'] ?? null;
    $lesson_id = $data['lesson_id'] ?? null;
    
    if (!$student_id || !$lesson_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Insert or update view record
    $query = "INSERT INTO lesson_views (lesson_id, student_id, viewed_at) 
              VALUES (?, ?, NOW())
              ON DUPLICATE KEY UPDATE viewed_at = NOW()";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([$lesson_id, $student_id]);
    
    // Increment view count
    $updateQuery = "UPDATE lessons SET view_count = view_count + 1 WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([$lesson_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Lesson marked as viewed'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to mark lesson as viewed'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>