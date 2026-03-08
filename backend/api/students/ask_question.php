<?php
// backend/api/students/ask_question.php
// ULTRA-DEFENSIVE version with extensive error handling

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Enable ALL error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../../config/database.php';

// Start output buffering to catch any stray output
ob_start();

try {
    // Read raw input
    $raw_input = file_get_contents("php://input");
    
    // Log raw input
    error_log("=== ASK QUESTION REQUEST ===");
    error_log("Raw input: " . $raw_input);
    
    // Parse JSON
    $data = json_decode($raw_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    error_log("Parsed data: " . print_r($data, true));
    
    // Extract fields
    $lesson_id = isset($data['lesson_id']) ? (int)$data['lesson_id'] : null;
    $student_id = isset($data['student_id']) ? (int)$data['student_id'] : null;
    $question = isset($data['question']) ? trim($data['question']) : null;
    
    // THE CRITICAL PART: Handle is_private
    // Default to false if not provided or if it's null
    $is_private = false;
    
    if (isset($data['is_private'])) {
        $raw_is_private = $data['is_private'];
        error_log("Raw is_private: " . var_export($raw_is_private, true) . " (type: " . gettype($raw_is_private) . ")");
        
        // Handle boolean
        if (is_bool($raw_is_private)) {
            $is_private = $raw_is_private;
        }
        // Handle integers (1 or 0)
        elseif (is_int($raw_is_private)) {
            $is_private = $raw_is_private === 1;
        }
        // Handle strings ('true', 'false', '1', '0')
        elseif (is_string($raw_is_private)) {
            $lower = strtolower(trim($raw_is_private));
            $is_private = in_array($lower, ['true', '1', 'yes', 't']);
        }
        // Handle numeric strings
        elseif (is_numeric($raw_is_private)) {
            $is_private = (int)$raw_is_private === 1;
        }
    }
    
    error_log("Processed is_private: " . var_export($is_private, true) . " (type: " . gettype($is_private) . ")");
    
    // Validate required fields
    if (!$lesson_id) {
        throw new Exception('Missing lesson_id');
    }
    if (!$student_id) {
        throw new Exception('Missing student_id');
    }
    if (empty($question)) {
        throw new Exception('Question cannot be empty');
    }
    
    // Get database connection
    $db = getDBConnection();
    
    // Prepare query
    $query = "INSERT INTO lesson_questions 
              (lesson_id, student_id, question, is_private, status, created_at) 
              VALUES 
              (:lesson_id, :student_id, :question, :is_private, 'pending', CURRENT_TIMESTAMP)
              RETURNING id, is_private";
    
    error_log("Executing query: " . $query);
    error_log("Parameters: lesson_id=$lesson_id, student_id=$student_id, is_private=" . ($is_private ? 'TRUE' : 'FALSE'));
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    $stmt->bindValue(':lesson_id', $lesson_id, PDO::PARAM_INT);
    $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindValue(':question', $question, PDO::PARAM_STR);
    
    // Try binding is_private as different types to see which works
    // First try as boolean
    $stmt->bindValue(':is_private', $is_private, PDO::PARAM_BOOL);
    
    error_log("About to execute...");
    
    // Execute
    $execute_result = $stmt->execute();
    
    error_log("Execute result: " . ($execute_result ? 'SUCCESS' : 'FAILED'));
    
    if (!$execute_result) {
        $errorInfo = $stmt->errorInfo();
        error_log("PDO Error: " . print_r($errorInfo, true));
        throw new Exception('Database error: ' . ($errorInfo[2] ?? 'Unknown error'));
    }
    
    // Fetch the inserted row
    $inserted = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Inserted row: " . print_r($inserted, true));
    
    $question_id = $inserted['id'] ?? null;
    
    if (!$question_id) {
        error_log("Warning: Could not get inserted ID from RETURNING clause");
        $question_id = $db->lastInsertId('lesson_questions_id_seq');
        error_log("Got ID from lastInsertId: " . $question_id);
    }
    
    // Clean output buffer
    ob_end_clean();
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Question posted successfully',
        'question_id' => (int)$question_id,
        'is_private' => $is_private,
        'debug' => [
            'received_is_private' => $data['is_private'] ?? 'NOT_SET',
            'processed_is_private' => $is_private,
            'inserted_is_private' => $inserted['is_private'] ?? 'UNKNOWN'
        ]
    ]);
    
} catch (PDOException $e) {
    ob_end_clean();
    error_log("PDO Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Exception $e) {
    ob_end_clean();
    error_log("Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>