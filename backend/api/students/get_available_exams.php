<?php
// backend/api/students/get_available_exams.php
require_once '../../config/database.php';

$allowedOrigins      = getenv('ALLOWED_ORIGINS') ?: 'http://localhost:5173';
$allowedOriginsArray = array_map('trim', explode(',', $allowedOrigins));
$origin              = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOriginsArray)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $db = getDBConnection();

    // Get all active exams (not archived)
    $stmt = $db->prepare("
        SELECT 
            id,
            title,
            description,
            duration,
            start_time,
            end_time,
            total_marks,
            passing_marks,
            status
        FROM exams
        WHERE (is_archived = false OR is_archived IS NULL)
          AND status = 'active'
        ORDER BY start_time DESC
    ");
    $stmt->execute();
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'exams' => $exams]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production' ? 'Server error.' : $e->getMessage()
    ]);
}
?>