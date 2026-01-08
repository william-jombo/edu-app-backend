<?php
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');
// require_once '../../config/database.php';

// try {
//     $db = new Database();
//     $conn = $db->getConnection();
    
//     $query = "SELECT 
//                 sp.id,
//                 sp.payment_date,
//                 sp.amount,
//                 sp.payment_method,
//                 sp.academic_year,
//                 sp.status,
//                 sp.created_at,
//                 CONCAT(s.firstname, ' ', s.lastname) as student_name,
//                 s.student_number,
//                 s.email as student_email,
//                 c.classname,
//                 c.grade_level
//               FROM tbl_student_payments sp
//               INNER JOIN tbl_student s ON sp.student_id = s.id
//               INNER JOIN tbl_class c ON s.class_id = c.id
//               WHERE sp.status = 'pending'
//               ORDER BY sp.created_at DESC";
    
//     $stmt = $conn->prepare($query);
//     $stmt->execute();
//     $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     echo json_encode([
//         'success' => true,
//         'payments' => $payments
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Error: ' . $e->getMessage()
//     ]);
// }
?>







header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT 
                p.id,
                p.payment_date,
                p.amount,
                p.payment_method,
                p.academic_year,
                p.semester,
                p.transaction_reference,
                p.status,
                p.notes,
                p.created_at,
                CONCAT(s.firstname, ' ', s.lastname) as student_name,
                s.student_number,
                u.email as student_email,
                c.class_name,
                c.grade_level
              FROM payments p
              INNER JOIN students s ON p.student_id = s.id
              INNER JOIN users u ON s.user_id = u.id
              INNER JOIN classes c ON s.class_id = c.id
              WHERE p.status = 'pending'
              ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'payments' => $payments,
        'count' => count($payments)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>