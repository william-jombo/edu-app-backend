<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }
    
    if (empty($data)) {
        echo json_encode(['success' => false, 'message' => 'No data received']);
        exit;
    }
    
    // Validate required fields
    $missingFields = [];
    if (empty($data['firstname'])) $missingFields[] = 'firstname';
    if (empty($data['lastname'])) $missingFields[] = 'lastname';
    if (empty($data['email'])) $missingFields[] = 'email';
    if (empty($data['password'])) $missingFields[] = 'password';
    if (!isset($data['subjects'])) $missingFields[] = 'subjects';
    if (!isset($data['classes'])) $missingFields[] = 'classes';
    
    if (!empty($missingFields)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing: ' . implode(', ', $missingFields)
        ]);
        exit;
    }

    if (count($data['subjects']) === 0 || count($data['classes']) === 0) {
        echo json_encode(['success' => false, 'message' => 'Select at least one subject and class']);
        exit;
    }

    $conn = getDBConnection();
    $conn->beginTransaction();
    
    // Check email exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$data['email']]);
    
    if ($checkStmt->fetch()) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Generate teacher_id
    if (empty($data['teacher_id'])) {
        $prefix = 'TCH' . date('Y');
        $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM teachers WHERE teacher_id LIKE ?");
        $countStmt->execute([$prefix . '%']);
        $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        $teacher_id = $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $teacher_id = $data['teacher_id'];
    }

    // Create user
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    $userStmt = $conn->prepare("INSERT INTO users (email, password, role, status, created_at) VALUES (?, ?, 'teacher', 'active', NOW())");
    $userStmt->execute([$data['email'], $hashedPassword]);
    $user_id = $conn->lastInsertId();

    // Create teacher
    $teacherStmt = $conn->prepare("
        INSERT INTO teachers (user_id, teacher_id, firstname, lastname, phone, department, specialization, hire_date, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_DATE, 'active', NOW())
    ");
    $teacherStmt->execute([
        $user_id, $teacher_id, $data['firstname'], $data['lastname'],
        $data['phone'] ?? null, $data['department'] ?? null, $data['specialization'] ?? null
    ]);
    $new_teacher_id = $conn->lastInsertId();

    // Create assignments
    $assignStmt = $conn->prepare("INSERT INTO teacher_assignments (teacher_id, class_id, subject_id, academic_year, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
    $assignmentCount = 0;
    $current_year = date('Y');
    
    foreach ($data['classes'] as $class_id) {
        foreach ($data['subjects'] as $subject_id) {
            $assignStmt->execute([$new_teacher_id, $class_id, $subject_id, $current_year]);
            $assignmentCount++;
        }
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Teacher added successfully',
        'teacher_id' => $teacher_id,
        'assignments_created' => $assignmentCount
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}