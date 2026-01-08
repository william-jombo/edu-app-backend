<?php
// FILE: backend/api/teachers/delete_lesson.php
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
    
    $lesson_id = $data['lesson_id'] ?? null;
    $teacher_id = $data['teacher_id'] ?? null;
    
    if (!$lesson_id || !$teacher_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get lesson details to delete file
    $query = "SELECT file_path FROM lessons WHERE id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$lesson_id, $teacher_id]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lesson) {
        echo json_encode([
            'success' => false,
            'message' => 'Lesson not found or unauthorized'
        ]);
        exit;
    }
    
    // Delete file if exists
    if ($lesson['file_path']) {
        $file_path = '../../' . $lesson['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Delete lesson from database
    $deleteQuery = "DELETE FROM lessons WHERE id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $result = $stmt->execute([$lesson_id, $teacher_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Lesson deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete lesson'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>