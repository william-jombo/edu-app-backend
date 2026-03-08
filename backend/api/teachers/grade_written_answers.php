<?php
// backend/api/teachers/grade_written_answers.php
require_once __DIR__ . '/cors.php';

try {
    $teacher_id = requireTeacherId();
    $db = getDBConnection();

    $data          = json_decode(file_get_contents('php://input'), true);
    $submission_id = (int) ($data['submission_id'] ?? 0);
    $writtenAnswers = $data['written_answers'] ?? [];

    if (!$submission_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'submission_id is required.']);
        exit();
    }

    // Verify submission belongs to one of this teacher's exams
    $check = $db->prepare("
        SELECT es.id FROM exam_submissions es
        JOIN exams e ON e.id = es.exam_id
        WHERE es.id = :submission_id AND e.teacher_id = :teacher_id
    ");
    $check->execute([':submission_id' => $submission_id, ':teacher_id' => $teacher_id]);
    if (!$check->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Submission not found or access denied.']);
        exit();
    }

    $db->beginTransaction();

    $totalWritten = 0;

    $updateStmt = $db->prepare("
        UPDATE exam_written_answers
        SET marks_obtained    = :marks_obtained,
            teacher_feedback  = :teacher_feedback,
            graded_at         = NOW()
        WHERE id = :id AND submission_id = :submission_id
    ");

    foreach ($writtenAnswers as $wa) {
        $marks = max(0, (int) ($wa['marks_obtained'] ?? 0));
        $updateStmt->execute([
            ':marks_obtained'   => $marks,
            ':teacher_feedback' => $wa['teacher_feedback'] ?? '',
            ':id'               => (int) $wa['id'],
            ':submission_id'    => $submission_id,
        ]);
        $totalWritten += $marks;
    }

    // Update the submission's written_score and mark as graded
    $db->prepare("
        UPDATE exam_submissions
        SET written_score  = :written_score,
            grading_status = 'graded',
            updated_at     = NOW()
        WHERE id = :submission_id
    ")->execute([
        ':written_score'  => $totalWritten,
        ':submission_id'  => $submission_id,
    ]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Grading saved successfully.']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production'
            ? 'Server error.' : $e->getMessage()
    ]);
}