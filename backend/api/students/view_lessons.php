<?php
// FILE: backend/api/students/view_lessons.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../../config/Database.php';

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get lessons for student's class and enrolled subjects
    $query = "SELECT 
                l.*,
                s.subject_name,
                s.subject_code,
                c.class_name,
                CONCAT(t.firstname, ' ', t.lastname) as teacher_name,
                lv.viewed_at,
                lv.completed
              FROM lessons l
              JOIN subjects s ON l.subject_id = s.id
              JOIN classes c ON l.class_id = c.id
              JOIN teachers t ON l.teacher_id = t.id
              JOIN students st ON st.class_id = l.class_id
              LEFT JOIN subject_enrollments se ON se.student_id = st.id AND se.subject_id = l.subject_id
              LEFT JOIN lesson_views lv ON lv.lesson_id = l.id AND lv.student_id = st.id
              WHERE st.id = ?
              AND l.status = 'published'
              AND (se.status = 'active' OR se.id IS NULL)
              ORDER BY l.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$student_id]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique subjects for filter
    $subjectsQuery = "SELECT DISTINCT s.id, s.subject_name 
                      FROM subjects s
                      JOIN lessons l ON l.subject_id = s.id
                      JOIN students st ON st.class_id = l.class_id
                      WHERE st.id = ?
                      AND l.status = 'published'
                      ORDER BY s.subject_name";
    
    $stmt = $conn->prepare($subjectsQuery);
    $stmt->execute([$student_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'lessons' => $lessons,
        'subjects' => $subjects
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>