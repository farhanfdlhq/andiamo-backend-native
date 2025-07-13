<?php
// /andiamo-backend-native/api/settings.php

header("Access-Control-Allow-Origin: https://andiamo.elenmorcreative.com");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-control-allow-headers: Content-Type, Authorization, Accept, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once __DIR__ . '/../bootstrap.php';

// Mulai sesi dengan parameter yang lengkap
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => $_ENV['SESSION_LIFETIME'] * 60 ?? 7200,
        'path' => '/',
        'domain' => $_ENV['SESSION_DOMAIN'] ?? '.elenmorcreative.com',
        'secure' => $_ENV['SESSION_SECURE_COOKIE'] ?? true,
        'httponly' => true,
        'samesite' => $_ENV['SESSION_SAME_SITE'] ?? 'None'
    ]);
    session_start();
}

$settingsFilePath = __DIR__ . '/../config/settings.json';

// Logika untuk GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['user_id'])) { // GET juga butuh otentikasi
        json_response(['message' => 'Unauthenticated.'], 401);
    }
    if (file_exists($settingsFilePath)) {
        json_response(json_decode(file_get_contents($settingsFilePath), true));
    } else {
        json_response(['defaultWhatsAppNumber' => '', 'defaultCTAMessage' => '']);
    }
}

// Logika untuk POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        json_response(['message' => 'Unauthenticated.'], 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
        json_response(['message' => 'Invalid JSON data.'], 400);
    }

    file_put_contents($settingsFilePath, json_encode($input, JSON_PRETTY_PRINT));
    json_response(['message' => 'Pengaturan berhasil disimpan!']);
}