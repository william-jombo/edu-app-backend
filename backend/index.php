<?php
// // Minimal status page to avoid Apache 403 when directory listing is disabled.
// http_response_code(200);
// header('Content-Type: text/plain; charset=utf-8');
// echo "edu-app-backend: OK\n";

?>





<?php


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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
?>
