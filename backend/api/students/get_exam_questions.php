<?php
// // backend/api/students/get_exam_questions.php

// session_start();
// require_once '../../config/database.php';

// header('Content-Type: application/json');

// // =============================================================================
// // AUTHENTICATION — uses edu-app session
// // =============================================================================

// if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit();
// }

// $student_id = $_SESSION['student_id'];

// // =============================================================================
// // INPUT VALIDATION
// // =============================================================================

// $exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// if ($exam_id <= 0) {
//     http_response_code(400);
//     echo json_encode(['success' => false, 'message' => 'Invalid exam ID']);
//     exit();
// }

// try {
//     $db = getDBConnection();

//     $db->exec("SET TIME ZONE 'Africa/Blantyre'");

//     // =============================================================================
//     // AUTHORIZATION — exam must be active, not archived, not already taken
//     // =============================================================================

//     $stmt = $db->prepare("
//         SELECT e.*, es.id AS has_taken
//         FROM exams.exams e
//         LEFT JOIN exams.exam_submissions es
//                ON e.id = es.exam_id AND es.student_id = :student_id
//         WHERE e.id = :exam_id
//           AND e.start_time <= NOW()
//           AND e.end_time   >= NOW()
//           AND e.is_archived = FALSE
//         LIMIT 1
//     ");
//     $stmt->execute([':student_id' => $student_id, ':exam_id' => $exam_id]);
//     $exam = $stmt->fetch(PDO::FETCH_ASSOC);

//     if (!$exam) {
//         http_response_code(403);
//         echo json_encode(['success' => false, 'message' => 'Exam not found or not currently available']);
//         exit();
//     }

//     if ($exam['has_taken']) {
//         http_response_code(403);
//         echo json_encode(['success' => false, 'message' => 'You have already taken this exam']);
//         exit();
//     }

//     // =============================================================================
//     // HELPER — sanitize a single question row (never send correct_answer to student)
//     // =============================================================================

//     $formatQuestion = function(array $q): array {
//         return [
//             'id'            => (int)$q['id'],
//             'question_type' => $q['question_type'],
//             'question_text' => htmlspecialchars($q['question_text'], ENT_QUOTES, 'UTF-8'),
//             'option_a'      => htmlspecialchars($q['option_a'] ?? '', ENT_QUOTES, 'UTF-8'),
//             'option_b'      => htmlspecialchars($q['option_b'] ?? '', ENT_QUOTES, 'UTF-8'),
//             'option_c'      => htmlspecialchars($q['option_c'] ?? '', ENT_QUOTES, 'UTF-8'),
//             'option_d'      => htmlspecialchars($q['option_d'] ?? '', ENT_QUOTES, 'UTF-8'),
//             'marks'         => (int)$q['marks']
//             // correct_answer intentionally excluded
//         ];
//     };

//     $exam_meta = [
//         'id'            => (int)$exam['id'],
//         'title'         => htmlspecialchars($exam['title'], ENT_QUOTES, 'UTF-8'),
//         'description'   => htmlspecialchars($exam['description'] ?? '', ENT_QUOTES, 'UTF-8'),
//         'duration'      => (int)$exam['duration'],
//         'total_marks'   => (int)$exam['total_marks'],
//         'passing_marks' => (int)$exam['passing_marks']
//     ];

//     // =============================================================================
//     // STRUCTURED EXAM — sections with questions
//     // =============================================================================

//     if ($exam['exam_type'] === 'structured') {

//         $stmt = $db->prepare("
//             SELECT id, title, description, display_order, time_limit
//             FROM exams.exam_sections
//             WHERE exam_id = :exam_id
//             ORDER BY display_order ASC
//         ");
//         $stmt->execute([':exam_id' => $exam_id]);
//         $raw_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         $sections = [];
//         foreach ($raw_sections as $section) {

