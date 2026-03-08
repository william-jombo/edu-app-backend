<?php
// backend/api/students/mark_lesson_viewed.php
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
    
    $student_id = $data['student_id'] ?? null;
    $lesson_id = $data['lesson_id'] ?? null;
    
    if (!$student_id || !$lesson_id) {
        throw new Exception('Missing required fields');
    }
    
    $db = getDBConnection();
    
    // Check if view record exists
    $checkQuery = "SELECT id FROM lesson_views WHERE lesson_id = ? AND student_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$lesson_id, $student_id]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing view
        $query = "UPDATE lesson_views SET viewed_at = CURRENT_TIMESTAMP WHERE lesson_id = ? AND student_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$lesson_id, $student_id]);
    } else {
        // Insert new view record
        $query = "INSERT INTO lesson_views (lesson_id, student_id, viewed_at) VALUES (?, ?, CURRENT_TIMESTAMP)";
        $stmt = $db->prepare($query);
        $stmt->execute([$lesson_id, $student_id]);
    }
    
    // Increment view count
    $updateQuery = "UPDATE lessons SET view_count = view_count + 1 WHERE id = ?";
    $stmt = $db->prepare($updateQuery);
    $stmt->execute([$lesson_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Lesson marked as viewed'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>