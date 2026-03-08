<?php
// FILE: backend/api/teachers/students.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../../config/database.php';

try {
    if (!isset($_GET['class_id']) || !isset($_GET['teacher_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Class ID and Teacher ID are required'
        ]);
        exit;
    }
    
    $class_id = $_GET['class_id'];
    $teacher_id = $_GET['teacher_id'];
    $subject_id = $_GET['subject_id'] ?? null;
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Build the query based on whether subject_id is provided
    if ($subject_id) {
        // When subject is specified, only show students enrolled in that subject
        $query = "SELECT DISTINCT
                    s.id,
                    s.student_number as student_id,
                    s.firstname as first_name,
                    s.lastname as last_name,
                    u.email,
                    s.phone,
                    (SELECT AVG(g.percentage) 
                     FROM grades g 
                     WHERE g.student_id = s.id 
                     AND g.class_id = :grade_class_id
                     AND g.teacher_id = :grade_teacher_id
                     AND g.subject_id = :grade_subject_id
                    ) as current_grade
                  FROM students s
                  JOIN users u ON s.user_id = u.id
                  INNER JOIN subject_enrollments se ON s.id = se.student_id
                  WHERE s.status = 'active'
                  AND s.class_id = :class_id
                  AND se.subject_id = :subject_id
                  AND se.status = 'active'
                  ORDER BY s.lastname, s.firstname";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindParam(':grade_class_id', $class_id, PDO::PARAM_INT);
        $stmt->bindParam(':grade_teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->bindParam(':grade_subject_id', $subject_id, PDO::PARAM_INT);
        
    } else {
        // When no subject specified, show all students in the class
        $query = "SELECT DISTINCT
                    s.id,
                    s.student_number as student_id,
                    s.firstname as first_name,
                    s.lastname as last_name,
                    u.email,
                    s.phone,
                    (SELECT AVG(g.percentage) 
                     FROM grades g 
                     WHERE g.student_id = s.id 
                     AND g.class_id = :grade_class_id
                     AND g.teacher_id = :grade_teacher_id
                    ) as current_grade
                  FROM students s
                  JOIN users u ON s.user_id = u.id
                  WHERE s.status = 'active'
                  AND s.class_id = :class_id
                  ORDER BY s.lastname, s.firstname";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->bindParam(':grade_class_id', $class_id, PDO::PARAM_INT);
        $stmt->bindParam(':grade_teacher_id', $teacher_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Round grades to 2 decimal places
    foreach ($students as &$student) {
        if ($student['current_grade']) {
            $student['current_grade'] = round($student['current_grade'], 2);
        }
    }
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'count' => count($students),
        'debug' => [
            'class_id' => $class_id,
            'teacher_id' => $teacher_id,
            'subject_id' => $subject_id
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>