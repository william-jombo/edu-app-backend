<?php
// backend/api/students/get_attendance.php
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
    
    // Get attendance records
    $query = "SELECT 
                attendance_date as date,
                status,
                notes
              FROM attendance
              WHERE student_id = ?
              ORDER BY attendance_date DESC
              LIMIT 50";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'records' => $records
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>