<?php
// backend/api/teachers/subjects.php

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
    
    // Check authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Unauthorized"
        ]);
        exit;
    }
    
    // Get teacher_id
    $stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Teacher not found"
        ]);
        exit;
    }
    
    $teacher_id = $teacher['id'];
    
    // Get subjects assigned to this teacher
    $stmt = $db->prepare("
        SELECT DISTINCT 
            s.id,
            s.subject_name,
            s.subject_code,
            s.description,
            s.credit_hours
        FROM subjects s
        INNER JOIN teacher_assignments ta ON s.id = ta.subject_id
        WHERE ta.teacher_id = :teacher_id
        AND ta.status = 'active'
        ORDER BY s.subject_name
    ");
    $stmt->execute(['teacher_id' => $teacher_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'subjects' => $subjects
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>