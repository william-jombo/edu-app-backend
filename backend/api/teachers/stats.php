<?php
// backend/api/teachers/stats.php

// IMPORTANT: Configure session BEFORE starting it
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
    
    // Check if user is logged in as teacher
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Unauthorized - Please login as teacher"
        ]);
        exit;
    }
    
    // Get teacher record using user_id from session
    $stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Teacher record not found"
        ]);
        exit;
    }
    
    $teacher_id = $teacher['id'];
    
    // Initialize stats
    $stats = [
        'subjects' => 0,
        'classes' => 0,
        'students' => 0,
        'pending_assignments' => 0
    ];
    
    // Count distinct subjects
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT subject_id) as count 
        FROM teacher_assignments 
        WHERE teacher_id = :teacher_id 
        AND status = 'active'
    ");
    $stmt->execute(['teacher_id' => $teacher_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['subjects'] = (int)($result['count'] ?? 0);
    
    // Count distinct classes
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT class_id) as count 
        FROM teacher_assignments 
        WHERE teacher_id = :teacher_id 
        AND status = 'active'
    ");
    $stmt->execute(['teacher_id' => $teacher_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['classes'] = (int)($result['count'] ?? 0);
    
    // Count students in teacher's classes using class_enrollments
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT ce.student_id) as count
        FROM class_enrollments ce
        INNER JOIN teacher_assignments ta ON ce.class_id = ta.class_id
        WHERE ta.teacher_id = :teacher_id
        AND ta.status = 'active'
        AND ce.status = 'active'
    ");
    $stmt->execute(['teacher_id' => $teacher_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['students'] = (int)($result['count'] ?? 0);
    
    // Count pending assignments (ungraded submissions)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM assignment_submissions asub
        INNER JOIN assignments a ON asub.assignment_id = a.id
        WHERE a.teacher_id = :teacher_id 
        AND asub.grade IS NULL
        AND asub.status = 'submitted'
    ");
    $stmt->execute(['teacher_id' => $teacher_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['pending_assignments'] = (int)($result['count'] ?? 0);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>