<?php

// //C:\Users\BR\Desktop\calmtech\php\htdocs\backend\api\students\get_attendance.php
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');
// require_once '../../config/database.php';

// $student_id = $_GET['student_id'] ?? null;

// if (!$student_id) {
//     echo json_encode(['success' => false, 'message' => 'Student ID required']);
//     exit;
// }

// try {
//     $db = new Database();
//     $conn = $db->getConnection();
    
//     $query = "SELECT 
//                 date,
//                 status,
//                 notes
//               FROM tbl_attendance
//               WHERE student_id = ?
//               ORDER BY date DESC
//               LIMIT 50";
    
//     $stmt = $conn->prepare($query);
//     $stmt->execute([$student_id]);
//     $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     echo json_encode([
//         'success' => true,
//         'records' => $records
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Error: ' . $e->getMessage()
//     ]);
// }
?>



<?php
// backend/api/students/get_attendance.php
// Updated for NEW database structure

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once '../../config/database.php';

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Query from attendance table (NEW)
    $query = "SELECT 
                attendance_date as date,
                status,
                notes
              FROM attendance
              WHERE student_id = ?
              ORDER BY attendance_date DESC
              LIMIT 50";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$student_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'records' => $records
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>