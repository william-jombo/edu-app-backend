<?php
// FILE: backend/api/students/unsubmit_assignment.php
// Allows students to unsubmit their assignment if not yet graded
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
    
    // Validate required fields
    if (!isset($data['submission_id']) || !isset($data['student_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields (submission_id, student_id)'
        ]);
        exit;
    }
    
    $submission_id = $data['submission_id'];
    $student_id = $data['student_id'];
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // First, check if the submission exists and belongs to this student
    $checkQuery = "SELECT id, status, submission_file 
                   FROM assignment_submissions 
                   WHERE id = ? AND student_id = ?";
    
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute([$submission_id, $student_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        echo json_encode([
            'success' => false,
            'message' => 'Submission not found or does not belong to you'
        ]);
        exit;
    }
    
    // Check if already graded - cannot unsubmit if graded
    if ($submission['status'] === 'graded') {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot unsubmit - assignment has already been graded by your teacher'
        ]);
        exit;
    }
    
    // Delete the submission file if it exists
    if ($submission['submission_file']) {
        $file_path = '../../' . $submission['submission_file'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Delete the submission record
    $deleteQuery = "DELETE FROM assignment_submissions WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $result = $stmt->execute([$submission_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Assignment unsubmitted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to unsubmit assignment'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>