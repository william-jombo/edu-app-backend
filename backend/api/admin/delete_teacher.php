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
    
    if (empty($data['teacher_id'])) {
        echo json_encode(['success' => false, 'message' => 'Teacher ID required']);
        exit;
    }
    
    $conn = getDBConnection();
    $conn->beginTransaction();
    
    // Get user_id
    $stmt = $conn->prepare("SELECT user_id FROM teachers WHERE id = ?");
    $stmt->execute([$data['teacher_id']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Teacher not found']);
        exit;
    }
    
    // Delete user (CASCADE will delete teacher)
    $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $deleteStmt->execute([$teacher['user_id']]);
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Teacher deleted successfully']);
    
} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}