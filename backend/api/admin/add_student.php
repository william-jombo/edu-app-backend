<?php
// backend/api/admin/add_student.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(0); }

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    // Required fields
    foreach (['firstname','lastname','email','password','student_number','class_id'] as $f) {
        if (empty($data[$f])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing required field: $f"]);
            exit;
        }
    }

    if (empty($data['subjects']) || !is_array($data['subjects']) || count($data['subjects']) < 7) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please select at least 7 subjects']);
        exit;
    }

    $db = getDBConnection();
    $db->beginTransaction();

    try {
        // Duplicate checks
        $s = $db->prepare("SELECT id FROM users WHERE email = ?");
        $s->execute([$data['email']]);
        if ($s->fetch()) throw new Exception('This email is already registered.');

        $s = $db->prepare("SELECT id FROM students WHERE student_number = ?");
        $s->execute([$data['student_number']]);
        if ($s->fetch()) throw new Exception('This student number is already in use.');

        // 1. users
        $s = $db->prepare("INSERT INTO users (email, password, role, status, created_at)
                            VALUES (?, ?, 'student', 'active', CURRENT_TIMESTAMP)");
        $s->execute([$data['email'], password_hash($data['password'], PASSWORD_DEFAULT)]);
        $user_id = $db->lastInsertId();

        // 2. students
        $s = $db->prepare("INSERT INTO students
                            (user_id, student_number, firstname, lastname, phone, class_id,
                             date_of_birth, gender, address, guardian_name, guardian_phone,
                             status, enrollment_date, created_at)
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,'active',CURRENT_DATE,CURRENT_TIMESTAMP)");
        $s->execute([
            $user_id,
            $data['student_number'],
            $data['firstname'],
            $data['lastname'],
            $data['phone']           ?? null,
            $data['class_id'],
            $data['date_of_birth']   ?: null,
            $data['gender']          ?: null,
            $data['address']         ?: null,
            $data['guardian_name']   ?: null,
            $data['guardian_phone']  ?: null,
        ]);
        $student_id = $db->lastInsertId();

        // 3. class_enrollments
        $s = $db->prepare("INSERT INTO class_enrollments (student_id, class_id, enrollment_date, status, created_at)
                            VALUES (?, ?, CURRENT_DATE, 'active', CURRENT_TIMESTAMP)");
        $s->execute([$student_id, $data['class_id']]);

        // 4. subject_enrollments
        $s = $db->prepare("INSERT INTO subject_enrollments (student_id, subject_id, enrollment_date, status, created_at)
                            VALUES (?, ?, CURRENT_DATE, 'active', CURRENT_TIMESTAMP)");
        foreach ($data['subjects'] as $subject_id) {
            $s->execute([$student_id, $subject_id]);
        }

        $db->commit();
        http_response_code(201);
        echo json_encode([
            'success'    => true,
            'message'    => 'Student added successfully.',
            'data'       => ['student_id' => $student_id, 'student_number' => $data['student_number']]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>