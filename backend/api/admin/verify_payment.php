<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$payment_id = $data['payment_id'] ?? null;
$admin_id = $data['admin_id'] ?? null;
$action = $data['action'] ?? null;
$receipt_number = $data['receipt_number'] ?? null;
$notes = $data['notes'] ?? null;

if (!$payment_id || !$admin_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $conn = getDBConnection();
    
    $status = ($action === 'verify') ? 'verified' : 'rejected';
    
    $stmt = $conn->prepare("
        UPDATE payments 
        SET status = ?, verified_by = ?, verified_at = NOW(), receipt_number = ?, notes = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$status, $admin_id, $receipt_number, $notes, $payment_id]);
    
    echo json_encode(['success' => true, 'message' => 'Payment ' . $status . ' successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}