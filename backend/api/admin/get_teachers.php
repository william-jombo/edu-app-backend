



<?php
// header('Content-Type: application/json');
// header('Access-Control-Allow-Origin: *');
// include_once '../../config/database.php';

// $database = new Database();
// $db = $database->getConnection();

// try {
//     $query = "SELECT t.id, t.user_id, u.email, u.first_name, u.last_name, u.phone, u.status, t.subject
//               FROM tbl_teacher t
//               LEFT JOIN tbl_users u ON u.id = t.user_id
//               WHERE u.role = 'teacher'
//               ORDER BY t.id DESC";
    
//     $stmt = $db->prepare($query);
//     $stmt->execute();

//     $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

//     echo json_encode([
//         "success" => true,
//         "teachers" => $teachers,
//         "count" => count($teachers)
//     ]);

// } catch(Exception $e) {
//     http_response_code(500);
//     echo json_encode([
//         "success" => false,
//         "message" => "Error: " . $e->getMessage()
//     ]);
// }
?>







<?php
// // FILE: backend/api/admin/get_teachers.php
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');

// require_once '../../config/Database.php';

// try {
//     $database = new Database();
//     $conn = $database->getConnection();
    
//     // Query to get teachers with their assigned subjects
//     $query = "SELECT 
//                 t.id,
//                 t.firstname AS first_name,
//                 t.lastname AS last_name,
//                 t.email,
//                 t.phone,
//                 t.department,
//                 t.status,
//                 t.teacher_id,
//                 GROUP_CONCAT(DISTINCT s.subject_name SEPARATOR ', ') AS subjects
//               FROM tbl_teacher t
//               LEFT JOIN tbl_teacher_classes tc ON t.id = tc.teacher_id
//               LEFT JOIN tbl_subjects s ON tc.subject_id = s.id
//               GROUP BY t.id
//               ORDER BY t.firstname, t.lastname";
    
//     $stmt = $conn->prepare($query);
//     $stmt->execute();
    
//     $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     // Format the data for display
//     foreach ($teachers as &$teacher) {
//         $teacher['subject'] = $teacher['subjects'] ?? 'N/A'; // Use the grouped subjects
//         unset($teacher['subjects']); // Remove the temporary field
//     }
    
//     echo json_encode([
//         'success' => true,
//         'teachers' => $teachers
//     ]);
    
// } catch (PDOException $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Database error: ' . $e->getMessage()
//     ]);
// }
?>










<?php
// ============================================================================
// FILE: backend/api/admin/get_teachers.php
// ============================================================================
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../../config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Query to get teachers with their email from users table
    $query = "SELECT 
                t.id,
                t.teacher_id,
                t.firstname,
                t.lastname,
                u.email,
                t.phone,
                t.department,
                t.specialization,
                t.status,
                t.hire_date,
                t.created_at,
                GROUP_CONCAT(DISTINCT s.subject_name ORDER BY s.subject_name SEPARATOR ', ') as subjects,
                GROUP_CONCAT(DISTINCT c.class_name ORDER BY c.class_name SEPARATOR ', ') as classes
              FROM teachers t
              INNER JOIN users u ON t.user_id = u.id
              LEFT JOIN teacher_assignments ta ON t.id = ta.teacher_id AND ta.status = 'active'
              LEFT JOIN subjects s ON ta.subject_id = s.id
              LEFT JOIN classes c ON ta.class_id = c.id
              WHERE u.status = 'active'
              GROUP BY t.id, t.teacher_id, t.firstname, t.lastname, u.email, t.phone, 
                       t.department, t.specialization, t.status, t.hire_date, t.created_at
              ORDER BY t.firstname, t.lastname";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'teachers' => $teachers,
        'count' => count($teachers)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>


<?php