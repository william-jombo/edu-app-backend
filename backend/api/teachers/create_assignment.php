




<?php
// // FILE: backend/api/teachers/create_assignment.php
// // Fixed for YOUR exact database structure
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');
// header('Access-Control-Allow-Methods: POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     exit(0);
// }

// require_once '../../config/Database.php';

// try {
//     $database = new Database();
//     $conn = $database->getConnection();
    
//     // Check if file upload or JSON
//     $isFileUpload = isset($_FILES['attachment']) && !empty($_FILES['attachment']['name']);
    
//     if ($isFileUpload) {
//         $data = $_POST;
//     } else {
//         $jsonData = file_get_contents("php://input");
//         $data = json_decode($jsonData, true);
//         if (!$data) {
//             $data = $_POST;
//         }
//     }
    
//     // Debug log
//     error_log("Assignment data received: " . print_r($data, true));
//     error_log("Is file upload: " . ($isFileUpload ? 'yes' : 'no'));
    
//     // Validate required fields
//     $requiredFields = ['teacher_id', 'class_id', 'subject_id', 'title', 'description', 'due_date', 'total_points'];
//     $missingFields = [];
    
//     foreach ($requiredFields as $field) {
//         if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
//             $missingFields[] = $field;
//         }
//     }
    
//     if (!empty($missingFields)) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Missing required fields: ' . implode(', ', $missingFields),
//             'received_fields' => array_keys($data),
//             'missing_fields' => $missingFields
//         ]);
//         exit;
//     }
    
//     // Handle file upload
//     $attachmentPath = null;
//     $attachmentName = null;
//     $attachmentType = null;
//     $attachmentSize = null;
    
//     if ($isFileUpload) {
//         $file = $_FILES['attachment'];
        
//         if ($file['error'] === UPLOAD_ERR_OK) {
//             $allowedTypes = [
//                 'application/pdf',
//                 'video/mp4',
//                 'video/mpeg',
//                 'video/quicktime',
//                 'application/msword',
//                 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
//                 'application/vnd.ms-powerpoint',
//                 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
//                 'image/jpeg',
//                 'image/png',
//                 'image/gif',
//                 'application/zip'
//             ];
            
//             $fileType = mime_content_type($file['tmp_name']);
            
//             if (!in_array($fileType, $allowedTypes)) {
//                 echo json_encode([
//                     'success' => false,
//                     'message' => 'Invalid file type'
//                 ]);
//                 exit;
//             }
            
//             $maxSize = 50 * 1024 * 1024;
//             if ($file['size'] > $maxSize) {
//                 echo json_encode([
//                     'success' => false,
//                     'message' => 'File too large. Maximum 50MB'
//                 ]);
//                 exit;
//             }
            
//             $uploadDir = __DIR__ . '/../../uploads/assignments/';
//             if (!file_exists($uploadDir)) {
//                 mkdir($uploadDir, 0777, true);
//             }
            
//             $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
//             $filename = uniqid('assignment_') . '_' . time() . '.' . $extension;
//             $uploadPath = $uploadDir . $filename;
            
//             if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
//                 $attachmentPath = 'uploads/assignments/' . $filename;
//                 $attachmentName = $file['name'];
//                 $attachmentType = $fileType;
//                 $attachmentSize = $file['size'];
//             }
//         }
//     }
    
//     // Check if tbl_assignments has attachment columns
//     $checkColumns = $conn->query("SHOW COLUMNS FROM tbl_assignments LIKE 'attachment_path'");
//     $hasAttachmentColumns = $checkColumns->rowCount() > 0;
    
//     if ($hasAttachmentColumns) {
//         // Insert with attachment columns
//         $query = "INSERT INTO tbl_assignments 
//                   (teacher_id, class_id, subject_id, title, description, due_date, total_points, 
//                    attachment_path, attachment_name, attachment_type, attachment_size, status) 
//                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
        
//         $stmt = $conn->prepare($query);
//         $result = $stmt->execute([
//             $data['teacher_id'],
//             $data['class_id'],
//             $data['subject_id'],
//             $data['title'],
//             $data['description'],
//             $data['due_date'],
//             $data['total_points'],
//             $attachmentPath,
//             $attachmentName,
//             $attachmentType,
//             $attachmentSize
//         ]);
//     } else {
//         // Insert without attachment columns (your current table structure)
//         $query = "INSERT INTO tbl_assignments 
//                   (teacher_id, class_id, subject_id, title, description, due_date, total_points) 
//                   VALUES (?, ?, ?, ?, ?, ?, ?)";
        
//         $stmt = $conn->prepare($query);
//         $result = $stmt->execute([
//             $data['teacher_id'],
//             $data['class_id'],
//             $data['subject_id'],
//             $data['title'],
//             $data['description'],
//             $data['due_date'],
//             $data['total_points']
//         ]);
//     }
    
//     if (!$result) {
//         throw new Exception('Failed to insert assignment');
//     }
    
//     $assignmentId = $conn->lastInsertId();
    
//     echo json_encode([
//         'success' => true,
//         'message' => 'Assignment created successfully',
//         'assignment_id' => $assignmentId,
//         'has_attachment' => $attachmentPath !== null,
//         'attachment_name' => $attachmentName,
//         'note' => !$hasAttachmentColumns ? 'File upload not saved - run fix_tbl_assignments.sql first' : null
//     ]);
    
// } catch (Exception $e) {
//     error_log("Assignment error: " . $e->getMessage());
//     echo json_encode([
//         'success' => false,
//         'message' => 'Error: ' . $e->getMessage()
//     ]);
// }
?>







<?php
// FILE: backend/api/teachers/create_assignment.php
// Updated for new database structure
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
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Handle file upload if present
    $attachment_path = null;
    $attachment_name = null;
    $attachment_type = null;
    $attachment_size = null;
    $has_attachment = false;
    
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/assignments/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = $_FILES['attachment']['name'];
        $file_tmp = $_FILES['attachment']['tmp_name'];
        $file_size = $_FILES['attachment']['size'];
        $file_type = $_FILES['attachment']['type'];
        
        // Generate unique filename
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_name;
        
        if (move_uploaded_file($file_tmp, $upload_path)) {
            $attachment_path = $upload_path;
            $attachment_name = $file_name;
            $attachment_type = $file_type;
            $attachment_size = $file_size;
            $has_attachment = true;
        }
    }
    
    // Insert assignment into assignments table (new structure)
    $query = "INSERT INTO assignments 
              (teacher_id, class_id, subject_id, title, description, due_date, total_points, 
               attachment_path, attachment_name, attachment_type, attachment_size, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    
    $stmt = $conn->prepare($query);
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
            'assignment_id' => $conn->lastInsertId(),
            'has_attachment' => $has_attachment,
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