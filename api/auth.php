<?php
// /andiamo-backend-native/api/auth.php

require_once __DIR__ . '/../../bootstrap.php';

// Selalu mulai sesi di setiap request yang butuh otentikasi
if (session_status() === PHP_SESSION_NONE) {
    // Pengaturan cookie session agar bisa diakses oleh subdomain frontend
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

$db = (new Database())->getConnection();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin($db);
        break;
    case 'logout':
        handleLogout();
        break;
    case 'user':
        handleGetUser();
        break;
    default:
        json_response(['message' => 'Invalid auth action'], 400);
        break;
}

function handleLogin($db)
{
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        json_response(['message' => 'Email dan password harus diisi.'], 422);
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Login berhasil, simpan info user di session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        // Kirim data user kembali ke frontend
        json_response([
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ]);
    } else {
        json_response(['message' => 'Email atau password salah.'], 401);
    }
}

function handleLogout()
{
    session_unset();
    session_destroy();
    json_response(['message' => 'Logout berhasil.']);
}

function handleGetUser()
{
    if (isset($_SESSION['user_id'])) {
        json_response([
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email']
        ]);
    } else {
        json_response(['message' => 'Unauthenticated.'], 401);
    }
}
