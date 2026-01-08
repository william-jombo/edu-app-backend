
<?php
// // FILE: backend/api/admin/add_teacher_with_assignments.php
// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');
// header('Access-Control-Allow-Methods: POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     exit(0);
// }

// require_once '../../config/Database.php';

// try {
//     $data = json_decode(file_get_contents("php://input"), true);
    
//     // Validate required fields
//     if (!isset($data['first_name']) || !isset($data['last_name']) || 
//         !isset($data['email']) || !isset($data['password']) ||
//         !isset($data['subjects']) || !isset($data['classes'])) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Missing required fields'
//         ]);
//         exit;
//     }

//     if (count($data['subjects']) === 0) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Please select at least one subject'
//         ]);
//         exit;
//     }

//     if (count($data['classes']) === 0) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Please select at least one class'
//         ]);
//         exit;
//     }

//     $database = new Database();
//     $conn = $database->getConnection();
    
//     // Start transaction
//     $conn->beginTransaction();
    
//     // 1. Check if email already exists
//     $checkQuery = "SELECT id FROM teachers WHERE email = ?";
//     $checkStmt = $conn->prepare($checkQuery);
//     $checkStmt->execute([$data['email']]);
    
//     if ($checkStmt->fetch()) {
//         throw new Exception('Email already exists');
//     }
    
//     // 2. Generate teacher_id
//     $teacher_id_prefix = 'TCH' . date('Y');
//     $countQuery = "SELECT COUNT(*) as count FROM teachers WHERE teacher_id LIKE ?";
//     $countStmt = $conn->prepare($countQuery);
//     $countStmt->execute([$teacher_id_prefix . '%']);
//     $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
//     $teacher_id = $teacher_id_prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    
//     // 2.5. Get the subject name for the first selected subject (for legacy column)
//     $firstSubjectId = $data['subjects'][0];
//     $subjectQuery = "SELECT subject_name FROM subjects WHERE id = ?";
//     $subjectStmt = $conn->prepare($subjectQuery);
//     $subjectStmt->execute([$firstSubjectId]);
//     $subjectData = $subjectStmt->fetch(PDO::FETCH_ASSOC);
//     $subjectName = $subjectData ? $subjectData['subject_name'] : null;

//     // 3. Insert teacher into teachers (NOW WITH SUBJECT!)
//     $insertTeacherQuery = "INSERT INTO teachers
//         (firstname, lastname, email, password, teacher_id, phone, department, subject, status, hire_date) 
//         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', CURDATE())";
    
//     $stmt = $conn->prepare($insertTeacherQuery);
//     $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
//     $stmt->execute([
//         $data['firstname'],
//         $data['lastname'],
//         $data['email'],
//         $hashedPassword,
//         $teacher_id,
//         $data['phone'] ?? null,
//         $data['department'] ?? null,
//         $subjectName  // ← ADDED THIS!
//     ]);
    
//     $new_teacher_id = $conn->lastInsertId();
    
//     // 4. Create user account in users
//     $insertUserQuery = "INSERT INTO users 
//         (email, password, role, status) 
//         VALUES (?, ?, 'teacher', 'active')";
    
//     $userStmt = $conn->prepare($insertUserQuery);
//     $userStmt->execute([
//         $data['email'],
//         $hashedPassword,
//         // $data['first_name'],
//         // $data['last_name'],
//         // $data['phone'] ?? null
//     ]);
    
//     $user_id = $conn->lastInsertId();
    
//     // Update teacher with user_id
//     $updateTeacherQuery = "UPDATE teachers SET user_id = ? WHERE id = ?";
//     $updateStmt = $conn->prepare($updateTeacherQuery);
//     $updateStmt->execute([$user_id, $new_teacher_id]);

//     // 5. Insert teacher-class-subject assignments into teacher_assignments
//     $insertAssignmentQuery = "INSERT INTO teacher_assignments 
//         (teacher_id, class_id, subject_id) 
//         VALUES (?, ?, ?)";
    
//     $assignmentStmt = $conn->prepare($insertAssignmentQuery);
//     $assignmentCount = 0;
    
//     // Create assignments for each combination of class and subject
//     foreach ($data['classes'] as $class_id) {
//         foreach ($data['subjects'] as $subject_id) {
//             $assignmentStmt->execute([
//                 $new_teacher_id,
//                 $class_id,
//                 $subject_id
//             ]);
//             $assignmentCount++;
//         }
//     }
    
//     // Commit transaction
//     $conn->commit();
    
//     echo json_encode([
//         'success' => true,
//         'message' => 'Teacher added successfully',
//         'teacher_id' => $teacher_id,
//         'assignments_created' => $assignmentCount,
//         'subjects_count' => count($data['subjects']),
//         'classes_count' => count($data['classes'])
//     ]);
    
