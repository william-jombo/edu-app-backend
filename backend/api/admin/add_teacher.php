<?php
header('Content-Type: application/json');
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->first_name) &&
    !empty($data->last_name) &&
    !empty($data->email) &&
    !empty($data->password)
) {
    
    // Check if email already exists
    $check_query = "SELECT id FROM tbl_users WHERE email = :email";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":email", $data->email);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email already exists"
        ]);
        exit();
    }

    $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);

    try {
        $db->beginTransaction();

        // Insert into tbl_users
        $query1 = "INSERT INTO tbl_users (email, password, role, first_name, last_name, phone, status) 
                   VALUES (:email, :password, 'teacher', :first_name, :last_name, :phone, 'active')";
        
        $stmt1 = $db->prepare($query1);
        $stmt1->bindParam(":email", $data->email);
        $stmt1->bindParam(":password", $hashed_password);
        $stmt1->bindParam(":first_name", $data->first_name);
        $stmt1->bindParam(":last_name", $data->last_name);
        $phone = $data->phone ?? null;
        $stmt1->bindParam(":phone", $phone);
        $stmt1->execute();

        $user_id = $db->lastInsertId();

        // Insert into tbl_teacher with subject
        $query2 = "INSERT INTO tbl_teacher (user_id, teacher_id, firstname, lastname, email, password, subject, status) 
                   VALUES (:user_id, :teacher_id, :first_name, :last_name, :email, :password, :subject, 'active')";
        
        $stmt2 = $db->prepare($query2);
        $stmt2->bindParam(":user_id", $user_id);
        $stmt2->bindParam(":teacher_id", $user_id);
        $stmt2->bindParam(":first_name", $data->first_name);
        $stmt2->bindParam(":last_name", $data->last_name);
        $stmt2->bindParam(":email", $data->email);
        $stmt2->bindParam(":password", $hashed_password);
        $subject = $data->subject ?? null;
        $stmt2->bindParam(":subject", $subject);
        $stmt2->execute();

        $db->commit();

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Teacher added successfully",
            "user_id" => $user_id
        ]);

    } catch(Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to add teacher: " . $e->getMessage()
        ]);
    }

} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Incomplete data"
    ]);
}
?>