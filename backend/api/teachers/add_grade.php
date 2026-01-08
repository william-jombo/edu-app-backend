

<?php
// // FILE: backend/api/teachers/add_grade.php  
// // Fixed for YOUR exact tbl_grades structure
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
    
//     // Debug log
//     error_log("Grade data received: " . print_r($data, true));
    
//     // Validate required fields
//     if (!isset($data['teacher_id']) || !isset($data['student_id']) || 
//         !isset($data['subject_id']) || !isset($data['score']) || !isset($data['grade_type'])) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Missing required fields',
//             'received' => array_keys($data),
//             'required' => ['teacher_id', 'student_id', 'subject_id', 'score', 'grade_type']
//         ]);
//         exit;
//     }
    
//     $database = new Database();
//     $conn = $database->getConnection();
    
//     // YOUR tbl_grades structure has:
//     // student_id, teacher_id, subject_id, class_id, grade_type, score, max_score, comments, date_recorded
    
//     // Get student's class_id
//     $classQuery = "SELECT class_id FROM tbl_students WHERE id = ? 
//                    UNION 
//                    SELECT class_id FROM tbl_student WHERE id = ?";
//     $stmt = $conn->prepare($classQuery);
//     $stmt->execute([$data['student_id'], $data['student_id']]);
//     $classResult = $stmt->fetch(PDO::FETCH_ASSOC);
//     $class_id = $classResult ? $classResult['class_id'] : null;
    
//     if (!$class_id) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Could not find student class'
//         ]);
//         exit;
//     }
    
//     // Insert grade using YOUR table structure
//     $query = "INSERT INTO tbl_grades 
//               (student_id, teacher_id, subject_id, class_id, grade_type, score, max_score, comments, date_recorded) 
//               VALUES (?, ?, ?, ?, ?, ?, 100.00, ?, CURDATE())";
    
//     $stmt = $conn->prepare($query);
//     $result = $stmt->execute([
//         $data['student_id'],
//         $data['teacher_id'],
//         $data['subject_id'],
//         $class_id,
//         $data['grade_type'],
//         $data['score'],
//         $data['comments'] ?? null
//     ]);
    
//     if (!$result) {
//         throw new Exception('Failed to insert grade');
//     }
    
//     echo json_encode([
//         'success' => true,
//         'message' => 'Grade added successfully',
//         'grade_id' => $conn->lastInsertId()
//     ]);
    
// } catch (Exception $e) {
//     error_log("Grade error: " . $e->getMessage());
//     echo json_encode([
//         'success' => false,
//         'message' => 'Error: ' . $e->getMessage()
//     ]);
// }
?>









<?php
// FILE: backend/api/teachers/add_grade.php  
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
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Debug log
    error_log("Grade data received: " . print_r($data, true));
    
    // Validate required fields
    if (!isset($data['teacher_id']) || !isset($data['student_id']) || 
        !isset($data['subject_id']) || !isset($data['score']) || !isset($data['grade_type'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields',
            'received' => array_keys($data),
            'required' => ['teacher_id', 'student_id', 'subject_id', 'score', 'grade_type']
        ]);
        exit;
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get student's class_id from students table (new structure)
    $classQuery = "SELECT class_id FROM students WHERE id = ?";
    $stmt = $conn->prepare($classQuery);
    $stmt->execute([$data['student_id']]);
    $classResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $class_id = $classResult ? $classResult['class_id'] : null;
    
    if (!$class_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Could not find student class'
        ]);
        exit;
    }
    
    // Insert grade using new grades table structure
    $query = "INSERT INTO grades 
              (student_id, teacher_id, subject_id, class_id, grade_type, score, max_score, comments, grade_date, academic_year) 
              VALUES (?, ?, ?, ?, ?, ?, 100.00, ?, CURDATE(), YEAR(CURDATE()))";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([
        $data['student_id'],
        $data['teacher_id'],
        $data['subject_id'],
        $class_id,
        $data['grade_type'],
        $data['score'],
        $data['comments'] ?? null
    ]);
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to insert grade',
            'error' => $stmt->errorInfo()
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Grade added successfully',
        'grade_id' => $conn->lastInsertId()
    ]);
    
} catch (Exception $e) {
    error_log("Error adding grade: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>