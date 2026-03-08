<?php
// backend/api/teachers/get_results.php
require_once __DIR__ . '/cors.php';

try {
    $teacher_id = requireTeacherId();
    $db = getDBConnection();

    $stmt = $db->prepare("
        SELECT
            es.id,
            s.firstname || ' ' || s.lastname   AS student_name,
            s.student_number,
            e.title                             AS exam_title,
            es.mcq_score,
            es.written_score,
            (es.mcq_score + COALESCE(es.written_score, 0)) AS score,
            e.total_marks,
            es.grading_status,
            CASE
                WHEN es.grading_status != 'graded' THEN 'pending'
                WHEN (es.mcq_score + COALESCE(es.written_score, 0)) >= e.passing_marks THEN 'passed'
                ELSE 'failed'
            END                                 AS status,
            es.submitted_at
        FROM exam_submissions es
        JOIN exams e      ON e.id      = es.exam_id
        JOIN students s   ON s.id      = es.student_id
        WHERE e.teacher_id = :teacher_id
        ORDER BY es.submitted_at DESC
    ");
    $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'results' => $results]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production'
            ? 'Server error.' : $e->getMessage()
    ]);
}