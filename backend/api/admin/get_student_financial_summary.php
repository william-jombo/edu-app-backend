




<?php
require_once '../../includes/cors.php';
// ============================================================================
// FILE: backend/api/admin/get_fee_summary.php
// ============================================================================
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $academic_year = $_GET['academic_year'] ?? date('Y');
    
    $query = "SELECT 
                s.id as student_id,
                s.student_number,
                CONCAT(s.firstname, ' ', s.lastname) as student_name,
                u.email,
                c.class_name,
                c.grade_level,
                COALESCE(fs.total_amount, 0) as total_fees,
                COALESCE(SUM(CASE WHEN p.status = 'verified' THEN p.amount ELSE 0 END), 0) as paid_amount,
                COALESCE(fs.total_amount, 0) - COALESCE(SUM(CASE WHEN p.status = 'verified' THEN p.amount ELSE 0 END), 0) as balance,
                fs.due_date,
                CASE 
                    WHEN fs.due_date < CURDATE() AND (COALESCE(fs.total_amount, 0) - COALESCE(SUM(CASE WHEN p.status = 'verified' THEN p.amount ELSE 0 END), 0)) > 0 
                    THEN 'overdue'
                    WHEN (COALESCE(fs.total_amount, 0) - COALESCE(SUM(CASE WHEN p.status = 'verified' THEN p.amount ELSE 0 END), 0)) = 0 
                    THEN 'paid'
                    ELSE 'pending'
                END as payment_status
              FROM students s
              INNER JOIN users u ON s.user_id = u.id
              INNER JOIN classes c ON s.class_id = c.id
              LEFT JOIN fee_structures fs ON fs.class_id = c.id AND fs.academic_year = ?
              LEFT JOIN payments p ON p.student_id = s.id AND p.academic_year = ?
              WHERE s.status = 'active'
              GROUP BY s.id, s.student_number, s.firstname, s.lastname, u.email, 
                       c.class_name, c.grade_level, fs.total_amount, fs.due_date
              ORDER BY c.class_name, s.lastname, s.firstname";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$academic_year, $academic_year]);
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $totalExpected = array_sum(array_column($summary, 'total_fees'));
    $totalCollected = array_sum(array_column($summary, 'paid_amount'));
    $totalOutstanding = array_sum(array_column($summary, 'balance'));
    
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'totals' => [
            'expected' => $totalExpected,
            'collected' => $totalCollected,
            'outstanding' => $totalOutstanding
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
