<?php
// backend/api/students/submit_exam.php
require_once '../../config/database.php';

$allowedOrigins      = getenv('ALLOWED_ORIGINS') ?: 'http://localhost:5173';
$allowedOriginsArray = array_map('trim', explode(',', $allowedOrigins));
$origin              = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOriginsArray)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get student_id from session or prop (you can add session logic if needed)
session_start();
$student_id = isset($_SESSION['student_id']) ? (int) $_SESSION['student_id'] : null;

$data = json_decode(file_get_contents('php://input'), true);
$exam_id = isset($data['exam_id']) ? (int) $data['exam_id'] : 0;
$answers = isset($data['answers']) ? $data['answers'] : [];

if (!$exam_id || !$student_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing exam_id or student_id']);
    exit();
}

try {
    $db = getDBConnection();
    $db->beginTransaction();

    // Get exam details
    $examStmt = $db->prepare("SELECT id, total_marks, passing_marks FROM exams WHERE id = :exam_id");
    $examStmt->execute([':exam_id' => $exam_id]);
    $exam = $examStmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Exam not found.']);
        exit();
    }

    // Get all questions
    $qStmt = $db->prepare("
        SELECT id, question_type, correct_answer, marks
        FROM exam_questions
        WHERE exam_id = :exam_id
    ");
    $qStmt->execute([':exam_id' => $exam_id]);
    $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate MCQ score
    $mcq_score = 0;
    $has_written = false;
    foreach ($questions as $q) {
        if ($q['question_type'] === 'mcq' && isset($answers[$q['id']])) {
            if ($answers[$q['id']] === $q['correct_answer']) {
                $mcq_score += (int) $q['marks'];
            }
        } elseif ($q['question_type'] === 'written') {
            $has_written = true;
        }
    }

    // Create submission
    $submissionStmt = $db->prepare("
        INSERT INTO exam_submissions
            (exam_id, student_id, mcq_score, written_score, grading_status, submitted_at, updated_at)
        VALUES
            (:exam_id, :student_id, :mcq_score, 0, :grading_status, NOW(), NOW())
        RETURNING id
    ");
    $grading_status = $has_written ? 'pending' : 'graded';
    $submissionStmt->execute([
        ':exam_id'        => $exam_id,
        ':student_id'     => $student_id,
        ':mcq_score'      => $mcq_score,
        ':grading_status' => $grading_status,
    ]);
    $submission_id = (int) $submissionStmt->fetchColumn();

    // Store written answers
    if ($has_written) {
        $waStmt = $db->prepare("
            INSERT INTO exam_written_answers
                (submission_id, question_id, answer_text, created_at)
            VALUES
                (:submission_id, :question_id, :answer_text, NOW())
        ");
        foreach ($questions as $q) {
            if ($q['question_type'] === 'written' && isset($answers[$q['id']])) {
                $waStmt->execute([
                    ':submission_id' => $submission_id,
                    ':question_id'   => $q['id'],
                    ':answer_text'   => $answers[$q['id']],
                ]);
            }
        }
    }

    $db->commit();

    $total_score = $mcq_score; // written_score added after grading
    $status = $total_score >= $exam['passing_marks'] ? 'passed' : 'failed';

    echo json_encode([
        'success'         => true,
        'message'         => 'Exam submitted successfully.',
        'submission_id'   => $submission_id,
        'mcq_score'       => $mcq_score,
        'score'           => $total_score,
        'total_marks'     => $exam['total_marks'],
        'status'          => $status,
        'pending_grading' => $has_written,
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production' ? 'Server error.' : $e->getMessage()
    ]);
}
?>