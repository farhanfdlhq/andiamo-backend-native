<?php
// /andiamo-backend-native/config/cors.php

$frontend_url = $_ENV['FRONTEND_URL'] ?? '*';

// Set header CORS
header("Access-Control-Allow-Origin: " . $frontend_url);
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN");
header("Access-Control-Allow-Credentials: true");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204); // No Content
    exit();
}
