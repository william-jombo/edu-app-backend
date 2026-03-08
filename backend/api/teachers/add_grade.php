<?php
// backend/api/teachers/add_grade.php

session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

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

$required_fields = ['student_id', 'subject_id', 'score', 'grade_type'];
$missing = [];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    http_response_code(400);
    echo json_encode([
        'success'        => false,
        'message'        => 'Missing required fields',
        'missing_fields' => $missing
    ]);
    exit();
}

$student_id = (int)$data['student_id'];
$subject_id = (int)$data['subject_id'];
$score      = (float)$data['score'];
$grade_type = htmlspecialchars(trim($data['grade_type']), ENT_QUOTES, 'UTF-8');
$comments   = htmlspecialchars(trim($data['comments'] ?? ''), ENT_QUOTES, 'UTF-8') ?: null;
$max_score  = isset($data['max_score']) ? (float)$data['max_score'] : 100.00;

$valid_grade_types = ['exam','quiz','assignment','project','participation','midterm','final'];
if (!in_array($grade_type, $valid_grade_types, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid grade_type. Must be one of: ' . implode(', ', $valid_grade_types)
    ]);
    exit();
}

if ($score < 0 || $score > $max_score) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Score must be between 0 and {$max_score}"]);
    exit();
}

try {
    $db = getDBConnection();

    // =============================================================================
    // GET STUDENT CLASS
    // =============================================================================

    $stmt = $db->prepare("SELECT class_id FROM students WHERE id = :student_id LIMIT 1");
    $stmt->execute([':student_id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student || !$student['class_id']) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found or has no class assigned']);
        exit();
    }

    $class_id = (int)$student['class_id'];

    // =============================================================================
    // INSERT GRADE
    // =============================================================================

    $stmt = $db->prepare("
        INSERT INTO grades
            (student_id, teacher_id, subject_id, class_id, grade_type,
             score, max_score, comments, grade_date, academic_year,
             created_at, updated_at)
        VALUES
            (:student_id, :teacher_id, :subject_id, :class_id, :grade_type,
             :score, :max_score, :comments, CURRENT_DATE,
             CAST(EXTRACT(YEAR FROM CURRENT_DATE) AS VARCHAR),
             NOW(), NOW())
        RETURNING id
    ");
    $stmt->execute([
        ':student_id' => $student_id,
        ':teacher_id' => $teacher_id,
        ':subject_id' => $subject_id,
        ':class_id'   => $class_id,
        ':grade_type' => $grade_type,
        ':score'      => $score,
        ':max_score'  => $max_score,
        ':comments'   => $comments
    ]);

    $grade_id = $stmt->fetchColumn();

    echo json_encode([
        'success'  => true,
        'message'  => 'Grade added successfully',
        'grade_id' => (int)$grade_id
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>