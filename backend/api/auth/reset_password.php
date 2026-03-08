<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['reset_token']) || empty($data['new_password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token and new password are required']);
        exit;
    }

    if (strlen($data['new_password']) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit;
    }

    $db = getDBConnection();

    // Validate token and expiry
    $stmt = $db->prepare("
        SELECT id FROM users
        WHERE reset_token = ? AND reset_token_expires > NOW()
    ");
    $stmt->execute([$data['reset_token']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Reset link is invalid or has expired. Please try again.']);
        exit;
    }

    // Update password and clear token
    $stmt = $db->prepare("
        UPDATE users
        SET password = ?, reset_token = NULL, reset_token_expires = NULL, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([password_hash($data['new_password'], PASSWORD_DEFAULT), $user['id']]);

    echo json_encode(['success' => true, 'message' => 'Password reset successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}