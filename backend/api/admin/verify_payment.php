<?php
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');
// require_once '../../config/database.php';

// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     echo json_encode(['success' => false, 'message' => 'Invalid request method']);
//     exit;
// }

// $data = json_decode(file_get_contents('php://input'), true);

// $payment_id = $data['payment_id'] ?? null;
// $admin_id = $data['admin_id'] ?? null;
// $action = $data['action'] ?? null; // 'verify' or 'reject'
// $receipt_number = $data['receipt_number'] ?? null;
// $notes = $data['notes'] ?? null;

// if (!$payment_id || !$admin_id || !$action) {
//     echo json_encode(['success' => false, 'message' => 'Missing required fields']);
//     exit;
// }

// try {
//     $db = new Database();
//     $conn = $db->getConnection();
    
//     $status = ($action === 'verify') ? 'verified' : 'rejected';
    
//     $query = "UPDATE tbl_student_payments 
//               SET status = ?,
//                   verified_by = ?,
//                   verification_date = NOW(),
//                   receipt_number = ?,
//                   notes = ?
//               WHERE id = ?";
    
//     $stmt = $conn->prepare($query);
//     $stmt->execute([$status, $admin_id, $receipt_number, $notes, $payment_id]);
    
//     echo json_encode([
//         'success' => true,
//         'message' => 'Payment ' . $status . ' successfully'
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Error: ' . $e->getMessage()
//     ]);
// }
?>






<?php
require_once '../../includes/cors.php';
// ============================================================================
// FILE: backend/api/admin/verify_payment.php
// ============================================================================
// header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$payment_id = $data['payment_id'] ?? null;
$admin_id = $data['admin_id'] ?? null; // user_id of admin
$action = $data['action'] ?? null; // 'verify' or 'reject'
$receipt_number = $data['receipt_number'] ?? null;
$notes = $data['notes'] ?? null;

if (!$payment_id || !$admin_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $status = ($action === 'verify') ? 'verified' : 'rejected';
    
    $query = "UPDATE payments 
              SET status = ?,
                  verified_by = ?,
                  verified_at = NOW(),
                  receipt_number = ?,
                  notes = ?
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$status, $admin_id, $receipt_number, $notes, $payment_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment ' . $status . ' successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>