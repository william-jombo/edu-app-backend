<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../../config/Database.php';

$teacher_id = $_GET['teacher_id'] ?? null;
$lesson_id = $_GET['lesson_id'] ?? null;

if (!$teacher_id) {
    echo json_encode(['success' => false, 'message' => 'Teacher ID required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Build query based on whether specific lesson or all lessons
    if ($lesson_id) {
        $query = "SELECT 
                    lq.id,
                    lq.lesson_id,
                    lq.question,
                    lq.is_private,
                    lq.status,
                    lq.created_at,
                    l.title as lesson_title,
                    CONCAT(s.firstname, ' ', s.lastname) as student_name,
                    s.student_number,
                    (SELECT COUNT(*) FROM lesson_answers WHERE question_id = lq.id) as answer_count
                  FROM lesson_questions lq
                  JOIN lessons l ON lq.lesson_id = l.id
                  JOIN students s ON lq.student_id = s.id
                  WHERE l.teacher_id = ? AND lq.lesson_id = ?
                  ORDER BY lq.created_at DESC";
        $params = [$teacher_id, $lesson_id];
    } else {
        $query = "SELECT 
                    lq.id,
                    lq.lesson_id,
                    lq.question,
                    lq.is_private,
                    lq.status,
                    lq.created_at,
                    l.title as lesson_title,
                    CONCAT(s.firstname, ' ', s.lastname) as student_name,
                    s.student_number,
                    (SELECT COUNT(*) FROM lesson_answers WHERE question_id = lq.id) as answer_count
                  FROM lesson_questions lq
                  JOIN lessons l ON lq.lesson_id = l.id
                  JOIN students s ON lq.student_id = s.id
                  WHERE l.teacher_id = ?
                  ORDER BY lq.created_at DESC";
        $params = [$teacher_id];
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each question, get existing answers
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