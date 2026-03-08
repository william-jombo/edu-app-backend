<?php
// backend/api/admin/get_all_data.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../../config/database.php';

try {
    $db = getDBConnection();

    $tables = [
        'users',
        'students',
        'teachers',
        'classes',
        'subjects',
        'teacher_assignments',
        'class_enrollments',
        'subject_enrollments',
        'lessons',
        'lesson_views',
        'lesson_questions',
        'lesson_answers',
        'assignments',
        'assignment_submissions',
        'attendance',
        'grades',
        'exams',
        'exam_questions',
        'exam_submissions',
        'exam_written_answers',
        'announcements',
        'announcement_reads',
        'class_posts',
        'class_post_comments',
        'class_post_reads',
        'payments',
        'fee_structures',
    ];

    $result = [];

    foreach ($tables as $table) {
        $stmt = $db->query("SELECT * FROM public.{$table} ORDER BY id DESC LIMIT 100");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result[$table] = [
            'count' => count($rows),
            'data'  => $rows,
        ];
    }

    echo json_encode([
        'success' => true,
        'tables'  => $result,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}