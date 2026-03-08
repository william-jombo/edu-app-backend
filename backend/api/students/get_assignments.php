<?php
// backend/api/students/get_assignments.php
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
    
    // Get student's class_id
    $stmt = $db->prepare("SELECT class_id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        throw new Exception('Student not found');
    }
    
    $class_id = $student['class_id'];
    
    // Get all assignments for student's class with submission status
    $stmt = $db->prepare("
        SELECT 
            a.*,
            s.subject_name,
            asub.id as submission_id,
            asub.submission_file,
            asub.submitted_at as submitted_date,
            asub.grade as your_score,
            asub.feedback as teacher_feedback,
            CASE 
                WHEN asub.grade IS NOT NULL THEN 'graded'
                WHEN asub.id IS NOT NULL THEN 'submitted'
                ELSE 'pending'
            END as status,
            CASE 
                WHEN asub.grade IS NOT NULL THEN ROUND((asub.grade / a.total_points) * 100, 1)
                ELSE NULL
            END as grade_percentage
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
        WHERE a.class_id = ? AND a.status = 'active'
        ORDER BY a.due_date DESC
    ");
    
    $stmt->execute([$student_id, $class_id]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'assignments' => $assignments
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>