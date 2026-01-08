


<?php
// //C:\Users\BR\Desktop\calmtech\php\htdocs\backend\api\students\get_assignments.php
// header('Content-Type: application/json');

// try {
//     require_once '../../config/database.php';
    
//     $database = new Database();
//     $pdo = $database->getConnection();
    
//     $student_id = $_GET['student_id'] ?? null;
    
//     if (!$student_id) {
//         throw new Exception('Student ID required');
//     }
    
//     // Get student's class_id
//     $stmt = $pdo->prepare("SELECT class_id FROM tbl_students WHERE id = ?");
//     $stmt->execute([$student_id]);
//     $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
//     if (!$student) {
//         throw new Exception('Student not found');
//     }
    
//     $class_id = $student['class_id'];
    
//     // Get all assignments for student's class with submission status
//     $stmt = $pdo->prepare("
//         SELECT 
//             a.*,
//             s.subject_name,
//             sub.id as submission_id,
//             sub.submission_file,
//             sub.submitted_date,
//             sub.grade as your_score,
//             sub.feedback as teacher_feedback,
//             CASE 
//                 WHEN sub.grade IS NOT NULL THEN 'graded'
//                 WHEN sub.id IS NOT NULL THEN 'submitted'
//                 ELSE 'pending'
//             END as status
//         FROM tbl_assignments a
//         JOIN tbl_subject s ON a.subject_id = s.id
//         LEFT JOIN tbl_assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
//         WHERE a.class_id = ? AND a.status = 'active'
//         ORDER BY a.due_date DESC
//     ");
    
//     $stmt->execute([$student_id, $class_id]);
//     $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     echo json_encode([
//         'success' => true,
//         'assignments' => $assignments
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => $e->getMessage()
//     ]);
// }
?>




<?php
// backend/api/students/get_assignments.php
// Updated for NEW database structure

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../../config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    $student_id = $_GET['student_id'] ?? null;
    
    if (!$student_id) {
        throw new Exception('Student ID required');
    }
    
    // Get student's class_id from students table (NEW)
    $stmt = $pdo->prepare("SELECT class_id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        throw new Exception('Student not found');
    }
    
    $class_id = $student['class_id'];
    
    // Get all assignments for student's class with submission status (NEW tables)
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            s.subject_name,
            sub.id as submission_id,
            sub.submission_file,
            sub.submitted_at as submitted_date,
            sub.grade as your_score,
            sub.feedback as teacher_feedback,
            CASE 
                WHEN sub.grade IS NOT NULL THEN 'graded'
                WHEN sub.id IS NOT NULL THEN 'submitted'
                ELSE 'pending'
            END as status,
            CASE 
                WHEN sub.grade IS NOT NULL THEN ROUND((sub.grade / a.total_points) * 100, 1)
                ELSE NULL
            END as grade_percentage
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
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
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>