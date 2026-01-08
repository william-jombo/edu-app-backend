<?php
// header('Content-Type: application/json');
// header('Access-Control-Allow-Origin: *');
// include_once '../../config/database.php';

// $database = new Database();
// $db = $database->getConnection();

// try {
//     // Get total students (active only)
//     $students_query = "SELECT COUNT(*) as total FROM tbl_users WHERE role = 'student' AND status = 'active'";
//     $students_stmt = $db->prepare($students_query);
//     $students_stmt->execute();
//     $totalStudents = $students_stmt->fetch(PDO::FETCH_ASSOC)['total'];

//     // Get withdrawn students
//     $withdrawn_query = "SELECT COUNT(*) as total FROM tbl_users WHERE role = 'student' AND status = 'withdrawn'";
//     $withdrawn_stmt = $db->prepare($withdrawn_query);
//     $withdrawn_stmt->execute();
//     $withdrawnStudents = $withdrawn_stmt->fetch(PDO::FETCH_ASSOC)['total'];

//     // Get total teachers
//     $teachers_query = "SELECT COUNT(*) as total FROM tbl_users WHERE role = 'teacher' AND status = 'active'";
//     $teachers_stmt = $db->prepare($teachers_query);
//     $teachers_stmt->execute();
//     $totalTeachers = $teachers_stmt->fetch(PDO::FETCH_ASSOC)['total'];

//     // Get total classes
//     $classes_query = "SELECT COUNT(*) as total FROM tbl_class";
//     $classes_stmt = $db->prepare($classes_query);
//     $classes_stmt->execute();
//     $totalClasses = $classes_stmt->fetch(PDO::FETCH_ASSOC)['total'];

//     // Financial stats (placeholder - we'll build this properly)
//     $totalRevenue = 0;
//     $unpaidFees = 0;

//     echo json_encode([
//         "success" => true,
//         "stats" => [
//             "totalStudents" => (int)$totalStudents,
//             "totalTeachers" => (int)$totalTeachers,
//             "totalClasses" => (int)$totalClasses,
//             "withdrawnStudents" => (int)$withdrawnStudents,
//             "totalRevenue" => $totalRevenue,
//             "unpaidFees" => $unpaidFees
//         ]
//     ]);

// } catch(Exception $e) {
//     http_response_code(500);
//     echo json_encode([
//         "success" => false,
//         "message" => "Error: " . $e->getMessage()
//     ]);
// }
?>





<?php
// ============================================================================
// FILE: backend/api/admin/get_dashboard_stats.php
// ============================================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Get total active students
    $students_query = "SELECT COUNT(*) as total FROM students WHERE status = 'active'";
    $students_stmt = $db->prepare($students_query);
    $students_stmt->execute();
    $totalStudents = $students_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get withdrawn students
    $withdrawn_query = "SELECT COUNT(*) as total FROM students WHERE status = 'withdrawn'";
    $withdrawn_stmt = $db->prepare($withdrawn_query);
    $withdrawn_stmt->execute();
    $withdrawnStudents = $withdrawn_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get total active teachers
    $teachers_query = "SELECT COUNT(*) as total FROM teachers WHERE status = 'active'";
    $teachers_stmt = $db->prepare($teachers_query);
    $teachers_stmt->execute();
    $totalTeachers = $teachers_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get total active classes
    $classes_query = "SELECT COUNT(*) as total FROM classes WHERE status = 'active'";
    $classes_stmt = $db->prepare($classes_query);
    $classes_stmt->execute();
    $totalClasses = $classes_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get total revenue from verified payments
    $revenue_query = "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'verified'";
    $revenue_stmt = $db->prepare($revenue_query);
    $revenue_stmt->execute();
    $totalRevenue = $revenue_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate unpaid fees
    $unpaid_query = "SELECT 
                        COALESCE(SUM(fs.total_amount), 0) - COALESCE(SUM(p.amount), 0) as unpaid
                     FROM students s
                     LEFT JOIN classes c ON s.class_id = c.id
                     LEFT JOIN fee_structures fs ON fs.class_id = c.id AND fs.academic_year = YEAR(CURDATE())
                     LEFT JOIN payments p ON p.student_id = s.id AND p.status = 'verified'
                     WHERE s.status = 'active'";
    $unpaid_stmt = $db->prepare($unpaid_query);
    $unpaid_stmt->execute();
    $unpaidFees = $unpaid_stmt->fetch(PDO::FETCH_ASSOC)['unpaid'];

    echo json_encode([
        "success" => true,
        "stats" => [
            "totalStudents" => (int)$totalStudents,
            "totalTeachers" => (int)$totalTeachers,
            "totalClasses" => (int)$totalClasses,
            "withdrawnStudents" => (int)$withdrawnStudents,
            "totalRevenue" => (float)$totalRevenue,
            "unpaidFees" => (float)$unpaidFees
        ]
    ]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>