//             $stmt2 = $db->prepare("
//                 SELECT id, question_type, question_text,
//                        option_a, option_b, option_c, option_d, marks
//                 FROM exams.questions
//                 WHERE exam_id = :exam_id AND section_id = :section_id
//                 ORDER BY id ASC
//             ");
//             $stmt2->execute([':exam_id' => $exam_id, ':section_id' => $section['id']]);
//             $raw_questions = $stmt2->fetchAll(PDO::FETCH_ASSOC);

//             $sections[] = [
//                 'id'            => (int)$section['id'],
//                 'title'         => htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8'),
//                 'description'   => htmlspecialchars($section['description'] ?? '', ENT_QUOTES, 'UTF-8'),
//                 'display_order' => (int)$section['display_order'],
//                 'time_limit'    => $section['time_limit'] ? (int)$section['time_limit'] : null,
//                 'questions'     => array_map($formatQuestion, $raw_questions)
//             ];
//         }

//         echo json_encode([
//             'success'   => true,
//             'message'   => 'Exam questions retrieved',
//             'exam_type' => 'structured',
//             'exam'      => $exam_meta,
//             'sections'  => $sections
//         ]);

//     // =============================================================================
//     // SIMPLE EXAM — flat question list
//     // =============================================================================

//     } else {

//         $stmt = $db->prepare("
//             SELECT id, question_type, question_text,
//                    option_a, option_b, option_c, option_d, marks
//             FROM exams.questions
//             WHERE exam_id = :exam_id
//             ORDER BY id ASC
//         ");
//         $stmt->execute([':exam_id' => $exam_id]);
//         $raw_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         echo json_encode([
//             'success'   => true,
//             'message'   => 'Exam questions retrieved',
//             'exam_type' => 'simple',
//             'exam'      => $exam_meta,
//             'questions' => array_map($formatQuestion, $raw_questions)
//         ]);
//     }

// } catch (Exception $e) {
//     http_response_code(500);
//     echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
// }
?>
<?php
// backend/api/students/get_exam_questions.php
require_once '../../config/database.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$exam_id = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : 0;

if (!$exam_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'exam_id parameter required']);
    exit();
}

try {
    $db = getDBConnection();

    // Verify exam exists and is active
    $examCheck = $db->prepare("
        SELECT id, title, start_time, end_time
        FROM exams
        WHERE id = :exam_id AND status = 'active'
    ");
    $examCheck->execute([':exam_id' => $exam_id]);
    $exam = $examCheck->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Exam not found or not available.']);
        exit();
    }

    // ✅ FIX: Use database NOW() for timezone-safe comparison
    $timeCheck = $db->prepare("
        SELECT 
            CASE 
                WHEN NOW() < :start_time THEN 'not_started'
                WHEN NOW() > :end_time THEN 'ended'
                ELSE 'active'
            END AS time_status
    ");
    $timeCheck->execute([
        ':start_time' => $exam['start_time'],
        ':end_time'   => $exam['end_time']
    ]);
    $timeStatus = $timeCheck->fetchColumn();

    if ($timeStatus === 'not_started') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Exam has not started yet.',
            'start_time' => $exam['start_time'],
            'current_time' => date('Y-m-d H:i:s')
        ]);
        exit();
    }

    if ($timeStatus === 'ended') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Exam has ended.',
            'end_time' => $exam['end_time'],
            'current_time' => date('Y-m-d H:i:s')
        ]);
        exit();
    }

    // Get questions (without showing correct answers)
    $stmt = $db->prepare("
        SELECT 
            id,
            question_type,
            question_text,
            option_a,
            option_b,
            option_c,
            option_d,
            marks,
            order_number
        FROM exam_questions
        WHERE exam_id = :exam_id
        ORDER BY order_number ASC, id ASC
    ");
    $stmt->execute([':exam_id' => $exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'questions' => $questions]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => getenv('ENVIRONMENT') === 'production' ? 'Server error.' : $e->getMessage()
    ]);
}
?>