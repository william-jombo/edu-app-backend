<?php

// //C:\Users\BR\Desktop\calmtech\php\htdocs\edu-app-backend\backend\includes\cors.php
// // CORS configuration - Updated for maximum compatibility
// $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// // Log for debugging
// error_log("CORS: Received origin: " . $origin);

// // Allow these specific origins
// $allowed_origins = [
//     'http://localhost:5173',
//     'http://localhost:3000',
//     'http://127.0.0.1:5173',
//     'https://edu-app-taupe.vercel.app',
// ];

// $allow_origin = '';

// // Check exact matches
// if (in_array($origin, $allowed_origins, true)) {
//     $allow_origin = $origin;
// } 
// // Check if ends with .vercel.app (for all preview deployments)
// elseif ($origin && (substr($origin, -11) === '.vercel.app')) {
//     $allow_origin = $origin;
// }

// // Send headers if we have a valid origin
// if ($allow_origin) {
//     header('Access-Control-Allow-Origin: ' . $allow_origin);
//     header('Access-Control-Allow-Credentials: false');
//     header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
//     header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
//     header('Access-Control-Max-Age: 86400');
//     error_log("CORS: Sent headers for origin: " . $allow_origin);
// } else {
//     error_log("CORS: No matching origin");
// }

// // Handle preflight
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit;
//}
?>



<?php
// C:\Users\BR\Desktop\calmtech\php\htdocs\edu-app-backend\backend\includes\cors.php

// NO CORS HEADERS NEEDED - Vercel proxy handles this!
// Requests come from Vercel server-to-server, not from browser

// Optional: Keep logging for debugging
error_log("Request received: " . $_SERVER['REQUEST_METHOD'] . " from " . ($_SERVER['HTTP_ORIGIN'] ?? 'no origin'));

// That's it! No headers, no preflight handling needed.
