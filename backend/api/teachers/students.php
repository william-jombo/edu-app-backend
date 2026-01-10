

<?php
// // FILE: backend/api/teachers/students.php
// // FINAL VERSION - Handles NULL statuses correctly
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');

// require_once '../../config/Database.php';

// try {
//     $database = new Database();
//     $conn = $database->getConnection();
    
//     $class_id = $_GET['class_id'] ?? null;
    
//     if (!$class_id) {
//         echo json_encode(['success' => false, 'message' => 'Class ID required']);
//         exit;
//     }
    
//     // Get students enrolled in this class
//     // IMPORTANT: Accept NULL status as valid (some students have NULL status)
//     $query = "
//         SELECT 
//             s.id,
//             s.first_name,
//             s.last_name,
//             s.email,
//             s.student_number as student_id,
//             s.phone,
//             s.class_id,
//             ce.status as enrollment_status,
//             s.status as student_status,
//             AVG(g.score) as current_grade
//         FROM tbl_students s
//         INNER JOIN tbl_class_enrollment ce ON s.id = ce.student_id
//         LEFT JOIN tbl_grades g ON s.id = g.student_id
//         WHERE ce.class_id = ?
//         AND ce.status = 'active'
//         AND (s.status = 'active' OR s.status IS NULL OR s.status = '')
//         GROUP BY s.id, s.first_name, s.last_name, s.email, s.student_number, s.phone, s.class_id, ce.status, s.status
//         ORDER BY s.last_name, s.first_name
//     ";
    
//     $stmt = $conn->prepare($query);
//     $stmt->execute([$class_id]);
//     $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     // Format grades
//     foreach ($students as &$student) {
//         $student['current_grade'] = $student['current_grade'] 
//             ? round($student['current_grade'], 2) 
//             : null;
//         $student['is_enrolled'] = true;
//     }
    
//     echo json_encode([
//         'success' => true,
//         'students' => $students,
//         'count' => count($students)
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Error: ' . $e->getMessage()
//     ]);
// }
?>






<?php
// FILE: backend/api/teachers/students.php
// Updated for new database structure
require_once '../../includes/cors.php';
require_once '../../config/Database.php';

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

    $database = new Database();
    $conn = $database->getConnection();
    
    // Get students in the class (new structure)
    $query = "SELECT 
                s.id,
                s.student_number as student_id,
                s.firstname as first_name,
                s.lastname as last_name,
                u.email,
                s.phone,
                (SELECT AVG(g.percentage) 
                 FROM grades g 
                 WHERE g.student_id = s.id 
                 AND g.class_id = ?
                 AND g.teacher_id = ?) as current_grade
              FROM students s
              JOIN users u ON s.user_id = u.id
              WHERE s.class_id = ?
              AND s.status = 'active'
              ORDER BY s.lastname, s.firstname";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$class_id, $teacher_id, $class_id]);
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Round grades to 2 decimal places
    foreach ($students as &$student) {
        if ($student['current_grade']) {
            $student['current_grade'] = round($student['current_grade'], 2);
        }
    }
    
    echo json_encode([
        'success' => true,
        'students' => $students
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>