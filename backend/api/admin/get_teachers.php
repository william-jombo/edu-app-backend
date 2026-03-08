<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $conn = getDBConnection();
    
    $query = "
        SELECT 
            t.id,
            t.teacher_id,
            t.firstname,
            t.lastname,
            t.phone,
            t.department,
            t.specialization,
            t.hire_date,
            t.status,
            u.email,
            COUNT(DISTINCT ta.subject_id) as subject_count,
            COUNT(DISTINCT ta.class_id) as class_count
        FROM teachers t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN teacher_assignments ta ON t.id = ta.teacher_id AND ta.status = 'active'
        GROUP BY t.id, u.email
        ORDER BY t.firstname, t.lastname
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'teachers' => $teachers
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}