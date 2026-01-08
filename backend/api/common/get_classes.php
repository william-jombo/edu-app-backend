<?php
// // C:\Users\BR\Desktop\calmtech\php\htdocs\backend\api\common\get_classes.php
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');

// require_once '../config/database.php';

// try {
//     $database = new Database();
//     $conn = $database->getConnection();
    
//     $query = "SELECT id, classname, grade_level, academic_year, semester 
//               FROM tbl_class 
//               ORDER BY id";
    
//     $stmt = $conn->prepare($query);
//     $stmt->execute();
    
//     $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     echo json_encode(['success' => true, 'classes' => $classes]);
    
// } catch (Exception $e) {
//     echo json_encode(['success' => false, 'message' => $e->getMessage()]);
// }
?>


<?php
// // FILE: backend/api/get_classes.php
// // Alternate endpoint name for fetching classes
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');

// include_once '../../config/database.php';

// try {
//     $database = new Database();
//     $conn = $database->getConnection();
    
//     $query = "SELECT id, classname, grade_level, academic_year, semester 
//               FROM tbl_class 
//               WHERE status = 'active'
//               ORDER BY grade_level, classname";
    
//     $stmt = $conn->prepare($query);
//     $stmt->execute();
    
//     $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     echo json_encode(['success' => true, 'classes' => $classes]);
    
// } catch (Exception $e) {
//     echo json_encode(['success' => false, 'message' => $e->getMessage()]);
// }
?>






<?php
// backend/api/common/get_classes.php
// Updated for new database structure

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Get all active classes
    $query = "SELECT 
                id, 
                class_name as classname, 
                grade_level, 
                academic_year, 
                semester,
                capacity,
                status
              FROM classes 
              WHERE status = 'active' 
              ORDER BY grade_level, class_name";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "classes" => $classes
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error fetching classes: " . $e->getMessage()
    ]);
}
?>
