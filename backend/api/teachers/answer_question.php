<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/Database.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $question_id = $data['question_id'] ?? null;
    $teacher_id = $data['teacher_id'] ?? null;
    $answer = $data['answer'] ?? null;
    
    if (!$question_id || !$teacher_id || !$answer) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Insert answer
    $query = "INSERT INTO lesson_answers (question_id, teacher_id, answer) 
              VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([$question_id, $teacher_id, $answer]);
    
    if ($result) {
        // Update question status to 'answered'
        $updateQuery = "UPDATE lesson_questions SET status = 'answered' WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute([$question_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Answer posted successfully',
            'answer_id' => $conn->lastInsertId()
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to post answer'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>