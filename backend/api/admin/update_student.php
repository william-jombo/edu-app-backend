<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Student ID required']);
        exit;
    }

    foreach (['firstname', 'lastname', 'email', 'status'] as $f) {
        if (empty($data[$f])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing required field: $f"]);
            exit;
        }
    }

    $db = getDBConnection();
    $db->beginTransaction();

    try {
        // Check email not taken by another user
        $stmt = $db->prepare("
            SELECT u.id FROM users u
            INNER JOIN students s ON s.user_id = u.id
            WHERE u.email = ? AND s.id != ?
        ");
        $stmt->execute([$data['email'], $data['id']]);
        if ($stmt->fetch()) {
            throw new Exception('This email is already used by another account.');
        }

        // Check student_number not taken by another student
        if (!empty($data['student_number'])) {
            $stmt = $db->prepare("SELECT id FROM students WHERE student_number = ? AND id != ?");
            $stmt->execute([$data['student_number'], $data['id']]);
            if ($stmt->fetch()) {
                throw new Exception('This student number is already in use.');
            }
        }

        // Update students table
        $stmt = $db->prepare("
            UPDATE students SET
                firstname      = :firstname,
                lastname       = :lastname,
                phone          = :phone,
                class_id       = :class_id,
                date_of_birth  = :date_of_birth,
                gender         = :gender,
                address        = :address,
                guardian_name  = :guardian_name,
                guardian_phone = :guardian_phone,
                status         = :status,
                updated_at     = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            'id'             => $data['id'],
            'firstname'      => $data['firstname'],
            'lastname'       => $data['lastname'],
            'phone'          => !empty($data['phone'])          ? $data['phone']          : null,
            'class_id'       => !empty($data['class_id'])       ? $data['class_id']       : null,
            'date_of_birth'  => !empty($data['date_of_birth'])  ? $data['date_of_birth']  : null,
            'gender'         => !empty($data['gender'])         ? $data['gender']         : null,
            'address'        => !empty($data['address'])        ? $data['address']        : null,
            'guardian_name'  => !empty($data['guardian_name'])  ? $data['guardian_name']  : null,
            'guardian_phone' => !empty($data['guardian_phone']) ? $data['guardian_phone'] : null,
            'status'         => $data['status'],
        ]);

        // Get user_id for this student
        $stmt = $db->prepare("SELECT user_id FROM students WHERE id = ?");
        $stmt->execute([$data['id']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            throw new Exception('Student not found.');
        }

        // Update users table (conditionally include password)
        $userQuery = "UPDATE users SET email = :email, status = :status, updated_at = NOW()";
        if (!empty($data['password'])) {
            $userQuery .= ", password = :password";
        }
        $userQuery .= " WHERE id = :user_id";

        $stmt = $db->prepare($userQuery);
        $params = [
            'email'   => $data['email'],
            'status'  => $data['status'],
            'user_id' => $student['user_id'],
        ];
        if (!empty($data['password'])) {
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $stmt->execute($params);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Student updated successfully.',
            'data'    => ['student_id' => $data['id']]
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