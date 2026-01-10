<?php
require_once '../../includes/cors.php';
// // FILE: backend/api/teachers/grade_submission.php
// // Grade a student's assignment submission
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');
// header('Access-Control-Allow-Methods: POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     exit(0);
// }

// require_once '../../config/Database.php';

// try {
//     $data = json_decode(file_get_contents("php://input"), true);
    
//     // Validate required fields
//     if (!isset($data['submission_id']) || !isset($data['grade']) || !isset($data['teacher_id'])) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Missing required fields (submission_id, grade, teacher_id)'
//         ]);
//         exit;
//     }
    
//     $submission_id = $data['submission_id'];
//     $grade = $data['grade'];
//     $feedback = $data['feedback'] ?? '';
//     $teacher_id = $data['teacher_id'];
    
//     $database = new Database();
//     $conn = $database->getConnection();
    
//     // Update the submission with grade and feedback
//     $query = "UPDATE assignment_submissions 
//               SET grade = ?,
//                   feedback = ?,
//                   status = 'graded',
//                   graded_at = NOW(),
//                   graded_by = ?
//               WHERE id = ?";
    
//     $stmt = $conn->prepare($query);
//     $result = $stmt->execute([$grade, $feedback, $teacher_id, $submission_id]);
    
//     if ($result) {
//         echo json_encode([
//             'success' => true,
//             'message' => 'Submission graded successfully'
//         ]);
//     } else {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Failed to grade submission'
//         ]);
//     }
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Error: ' . $e->getMessage()
//     ]);
// }
?>












<?php
// FILE: backend/api/teachers/grade_submission.php
// Grade a student's assignment submission
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
    
    // DEBUG: Log what we received
    error_log("=== GRADE SUBMISSION DEBUG ===");
    error_log("Received data: " . print_r($data, true));
    
    // Validate required fields
    if (!isset($data['submission_id']) || !isset($data['grade']) || !isset($data['teacher_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields (submission_id, grade, teacher_id)',
            'received' => array_keys($data)
        ]);
        exit;
    }
    
    $submission_id = $data['submission_id'];
    $grade = $data['grade'];
    $feedback = $data['feedback'] ?? '';
    $teacher_id = $data['teacher_id'];
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // DEBUG: Check if submission exists BEFORE update
    $checkQuery = "SELECT id, student_id, assignment_id, status FROM assignment_submissions WHERE id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$submission_id]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Existing submission: " . print_r($existing, true));
    
    if (!$existing) {
        echo json_encode([
            'success' => false,
            'message' => "Submission ID $submission_id not found in database"
        ]);
        exit;
    }
    
    // Update the submission with grade and feedback
    $query = "UPDATE assignment_submissions 
              SET grade = ?,
                  feedback = ?,
                  status = 'graded',
                  graded_at = NOW(),
                  graded_by = ?
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([$grade, $feedback, $teacher_id, $submission_id]);
    
    // DEBUG: Check affected rows
    $rowsAffected = $stmt->rowCount();
    error_log("Rows affected: $rowsAffected");
    
    if ($result && $rowsAffected > 0) {
        // Verify the update worked
        $verifyStmt = $conn->prepare($checkQuery);
        $verifyStmt->execute([$submission_id]);
        $updated = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        error_log("After update: " . print_r($updated, true));
        
        echo json_encode([
            'success' => true,
            'message' => 'Submission graded successfully',
            'debug' => [
                'submission_id' => $submission_id,
                'rows_affected' => $rowsAffected,
                'grade' => $grade
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to grade submission - no rows updated',
            'debug' => [
                'submission_id' => $submission_id,
                'rows_affected' => $rowsAffected,
                'error' => $stmt->errorInfo()
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("Grade submission error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>