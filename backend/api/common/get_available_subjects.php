<?php
// // FILE: backend/api/get_available_subjects.php
// // Alternate endpoint name for fetching subjects
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');
// header('Access-Control-Allow-Methods: GET');
// header('Access-Control-Allow-Headers: Content-Type');

// include_once'../../config/database.php';

// try {
//     $database = new Database();
//     $conn = $database->getConnection();
    
//     // Fetch all active subjects
//     $query = "SELECT id, subject_name, subject_code, description, credit_hours 
//               FROM tbl_subject 
//               WHERE status = 'active'
//               ORDER BY subject_name";
    
//     $stmt = $conn->prepare($query);
//     $stmt->execute();
    
//     $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     echo json_encode([
//         'success' => true, 
//         'subjects' => $subjects
//     ]);
    
// } catch (Exception $e) {
//     echo json_encode([
//         'success' => false, 
//         'message' => 'Error fetching subjects: ' . $e->getMessage()
//     ]);
// }
?>






<?php
// backend/api/common/get_available_subjects.php
// Updated for new database structure

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Get all active subjects
    $query = "SELECT 
                id, 
                subject_name, 
                subject_code, 
                description,
                credit_hours,
                status
              FROM subjects 
              WHERE status = 'active' 
              ORDER BY subject_name";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "subjects" => $subjects
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error fetching subjects: " . $e->getMessage()
    ]);
}
?>