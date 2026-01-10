



<?php
// // ============================================================================
// // FILE: backend/api/admin/get_students_by_form.php
// // ============================================================================
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');

// require_once '../../config/database.php';

// try {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $form = isset($_GET['form']) ? $_GET['form'] : null;
    
//     if (!$form) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Form/grade level is required'
//         ]);
//         exit;
//     }
    
//     // Query students by grade level
//     $query = "SELECT 
//                 s.id,
//                 s.student_number,
//                 s.firstname,
//                 s.lastname,
//                 u.email,
//                 s.phone,
//                 s.class_id,
//                 c.class_name,
//                 c.grade_level,
//                 s.date_of_birth,
//                 s.gender,
//                 s.guardian_name,
//                 s.guardian_phone,
//                 s.status,
//                 s.enrollment_date,
//                 s.created_at
//               FROM students s
//               INNER JOIN users u ON s.user_id = u.id
//               LEFT JOIN classes c ON s.class_id = c.id
//               WHERE c.grade_level = :form AND s.status = 'active'
//               ORDER BY s.firstname, s.lastname";
    
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':form', $form);
//     $stmt->execute();
    
//     $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     echo json_encode([
//         'success' => true,
//         'students' => $students,
//         'count' => count($students)
//     ]);
    
// } catch (PDOException $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Database error: ' . $e->getMessage()
//     ]);
// }
?>






<?php
require_once '../../includes/cors.php';
// ============================================================================
// FILE: backend/api/admin/get_students.php
// ============================================================================
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Accept 'form' parameter (e.g., "Form 1", "Form 2", etc.)
    $form = isset($_GET['form']) ? $_GET['form'] : null;
    
    if (!$form) {
        echo json_encode([
            'success' => false,
            'message' => 'Form/grade level is required'
        ]);
        exit;
    }
    
    // Query students by grade level
    $query = "SELECT 
                s.id,
                s.student_number,
                s.firstname,
                s.lastname,
                u.email,
                s.phone,
                s.class_id,
                c.class_name,
                c.grade_level,
                s.date_of_birth,
                s.gender,
                s.guardian_name,
                s.guardian_phone,
                s.status,
                s.enrollment_date,
                s.created_at
              FROM students s
              INNER JOIN users u ON s.user_id = u.id
              LEFT JOIN classes c ON s.class_id = c.id
              WHERE c.grade_level = :form AND s.status = 'active'
              ORDER BY s.firstname, s.lastname";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':form', $form);
    $stmt->execute();
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'count' => count($students)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>