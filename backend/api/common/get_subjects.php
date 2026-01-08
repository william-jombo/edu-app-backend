<?php
// FILE: backend/api/common/get_subjects.php
// Updated for NEW database structure

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

include_once '../../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Fetch all active subjects from subjects table (NEW)
    $query = "SELECT id, subject_name, subject_code, description, credit_hours 
              FROM subjects 
              WHERE status = 'active'
              ORDER BY subject_name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'subjects' => $subjects
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error fetching subjects: ' . $e->getMessage()
    ]);
}
?>