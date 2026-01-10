<?php
require_once '../../includes/cors.php';
// FILE: backend/api/teachers/get_lessons.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../../config/Database.php';

$teacher_id = $_GET['teacher_id'] ?? null;

if (!$teacher_id) {
    echo json_encode(['success' => false, 'message' => 'Teacher ID required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT 
                l.*,
                s.subject_name,
                s.subject_code,
                c.class_name
              FROM lessons l
              JOIN subjects s ON l.subject_id = s.id
              JOIN classes c ON l.class_id = c.id
              WHERE l.teacher_id = ?
              ORDER BY l.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$teacher_id]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'lessons' => $lessons
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>