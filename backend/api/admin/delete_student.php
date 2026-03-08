<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['student_id'])) {
        echo json_encode(['success' => false, 'message' => 'Student ID required']);
        exit;
    }
    
    $conn = getDBConnection();
    $conn->beginTransaction();
    
    // Get user_id
    $stmt = $conn->prepare("SELECT user_id FROM students WHERE id = ?");
    $stmt->execute([$data['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    // Delete user (CASCADE will delete student)
    $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $deleteStmt->execute([$student['user_id']]);
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
    
} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}