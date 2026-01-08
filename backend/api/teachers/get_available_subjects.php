


<?php
// FILE: backend/api/teachers/create_assignment.php
// Updated with file upload support (PDF, videos, documents)
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if this is a file upload request or JSON request
    $isFileUpload = isset($_FILES['attachment']);
    
    if ($isFileUpload) {
        // Handle multipart/form-data (with file)
        $data = $_POST;
    } else {
        // Handle JSON request (without file)
        $data = json_decode(file_get_contents("php://input"), true);
    }
    
    // Validate required fields
    if (!isset($data['teacher_id']) || !isset($data['class_id']) || 
        !isset($data['subject_id']) || !isset($data['title']) || 
        !isset($data['description']) || !isset($data['due_date']) || 
        !isset($data['total_points'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    // Create assignments table if it doesn't exist
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS tbl_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            class_id INT NOT NULL,
            subject_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            due_date DATE NOT NULL,
            total_points INT NOT NULL,
            attachment_path VARCHAR(500),
            attachment_name VARCHAR(255),
            attachment_type VARCHAR(100),
            attachment_size INT,
            status ENUM('active', 'closed', 'draft') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_teacher (teacher_id),
            INDEX idx_class (class_id),
            INDEX idx_subject (subject_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $conn->exec($createTableQuery);
    
    // Handle file upload if present
    $attachmentPath = null;
    $attachmentName = null;
    $attachmentType = null;
    $attachmentSize = null;
    
    if ($isFileUpload && isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];
        
        // Validate file type
        $allowedTypes = [
            'application/pdf',
            'video/mp4',
            'video/mpeg',
            'video/quicktime',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'application/zip'
        ];
        
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file type. Allowed: PDF, Videos (MP4), Documents (Word, PowerPoint), Images, ZIP'
            ]);
            exit;
        }
        
        // Validate file size (max 50MB)
        $maxSize = 50 * 1024 * 1024; // 50MB in bytes
        if ($file['size'] > $maxSize) {
            echo json_encode([
                'success' => false,
                'message' => 'File too large. Maximum size is 50MB'
            ]);
            exit;
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = '../uploads/assignments/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('assignment_') . '_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $attachmentPath = 'uploads/assignments/' . $filename;
            $attachmentName = $file['name'];
            $attachmentType = $fileType;
            $attachmentSize = $file['size'];
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to upload file'
            ]);
            exit;
        }
    }
    
    // Insert assignment
    $query = "INSERT INTO tbl_assignments 
              (teacher_id, class_id, subject_id, title, description, due_date, total_points, 
               attachment_path, attachment_name, attachment_type, attachment_size, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $data['teacher_id'],
        $data['class_id'],
        $data['subject_id'],
        $data['title'],
        $data['description'],
        $data['due_date'],
        $data['total_points'],
        $attachmentPath,
        $attachmentName,
        $attachmentType,
        $attachmentSize
    ]);
    
    $assignmentId = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Assignment created successfully',
        'assignment_id' => $assignmentId,
        'has_attachment' => $attachmentPath !== null,
        'attachment_name' => $attachmentName
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>