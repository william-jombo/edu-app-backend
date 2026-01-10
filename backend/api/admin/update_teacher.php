

<?php
// //C:\Users\BR\Desktop\calmtech\php\htdocs\backend\api\admin\update_teacher.php
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');
// header('Content-Type: application/json');

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     exit(0);
// }

// require_once '../../config/database.php';

// try {
//     $database = new Database();
//     $db = $database->getConnection();
    
//     $data = json_decode(file_get_contents("php://input"));
    
//     if (empty($data->id) || empty($data->first_name) || empty($data->last_name) || empty($data->email)) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Missing required fields'
//         ]);
//         exit;
//     }
    
//     // Update teacher table (note: firstname and lastname, not first_name/last_name)
//     $query = "UPDATE tbl_teacher SET 
//               firstname = :first_name,
//               lastname = :last_name,
//               email = :email,
//               phone = :phone,
//               department = :department,
//               subject = :subject,
//               status = :status
//               WHERE id = :id";
    
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':id', $data->id);
//     $stmt->bindParam(':first_name', $data->first_name);
//     $stmt->bindParam(':last_name', $data->last_name);
//     $stmt->bindParam(':email', $data->email);
//     $stmt->bindParam(':phone', $data->phone);           // ✅ FIXED
//     $stmt->bindParam(':department', $data->department); // ✅ FIXED
//     $stmt->bindParam(':subject', $data->subject);
//     $stmt->bindParam(':status', $data->status);
    
//     if ($stmt->execute()) {
//         // Check if password needs to be updated
//         $passwordUpdate = '';
//         $passwordParam = null;
        
//         if (!empty($data->password)) {
//             // Hash the password before storing
//             $passwordParam = password_hash($data->password, PASSWORD_DEFAULT);
//             $passwordUpdate = ', password = :password';
            
//             // Also update password in tbl_teacher
//             $teacherPasswordQuery = "UPDATE tbl_teacher SET password = :password WHERE id = :teacher_id";
//             $teacherPwdStmt = $db->prepare($teacherPasswordQuery);
//             $teacherPwdStmt->bindParam(':password', $passwordParam);
//             $teacherPwdStmt->bindParam(':teacher_id', $data->id);
//             $teacherPwdStmt->execute();
//         }
        
//         // Update tbl_users table
//         $updateUserQuery = "UPDATE tbl_users SET 
//                            first_name = :first_name,
//                            last_name = :last_name,
//                            email = :email,
//                            phone = :phone,
//                            status = :status
//                            $passwordUpdate
//                            WHERE id = (SELECT user_id FROM tbl_teacher WHERE id = :teacher_id)";
        
//         $userStmt = $db->prepare($updateUserQuery);
//         $userStmt->bindParam(':first_name', $data->first_name);
//         $userStmt->bindParam(':last_name', $data->last_name);
//         $userStmt->bindParam(':email', $data->email);
//         $userStmt->bindParam(':phone', $data->phone);
//         $userStmt->bindParam(':status', $data->status);
        
//         // Bind password only if it's being updated
//         if (!empty($data->password)) {
//             $userStmt->bindParam(':password', $passwordParam);
//         }
        
//         $userStmt->bindParam(':teacher_id', $data->id);
//         $userStmt->execute();
        
//         echo json_encode([
//             'success' => true,
//             'message' => 'Teacher updated successfully'
//         ]);
//     } else {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Failed to update teacher'
//         ]);
//     }
    
// } catch (PDOException $e) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Database error: ' . $e->getMessage()
//     ]);
// }
?>


<?php
// ============================================================================
// FILE: backend/api/admin/update_teacher.php
// ============================================================================
require_once '../../includes/cors.php';
require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->id) || empty($data->firstname) || empty($data->lastname) || empty($data->email)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Update teacher table
    $query = "UPDATE teachers SET 
              firstname = :firstname,
              lastname = :lastname,
              phone = :phone,
              department = :department,
              specialization = :specialization,
              status = :status
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':firstname', $data->firstname);
    $stmt->bindParam(':lastname', $data->lastname);
    $stmt->bindParam(':phone', $data->phone);
    $stmt->bindParam(':department', $data->department);
    $stmt->bindParam(':specialization', $data->specialization);
    $stmt->bindParam(':status', $data->status);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update teacher');
    }
    
    // Get user_id for this teacher
    $getUserQuery = "SELECT user_id FROM teachers WHERE id = :teacher_id";
    $getUserStmt = $db->prepare($getUserQuery);
    $getUserStmt->bindParam(':teacher_id', $data->id);
    $getUserStmt->execute();
    $teacher = $getUserStmt->fetch(PDO::FETCH_ASSOC);
    
    // Update users table
    $passwordUpdate = '';
    $updateUserQuery = "UPDATE users SET 
                       email = :email,
                       status = :status";
    
    // Add password update if provided
    if (!empty($data->password)) {
        $passwordUpdate = ', password = :password';
        $updateUserQuery .= $passwordUpdate;
    }
    
    $updateUserQuery .= " WHERE id = :user_id";
    
    $userStmt = $db->prepare($updateUserQuery);
    $userStmt->bindParam(':email', $data->email);
    $userStmt->bindParam(':status', $data->status);
    
    if (!empty($data->password)) {
        $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT);
        $userStmt->bindParam(':password', $hashedPassword);
    }
    
    $userStmt->bindParam(':user_id', $teacher['user_id']);
    $userStmt->execute();
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Teacher updated successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
