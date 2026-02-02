<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/env.php';
require_once __DIR__ . '/../../../utils/db.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Authentication failed.', [], 401);
}

$propertyId = $_POST['property_id'] ?? null;
if (!$propertyId) {
    send_error('Property ID is required.', [], 400);
}

try {
    $pdo = Database::getInstance();

    // Verify ownership
    $stmt = $pdo->prepare("SELECT host_id FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $propertyHostId = $stmt->fetchColumn();

    if (!$propertyHostId || $propertyHostId != $userId) {
        send_error('Unauthorized: You do not own this listing.', [], 403);
    }

    if (!isset($_FILES['media']) || empty($_FILES['media']['name'])) {
        send_error('No media file uploaded.', [], 400);
    }

    $file = $_FILES['media'];
    $fileName = time() . '_' . basename($file['name']);
    $uploadDir = __DIR__ . '/../../../public/uploads/properties/' . $propertyId . '/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $targetFilePath = $uploadDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Allow certain file formats
    $allowTypes = ['jpg', 'png', 'jpeg', 'gif', 'mp4', 'mov', 'avi'];
    if (!in_array($fileType, $allowTypes)) {
        send_error('Sorry, only JPG, JPEG, PNG, GIF, MP4, MOV, & AVI files are allowed.', [], 400);
    }

    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        $mediaType = in_array($fileType, ['mp4', 'mov', 'avi']) ? 'video' : 'image';
        $relativeUrl = 'public/uploads/properties/' . $propertyId . '/' . $fileName;

        $stmt = $pdo->prepare("INSERT INTO property_images (property_id, media_url, media_type) VALUES (?, ?, ?)");
        $stmt->execute([$propertyId, $relativeUrl, $mediaType]);

        send_success('Media uploaded successfully.', [
            'media_id' => $pdo->lastInsertId(),
            'url' => $relativeUrl,
            'type' => $mediaType
        ]);
    } else {
        send_error('Sorry, there was an error uploading your file.', [], 500);
    }

} catch (Exception $e) {
    error_log("Upload media error: " . $e->getMessage());
    send_error('An error occurred while uploading media.', [], 500);
}
