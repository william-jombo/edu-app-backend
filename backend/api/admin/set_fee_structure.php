


<?php
require_once '../../includes/cors.php';
// ============================================================================
// FILE: backend/api/admin/set_fee_structure.php
// ============================================================================
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$class_id = $data['class_id'] ?? null;
$academic_year = $data['academic_year'] ?? date('Y');
$semester = $data['semester'] ?? null;
$tuition_fee = $data['tuition_fee'] ?? 0;
$lab_fee = $data['lab_fee'] ?? 0;
$library_fee = $data['library_fee'] ?? 0;
$sports_fee = $data['sports_fee'] ?? 0;
$examination_fee = $data['examination_fee'] ?? 0;
$other_fees = $data['other_fees'] ?? 0;
$due_date = $data['due_date'] ?? null;

if (!$class_id) {
    echo json_encode(['success' => false, 'message' => 'Class ID required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $total_amount = $tuition_fee + $lab_fee + $library_fee + $sports_fee + $examination_fee + $other_fees;
    
    // Check if fee structure already exists
    $checkQuery = "SELECT id FROM fee_structures 
                   WHERE class_id = ? AND academic_year = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$class_id, $academic_year]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        // Update existing structure
        $updateQuery = "UPDATE fee_structures 
                       SET total_amount = ?,
                           tuition_fee = ?,
                           lab_fee = ?,
                           library_fee = ?,
                           sports_fee = ?,
                           examination_fee = ?,
                           other_fees = ?,
                           semester = ?,
                           due_date = ?,
                           status = 'active'
                       WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([
            $total_amount, $tuition_fee, $lab_fee, $library_fee, 
            $sports_fee, $examination_fee, $other_fees, $semester, $due_date, $existing['id']
        ]);
        $message = 'Fee structure updated successfully';
    } else {
        // Insert new structure
        $insertQuery = "INSERT INTO fee_structures 
                       (class_id, academic_year, semester, total_amount, tuition_fee, lab_fee, 
                        library_fee, sports_fee, examination_fee, other_fees, due_date, status)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->execute([
            $class_id, $academic_year, $semester, $total_amount, $tuition_fee, $lab_fee,
            $library_fee, $sports_fee, $examination_fee, $other_fees, $due_date
        ]);
        $message = 'Fee structure created successfully';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
