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
    
//     $data = json_decode(file_get_contents("php://input"));
    
//     if (empty($data->teacher_id)) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Teacher ID is required'
//         ]);
//         exit;
//     }
    
//     // Get user_id before deleting
//     $getUserQuery = "SELECT user_id FROM tbl_teacher WHERE id = :teacher_id";
//     $getUserStmt = $db->prepare($getUserQuery);
//     $getUserStmt->bindParam(':teacher_id', $data->teacher_id);
//     $getUserStmt->execute();
//     $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);
    
//     // Delete teacher
//     $query = "DELETE FROM tbl_teacher WHERE id = :teacher_id";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':teacher_id', $data->teacher_id);
    
//     if ($stmt->execute()) {
//         // Delete from tbl_users table
//         if ($user && isset($user['user_id'])) {
//             $deleteUserQuery = "DELETE FROM tbl_users WHERE id = :user_id";
//             $deleteUserStmt = $db->prepare($deleteUserQuery);
//             $deleteUserStmt->bindParam(':user_id', $user['user_id']);
//             $deleteUserStmt->execute();
//         }
        
//         echo json_encode([
//             'success' => true,
//             'message' => 'Teacher deleted successfully'
//         ]);
//     } else {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Failed to delete teacher'
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
require_once '../../includes/cors.php';
// ============================================================================
// FILE: backend/api/admin/delete_teacher.php
// ============================================================================
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->teacher_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher ID is required'
        ]);
        exit;
    }
    
    // Get user_id before deleting
    $getUserQuery = "SELECT user_id FROM teachers WHERE id = :teacher_id";
    $getUserStmt = $db->prepare($getUserQuery);
    $getUserStmt->bindParam(':teacher_id', $data->teacher_id);
    $getUserStmt->execute();
    $teacher = $getUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher not found'
        ]);
        exit;
    }
    
    // Delete teacher (cascade will handle teacher_assignments)
    $query = "DELETE FROM teachers WHERE id = :teacher_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':teacher_id', $data->teacher_id);
    
    if ($stmt->execute()) {
        // Delete from users table (cascade will delete teacher)
        if ($teacher['user_id']) {
            $deleteUserQuery = "DELETE FROM users WHERE id = :user_id";
            $deleteUserStmt = $db->prepare($deleteUserQuery);
            $deleteUserStmt->bindParam(':user_id', $teacher['user_id']);
            $deleteUserStmt->execute();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Teacher deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete teacher'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>


