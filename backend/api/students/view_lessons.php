<?php
// backend/api/students/view_lessons.php
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
    $student_id = $_GET['student_id'] ?? null;
    
    if (!$student_id) {
        throw new Exception('Student ID required');
    }
    
    $db = getDBConnection();
    
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
    
    $stmt = $db->prepare($query);
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
    
    $stmt = $db->prepare($subjectsQuery);
    $stmt->execute([$student_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'lessons' => $lessons,
        'subjects' => $subjects
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>