<?php
// //C:\Users\BR\Desktop\calmtech\php\htdocs\backend\api\students\submit_payment.php
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');
// require_once '../../config/database.php';

// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     echo json_encode(['success' => false, 'message' => 'Invalid request method']);
//     exit;
// }

// $data = json_decode(file_get_contents('php://input'), true);

// $student_id = $data['student_id'] ?? null;
// $amount = $data['amount'] ?? null;
// $payment_method = $data['payment_method'] ?? null;
// $academic_year = $data['academic_year'] ?? date('Y');

// if (!$student_id || !$amount || !$payment_method) {
//     echo json_encode(['success' => false, 'message' => 'Missing required fields']);
//     exit;
// }

// try {
//     $db = new Database();
//     $conn = $db->getConnection();
    
//     $query = "INSERT INTO tbl_student_payments 
//               (student_id, amount, payment_method, payment_date, academic_year, status)
//               VALUES (?, ?, ?, NOW(), ?, 'pending')";
    
//     $stmt = $conn->prepare($query);
//     $stmt->execute([$student_id, $amount, $payment_method, $academic_year]);
    
//     echo json_encode([
//         'success' => true,
//         'message' => 'Payment submitted successfully. Awaiting verification.'
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Error: ' . $e->getMessage()
//     ]);
// }
?>





<?php
// backend/api/students/submit_payment.php
// Updated for NEW database structure

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$student_id = $data['student_id'] ?? null;
$amount = $data['amount'] ?? null;
$payment_method = $data['payment_method'] ?? null;
$academic_year = $data['academic_year'] ?? date('Y');

if (!$student_id || !$amount || !$payment_method) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Insert into payments table (NEW)
    $query = "INSERT INTO payments 
              (student_id, amount, payment_method, payment_date, academic_year, status)
              VALUES (?, ?, ?, NOW(), ?, 'pending')";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$student_id, $amount, $payment_method, $academic_year]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment submitted successfully. Awaiting verification.'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>