<?php
// backend/api/teachers/get_archived_exams.php
require_once __DIR__ . '/cors.php';

try {
    $teacher_id = requireTeacherId();
    $db = getDBConnection();

    $stmt = $db->prepare("
        SELECT 
            e.id,
            e.title,
            e.description,
            e.duration,
            e.total_marks,
            e.passing_marks,
            e.archived_at,
            COUNT(DISTINCT es.id) AS submission_count
        FROM exams e
        LEFT JOIN exam_submissions es ON es.exam_id = e.id
        WHERE e.teacher_id = :teacher_id
          AND e.is_archived = true
        GROUP BY e.id
        ORDER BY e.archived_at DESC
    ");
    $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
    $stmt->execute();

    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'exams' => $exams]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production'
            ? 'Server error.' : $e->getMessage()
    ]);
}