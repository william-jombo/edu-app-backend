


<?php
// //C:\Users\BR\Desktop\calmtech\php\htdocs\backend\api\students\get_enrolled_subjects.php
// header('Content-Type: application/json');

// try {
//     require_once '../../config/database.php';
    
//     $database = new Database();
//     $pdo = $database->getConnection();
    
//     $student_id = $_GET['student_id'] ?? null;
    
//     if (!$student_id) {
//         throw new Exception('Student ID required');
//     }
    
//     // Query BOTH subject tables and combine results
//     $stmt = $pdo->prepare("
//         SELECT DISTINCT
//             sub.id,
//             sub.subject_name,
//             sub.subject_code,
//             sub.credit_hours,
//             sub.description,
//             t.firstname as teacher_name,
//             AVG(g.score) as current_grade
//         FROM (
//             -- Get subjects from tbl_student_subjects
//             SELECT student_id, subject_id, enrollment_date, status
//             FROM tbl_student_subjects
//             WHERE student_id = ? AND status = 'active'
            
//             UNION
            
//             -- Get subjects from tbl_student_subject
//             SELECT student_id, subject_id, enrollment_date, status
//             FROM tbl_student_subject
//             WHERE student_id = ? AND status = 'active'
//         ) as ss
//         JOIN tbl_subject sub ON ss.subject_id = sub.id
//         LEFT JOIN tbl_teacher_classes tc ON tc.subject_id = sub.id
//         LEFT JOIN tbl_teacher t ON tc.teacher_id = t.id
//         LEFT JOIN tbl_grades g ON g.student_id = ss.student_id AND g.subject_id = sub.id
//         GROUP BY sub.id, sub.subject_name, sub.subject_code, sub.credit_hours, sub.description, t.firstname
//         ORDER BY sub.subject_name
//     ");
    
//     $stmt->execute([$student_id, $student_id]);
//     $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     // Get class info
//     $stmt = $pdo->prepare("
//         SELECT c.*, ce.enrollment_date
//         FROM tbl_students s
//         JOIN tbl_class c ON s.class_id = c.id
//         LEFT JOIN tbl_class_enrollment ce ON ce.student_id = s.id AND ce.class_id = c.id
//         WHERE s.id = ?
//     ");
//     $stmt->execute([$student_id]);
//     $classInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
//     echo json_encode([
//         'success' => true,
//         'subjects' => $subjects,
//         'classInfo' => $classInfo ?: []
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => $e->getMessage()
//     ]);
// }
?>








<?php
// backend/api/students/get_enrolled_subjects.php
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
    
    // Get enrolled subjects from subject_enrollments (NEW table)
    $stmt = $pdo->prepare("
        SELECT 
            sub.id,
            sub.subject_name,
            sub.subject_code,
            sub.credit_hours,
            sub.description,
            CONCAT(t.firstname, ' ', t.lastname) as teacher_name,
            AVG(g.score) as current_grade
        FROM subject_enrollments se
        JOIN subjects sub ON se.subject_id = sub.id
        LEFT JOIN teacher_assignments ta ON ta.subject_id = sub.id
        LEFT JOIN teachers t ON ta.teacher_id = t.id
        LEFT JOIN grades g ON g.student_id = se.student_id AND g.subject_id = sub.id
        WHERE se.student_id = ? AND se.status = 'active'
        GROUP BY sub.id, sub.subject_name, sub.subject_code, sub.credit_hours, sub.description, t.firstname, t.lastname
        ORDER BY sub.subject_name
    ");
    
    $stmt->execute([$student_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get class info from students and classes (NEW)
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.class_name,
            c.grade_level,
            c.academic_year,
            c.semester,
            ce.enrollment_date
        FROM students s
        JOIN classes c ON s.class_id = c.id
        LEFT JOIN class_enrollments ce ON ce.student_id = s.id AND ce.class_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$student_id]);
    $classInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'subjects' => $subjects,
        'classInfo' => $classInfo ?: []
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>