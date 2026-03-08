<?php

// backend/api/teachers/classes.php

$environment = getenv('ENVIRONMENT') ?: 'development';
if ($environment === 'production') {
    ini_set('session.cookie_samesite', 'None');
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_httponly', '1');
}

session_start();
header('Content-Type: application/json');

try {
    require_once '../../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Teacher not found']);
        exit;
    }
    
    $teacher_id = $teacher['id'];
    
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.class_name,
            c.grade_level,
            c.academic_year,
            c.semester,
            ta.subject_id,
            s.subject_name,
            s.subject_code,
            ta.schedule,
            COUNT(DISTINCT se.student_id) as student_count
        FROM classes c
        INNER JOIN teacher_assignments ta ON c.id = ta.class_id
        INNER JOIN subjects s ON ta.subject_id = s.id
        LEFT JOIN subject_enrollments se ON se.subject_id = ta.subject_id
            AND se.status = 'active'
            AND se.student_id IN (
                SELECT id FROM students 
                WHERE class_id = c.id 
                AND status = 'active'
            )
        WHERE ta.teacher_id = :teacher_id
        AND ta.status = 'active'
        GROUP BY c.id, c.class_name, c.grade_level, c.academic_year, 
                 c.semester, ta.subject_id, s.subject_name, s.subject_code, ta.schedule
        ORDER BY c.class_name, s.subject_name
    ");
    $stmt->execute(['teacher_id' => $teacher_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'classes' => $classes
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}


?>