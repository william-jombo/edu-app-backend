<?php
// backend/api/teachers/get_exam_details.php
require_once __DIR__ . '/cors.php';

try {
    $teacher_id = requireTeacherId();
    $db = getDBConnection();

    $exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : 0;
    if (!$exam_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'exam_id is required.']);
        exit();
    }

    // Fetch exam — verify ownership
    $stmt = $db->prepare("
        SELECT id, title, description, duration, start_time, end_time,
               total_marks, passing_marks, status, is_archived
        FROM exams
        WHERE id = :exam_id AND teacher_id = :teacher_id
    ");
    $stmt->bindParam(':exam_id',    $exam_id,    PDO::PARAM_INT);
    $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
    $stmt->execute();

    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$exam) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Exam not found.']);
        exit();
    }

    // Fetch questions
    $qStmt = $db->prepare("
        SELECT id, question_type, question_text,
               option_a, option_b, option_c, option_d,
               correct_answer, marks, order_number
        FROM exam_questions
        WHERE exam_id = :exam_id
        ORDER BY order_number ASC, id ASC
    ");
    $qStmt->bindParam(':exam_id', $exam_id, PDO::PARAM_INT);
    $qStmt->execute();
    $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'   => true,
        'exam'      => $exam,
        'questions' => $questions
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production'
            ? 'Server error.' : $e->getMessage()
    ]);
}