<?php
// backend/api/students/get_fees.php
// Updated for Supabase PostgreSQL

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once '../../config/database.php';

try {
    $student_id = $_GET['student_id'] ?? null;
    
    if (!$student_id) {
        throw new Exception('Student ID required');
    }
    
    $db = getDBConnection();
    
    // Get fee structure for student's class
    $feeQuery = "SELECT 
                    COALESCE(fs.total_amount, 0) as total_fees
                 FROM students s
                 LEFT JOIN fee_structures fs ON fs.class_id = s.class_id 
                    AND fs.academic_year = EXTRACT(YEAR FROM CURRENT_DATE)::text
                 WHERE s.id = ?";
    
    $feeStmt = $db->prepare($feeQuery);
    $feeStmt->execute([$student_id]);
    $feeData = $feeStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total paid amount from payments
    $paidQuery = "SELECT COALESCE(SUM(amount), 0) as paid_amount
                  FROM payments
                  WHERE student_id = ? AND status = 'verified'";
    
    $paidStmt = $db->prepare($paidQuery);
    $paidStmt->execute([$student_id]);
    $paidData = $paidStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get payment history
    $historyQuery = "SELECT 
                        TO_CHAR(payment_date, 'YYYY-MM-DD') as date,
                        amount,
                        payment_method as method,
                        status
                     FROM payments
                     WHERE student_id = ?
                     ORDER BY payment_date DESC";
    
    $historyStmt = $db->prepare($historyQuery);
    $historyStmt->execute([$student_id]);
    $paymentHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalFees = floatval($feeData['total_fees'] ?? 0);
    $paidAmount = floatval($paidData['paid_amount'] ?? 0);
    $balance = $totalFees - $paidAmount;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_fees' => $totalFees,
            'paid_amount' => $paidAmount,
            'balance' => $balance,
            'payment_history' => $paymentHistory
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>