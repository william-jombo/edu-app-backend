<?php
// backend/api/students/unsubmit_assignment.php
// Updated for Supabase PostgreSQL with Supabase Storage

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once '../../config/database.php';
require_once '../../config/supabase.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $submission_id = $data['submission_id'] ?? null;
    $student_id = $data['student_id'] ?? null;
    
    if (!$submission_id || !$student_id) {
        throw new Exception('Missing required fields (submission_id, student_id)');
    }
    
    $db = getDBConnection();
    $storage = getSupabaseStorage();
    
    // Check if submission exists and belongs to this student
    $checkQuery = "SELECT id, status, submission_file 
                   FROM assignment_submissions 
                   WHERE id = ? AND student_id = ?";
    
    $stmt = $db->prepare($checkQuery);
    $stmt->execute([$submission_id, $student_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        throw new Exception('Submission not found or does not belong to you');
    }
    
    // Check if already graded - cannot unsubmit if graded
    if ($submission['status'] === 'graded') {
        throw new Exception('Cannot unsubmit - assignment has already been graded by your teacher');
    }
    
    // Delete the file from Supabase Storage if it exists
    if ($submission['submission_file']) {
        $storage->deleteByFullPath($submission['submission_file']);
    }
    
    // Delete the submission record
    $deleteQuery = "DELETE FROM assignment_submissions WHERE id = ?";
    $stmt = $db->prepare($deleteQuery);
    $result = $stmt->execute([$submission_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Assignment unsubmitted successfully'
        ]);
    } else {
        throw new Exception('Failed to unsubmit assignment');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>