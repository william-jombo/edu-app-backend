







<?php
require_once '../../includes/cors.php';
// // FILE: backend/api/teachers/classes.php
// // Get classes taught by teacher with student counts
// require_once '../../config/Database.php';

// header('Content-Type: application/json');

// if (!isset($_GET['teacher_id'])) {
//     echo json_encode(['success' => false, 'message' => 'Teacher ID required']);
//     exit();
// }

// $teacher_id = intval($_GET['teacher_id']);

// try {
//     $database = new Database();
//     $conn = $database->getConnection();
    
//     // Get classes with student counts
//     $query = "SELECT 
//                 tc.id,
//                 tc.class_id as id,
//                 tc.subject_id,
//                 c.classname as class_name,
//                 c.grade_level,
//                 s.subject_name,
//                 tc.schedule,
//                 (SELECT COUNT(DISTINCT student_id) 
//                  FROM tbl_class_enrollment 
//                  WHERE class_id = tc.class_id 
//                  AND status = 'active') as student_count
//               FROM tbl_teacher_classes tc
//               INNER JOIN tbl_class c ON tc.class_id = c.id
//               INNER JOIN tbl_subject s ON tc.subject_id = s.id
//               WHERE tc.teacher_id = :teacher_id
//               ORDER BY c.grade_level, c.classname, s.subject_name";
    
//     $stmt = $conn->prepare($query);
//     $stmt->execute(['teacher_id' => $teacher_id]);
//     $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     echo json_encode([
//         'success' => true,
//         'classes' => $classes
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => $e->getMessage()
//     ]);
// }
?>





<?php
// FILE: backend/api/teachers/classes.php
// Updated for new database structure
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../../config/Database.php';

try {
    if (!isset($_GET['teacher_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher ID is required'
        ]);
        exit;
    }
    
    $teacher_id = $_GET['teacher_id'];
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get classes with subjects from teacher_assignments table (new structure)
    $query = "SELECT 
                c.id,
                c.class_name,
                c.grade_level,
                c.academic_year,
                s.id as subject_id,
                s.subject_name,
                s.subject_code,
                ta.schedule,
                (SELECT COUNT(DISTINCT se.student_id) 
                 FROM subject_enrollments se 
                 WHERE se.subject_id = ta.subject_id 
                 AND se.student_id IN (
                     SELECT st.id FROM students st WHERE st.class_id = c.id
                 )
                 AND se.status = 'active') as student_count
              FROM teacher_assignments ta
              JOIN classes c ON ta.class_id = c.id
              JOIN subjects s ON ta.subject_id = s.id
              WHERE ta.teacher_id = ? 
              AND ta.status = 'active'
              AND c.status = 'active'
              ORDER BY c.class_name, s.subject_name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$teacher_id]);
    
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'classes' => $classes
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>