// } catch (Exception $e) {
//     if (isset($conn)) {
//         $conn->rollBack();
//     }
//     echo json_encode([
//         'success' => false,
//         'message' => $e->getMessage()
//     ]);
// }
?>















<?php
// ============================================================================
// FILE: backend/api/admin/add_teacher_with_assignments.php
// ============================================================================
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/Database.php';

try {
    // Get raw input
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    
    // Log what we received for debugging
    error_log("Received data: " . print_r($data, true));
    
    // Check if JSON decode was successful
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data: ' . json_last_error_msg()
        ]);
        exit;
    }
    
    // Check if data is empty
    if (empty($data)) {
        echo json_encode([
            'success' => false,
            'message' => 'No data received'
        ]);
        exit;
    }
    
    // Build a detailed error message for missing fields
    $missingFields = [];
    
    if (!isset($data['firstname']) || empty($data['firstname'])) {
        $missingFields[] = 'firstname';
    }
    if (!isset($data['lastname']) || empty($data['lastname'])) {
        $missingFields[] = 'lastname';
    }
    if (!isset($data['email']) || empty($data['email'])) {
        $missingFields[] = 'email';
    }
    if (!isset($data['password']) || empty($data['password'])) {
        $missingFields[] = 'password';
    }
    if (!isset($data['subjects'])) {
        $missingFields[] = 'subjects';
    }
    if (!isset($data['classes'])) {
        $missingFields[] = 'classes';
    }
    
    if (!empty($missingFields)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: ' . implode(', ', $missingFields),
            'received_fields' => array_keys($data),
            'debug' => 'Check field names match exactly'
        ]);
        exit;
    }

    if (count($data['subjects']) === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Please select at least one subject'
        ]);
        exit;
    }

    if (count($data['classes']) === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Please select at least one class'
        ]);
        exit;
    }

    $database = new Database();
    $conn = $database->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    // 1. Check if email already exists in users table
    $checkQuery = "SELECT id FROM users WHERE email = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$data['email']]);
    
    if ($checkStmt->fetch()) {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Email already exists'
        ]);
        exit;
    }
    
    // 2. Generate teacher_id if not provided
    if (empty($data['teacher_id'])) {
        $teacher_id_prefix = 'TCH' . date('Y');
        $countQuery = "SELECT COUNT(*) as count FROM teachers WHERE teacher_id LIKE ?";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute([$teacher_id_prefix . '%']);
        $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        $teacher_id = $teacher_id_prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $teacher_id = $data['teacher_id'];
    }

    // 3. Create user account first
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $insertUserQuery = "INSERT INTO users (email, password, role, status) 
                        VALUES (?, ?, 'teacher', 'active')";
    
    $userStmt = $conn->prepare($insertUserQuery);
    $userStmt->execute([$data['email'], $hashedPassword]);
    
    $user_id = $conn->lastInsertId();

    // 4. Insert teacher into teachers table
    $insertTeacherQuery = "INSERT INTO teachers
        (user_id, teacher_id, firstname, lastname, phone, department, specialization, hire_date, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), 'active')";
    
    $stmt = $conn->prepare($insertTeacherQuery);
    $stmt->execute([
        $user_id,
        $teacher_id,
        $data['firstname'],
        $data['lastname'],
        $data['phone'] ?? null,
        $data['department'] ?? null,
        $data['specialization'] ?? null
    ]);
    
    $new_teacher_id = $conn->lastInsertId();

    // 5. Insert teacher assignments into teacher_assignments table
    $insertAssignmentQuery = "INSERT INTO teacher_assignments 
        (teacher_id, class_id, subject_id, academic_year, status) 
        VALUES (?, ?, ?, ?, 'active')";
    
    $assignmentStmt = $conn->prepare($insertAssignmentQuery);
    $assignmentCount = 0;
    $current_year = date('Y');
    
    // Create assignments for each combination of class and subject
    foreach ($data['classes'] as $class_id) {
        foreach ($data['subjects'] as $subject_id) {
            $assignmentStmt->execute([
                $new_teacher_id,
                $class_id,
                $subject_id,
                $current_year
            ]);
            $assignmentCount++;
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Teacher added successfully',
        'teacher_id' => $teacher_id,
        'db_teacher_id' => $new_teacher_id,
        'assignments_created' => $assignmentCount,
        'subjects_count' => count($data['subjects']),
        'classes_count' => count($data['classes'])
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log the full error
    error_log("Error adding teacher: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => $e->getFile() . ':' . $e->getLine()
    ]);
}
?>