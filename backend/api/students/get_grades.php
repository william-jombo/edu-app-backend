<?php
// backend/api/students/get_grades.php
require_once '../../config/database.php';

// ✅ CORS headers
$allowedOrigins      = getenv('ALLOWED_ORIGINS') ?: 'http://localhost:5173';
$allowedOriginsArray = array_map('trim', explode(',', $allowedOrigins));
$origin              = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOriginsArray)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

header('Content-Type: application/json');

// ✅ OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ Get student_id from query param (no session)
$student_id = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;

if (!$student_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'student_id parameter required']);
    exit();
}

try {
    $db = getDBConnection();

    // =========================================================================
    // PART 1 — Assignment grades
    // =========================================================================
    $stmt = $db->prepare("
        SELECT
            asub.id,
            asub.grade                                          AS score,
            a.total_points                                      AS max_score,
            ROUND((asub.grade / a.total_points) * 100, 2)      AS percentage,
            'assignment'                                        AS grade_type,
            asub.feedback                                       AS comments,
            asub.graded_at::date                                AS grade_date,
            s.subject_name,
            s.subject_code,
            s.id                                                AS subject_id,
            a.title                                             AS item_title,
            asub.status
        FROM assignment_submissions asub
        JOIN assignments a ON asub.assignment_id = a.id
        JOIN subjects    s ON a.subject_id = s.id
        WHERE asub.student_id = :student_id
          AND asub.status = 'graded'
          AND asub.grade IS NOT NULL
        ORDER BY asub.graded_at DESC
    ");
    $stmt->execute([':student_id' => $student_id]);
    $assignment_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================================
    // PART 2 — Course grades (quizzes, projects, etc.)
    // =========================================================================
    $stmt = $db->prepare("
        SELECT
            g.id,
            g.score,
            g.max_score,
            g.percentage,
            g.grade_type,
            g.comments,
            g.grade_date,
            s.subject_name,
            s.subject_code,
            g.subject_id,
            NULL   AS item_title,
            'graded' AS status
        FROM grades g
        JOIN subjects s ON g.subject_id = s.id
        WHERE g.student_id = :student_id
        ORDER BY g.grade_date DESC
    ");
    $stmt->execute([':student_id' => $student_id]);
    $course_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================================
    // PART 3 — Exam results
    // =========================================================================
    $stmt = $db->prepare("
        SELECT
            es.id,
            (es.mcq_score + COALESCE(es.written_score, 0))              AS score,
            e.total_marks                                               AS max_score,
            ROUND(((es.mcq_score + COALESCE(es.written_score, 0))::numeric / NULLIF(e.total_marks, 0)) * 100, 2) AS percentage,
            'exam'                                                      AS grade_type,
            NULL                                                        AS comments,
            es.submitted_at::date                                       AS grade_date,
            'General'                                                   AS subject_name,
            ''                                                          AS subject_code,
            NULL                                                        AS subject_id,
            e.title                                                     AS item_title,
            CASE
                WHEN es.grading_status = 'graded' THEN 'graded'
                ELSE 'pending'
            END AS status,
            es.mcq_score,
            es.written_score,
            es.grading_status
        FROM exam_submissions es
        JOIN exams e ON es.exam_id = e.id
        WHERE es.student_id = :student_id
          AND es.grading_status = 'graded'
        ORDER BY es.submitted_at DESC
    ");
    $stmt->execute([':student_id' => $student_id]);
    $exam_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================================
    // MERGE & ADD TITLES
    // =========================================================================
    foreach ($assignment_grades as &$g) {
        $g['title']  = $g['item_title'] . ' (Assignment)';
        $g['source'] = 'assignment';
    }
    unset($g);

    foreach ($course_grades as &$g) {
        $g['title']       = ucfirst($g['grade_type']);
        $g['item_title']  = null;
        $g['source']      = 'course';
    }
    unset($g);

    foreach ($exam_grades as &$g) {
        $g['title']  = $g['item_title'] . ' (Exam)';
        $g['source'] = 'exam';
    }
    unset($g);

    $all_grades = array_merge($assignment_grades, $course_grades, $exam_grades);

    // Sort by date descending
    usort($all_grades, function ($a, $b) {
        return strtotime($b['grade_date']) - strtotime($a['grade_date']);
    });

    // =========================================================================
    // RESPONSE
    // =========================================================================
    echo json_encode([
        'success' => true,
        'grades'  => $all_grades,
        'summary' => [
            'total_grades'      => count($all_grades),
            'assignment_grades' => count($assignment_grades),
            'course_grades'     => count($course_grades),
            'exam_grades'       => count($exam_grades)
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production'
            ? 'Server error.' : $e->getMessage()
    ]);
}
?>