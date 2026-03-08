<?php
// backend/api/admin/get_students.php
// FLEXIBLE: Handles multiple grade_level formats and filters

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once '../../config/database.php';

try {
    $conn = getDBConnection();
    
    // Get filters
    $form = $_GET['form'] ?? null;
    $classId = $_GET['class_id'] ?? null;
    $status = $_GET['status'] ?? 'active';
    $studentId = $_GET['student_id'] ?? null;
    $search = $_GET['search'] ?? null;
    
    $query = "
        SELECT 
            s.id,
            s.user_id,
            s.student_number,
            s.firstname,
            s.lastname,
            s.phone,
            s.profile_pic,
            s.class_id,
            s.date_of_birth,
            s.gender,
            s.address,
            s.guardian_name,
            s.guardian_phone,
            s.status,
            s.enrollment_date,
            s.created_at,
            s.updated_at,
            u.email,
            c.class_name,
            c.grade_level
        FROM students s
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Filter by student ID
    if ($studentId) {
        $query .= " AND s.id = :student_id";
        $params[':student_id'] = $studentId;
    }
    
    // Filter by class ID (priority over form filter)
    if ($classId) {
        $query .= " AND s.class_id = :class_id";
        $params[':class_id'] = $classId;
    }
    // Filter by form/grade level (if class_id not provided)
    elseif ($form) {
        // Match against multiple possible formats:
        // 1. Exact match on grade_level
        // 2. Exact match on class_name  
        // 3. If form is "Form 1", also try just "1"
        // 4. If form is "1", also try "Form 1"
        
        $query .= " AND (
            c.grade_level = :form 
            OR c.class_name = :form
            OR c.grade_level = :form_alt1
            OR c.class_name = :form_alt2
        )";
        
        $params[':form'] = $form;
        
        // Try alternative formats
        if (preg_match('/Form (\d+)/i', $form, $matches)) {
            // Input is "Form 1" -> also try "1"
            $params[':form_alt1'] = $matches[1];
            $params[':form_alt2'] = $matches[1];
        } else if (is_numeric($form)) {
            // Input is "1" -> try "Form 1"
            $params[':form_alt1'] = "Form " . $form;
            $params[':form_alt2'] = "Form " . $form;
        } else {
            // Input is something else, just use same value
            $params[':form_alt1'] = $form;
            $params[':form_alt2'] = $form;
        }
    }
    
    // Filter by status
    if ($status !== 'all') {
        $query .= " AND s.status = :status";
        $params[':status'] = $status;
    }
    
    // Search filter
    if ($search) {
        $query .= " AND (
            s.firstname ILIKE :search OR 
            s.lastname ILIKE :search OR 
            s.student_number ILIKE :search OR
            u.email ILIKE :search
        )";
        $params[':search'] = '%' . $search . '%';
    }
    
    $query .= " ORDER BY s.lastname ASC, s.firstname ASC";
    
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formattedStudents = array_map(function($student) {
        return [
            'id' => (int)$student['id'],
            'user_id' => (int)$student['user_id'],
            'student_number' => $student['student_number'],
            'firstname' => $student['firstname'],
            'lastname' => $student['lastname'],
            'full_name' => $student['firstname'] . ' ' . $student['lastname'],
            'email' => $student['email'],
            'phone' => $student['phone'],
            'profile_pic' => $student['profile_pic'],
            'class_id' => $student['class_id'] ? (int)$student['class_id'] : null,
            'class_name' => $student['class_name'],
            'grade_level' => $student['grade_level'],
            'date_of_birth' => $student['date_of_birth'],
            'gender' => $student['gender'],
            'address' => $student['address'],
            'guardian_name' => $student['guardian_name'],
            'guardian_phone' => $student['guardian_phone'],
            'status' => $student['status'],
            'enrollment_date' => $student['enrollment_date'],
            'created_at' => $student['created_at'],
            'updated_at' => $student['updated_at']
        ];
    }, $students);
    
    // Return with BOTH keys for compatibility
    echo json_encode([
        'success' => true,
        'message' => 'Students retrieved successfully',
        'data' => $formattedStudents,      // For report generator component
        'students' => $formattedStudents,  // For existing components
        'count' => count($formattedStudents),
        'filter' => [
            'form' => $form,
            'class_id' => $classId,
            'status' => $status
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching students: ' . $e->getMessage(),
        'data' => [],
        'students' => []
    ]);
}
?>