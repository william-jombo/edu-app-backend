<?php
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');
// require_once '../../config/database.php';

// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     echo json_encode(['success' => false, 'message' => 'Invalid request method']);
//     exit;
// }

// $data = json_decode(file_get_contents('php://input'), true);

// $submission_id = $data['submission_id'] ?? null;
// $grade = $data['grade'] ?? null;
// $feedback = $data['feedback'] ?? null;

// if (!$submission_id || !$grade) {
//     echo json_encode(['success' => false, 'message' => 'Missing required fields']);
//     exit;
// }

// try {
//     $db = new Database();
//     $conn = $db->getConnection();
    
//     $query = "UPDATE tbl_assignment_submissions 
//               SET grade = ?,
//                   feedback = ?,
//                   status = 'graded'
//               WHERE id = ?";
    
//     $stmt = $conn->prepare($query);
//     $stmt->execute([$grade, $feedback, $submission_id]);
    
//     echo json_encode([
//         'success' => true,
//         'message' => 'Assignment graded successfully'
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Error: ' . $e->getMessage()
//     ]);
// }

?>







<?php
// ============================================================================
// FILE: backend/api/admin/grade_submission.php
// ============================================================================
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$submission_id = $data['submission_id'] ?? null;
$grade = $data['grade'] ?? null;
$feedback = $data['feedback'] ?? null;
$graded_by = $data['graded_by'] ?? null; // teacher_id

if (!$submission_id || $grade === null) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "UPDATE assignment_submissions 
              SET grade = ?,
                  feedback = ?,
                  status = 'graded',
                  graded_at = NOW(),
                  graded_by = ?
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$grade, $feedback, $graded_by, $submission_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Assignment graded successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>


<?php