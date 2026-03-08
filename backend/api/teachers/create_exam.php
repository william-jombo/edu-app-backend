<?php
// backend/api/teachers/create_exam.php
require_once __DIR__ . '/cors.php';

try {
    $teacher_id = requireTeacherId();
    $db = getDBConnection();

    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required = ['title', 'duration', 'start_time', 'end_time', 'total_marks', 'passing_marks'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required."]);
            exit();
        }
    }

    if (empty($data['questions']) || !is_array($data['questions'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'At least one question is required.']);
        exit();
    }

    $db->beginTransaction();

    // Insert exam
    $stmt = $db->prepare("
        INSERT INTO exams
            (teacher_id, title, description, duration, start_time, end_time,
             total_marks, passing_marks, status, is_archived, created_at, updated_at)
        VALUES
            (:teacher_id, :title, :description, :duration, :start_time, :end_time,
             :total_marks, :passing_marks, 'active', false, NOW(), NOW())
        RETURNING id
    ");
    $stmt->execute([
        ':teacher_id'    => $teacher_id,
        ':title'         => trim($data['title']),
        ':description'   => trim($data['description'] ?? ''),
        ':duration'      => (int) $data['duration'],
        ':start_time'    => $data['start_time'],
        ':end_time'      => $data['end_time'],
        ':total_marks'   => (int) $data['total_marks'],
        ':passing_marks' => (int) $data['passing_marks'],
    ]);

    $exam_id = (int) $stmt->fetchColumn();

    // Insert questions
    $qStmt = $db->prepare("
        INSERT INTO exam_questions
            (exam_id, question_type, question_text,
             option_a, option_b, option_c, option_d,
             correct_answer, marks, order_number, created_at)
        VALUES
            (:exam_id, :question_type, :question_text,
             :option_a, :option_b, :option_c, :option_d,
             :correct_answer, :marks, :order_number, NOW())
    ");

    foreach ($data['questions'] as $index => $q) {
        $qStmt->execute([
            ':exam_id'       => $exam_id,
            ':question_type' => $q['question_type'] ?? 'mcq',
            ':question_text' => $q['question_text'],
            ':option_a'      => $q['option_a'] ?? null,
            ':option_b'      => $q['option_b'] ?? null,
            ':option_c'      => $q['option_c'] ?? null,
            ':option_d'      => $q['option_d'] ?? null,
            ':correct_answer'=> $q['correct_answer'] ?? null,
            ':marks'         => (int) ($q['marks'] ?? 1),
            ':order_number'  => $index + 1,
        ]);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Exam created successfully.',
        'exam_id' => $exam_id
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production'
            ? 'Server error.' : $e->getMessage()
    ]);
}