<?php
// /andiamo-backend-native/bootstrap.php

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1); // Matikan di produksi

// Load Environment Variables
function loadEnv($path)
{
    if (!file_exists($path)) {
        throw new Exception('.env file not found');
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Muat .env dari direktori root proyek
loadEnv(__DIR__ . '/.env');

// Autoload Konfigurasi
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/database.php';

// Fungsi bantuan global
function json_response($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
