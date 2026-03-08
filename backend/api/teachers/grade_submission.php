<?php
// backend/api/teachers/grade_submission.php

// CORS headers
$allowed_origins = [
    'https://edu-app-rose.vercel.app',
    'http://localhost:5173',
    'http://localhost:3000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit(0);
}

// Session configuration
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
session_start();

require_once '../../config/database.php';

// =============================================================================
// AUTHENTICATION
// =============================================================================

if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// =============================================================================
// INPUT VALIDATION
// =============================================================================

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

if (!isset($data['submission_id']) || !isset($data['grade'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: submission_id, grade']);
    exit();
}

$submission_id = (int)$data['submission_id'];
$grade         = (float)$data['grade'];
$feedback      = htmlspecialchars(trim($data['feedback'] ?? ''), ENT_QUOTES, 'UTF-8');

if ($grade < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Grade cannot be negative']);
    exit();
}

try {
    $db = getDBConnection();

    // =============================================================================
    // VERIFY SUBMISSION EXISTS AND BELONGS TO THIS TEACHER'S ASSIGNMENTS
    // =============================================================================

    $stmt = $db->prepare("
        SELECT asub.id, asub.student_id, a.teacher_id
        FROM assignment_submissions asub
        JOIN assignments a ON asub.assignment_id = a.id
        WHERE asub.id = :submission_id
        LIMIT 1
    ");
    $stmt->execute([':submission_id' => $submission_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Submission not found']);
        exit();
    }

    if ((int)$existing['teacher_id'] !== $teacher_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to grade this submission']);
        exit();
    }

    // =============================================================================
    // UPDATE GRADE
    // =============================================================================

    $stmt = $db->prepare("
        UPDATE assignment_submissions
        SET grade      = :grade,
            feedback   = :feedback,
            status     = 'graded',
            graded_at  = NOW(),
            graded_by  = :teacher_id,
            updated_at = NOW()
        WHERE id = :submission_id
    ");
    $result = $stmt->execute([
        ':grade'         => $grade,
        ':feedback'      => $feedback,
        ':teacher_id'    => $teacher_id,
        ':submission_id' => $submission_id
    ]);

    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success'       => true,
            'message'       => 'Submission graded successfully',
            'submission_id' => $submission_id,
            'grade'         => $grade
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Failed to grade submission — no rows updated']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>