


<?php
// //C:\Users\BR\Desktop\calmtech\php\htdocs\backend\api\teachers\stats.php
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
    
//     // Subjects count
//     $stmt = $conn->prepare("SELECT COUNT(DISTINCT subject_id) as count FROM tbl_teacher_classes WHERE teacher_id = ?");
//     $stmt->execute([$teacher_id]);
//     $subjects_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
//     // Classes count
//     $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_teacher_classes WHERE teacher_id = ?");
//     $stmt->execute([$teacher_id]);
//     $classes_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
//     // Students count - FIXED
//     $stmt = $conn->prepare("
//         SELECT COUNT(DISTINCT ce.student_id) as count 
//         FROM tbl_class_enrollment ce
//         INNER JOIN tbl_teacher_classes tc ON ce.class_id = tc.class_id
//         WHERE tc.teacher_id = ?
//         AND ce.status = 'active'
//     ");
//     $stmt->execute([$teacher_id]);
//     $students_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
//     echo json_encode([
//         'success' => true,
//         'stats' => [
//             'subjects' => (int)$subjects_count,
//             'classes' => (int)$classes_count,
//             'students' => (int)$students_count
//         ]
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode(['success' => false, 'message' => $e->getMessage()]);
// }
?>








<?php
// FILE: backend/api/teachers/stats.php
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
    
    // Count distinct subjects
    $subjectsQuery = "SELECT COUNT(DISTINCT subject_id) as count 
                      FROM teacher_assignments 
                      WHERE teacher_id = ? AND status = 'active'";
    $stmt = $conn->prepare($subjectsQuery);
    $stmt->execute([$teacher_id]);
    $subjectsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count distinct classes
    $classesQuery = "SELECT COUNT(DISTINCT class_id) as count 
                     FROM teacher_assignments 
                     WHERE teacher_id = ? AND status = 'active'";
    $stmt = $conn->prepare($classesQuery);
    $stmt->execute([$teacher_id]);
    $classesCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count total students across all classes
    $studentsQuery = "SELECT COUNT(DISTINCT s.id) as count
                      FROM students s
                      WHERE s.class_id IN (
                          SELECT DISTINCT class_id 
                          FROM teacher_assignments 
                          WHERE teacher_id = ? AND status = 'active'
                      )
                      AND s.status = 'active'";
    $stmt = $conn->prepare($studentsQuery);
    $stmt->execute([$teacher_id]);
    $studentsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'subjects' => (int)$subjectsCount,
            'classes' => (int)$classesCount,
            'students' => (int)$studentsCount
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>