
<!-- C:\Users\BR\Desktop\calmtech\php\htdocs\edu-app-backend\backend\index.php -->


<?php
//header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'EDU App Backend API is running!',
    'version' => '1.0',
    'timestamp' => date('Y-m-d H:i:s'),
    'endpoints' => [
        'auth' => '/api/auth/',
        'admin' => '/api/admin/',
        'student' => '/api/student/',
        'teacher' => '/api/teacher/'
    ]
]);