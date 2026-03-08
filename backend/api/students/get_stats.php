<?php
// backend/api/students/get_stats.php
// Updated for Supabase PostgreSQL

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once '../../config/database.php';

try {
    $student_id = $_GET['student_id'] ?? null;
    
    if (!$student_id) {
        throw new Exception('Student ID required');
    }
    
    $db = getDBConnection();
    
    // Get attendance stats
    $attendanceQuery = "SELECT 
                          SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                          SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                          SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                          COUNT(*) as total_days
                        FROM attendance
                        WHERE student_id = ?";
    
    $stmt = $db->prepare($attendanceQuery);
    $stmt->execute([$student_id]);
    $attendanceStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate attendance rate
    $totalDays = (int)($attendanceStats['total_days'] ?? 0);
    $presentDays = (int)($attendanceStats['present_days'] ?? 0);
    $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;
    
    // Get grade average
    $gradeQuery = "SELECT AVG(percentage) as average_grade
                   FROM grades
                   WHERE student_id = ?";
    
    $stmt = $db->prepare($gradeQuery);
    $stmt->execute([$student_id]);
    $gradeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get assignment stats
    $assignmentQuery = "SELECT 
                          COUNT(DISTINCT a.id) as total_assignments,
                          COUNT(DISTINCT asub.id) as submitted_assignments,
                          COUNT(DISTINCT CASE WHEN asub.grade IS NOT NULL THEN asub.id END) as graded_assignments
                        FROM assignments a
                        JOIN students s ON a.class_id = s.class_id
                        LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
                        WHERE s.id = ? AND a.status = 'active'";
    
    $stmt = $db->prepare($assignmentQuery);
    $stmt->execute([$student_id, $student_id]);
    $assignmentStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'attendance_rate' => $attendanceRate,
            'present_days' => (int)($attendanceStats['present_days'] ?? 0),
            'absent_days' => (int)($attendanceStats['absent_days'] ?? 0),
            'late_days' => (int)($attendanceStats['late_days'] ?? 0),
            'average_grade' => round($gradeData['average_grade'] ?? 0, 1),
            'total_assignments' => (int)($assignmentStats['total_assignments'] ?? 0),
            'submitted_assignments' => (int)($assignmentStats['submitted_assignments'] ?? 0),
            'graded_assignments' => (int)($assignmentStats['graded_assignments'] ?? 0)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>