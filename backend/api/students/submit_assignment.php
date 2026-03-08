<?php
// backend/api/students/submit_assignment.php
// Improved with better error handling and debugging

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
    $student_id = $_POST['student_id'] ?? null;
    $assignment_id = $_POST['assignment_id'] ?? null;
    
    if (!$student_id || !$assignment_id) {
        throw new Exception('Missing required fields: student_id or assignment_id');
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['submission_file'])) {
        throw new Exception('No file uploaded');
    }
    
    if ($_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        $error_code = $_FILES['submission_file']['error'];
        $error_msg = $error_messages[$error_code] ?? 'Unknown upload error';
        throw new Exception('Upload error: ' . $error_msg);
    }
    
    $db = getDBConnection();
    
    // Verify student exists
    $stmt = $db->prepare("SELECT id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Student not found');
    }
    
    // Verify assignment exists
    $stmt = $db->prepare("SELECT id FROM assignments WHERE id = ?");
    $stmt->execute([$assignment_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Assignment not found');
    }
    
    $file = $_FILES['submission_file'];
    
    // Validate file size (max 50MB)
    if ($file['size'] > 50 * 1024 * 1024) {
        throw new Exception('File too large. Maximum size is 50MB');
    }
    
    // Validate file extension
    $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('Invalid file type. Allowed: ' . implode(', ', $allowed_extensions));
    }
    
    // Generate unique filename
    $unique_name = 'submission_' . $assignment_id . '_student_' . $student_id . '_' . time() . '.' . $file_extension;
    
    // Try to upload to Supabase Storage
    try {
        $storage = getSupabaseStorage();
        $upload_result = $storage->uploadFromRequest('submissions', $file, $unique_name);
        
        if (!$upload_result['success']) {
            throw new Exception('Supabase upload failed: ' . ($upload_result['error'] ?? 'Unknown error'));
        }
        
        $file_path = $upload_result['path']; // e.g., "submissions/submission_1_student_2_123456.pdf"
        
    } catch (Exception $e) {
        // If Supabase fails, log the error and provide helpful message
        error_log("Supabase upload error: " . $e->getMessage());
        throw new Exception('Failed to upload to storage: ' . $e->getMessage() . '. Please ensure the "submissions" bucket exists and is public in Supabase Storage.');
    }
    
    // Check if submission already exists
    $checkStmt = $db->prepare("SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
    $checkStmt->execute([$assignment_id, $student_id]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing submission
        $stmt = $db->prepare("
            UPDATE assignment_submissions 
            SET submission_file = ?, submitted_at = CURRENT_TIMESTAMP, status = 'submitted'
            WHERE assignment_id = ? AND student_id = ?
        ");
        $stmt->execute([$file_path, $assignment_id, $student_id]);
    } else {
        // Insert new submission
        $stmt = $db->prepare("
            INSERT INTO assignment_submissions 
            (assignment_id, student_id, submission_file, submitted_at, status) 
            VALUES (?, ?, ?, CURRENT_TIMESTAMP, 'submitted')
        ");
        $stmt->execute([$assignment_id, $student_id, $file_path]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Assignment submitted successfully',
        'filename' => $unique_name,
        'file_path' => $file_path
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in submit_assignment.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in submit_assignment.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>