<?php
// backend/api/teachers/get_pending_grading.php
require_once __DIR__ . '/cors.php';

try {
    $teacher_id = requireTeacherId();
    $db = getDBConnection();

    // Get submissions that still need written-answer grading
    $stmt = $db->prepare("
        SELECT
            es.id                                AS result_id,
            s.firstname || ' ' || s.lastname     AS student_name,
            e.title                              AS exam_title,
            es.mcq_score,
            es.submitted_at,
            SUM(CASE WHEN eq.question_type = 'mcq' THEN eq.marks ELSE 0 END)     AS total_mcq_marks,
            SUM(CASE WHEN eq.question_type = 'written' THEN eq.marks ELSE 0 END) AS total_written_marks,
            COUNT(CASE WHEN eq.question_type = 'written' THEN 1 END)             AS written_count
        FROM exam_submissions es
        JOIN exams e       ON e.id    = es.exam_id
        JOIN students s    ON s.id    = es.student_id
        JOIN exam_questions eq ON eq.exam_id = e.id
        WHERE e.teacher_id    = :teacher_id
          AND es.grading_status = 'pending'
        GROUP BY es.id, s.firstname, s.lastname, e.title, es.mcq_score, es.submitted_at
        ORDER BY es.submitted_at ASC
    ");
    $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
    $stmt->execute();

    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For each submission, load the written answers
    $waStmt = $db->prepare("
        SELECT
            wa.id,
            wa.question_id,
            eq.question_text,
            wa.answer_text,
            eq.marks             AS max_marks,
            wa.marks_obtained,
            wa.teacher_feedback
        FROM exam_written_answers wa
        JOIN exam_questions eq ON eq.id = wa.question_id
        WHERE wa.submission_id = :submission_id
        ORDER BY eq.order_number ASC
    ");

    foreach ($submissions as &$sub) {
        $waStmt->execute([':submission_id' => $sub['result_id']]);
        $sub['written_answers'] = $waStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($sub);

    echo json_encode(['success' => true, 'submissions' => $submissions]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production'
            ? 'Server error.' : $e->getMessage()
    ]);
}