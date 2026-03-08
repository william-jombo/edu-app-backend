<?php
// backend/api/teachers/archive_exam.php
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

    // Ownership check + get current archive status
    $check = $db->prepare("
        SELECT id, is_archived FROM exams
        WHERE id = :id AND teacher_id = :teacher_id
    ");
    $check->execute([':id' => $exam_id, ':teacher_id' => $teacher_id]);
    $exam = $check->fetch();

    if (!$exam) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Exam not found or access denied.']);
        exit();
    }

    $currentlyArchived = (bool) $exam['is_archived'];
    $newArchived       = !$currentlyArchived;   // toggle
    $archivedAt        = $newArchived ? 'NOW()' : 'NULL';

    $stmt = $db->prepare("
        UPDATE exams
        SET is_archived = :is_archived,
            archived_at = " . ($newArchived ? 'NOW()' : 'NULL') . ",
            updated_at  = NOW()
        WHERE id = :exam_id AND teacher_id = :teacher_id
    ");
    $stmt->execute([
        ':is_archived' => $newArchived ? 'true' : 'false',
        ':exam_id'     => $exam_id,
        ':teacher_id'  => $teacher_id,
    ]);

    $action  = $newArchived ? 'archived' : 'unarchived';
    $message = "Exam $action successfully.";

    echo json_encode(['success' => true, 'message' => $message, 'is_archived' => $newArchived]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production'
            ? 'Server error.' : $e->getMessage()
    ]);
}