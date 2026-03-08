<?php
// backend/api/students/export_exam.php
// Same as get_exam_review.php but returns data formatted for PDF export
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

session_start();
$student_id = isset($_SESSION['student_id']) ? (int) $_SESSION['student_id'] : null;
$exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : 0;

if (!$exam_id || !$student_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing exam_id or student_id']);
    exit();
}

try {
    $db = getDBConnection();

    // Get submission
    $subStmt = $db->prepare("
        SELECT id FROM exam_submissions
        WHERE exam_id = :exam_id AND student_id = :student_id
    ");
    $subStmt->execute([':exam_id' => $exam_id, ':student_id' => $student_id]);
    $submission = $subStmt->fetch(PDO::FETCH_ASSOC);

    if (!$submission) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Submission not found.']);
        exit();
    }

    $submission_id = $submission['id'];

    // Get questions with correct answers
    $qStmt = $db->prepare("
        SELECT 
            id,
            question_type,
            question_text,
            option_a,
            option_b,
            option_c,
            option_d,
            correct_answer,
            marks
        FROM exam_questions
        WHERE exam_id = :exam_id
        ORDER BY order_number ASC, id ASC
    ");
    $qStmt->execute([':exam_id' => $exam_id]);
    $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get answers
    $waStmt = $db->prepare("
        SELECT 
            question_id,
            answer_text,
            marks_obtained,
            teacher_feedback
        FROM exam_written_answers
        WHERE submission_id = :submission_id
    ");
    $waStmt->execute([':submission_id' => $submission_id]);
    $answers = [];
    while ($row = $waStmt->fetch(PDO::FETCH_ASSOC)) {
        $answers[$row['question_id']] = $row;
    }

    // Merge for export
    foreach ($questions as &$q) {
        $ans = $answers[$q['id']] ?? null;
        $q['student_answer']   = $ans ? $ans['answer_text'] : null;
        $q['marks_obtained']   = $ans ? $ans['marks_obtained'] : null;
        $q['teacher_feedback'] = $ans ? $ans['teacher_feedback'] : null;
    }
    unset($q);

    echo json_encode(['success' => true, 'questions' => $questions]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production' ? 'Server error.' : $e->getMessage()
    ]);
}
?>