<?php
// backend/api/teachers/delete_exam.php
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

    $db->beginTransaction();

    // Delete in order: written answers → submissions → questions → exam
    $db->prepare("
        DELETE FROM exam_written_answers
        WHERE submission_id IN (
            SELECT id FROM exam_submissions WHERE exam_id = :exam_id
        )
    ")->execute([':exam_id' => $exam_id]);

    $db->prepare("DELETE FROM exam_submissions WHERE exam_id = :exam_id")
       ->execute([':exam_id' => $exam_id]);

    $db->prepare("DELETE FROM exam_questions WHERE exam_id = :exam_id")
       ->execute([':exam_id' => $exam_id]);

    $db->prepare("DELETE FROM exams WHERE id = :exam_id AND teacher_id = :teacher_id")
       ->execute([':exam_id' => $exam_id, ':teacher_id' => $teacher_id]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Exam deleted successfully.']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production'
            ? 'Server error.' : $e->getMessage()
    ]);
}