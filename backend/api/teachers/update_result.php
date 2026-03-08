<?php
// backend/api/teachers/update_result.php
require_once __DIR__ . '/cors.php';

try {
    $teacher_id = requireTeacherId();
    $db = getDBConnection();

    $data          = json_decode(file_get_contents('php://input'), true);
    $submission_id = (int) ($data['submission_id'] ?? 0);
    $mcq_score     = (int) ($data['mcq_score']     ?? 0);
    $written_score = (int) ($data['written_score']  ?? 0);

    if (!$submission_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'submission_id is required.']);
        exit();
    }

    // Verify this submission belongs to one of the teacher's exams
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

    $stmt = $db->prepare("
        UPDATE exam_submissions
        SET mcq_score      = :mcq_score,
            written_score  = :written_score,
            grading_status = 'graded',
            updated_at     = NOW()
        WHERE id = :submission_id
    ");
    $stmt->execute([
        ':mcq_score'      => $mcq_score,
        ':written_score'  => $written_score,
        ':submission_id'  => $submission_id,
    ]);

    echo json_encode(['success' => true, 'message' => 'Result updated successfully.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production'
            ? 'Server error.' : $e->getMessage()
    ]);
}