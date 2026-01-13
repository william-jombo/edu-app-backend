<?php
//C:\Users\BR\Desktop\calmtech\php\htdocs\edu-app-backend\backend\api\auth\logout.php
session_start();
header('Content-Type: application/json');

session_destroy();

echo json_encode([
    "success" => true,
    "message" => "Logged out successfully"
]);
?>