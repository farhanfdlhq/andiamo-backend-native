<?php
// /andiamo-backend-native/api/dashboard.php

// ===== TAMBAHKAN BLOK KODE CORS DI SINI =====
header("Access-Control-Allow-Origin: https://andiamo.elenmorcreative.com");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-control-allow-headers: Content-Type, Authorization, Accept, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit();
}
// ===== AKHIR BLOK KODE CORS =====

// ===== PERBAIKI PATH DI BARIS INI =====
require_once __DIR__ . '/../bootstrap.php';

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

if (!isset($_SESSION['user_id'])) {
    json_response(['message' => 'Unauthenticated.'], 401);
}

$db = (new Database())->getConnection();

$sql = "
    SELECT
        COUNT(*) as totalBatches,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as activeBatches,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closedBatches
    FROM batches
";

$result = $db->query($sql);
$summary = $result->fetch_assoc();

// Pastikan nilai integer, bukan string
$summary['totalBatches'] = (int) ($summary['totalBatches'] ?? 0);
$summary['activeBatches'] = (int) ($summary['activeBatches'] ?? 0);
$summary['closedBatches'] = (int) ($summary['closedBatches'] ?? 0);

json_response($summary);