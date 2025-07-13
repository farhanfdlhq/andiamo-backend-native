<?php
// /andiamo-backend-native/api/profile.php

// ===== BLOK KODE CORS =====
header("Access-Control-Allow-Origin: https://andiamo.elenmorcreative.com");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-control-allow-headers: Content-Type, Authorization, Accept, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit();
}
// ===== AKHIR BLOK KODE CORS =====

// ===== PERBAIKAN PATH =====
require_once __DIR__ . '/../bootstrap.php';

// Mulai sesi
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

// Semua aksi di sini butuh login
if (!isset($_SESSION['user_id'])) {
    json_response(['message' => 'Unauthenticated.'], 401);
}

$db = (new Database())->getConnection();
$action = $_GET['action'] ?? '';

if ($action === 'change-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleChangePassword($db);
} else {
    json_response(['message' => 'Invalid action'], 400);
}

function handleChangePassword($db)
{
    $input = json_decode(file_get_contents('php://input'), true);
    $current_password = $input['current_password'] ?? '';
    $new_password = $input['new_password'] ?? '';

    if (empty($current_password) || empty($new_password)) {
        json_response(['message' => 'Semua field password harus diisi.'], 422);
    }
    if (strlen($new_password) < 8) {
        json_response(['message' => 'Password baru minimal 8 karakter.'], 422);
    }

    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($current_password, $user['password'])) {
        json_response(['message' => 'Password saat ini salah.'], 422);
    }

    $new_password_hashed = password_hash($new_password, PASSWORD_BCRYPT);
    $update_stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update_stmt->bind_param('si', $new_password_hashed, $user_id);

    if ($update_stmt->execute()) {
        json_response(['message' => 'Password berhasil diubah!']);
    } else {
        json_response(['message' => 'Gagal mengubah password.'], 500);
    }
}