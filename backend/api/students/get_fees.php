<?php

// //C:\Users\BR\Desktop\calmtech\php\htdocs\backend\api\students\get_fees.php
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');
// require_once '../../config/database.php';

// $student_id = $_GET['student_id'] ?? null;

// if (!$student_id) {
//     echo json_encode(['success' => false, 'message' => 'Student ID required']);
//     exit;
// }

// try {
//     $db = new Database();
//     $conn = $db->getConnection();
    
//     // Get fee structure for student's class
//     $feeQuery = "SELECT 
//                     COALESCE(fs.total_amount, 0) as total_fees
//                  FROM tbl_student s
//                  LEFT JOIN tbl_fee_structure fs ON fs.class_id = s.class_id 
//                     AND fs.academic_year = YEAR(CURDATE())
//                  WHERE s.id = ?";
    
//     $feeStmt = $conn->prepare($feeQuery);
//     $feeStmt->execute([$student_id]);
//     $feeData = $feeStmt->fetch(PDO::FETCH_ASSOC);
    
//     // Get total paid amount
//     $paidQuery = "SELECT COALESCE(SUM(amount), 0) as paid_amount
//                   FROM tbl_student_payments
//                   WHERE student_id = ? AND status = 'verified'";
    
//     $paidStmt = $conn->prepare($paidQuery);
//     $paidStmt->execute([$student_id]);
//     $paidData = $paidStmt->fetch(PDO::FETCH_ASSOC);
    
//     // Get payment history
//     $historyQuery = "SELECT 
//                         DATE_FORMAT(payment_date, '%Y-%m-%d') as date,
//                         amount,
//                         payment_method as method,
//                         status
//                      FROM tbl_student_payments
//                      WHERE student_id = ?
//                      ORDER BY payment_date DESC";
    
//     $historyStmt = $conn->prepare($historyQuery);
//     $historyStmt->execute([$student_id]);
//     $paymentHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    
//     $totalFees = $feeData['total_fees'] ?? 0;
//     $paidAmount = $paidData['paid_amount'] ?? 0;
//     $balance = $totalFees - $paidAmount;
    
//     echo json_encode([
//         'success' => true,
//         'data' => [
//             'total_fees' => $totalFees,
//             'paid_amount' => $paidAmount,
//             'balance' => $balance,
//             'payment_history' => $paymentHistory
//         ]
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Error: ' . $e->getMessage()
//     ]);
// }
?>








<?php
// backend/api/students/get_fees.php
// Updated for NEW database structure

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once '../../config/database.php';

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get fee structure for student's class (NEW tables)
    $feeQuery = "SELECT 
                    COALESCE(fs.total_amount, 0) as total_fees
                 FROM students s
                 LEFT JOIN fee_structures fs ON fs.class_id = s.class_id 
                    AND fs.academic_year = YEAR(CURDATE())
                 WHERE s.id = ?";
    
    $feeStmt = $conn->prepare($feeQuery);
    $feeStmt->execute([$student_id]);
    $feeData = $feeStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total paid amount from payments (NEW table)
    $paidQuery = "SELECT COALESCE(SUM(amount), 0) as paid_amount
                  FROM payments
                  WHERE student_id = ? AND status = 'verified'";
    
    $paidStmt = $conn->prepare($paidQuery);
    $paidStmt->execute([$student_id]);
    $paidData = $paidStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get payment history (NEW table)
    $historyQuery = "SELECT 
                        DATE_FORMAT(payment_date, '%Y-%m-%d') as date,
                        amount,
                        payment_method as method,
                        status
                     FROM payments
                     WHERE student_id = ?
                     ORDER BY payment_date DESC";
    
    $historyStmt = $conn->prepare($historyQuery);
    $historyStmt->execute([$student_id]);
    $paymentHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalFees = $feeData['total_fees'] ?? 0;
    $paidAmount = $paidData['paid_amount'] ?? 0;
    $balance = $totalFees - $paidAmount;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_fees' => floatval($totalFees),
            'paid_amount' => floatval($paidAmount),
            'balance' => floatval($balance),
            'payment_history' => $paymentHistory
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>