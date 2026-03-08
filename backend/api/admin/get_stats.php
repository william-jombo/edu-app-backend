<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $conn = getDBConnection();
    
    // Count total students
    $studentStmt = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
    $studentCount = $studentStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count total teachers
    $teacherStmt = $conn->query("SELECT COUNT(*) as count FROM teachers WHERE status = 'active'");
    $teacherCount = $teacherStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count total classes
    $classStmt = $conn->query("SELECT COUNT(*) as count FROM classes WHERE status = 'active'");
    $classCount = $classStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count pending payments
    $paymentStmt = $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'");
    $pendingPayments = $paymentStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'students' => (int)$studentCount,
            'teachers' => (int)$teacherCount,
            'classes' => (int)$classCount,
            'pending_payments' => (int)$pendingPayments
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}