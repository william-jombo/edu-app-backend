



<?php


// //C:\Users\BR\Desktop\calmtech\php\htdocs\backend\api\students\get_student_id.php
// header('Content-Type: application/json');
// error_reporting(E_ALL);
// ini_set('display_errors', 0);

// try {
//     require_once '../../config/database.php';
    
//     $database = new Database();
//     $pdo = $database->getConnection();
    
//     if (!$pdo) {
//         throw new Exception('Failed to establish database connection');
//     }
    
//     $user_id = $_GET['user_id'] ?? null;
    
//     if (!$user_id) {
//         throw new Exception('User ID is required');
//     }
    
//     // Simply get student_id from tbl_students using user_id
//     // Both tables are now synced with same IDs
//     $stmt = $pdo->prepare("SELECT id FROM tbl_students WHERE user_id = ?");
//     $stmt->execute([$user_id]);
//     $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
//     if ($student) {
//         echo json_encode([
//             'success' => true,
//             'student_id' => $student['id'],
//             'source' => 'tbl_students'
//         ]);
//     } else {
//         throw new Exception('Student not found');
//     }
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => $e->getMessage()
//     ]);
// }
?>







<?php
// backend/api/students/get_student_id.php
// Updated for NEW database structure

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../../config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    $user_id = $_GET['user_id'] ?? null;
    
    if (!$user_id) {
        throw new Exception('User ID required');
    }
    
    // Get student_id from students table using user_id (NEW structure)
    $stmt = $pdo->prepare("SELECT id as student_id FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'student_id' => $result['student_id']
        ]);
    } else {
        throw new Exception('Student record not found for this user');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>