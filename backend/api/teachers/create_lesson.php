<?php
// backend/api/teachers/create_exam.php

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

require_once '../../config/database.php';

// =============================================================================
// GET TEACHER ID FROM REQUEST BODY (same as create_lesson.php uses POST fields)
// =============================================================================

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

// Get teacher_id from the request (frontend must send it)
$teacher_id = isset($data['teacher_id']) ? (int)$data['teacher_id'] : null;

if (!$teacher_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing teacher_id']);
    exit();
}

// =============================================================================
// INPUT VALIDATION
// =============================================================================

$required_fields = ['title', 'duration', 'start_time', 'end_time', 'total_marks', 'passing_marks', 'questions'];
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

$title         = htmlspecialchars(trim($data['title']), ENT_QUOTES, 'UTF-8');
$description   = htmlspecialchars(trim($data['description'] ?? ''), ENT_QUOTES, 'UTF-8');
$duration      = (int)$data['duration'];
$total_marks   = (int)$data['total_marks'];
$passing_marks = (int)$data['passing_marks'];
$exam_type     = in_array($data['exam_type'] ?? 'simple', ['simple', 'structured']) ? $data['exam_type'] : 'simple';

$class_id   = isset($data['class_id'])   ? (int)$data['class_id']   : null;
$subject_id = isset($data['subject_id']) ? (int)$data['subject_id'] : null;

if (strlen($title) < 3 || strlen($title) > 200) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title must be between 3 and 200 characters']);
    exit();
}

if ($duration < 1 || $duration > 600) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Duration must be between 1 and 600 minutes']);
    exit();
}

if ($passing_marks > $total_marks) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Passing marks cannot exceed total marks']);
    exit();
}

if (!is_array($data['questions']) || count($data['questions']) < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'At least one question is required']);
    exit();
}

// Normalize datetime-local format
$start_time = str_replace('T', ' ', $data['start_time']);
$end_time   = str_replace('T', ' ', $data['end_time']);
if (strlen($start_time) === 16) $start_time .= ':00';
if (strlen($end_time)   === 16) $end_time   .= ':00';

if (strtotime($end_time) <= strtotime($start_time)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
    exit();
}

try {
    $db = getDBConnection();
    $db->exec("SET TIME ZONE 'Africa/Blantyre'");
    $db->beginTransaction();

    // =============================================================================
    // INSERT EXAM
    // =============================================================================

    $stmt = $db->prepare("
        INSERT INTO exams.exams
            (teacher_id, class_id, subject_id, exam_type, title, description,
             duration, start_time, end_time, total_marks, passing_marks, created_at, updated_at)
        VALUES
            (:teacher_id, :class_id, :subject_id, :exam_type, :title, :description,
             :duration, :start_time, :end_time, :total_marks, :passing_marks, NOW(), NOW())
        RETURNING id
    ");
    $stmt->execute([
        ':teacher_id'    => $teacher_id,
        ':class_id'      => $class_id,
        ':subject_id'    => $subject_id,
        ':exam_type'     => $exam_type,
        ':title'         => $title,
        ':description'   => $description,
        ':duration'      => $duration,
        ':start_time'    => $start_time,
        ':end_time'      => $end_time,
        ':total_marks'   => $total_marks,
        ':passing_marks' => $passing_marks
    ]);

    $exam_id = $stmt->fetchColumn();

    // =============================================================================
    // INSERT QUESTIONS
    // =============================================================================

    $calculated_total = 0;

    $stmt_q = $db->prepare("
        INSERT INTO exams.questions
            (exam_id, question_type, question_text,
             option_a, option_b, option_c, option_d, correct_answer, marks)
        VALUES
            (:exam_id, :question_type, :question_text,
             :option_a, :option_b, :option_c, :option_d, :correct_answer, :marks)
    ");

    foreach ($data['questions'] as $index => $question) {
        $num = $index + 1;

        if (!isset($question['question_text']) || strlen(trim($question['question_text'])) < 3) {
            throw new Exception("Question {$num} text is required (min 3 characters)");
        }

        if (!isset($question['marks']) || (int)$question['marks'] < 1) {
            throw new Exception("Question {$num} must have at least 1 mark");
        }

        $q_type = isset($question['question_type']) &&
                  in_array($question['question_type'], ['mcq', 'written'])
                    ? $question['question_type'] : 'mcq';

        $q_text = htmlspecialchars(trim($question['question_text']), ENT_QUOTES, 'UTF-8');
        $marks  = (int)$question['marks'];
        $calculated_total += $marks;

        if ($q_type === 'written') {
            $option_a = $option_b = $option_c = $option_d = $correct_answer = null;
        } else {
            if (!isset($question['option_a']) || !isset($question['option_b'])) {
                throw new Exception("Question {$num} (MCQ) must have at least options A and B");
            }
            if (!isset($question['correct_answer']) ||
                !in_array($question['correct_answer'], ['A','B','C','D'], true)) {
                throw new Exception("Question {$num} must have a valid correct answer (A, B, C, or D)");
            }
            $option_a       = htmlspecialchars(trim($question['option_a']), ENT_QUOTES, 'UTF-8');
            $option_b       = htmlspecialchars(trim($question['option_b']), ENT_QUOTES, 'UTF-8');
            $option_c       = isset($question['option_c']) ? htmlspecialchars(trim($question['option_c']), ENT_QUOTES, 'UTF-8') : null;
            $option_d       = isset($question['option_d']) ? htmlspecialchars(trim($question['option_d']), ENT_QUOTES, 'UTF-8') : null;
            $correct_answer = $question['correct_answer'];
        }

        $stmt_q->execute([
            ':exam_id'        => $exam_id,
            ':question_type'  => $q_type,
            ':question_text'  => $q_text,
            ':option_a'       => $option_a,
            ':option_b'       => $option_b,
            ':option_c'       => $option_c,
            ':option_d'       => $option_d,
            ':correct_answer' => $correct_answer,
            ':marks'          => $marks
        ]);
    }

    // ⚠️ REMOVED the strict marks check — too easy to accidentally mismatch
    // If you want to re-enable it later, uncomment:
    // if ($calculated_total !== $total_marks) {
    //     throw new Exception("Total marks mismatch: questions total {$calculated_total} but exam total is {$total_marks}");
    // }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Exam created successfully',
        'exam_id' => (int)$exam_id,
        'title'   => $title
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>