<?php
// FILE: backend/api/teachers/create_lesson.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/Database.php';

try {
    // Get form data
    $teacher_id = $_POST['teacher_id'] ?? null;
    $class_id = $_POST['class_id'] ?? null;
    $subject_id = $_POST['subject_id'] ?? null;
    $title = $_POST['title'] ?? null;
    $description = $_POST['description'] ?? '';
    $lesson_type = $_POST['lesson_type'] ?? 'pdf';
    $external_link = $_POST['external_link'] ?? '';
    $content = $_POST['content'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $status = $_POST['status'] ?? 'published';
    
    // Validate required fields
    if (!$teacher_id || !$class_id || !$subject_id || !$title) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $file_path = null;
    $file_name = null;
    $file_size = null;
    
    // Handle file upload
    if (isset($_FILES['lesson_file']) && $_FILES['lesson_file']['error'] === 0) {
        $upload_dir = '../../uploads/lessons/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file = $_FILES['lesson_file'];
        $file_size = $file['size'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        $allowed_extensions = ['pdf', 'doc', 'docx', 'mp4', 'avi', 'mov', 'wmv'];
        if (!in_array($file_extension, $allowed_extensions)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_extensions)
            ]);
            exit;
        }
        
        // Generate unique filename
        $new_filename = 'lesson_' . $teacher_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $new_filename;
        $file_name = $file['name'];
        
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to upload file'
            ]);
            exit;
        }
        
        // Store relative path
        $file_path = 'uploads/lessons/' . $new_filename;
    }
    
    // Insert lesson
    $query = "INSERT INTO lessons 
              (teacher_id, class_id, subject_id, title, description, lesson_type, 
               file_path, file_name, file_size, external_link, content, duration, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([
        $teacher_id,
        $class_id,
        $subject_id,
        $title,
        $description,
        $lesson_type,
        $file_path,
        $file_name,
        $file_size,
        $external_link,
        $content,
        $duration,
        $status
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Lesson created successfully',
            'lesson_id' => $conn->lastInsertId()
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create lesson'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>