<?php

// //C:\Users\BR\Desktop\calmtech\php\htdocs\backend\api\students\get_stats.php
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
    
//     // Attendance statistics
//     $attendanceQuery = "SELECT 
//                           COUNT(*) as total_days,
//                           SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
//                           SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
//                           SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days
//                         FROM tbl_attendance
//                         WHERE student_id = ?";
    
//     $attStmt = $conn->prepare($attendanceQuery);
//     $attStmt->execute([$student_id]);
//     $attData = $attStmt->fetch(PDO::FETCH_ASSOC);
    
//     $attendanceRate = 0;
//     if ($attData['total_days'] > 0) {
//         $attendanceRate = round(($attData['present_days'] / $attData['total_days']) * 100, 1);
//     }
    
//     echo json_encode([
//         'success' => true,
//         'data' => [
//             'present_days' => $attData['present_days'],
//             'absent_days' => $attData['absent_days'],
//             'late_days' => $attData['late_days'],
//             'attendance_rate' => $attendanceRate
//         ]
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Error: ' . $e->getMessage()
//     ]);
// }

?>








<?php
// backend/api/students/get_stats.php
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
    
    // Get attendance stats (NEW table)
    $attendanceQuery = "SELECT 
                          SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                          SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                          SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                          COUNT(*) as total_days
                        FROM attendance
                        WHERE student_id = ?";
    
    $stmt = $conn->prepare($attendanceQuery);
    $stmt->execute([$student_id]);
    $attendanceStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate attendance rate
    $totalDays = $attendanceStats['total_days'] ?? 0;
    $presentDays = $attendanceStats['present_days'] ?? 0;
    $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;
    
    // Get grade average (NEW table)
    $gradeQuery = "SELECT AVG(percentage) as average_grade
                   FROM grades
                   WHERE student_id = ?";
    
    $stmt = $conn->prepare($gradeQuery);
    $stmt->execute([$student_id]);
    $gradeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get assignment stats (NEW tables)
    $assignmentQuery = "SELECT 
                          COUNT(DISTINCT a.id) as total_assignments,
                          COUNT(DISTINCT sub.id) as submitted_assignments,
                          COUNT(DISTINCT CASE WHEN sub.grade IS NOT NULL THEN sub.id END) as graded_assignments
                        FROM assignments a
                        JOIN students s ON a.class_id = s.class_id
                        LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
                        WHERE s.id = ? AND a.status = 'active'";
    
    $stmt = $conn->prepare($assignmentQuery);
    $stmt->execute([$student_id, $student_id]);
    $assignmentStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'attendance_rate' => $attendanceRate,
            'present_days' => $attendanceStats['present_days'] ?? 0,
            'absent_days' => $attendanceStats['absent_days'] ?? 0,
            'late_days' => $attendanceStats['late_days'] ?? 0,
            'average_grade' => round($gradeData['average_grade'] ?? 0, 1),
            'total_assignments' => $assignmentStats['total_assignments'] ?? 0,
            'submitted_assignments' => $assignmentStats['submitted_assignments'] ?? 0,
            'graded_assignments' => $assignmentStats['graded_assignments'] ?? 0
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>