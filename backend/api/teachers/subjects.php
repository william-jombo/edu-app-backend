
<?php
require_once '../../includes/cors.php';
// // FILE: backend/api/teachers/subjects.php
// // Get subjects taught by teacher from tbl_teacher_classes
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
    
//     // Get subjects this teacher teaches
//     $query = "SELECT DISTINCT 
//                 s.id,
//                 s.subject_name,
//                 s.subject_code,
//                 s.description,
//                 COUNT(DISTINCT tc.class_id) as class_count
//               FROM tbl_teacher_classes tc
//               INNER JOIN tbl_subject s ON tc.subject_id = s.id
//               WHERE tc.teacher_id = :teacher_id
//               GROUP BY s.id, s.subject_name, s.subject_code, s.description
//               ORDER BY s.subject_name";
    
//     $stmt = $conn->prepare($query);
//     $stmt->execute(['teacher_id' => $teacher_id]);
//     $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     echo json_encode([
//         'success' => true,
//         'subjects' => $subjects
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => $e->getMessage()
//     ]);
// }
?>








<?php
// FILE: backend/api/teachers/subjects.php
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
    
    // Get subjects from teacher_assignments table (new structure)
    $query = "SELECT DISTINCT 
                s.id,
                s.subject_name,
                s.subject_code,
                s.description,
                s.credit_hours
              FROM teacher_assignments ta
              JOIN subjects s ON ta.subject_id = s.id
              WHERE ta.teacher_id = ? 
              AND ta.status = 'active'
              ORDER BY s.subject_name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$teacher_id]);
    
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'subjects' => $subjects
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>