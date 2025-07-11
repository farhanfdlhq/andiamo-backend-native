<?php
// /andiamo-backend-native/api/batches/single.php

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

// Dapatkan ID dari URL, contoh: /api/batches/single.php?id=1
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    json_response(['message' => 'Invalid Batch ID'], 400);
}

switch ($method) {
    case 'GET':
        handleGet($db, $id);
        break;
    case 'POST': // HTML forms don't support PUT, so we use POST with a _method field
        if (isset($_POST['_method']) && strtoupper($_POST['_method']) === 'PUT') {
            handlePut($db, $id);
        } else {
            json_response(['message' => 'Method Not Allowed'], 405);
        }
        break;
    case 'DELETE':
        handleDelete($db, $id);
        break;
    default:
        json_response(['message' => 'Method Not Allowed'], 405);
        break;
}

function handleGet($db, $id)
{
    $stmt = $db->prepare("SELECT * FROM batches WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $batch = $result->fetch_assoc();

    if ($batch) {
        $batch['image_urls'] = json_decode($batch['image_urls'] ?? '[]', true);
        json_response($batch);
    } else {
        json_response(['message' => 'Batch not found'], 404);
    }
}

function handlePut($db, $id)
{
    // Ambil data batch saat ini
    $stmt = $db->prepare("SELECT image_urls FROM batches WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $currentBatch = $stmt->get_result()->fetch_assoc();
    if (!$currentBatch) {
        json_response(['message' => 'Batch not found'], 404);
    }

    $currentImagePaths = json_decode($currentBatch['image_urls'] ?? '[]', true);
    $finalImagePaths = $currentImagePaths;

    // Proses jika ada gambar baru
    if (isset($_POST['replace_existing_images']) && $_POST['replace_existing_images'] === 'true') {
        // Hapus gambar lama dari storage
        $storage_path_base = __DIR__ . '/../../public/storage/';
        foreach ($currentImagePaths as $oldPath) {
            if (file_exists($storage_path_base . $oldPath)) {
                unlink($storage_path_base . $oldPath);
            }
        }
        $finalImagePaths = []; // Kosongkan array path
    }

    if (!empty($_FILES['images'])) {
        $newUploadedPaths = [];
        $storage_path = __DIR__ . '/../../public/storage/batches'; // Sesuaikan path ini
        if (!is_dir($storage_path)) mkdir($storage_path, 0777, true);

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = uniqid() . '-' . basename($_FILES['images']['name'][$key]);
                $destination = $storage_path . '/' . $file_name;
                if (move_uploaded_file($tmp_name, $destination)) {
                    $newUploadedPaths[] = 'batches/' . $file_name;
                }
            }
        }
        $finalImagePaths = array_merge($finalImagePaths, $newUploadedPaths);
    }

    // Update database
    $sql = "UPDATE batches SET name = ?, description = ?, shortDescription = ?, region = ?, departure_date = ?, arrival_date = ?, whatsappLink = ?, status = ?, image_urls = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($sql);

    $name = $_POST['name'];
    $description = $_POST['description'] ?? null;
    $shortDescription = $_POST['shortDescription'] ?? null;
    $region = $_POST['region'] ?? null;
    $departure_date = !empty($_POST['departure_date']) ? $_POST['departure_date'] : null;
    $arrival_date = !empty($_POST['arrival_date']) ? $_POST['arrival_date'] : null;
    $whatsappLink = $_POST['whatsappLink'] ?? null;
    $status = $_POST['status'];
    $image_urls_json = json_encode(array_values(array_unique($finalImagePaths)));

    $stmt->bind_param("sssssssssi", $name, $description, $shortDescription, $region, $departure_date, $arrival_date, $whatsappLink, $status, $image_urls_json, $id);

    if ($stmt->execute()) {
        handleGet($db, $id); // Kirim kembali data yang sudah diupdate
    } else {
        json_response(['message' => 'Failed to update batch.'], 500);
    }
}


function handleDelete($db, $id)
{
    // Ambil path gambar sebelum dihapus dari DB
    $stmt = $db->prepare("SELECT image_urls FROM batches WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        $imagePaths = json_decode($result['image_urls'] ?? '[]', true);
        $storage_path_base = __DIR__ . '/../../public/storage/';
        foreach ($imagePaths as $path) {
            if (file_exists($storage_path_base . $path)) {
                unlink($storage_path_base . $path);
            }
        }
    }

    $delete_stmt = $db->prepare("DELETE FROM batches WHERE id = ?");
    $delete_stmt->bind_param('i', $id);
    if ($delete_stmt->execute()) {
        json_response(null, 204);
    } else {
        json_response(['message' => 'Failed to delete batch.'], 500);
    }
}
