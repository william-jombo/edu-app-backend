<?php
// backend/api/teachers/create_assignment.php
// Updated with Supabase Storage integration
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../config/supabase.php';

try {
    // Get form data
    $teacher_id = $_POST['teacher_id'] ?? null;
    $class_id = $_POST['class_id'] ?? null;
    $subject_id = $_POST['subject_id'] ?? null;
    $title = $_POST['title'] ?? null;
    $description = $_POST['description'] ?? null;
    $due_date = $_POST['due_date'] ?? null;
    $total_points = $_POST['total_points'] ?? 100;
    
    // Validate required fields
    if (!$teacher_id || !$class_id || !$subject_id || !$title || !$description || !$due_date) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    $db = getDBConnection();
    $storage = getSupabaseStorage();
    
    // Handle file upload to Supabase Storage
    $attachment_path = null;
    $attachment_name = null;
    $attachment_type = null;
    $attachment_size = null;
    
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];
        
        // Validate file type
        $allowed_types = [
            'application/pdf',
            'video/mp4', 'video/mpeg', 'video/quicktime',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg', 'image/png', 'image/gif',
            'application/zip'
        ];
        
        $file_type = $file['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file type. Allowed: PDF, Videos, Documents, Images, ZIP'
            ]);
            exit;
        }
        
        // Validate file size (max 50MB)
        $max_size = 50 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            echo json_encode([
                'success' => false,
                'message' => 'File too large. Maximum size is 50MB'
            ]);
            exit;
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_name = 'assignment_' . $teacher_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
        
        // Upload to Supabase Storage
        $upload_result = $storage->uploadFromRequest('assignments', $file, $unique_name);
        
        if (!$upload_result['success']) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to upload file: ' . $upload_result['error']
            ]);
            exit;
        }
        
        // Store Supabase path and URL
        $attachment_path = $upload_result['path']; // e.g., "assignments/assignment_1_123456.pdf"
        $attachment_name = $file['name'];
        $attachment_type = $file_type;
        $attachment_size = $file['size'];
    }
    
    // Insert assignment into database
    $query = "INSERT INTO assignments 
              (teacher_id, class_id, subject_id, title, description, due_date, total_points, 
               attachment_path, attachment_name, attachment_type, attachment_size, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        $teacher_id,
        $class_id,
        $subject_id,
        $title,
        $description,
        $due_date,
        $total_points,
        $attachment_path,
        $attachment_name,
        $attachment_type,
        $attachment_size
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Assignment created successfully',
            'assignment_id' => $db->lastInsertId(),
            'has_attachment' => $attachment_path !== null,
            'attachment_name' => $attachment_name
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create assignment'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>