<?php

//C:\Users\BR\Desktop\calmtech\php\htdocs\backend\api\teachers\attendance.php
include_once '../../config/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$required = ['teacher_id', 'class_id', 'attendance_records'];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

$teacher_id = intval($data['teacher_id']);
$class_id = intval($data['class_id']);
$attendance_records = $data['attendance_records'];
$date = isset($data['date']) ? $data['date'] : date('Y-m-d');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $conn->beginTransaction();
    
    $query = "INSERT INTO tbl_attendance (student_id, class_id, teacher_id, date, status, notes)
              VALUES (:student_id, :class_id, :teacher_id, :date, :status, :notes)
              ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes)";
    
    $stmt = $conn->prepare($query);
    
    foreach ($attendance_records as $record) {
        $student_id = intval($record['student_id']);
        $status = $record['status'];
        $notes = isset($record['notes']) ? $record['notes'] : '';
        
        $stmt->execute([
            'student_id' => $student_id,
            'class_id' => $class_id,
            'teacher_id' => $teacher_id,
            'date' => $date,
            'status' => $status,
            'notes' => $notes
        ]);
    }
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Attendance recorded successfully']);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>