<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(0); }

require_once '../../config/database.php';

try {
    $student_id = $_GET['student_id'] ?? null;
    if (!$student_id) throw new Exception('Student ID required');

    $db = getDBConnection();

    // Get student's class_id first
    $stmt = $db->prepare("SELECT class_id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $class_id = $student['class_id'] ?? null;

    // Get subjects — one row per subject, teachers merged into one string
    $stmt = $db->prepare("
        SELECT
            sub.id,
            sub.subject_name,
            sub.subject_code,
            sub.credit_hours,
            sub.description,
            STRING_AGG(DISTINCT CONCAT(t.firstname, ' ', t.lastname), ' & ' ORDER BY CONCAT(t.firstname, ' ', t.lastname)) AS teacher_name,
            ROUND(AVG(g.percentage)::numeric, 1) AS current_grade
        FROM subject_enrollments se
        JOIN subjects sub ON se.subject_id = sub.id
        LEFT JOIN teacher_assignments ta ON ta.subject_id = sub.id AND ta.class_id = :class_id
        LEFT JOIN teachers t ON t.id = ta.teacher_id
        LEFT JOIN grades g ON g.student_id = se.student_id AND g.subject_id = sub.id
        WHERE se.student_id = :student_id AND se.status = 'active'
        GROUP BY sub.id, sub.subject_name, sub.subject_code, sub.credit_hours, sub.description
        ORDER BY sub.subject_name
    ");
    $stmt->execute([
        'student_id' => $student_id,
        'class_id'   => $class_id,
    ]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get class info
    $stmt = $db->prepare("
        SELECT c.id, c.class_name, c.grade_level, c.academic_year, c.semester, ce.enrollment_date
        FROM students s
        JOIN classes c ON s.class_id = c.id
        LEFT JOIN class_enrollments ce ON ce.student_id = s.id AND ce.class_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$student_id]);
    $classInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'   => true,
        'subjects'  => $subjects,
        'classInfo' => $classInfo ?: [],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}