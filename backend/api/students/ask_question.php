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
    
    $lesson_id = $data['lesson_id'] ?? null;
    $student_id = $data['student_id'] ?? null;
    $question = $data['question'] ?? null;
    $is_private = $data['is_private'] ?? false;
    
    if (!$lesson_id || !$student_id || !$question) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "INSERT INTO lesson_questions (lesson_id, student_id, question, is_private, status) 
              VALUES (?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([$lesson_id, $student_id, $question, $is_private ? 1 : 0]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Question posted successfully',
            'question_id' => $conn->lastInsertId()
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to post question'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>