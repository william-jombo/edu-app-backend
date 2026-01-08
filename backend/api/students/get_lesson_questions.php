<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../../config/Database.php';

$lesson_id = $_GET['lesson_id'] ?? null;
$student_id = $_GET['student_id'] ?? null;

if (!$lesson_id) {
    echo json_encode(['success' => false, 'message' => 'Lesson ID required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get all questions (public + student's own private questions)
    $query = "SELECT 
                lq.id,
                lq.question,
                lq.is_private,
                lq.status,
                lq.created_at,
                CONCAT(s.firstname, ' ', s.lastname) as student_name,
                lq.student_id,
                (SELECT COUNT(*) FROM lesson_answers WHERE question_id = lq.id) as answer_count
              FROM lesson_questions lq
              JOIN students s ON lq.student_id = s.id
              WHERE lq.lesson_id = ?
              AND (lq.is_private = 0 OR lq.student_id = ?)
              ORDER BY lq.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$lesson_id, $student_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each question, get answers
    foreach ($questions as &$question) {
        $ansQuery = "SELECT 
                        la.id,
                        la.answer,
                        la.created_at,
                        CONCAT(t.firstname, ' ', t.lastname) as teacher_name
                     FROM lesson_answers la
                     JOIN teachers t ON la.teacher_id = t.id
                     WHERE la.question_id = ?
                     ORDER BY la.created_at ASC";
        
        $stmt = $conn->prepare($ansQuery);
        $stmt->execute([$question['id']]);
        $question['answers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'questions' => $questions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>