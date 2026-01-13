<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/response.php';

class UploadManager {

    public static function uploadFile(string $fileKey): ?string {
        if (!isset($_FILES[$fileKey])) {
            send_error('No file uploaded for key: ' . $fileKey, [], 400);
        }

        $file = $_FILES[$fileKey];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            send_error('File upload error: ' . $file['error'], [], 400);
        }

        // Validate file size
        if ($file['size'] > MAX_FILE_SIZE) {
            send_error('File size exceeds the maximum limit of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB', [], 400);
        }

        // Validate file type
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ALLOWED_FILE_TYPES)) {
            send_error('Invalid file type. Only ' . implode(', ', ALLOWED_FILE_TYPES) . ' are allowed.', [], 400);
        }

        // Generate a unique file name
        $fileName = uniqid() . '.' . $fileExtension;
        $destination = UPLOAD_DIR . $fileName;

        // Move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Return the relative path or URL for storage in DB
            return '/public/uploads/' . $fileName;
        } else {
            send_error('Failed to move uploaded file.', [], 500);
        }
        return null; // Should not be reached
    }
}
