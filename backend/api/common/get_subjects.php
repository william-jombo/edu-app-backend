<?php
// backend/api/common/get_subjects.php
// Fetch all active subjects - Updated for Supabase PostgreSQL

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once '../../config/database.php';

try {
    $db = getDBConnection();
    
    // Fetch all active subjects from subjects table
    $query = "SELECT 
                id, 
                subject_name, 
                subject_code, 
                description, 
                credit_hours,
                status
              FROM subjects 
              WHERE status = 'active'
              ORDER BY subject_name ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'subjects' => $subjects,
        'count' => count($subjects)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error fetching subjects: ' . $e->getMessage()
    ]);
}
?>