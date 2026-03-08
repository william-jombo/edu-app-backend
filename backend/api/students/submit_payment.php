<?php
// backend/api/students/submit_payment.php
// Updated for Supabase PostgreSQL

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $student_id = $data['student_id'] ?? null;
    $amount = $data['amount'] ?? null;
    $payment_method = $data['payment_method'] ?? null;
    $academic_year = $data['academic_year'] ?? date('Y');
    
    if (!$student_id || !$amount || !$payment_method) {
        throw new Exception('Missing required fields');
    }
    
    $db = getDBConnection();
    
    // Insert into payments table
    $query = "INSERT INTO payments 
              (student_id, amount, payment_method, payment_date, academic_year, status)
              VALUES (?, ?, ?, CURRENT_DATE, ?, 'pending')";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id, $amount, $payment_method, $academic_year]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment submitted successfully. Awaiting verification.'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>