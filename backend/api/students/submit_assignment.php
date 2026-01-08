

<?php
// //C:\Users\BR\Desktop\calmtech\php\htdocs\backend\api\students\submit_assignment.php
// header('Content-Type: application/json');
// error_reporting(E_ALL);
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);

// ob_start();

// try {
//     require_once '../../config/database.php';
    
//     $database = new Database();
//     $pdo = $database->getConnection();
    
//     if (!$pdo) {
//         throw new Exception('Failed to establish database connection');
//     }
    
//     // Get form data
//     $student_id = $_POST['student_id'] ?? null;
//     $assignment_id = $_POST['assignment_id'] ?? null;
    
//     if (!$student_id || !$assignment_id) {
//         throw new Exception('Missing required fields');
//     }
    
//     // Verify student exists in tbl_student (the one foreign keys reference)
//     $stmt = $pdo->prepare("SELECT id FROM tbl_student WHERE id = ?");
//     $stmt->execute([$student_id]);
//     if (!$stmt->fetch()) {
//         throw new Exception('Student ID ' . $student_id . ' not found in tbl_student');
//     }
    
//     // Verify assignment exists
//     $stmt = $pdo->prepare("SELECT id FROM tbl_assignments WHERE id = ?");
//     $stmt->execute([$assignment_id]);
//     if (!$stmt->fetch()) {
//         throw new Exception('Assignment not found');
//     }
    
//     // Check if file was uploaded
//     if (!isset($_FILES['submission_file'])) {
//         throw new Exception('No file uploaded');
//     }
    
//     if ($_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
//         $error_messages = [
//             UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
//             UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
//             UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
//             UPLOAD_ERR_NO_FILE => 'No file was uploaded',
//             UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
//             UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
//             UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
//         ];
//         $error_code = $_FILES['submission_file']['error'];
//         $error_msg = $error_messages[$error_code] ?? 'Unknown upload error';
//         throw new Exception($error_msg);
//     }
    
//     $file = $_FILES['submission_file'];
    
//     // Validate file size (max 10MB)
//     if ($file['size'] > 10 * 1024 * 1024) {
//         throw new Exception('File too large. Maximum size is 10MB');
//     }
    
//     // Validate file extension
//     $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'zip'];
//     $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
//     if (!in_array($file_extension, $allowed_extensions)) {
//         throw new Exception('Invalid file type. Allowed: ' . implode(', ', $allowed_extensions));
//     }
    
//     // Create upload directory
//     $upload_dir = '../../uploads/assignments/';
//     if (!file_exists($upload_dir)) {
//         if (!mkdir($upload_dir, 0777, true)) {
//             throw new Exception('Failed to create upload directory');
//         }
//     }
    
//     // Generate unique filename
//     $new_filename = 'submission_' . $assignment_id . '_student_' . $student_id . '_' . time() . '.' . $file_extension;
//     $upload_path = $upload_dir . $new_filename;
//     $db_path_store = 'uploads/assignments/' . $new_filename;
    
//     // Move uploaded file
//     if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
//         throw new Exception('Failed to save file to server');
//     }
    
//     // Insert or update submission in database
//     $stmt = $pdo->prepare("
//         INSERT INTO tbl_assignment_submissions 
//         (assignment_id, student_id, submission_file, submitted_date, status) 
//         VALUES (?, ?, ?, NOW(), 'submitted')
//         ON DUPLICATE KEY UPDATE 
//         submission_file = VALUES(submission_file),
//         submitted_date = NOW(),
//         status = 'submitted'
//     ");
    
//     if (!$stmt->execute([$assignment_id, $student_id, $db_path_store])) {
//         if (file_exists($upload_path)) {
//             unlink($upload_path);
//         }
//         $errorInfo = $stmt->errorInfo();
//         throw new Exception('Database error: ' . $errorInfo[2]);
//     }
    
//     ob_end_clean();
    
//     echo json_encode([
//         'success' => true,
//         'message' => 'Assignment submitted successfully',
//         'filename' => $new_filename
//     ]);
    
// } catch (Exception $e) {
//     ob_end_clean();
    
//     http_response_code(400);
//     echo json_encode([
//         'success' => false,
//         'message' => $e->getMessage()
//     ]);
// }
?>








<?php
// backend/api/students/submit_assignment.php
// Updated for NEW database structure

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

try {
    require_once '../../config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Failed to establish database connection');
    }
    
    // Get form data
    $student_id = $_POST['student_id'] ?? null;
    $assignment_id = $_POST['assignment_id'] ?? null;
    
    if (!$student_id || !$assignment_id) {
        throw new Exception('Missing required fields');
    }
    
    // Verify student exists in students table (NEW)
    $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Student ID ' . $student_id . ' not found');
    }
    
    // Verify assignment exists in assignments table (NEW)
    $stmt = $pdo->prepare("SELECT id FROM assignments WHERE id = ?");
    $stmt->execute([$assignment_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Assignment not found');
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
        throw new Exception($error_msg);
    }
    
    $file = $_FILES['submission_file'];
    
    // Validate file size (max 50MB)
    if ($file['size'] > 50 * 1024 * 1024) {
        throw new Exception('File too large. Maximum size is 50MB');
    }
    
    // Validate file extension
    $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'zip'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('Invalid file type. Allowed: ' . implode(', ', $allowed_extensions));
    }
    
    // Create upload directory
    $upload_dir = '../../uploads/assignments/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    // Generate unique filename
    $new_filename = 'submission_' . $assignment_id . '_student_' . $student_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    $db_path_store = 'uploads/assignments/' . $new_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Failed to save file to server');
    }
    
    // Insert or update submission in assignment_submissions table (NEW)
    $stmt = $pdo->prepare("
        INSERT INTO assignment_submissions 
        (assignment_id, student_id, submission_file, submitted_at, status) 
        VALUES (?, ?, ?, NOW(), 'submitted')
        ON DUPLICATE KEY UPDATE 
        submission_file = VALUES(submission_file),
        submitted_at = NOW(),
        status = 'submitted'
    ");
    
    if (!$stmt->execute([$assignment_id, $student_id, $db_path_store])) {
        // Delete uploaded file if database insert fails
        if (file_exists($upload_path)) {
            unlink($upload_path);
        }
        $errorInfo = $stmt->errorInfo();
        throw new Exception('Database error: ' . $errorInfo[2]);
    }
    
    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'message' => 'Assignment submitted successfully',
        'filename' => $new_filename
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>