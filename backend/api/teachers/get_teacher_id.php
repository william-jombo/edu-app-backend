

<?php
// FILE: backend/api/teachers/get_teacher_id.php
// Updated for new database structure
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../../config/Database.php';

try {
    if (!isset($_GET['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required'
        ]);
        exit;
    }
    
    $user_id = $_GET['user_id'];
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get teacher ID from teachers table (new structure)
    $query = "SELECT id as teacher_id FROM teachers WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'teacher_id' => $result['teacher_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>