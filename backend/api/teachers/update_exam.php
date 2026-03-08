<?php
// backend/api/teachers/update_exam.php
require_once __DIR__ . '/cors.php';

try {
    $teacher_id = requireTeacherId();
    $db = getDBConnection();

    $data    = json_decode(file_get_contents('php://input'), true);
    $exam_id = (int) ($data['exam_id'] ?? 0);

    if (!$exam_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'exam_id is required.']);
        exit();
    }

    // Ownership check
    $check = $db->prepare("SELECT id FROM exams WHERE id = :id AND teacher_id = :teacher_id");
    $check->execute([':id' => $exam_id, ':teacher_id' => $teacher_id]);
    if (!$check->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Exam not found or access denied.']);
        exit();
    }

    if (empty($data['questions']) || !is_array($data['questions'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'At least one question is required.']);
        exit();
    }

    $db->beginTransaction();

    // Update exam details
    $stmt = $db->prepare("
        UPDATE exams SET
            title         = :title,
            description   = :description,
            duration      = :duration,
            start_time    = :start_time,
            end_time      = :end_time,
            total_marks   = :total_marks,
            passing_marks = :passing_marks,
            updated_at    = NOW()
        WHERE id = :exam_id AND teacher_id = :teacher_id
    ");
    $stmt->execute([
        ':title'         => trim($data['title']),
        ':description'   => trim($data['description'] ?? ''),
        ':duration'      => (int) $data['duration'],
        ':start_time'    => $data['start_time'],
        ':end_time'      => $data['end_time'],
        ':total_marks'   => (int) $data['total_marks'],
        ':passing_marks' => (int) $data['passing_marks'],
        ':exam_id'       => $exam_id,
        ':teacher_id'    => $teacher_id,
    ]);

    // Replace all questions (delete + re-insert)
    $db->prepare("DELETE FROM exam_questions WHERE exam_id = :exam_id")
       ->execute([':exam_id' => $exam_id]);

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
            ':exam_id'        => $exam_id,
            ':question_type'  => $q['question_type'] ?? 'mcq',
            ':question_text'  => $q['question_text'],
            ':option_a'       => $q['option_a'] ?? null,
            ':option_b'       => $q['option_b'] ?? null,
            ':option_c'       => $q['option_c'] ?? null,
            ':option_d'       => $q['option_d'] ?? null,
            ':correct_answer' => $q['correct_answer'] ?? null,
            ':marks'          => (int) ($q['marks'] ?? 1),
            ':order_number'   => $index + 1,
        ]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Exam updated successfully.']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production'
            ? 'Server error.' : $e->getMessage()
    ]);
}