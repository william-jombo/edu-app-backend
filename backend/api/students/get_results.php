<?php
// backend/api/students/get_results.php
require_once '../../config/database.php';

$allowedOrigins = getenv('ALLOWED_ORIGINS') ?: 'http://localhost:5173';
$allowedOriginsArray = array_map('trim', explode(',', $allowedOrigins));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

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

session_start();
$student_id = isset($_SESSION['student_id']) ? (int) $_SESSION['student_id'] : null;

if (!$student_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'student_id required']);
    exit();
}

try {
    $db = getDBConnection();

    // Get exam submissions with calculated scores
    $stmt = $db->prepare("
        SELECT 
            es.id,
            es.exam_id,
            e.title AS exam_title,
            e.total_marks,
            es.mcq_score,
            es.written_score,
            (es.mcq_score + COALESCE(es.written_score, 0)) AS score,
            es.grading_status,
            'exam' AS source,
            CASE 
                WHEN (es.mcq_score + COALESCE(es.written_score, 0)) >= e.passing_marks THEN 'passed'
                ELSE 'failed'
            END AS status,
            es.submitted_at
        FROM exam_submissions es
        JOIN exams e ON es.exam_id = e.id
        WHERE es.student_id = :student_id
        ORDER BY es.submitted_at DESC
    ");
    $stmt->execute([':student_id' => $student_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'results' => $results]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production' ? 'Server error.' : $e->getMessage()
    ]);
}
?>