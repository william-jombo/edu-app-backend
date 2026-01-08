<?php
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');
// header('Content-Type: application/json');

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     exit(0);
// }

// require_once '../../config/database.php';

// try {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     // Get posted data
//     $data = json_decode(file_get_contents("php://input"));
    
//     if (empty($data->student_id)) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Student ID is required'
//         ]);
//         exit;
//     }
    
//     // Get user_id before deleting student
//     $getUserQuery = "SELECT user_id FROM tbl_students WHERE id = :student_id";
//     $getUserStmt = $db->prepare($getUserQuery);
//     $getUserStmt->bindParam(':student_id', $data->student_id);
//     $getUserStmt->execute();
//     $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);
    
//     // Delete student record
//     $query = "DELETE FROM tbl_students WHERE id = :student_id";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':student_id', $data->student_id);
    
//     if ($stmt->execute()) {
//         // Also delete from tbl_users table
//         if ($user && isset($user['user_id'])) {
//             $deleteUserQuery = "DELETE FROM tbl_users WHERE id = :user_id";
//             $deleteUserStmt = $db->prepare($deleteUserQuery);
//             $deleteUserStmt->bindParam(':user_id', $user['user_id']);
//             $deleteUserStmt->execute();
//         }
        
//         echo json_encode([
//             'success' => true,
//             'message' => 'Student deleted successfully'
//         ]);
//     } else {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Failed to delete student'
//         ]);
//     }
    
// } catch (PDOException $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Database error: ' . $e->getMessage()
//     ]);
// }
?>





<?php
// ============================================================================
// FILE: backend/api/admin/delete_student.php
// ============================================================================
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->student_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Student ID is required'
        ]);
        exit;
    }
    
    // Get user_id before deleting student
    $getUserQuery = "SELECT user_id FROM students WHERE id = :student_id";
    $getUserStmt = $db->prepare($getUserQuery);
    $getUserStmt->bindParam(':student_id', $data->student_id);
    $getUserStmt->execute();
    $student = $getUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode([
            'success' => false,
            'message' => 'Student not found'
        ]);
        exit;
    }
    
    // Delete student record (cascade will handle related records)
    $query = "DELETE FROM students WHERE id = :student_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':student_id', $data->student_id);
    
    if ($stmt->execute()) {
        // Delete from users table
        if ($student['user_id']) {
            $deleteUserQuery = "DELETE FROM users WHERE id = :user_id";
            $deleteUserStmt = $db->prepare($deleteUserQuery);
            $deleteUserStmt->bindParam(':user_id', $student['user_id']);
            $deleteUserStmt->execute();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Student deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete student'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
