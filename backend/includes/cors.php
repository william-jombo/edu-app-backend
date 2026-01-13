<?php
// CORS configuration - Updated to support all Vercel deployments
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Allowed origins
$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'https://edu-app-taupe.vercel.app',
];

// Determine which origin to allow
$allow_origin = '';

// Check if origin is in allowed list
if (in_array($origin, $allowed_origins, true)) {
    $allow_origin = $origin;
} 
// Allow ALL Vercel preview deployments (*.vercel.app)
// FIXED: Check if the origin ends with .vercel.app
elseif ($origin && str_ends_with($origin, '.vercel.app')) {
    $allow_origin = $origin;
}

// Only send headers if we have a valid origin
if ($allow_origin) {
    header('Access-Control-Allow-Origin: ' . $allow_origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
}

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}