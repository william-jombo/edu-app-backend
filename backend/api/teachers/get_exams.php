<?php
// backend/api/teachers/get_exams.php
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
            e.start_time,
            e.end_time,
            e.total_marks,
            e.passing_marks,
            e.status,
            e.created_at,
            COUNT(DISTINCT eq.id)  AS question_count,
            COUNT(DISTINCT es.id)  AS submission_count
        FROM exams e
        LEFT JOIN exam_questions eq ON eq.exam_id = e.id
        LEFT JOIN exam_submissions es ON es.exam_id = e.id
        WHERE e.teacher_id = :teacher_id
          AND (e.is_archived = false OR e.is_archived IS NULL)
        GROUP BY e.id
        ORDER BY e.created_at DESC
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