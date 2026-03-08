<?php
// backend/api/common/get_available_subjects.php
// Fetch all active subjects for student registration

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
    $db = getDBConnection();
    
    // Get all active subjects
    $query = "SELECT 
                id,
                subject_name,
                subject_code,
                description,
                credit_hours
              FROM subjects
              WHERE status = 'active'
              ORDER BY subject_name ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'subjects' => $subjects
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching subjects: ' . $e->getMessage()
    ]);
}
?>