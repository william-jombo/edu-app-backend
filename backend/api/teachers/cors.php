<?php
// backend/api/teachers/cors.php
// require_once this at the TOP of every teacher PHP file

require_once __DIR__ . '/../../config/database.php';

$allowedOrigins      = getenv('ALLOWED_ORIGINS') ?: 'http://localhost:5173';
$allowedOriginsArray = array_map('trim', explode(',', $allowedOrigins));
$origin              = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOriginsArray)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

function requireTeacherId(): int {
    if (!empty($_SESSION['teacher_id'])) {
        return (int) $_SESSION['teacher_id'];
    }
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    if (!empty($body['teacher_id'])) {
        return (int) $body['teacher_id'];
    }
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit();
}