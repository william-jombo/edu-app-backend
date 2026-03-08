<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['email']) || empty($data['student_number'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email and student number are required']);
        exit;
    }

    $db = getDBConnection();

    // Verify email + student_number match
    $stmt = $db->prepare("
        SELECT s.id AS student_id, u.id AS user_id
        FROM students s
        INNER JOIN users u ON u.id = s.user_id
        WHERE u.email = ? AND s.student_number = ? AND u.status = 'active'
    ");
    $stmt->execute([$data['email'], $data['student_number']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No account found with that email and student number']);
        exit;
    }

    // Generate a secure token and store it
    $token     = bin2hex(random_bytes(32));
    // $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    // // Store token in users table (reuse a spare column or add one)
    // // We'll store in a simple way: update a reset_token + reset_token_expires column
    // // If those columns don't exist yet, the catch below will surface it clearly
    // $stmt = $db->prepare("
    //     UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?
    // ");
    // $stmt->execute([$token, $expiresAt, $student['user_id']]);


$stmt = $db->prepare("
    UPDATE users SET reset_token = ?, reset_token_expires = NOW() + INTERVAL '30 minutes' WHERE id = ?
");
$stmt->execute([$token, $student['user_id']]);
    

    echo json_encode(['success' => true, 'reset_token' => $token]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}