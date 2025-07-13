<?php
// /www/wwwroot/api.elenmorcreative.com/api/router.php

// Memuat semua konfigurasi penting, termasuk CORS.
require_once __DIR__ . '/../bootstrap.php';

$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/api';

// Menghapus query string dari URI
if (false !== $pos = strpos($request_uri, '?')) {
    $request_uri = substr($request_uri, 0, $pos);
}
$request_uri = rawurldecode($request_uri);

// Menghapus trailing slash jika ada
if (strlen($request_uri) > 1 && substr($request_uri, -1) === '/') {
    $request_uri = substr($request_uri, 0, -1);
}

// Logika routing
switch (true) {
    case ($request_uri === $base_path . '/auth'):
        require __DIR__ . '/auth.php';
        break;

    case ($request_uri === $base_path . '/admin/dashboard-summary'):
        require __DIR__ . '/dashboard.php';
        break;

    case ($request_uri === $base_path . '/admin/settings'):
        require __DIR__ . '/settings.php';
        break;

    case ($request_uri === $base_path . '/profile'):
        require __DIR__ . '/profile.php';
        break;

    case ($request_uri === $base_path . '/batches'):
        require __DIR__ . '/batches/index.php';
        break;

    case (preg_match('#^/api/batches/(\d+)$#', $request_uri, $matches)):
        $_GET['id'] = $matches[1];
        require __DIR__ . '/batches/single.php';
        break;

    default:
        http_response_code(404);
        json_response(['message' => 'Endpoint Not Found: ' . $request_uri]);
        break;
}