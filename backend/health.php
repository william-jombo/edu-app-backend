<?php
// Simple health check
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Backend is running',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>