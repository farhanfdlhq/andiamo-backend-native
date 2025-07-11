<?php
// /andiamo-backend-native/api/batches/index.php

require_once __DIR__ . '/../../bootstrap.php';

// Mulai atau lanjutkan sesi
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

// Cek apakah user sudah login untuk metode selain GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    if (!isset($_SESSION['user_id'])) {
        json_response(['message' => 'Unauthenticated.'], 401);
    }
}

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($db);
        break;
    case 'POST':
        handlePost($db);
        break;
    default:
        json_response(['message' => 'Method Not Allowed'], 405);
        break;
}

function handleGet($db)
{
    $status = $_GET['status'] ?? null;
    $region = $_GET['region'] ?? null;
    $sortBy = $_GET['sortBy'] ?? 'departure_date';
    $sortDir = $_GET['sortDir'] ?? 'desc';

    $sql = "SELECT * FROM batches";
    $whereClauses = [];
    $params = [];
    $types = '';

    if ($status && $status !== 'all') {
        $whereClauses[] = "status = ?";
        $params[] = $status;
        $types .= 's';
    }
    if ($region && $region !== 'all') {
        $whereClauses[] = "region = ?";
        $params[] = $region;
        $types .= 's';
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    $allowedSortColumns = ['name', 'region', 'status', 'departure_date', 'arrival_date'];
    if (in_array($sortBy, $allowedSortColumns) && in_array(strtolower($sortDir), ['asc', 'desc'])) {
        $sql .= " ORDER BY " . $sortBy . " " . $sortDir;
    } else {
        $sql .= " ORDER BY departure_date DESC";
    }

    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $batches = [];
    while ($row = $result->fetch_assoc()) {
        $row['image_urls'] = json_decode($row['image_urls'] ?? '[]', true);
        $batches[] = $row;
    }

    json_response($batches);
}

function handlePost($db)
{
    // Validasi sederhana
    if (empty($_POST['name']) || empty($_POST['status'])) {
        json_response(['message' => 'Name and status are required.'], 422);
    }

    $uploadedImagePaths = [];
    if (!empty($_FILES['images'])) {
        $storage_path = __DIR__ . '/../../public/storage/batches'; // Sesuaikan path ini
        if (!is_dir($storage_path)) {
            mkdir($storage_path, 0777, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = uniqid() . '-' . basename($_FILES['images']['name'][$key]);
                $destination = $storage_path . '/' . $file_name;
                if (move_uploaded_file($tmp_name, $destination)) {
                    // Simpan path relatif dari folder 'storage'
                    $uploadedImagePaths[] = 'batches/' . $file_name;
                }
            }
        }
    }

    $sql = "INSERT INTO batches (name, description, shortDescription, region, departure_date, arrival_date, whatsappLink, status, image_urls, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $db->prepare($sql);

    $name = $_POST['name'];
    $description = $_POST['description'] ?? null;
    $shortDescription = $_POST['shortDescription'] ?? null;
    $region = $_POST['region'] ?? null;
    $departure_date = !empty($_POST['departure_date']) ? $_POST['departure_date'] : null;
    $arrival_date = !empty($_POST['arrival_date']) ? $_POST['arrival_date'] : null;
    $whatsappLink = $_POST['whatsappLink'] ?? null;
    $status = $_POST['status'];
    $image_urls_json = json_encode($uploadedImagePaths);

    $stmt->bind_param(
        "sssssssss",
        $name,
        $description,
        $shortDescription,
        $region,
        $departure_date,
        $arrival_date,
        $whatsappLink,
        $status,
        $image_urls_json
    );

    if ($stmt->execute()) {
        $batch_id = $db->insert_id;
        $select_sql = "SELECT * FROM batches WHERE id = ?";
        $select_stmt = $db->prepare($select_sql);
        $select_stmt->bind_param('i', $batch_id);
        $select_stmt->execute();
        $result = $select_stmt->get_result()->fetch_assoc();
        $result['image_urls'] = json_decode($result['image_urls'] ?? '[]', true);
        json_response($result, 201);
    } else {
        json_response(['message' => 'Failed to create batch.'], 500);
    }
}
