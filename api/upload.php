<?php
/* ════════════════════════════════════════════════════════════════
   api/upload.php — Upload a product image (admin)
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondError('Method not allowed.', 405);
requireAdmin();

if (!isset($_FILES['image'])) respondError('No image file provided.');

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $errMsg = match($file['error']) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large.',
        UPLOAD_ERR_NO_FILE                         => 'No file uploaded.',
        default                                    => 'Upload error code ' . $file['error'],
    };
    respondError($errMsg);
}

/* Validate size: max 5 MB */
if ($file['size'] > 5 * 1024 * 1024) respondError('File too large. Maximum size is 5 MB.');

/* Validate MIME type */
$mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
if (!array_key_exists($mimeType, $allowed)) {
    respondError('Invalid file type. Only JPG, PNG, WEBP, and GIF are allowed.');
}

$ext      = $allowed[$mimeType];
$filename = uniqid('img_', true) . '.' . $ext;
$destDir  = UPLOAD_DIR;

/* Create directory if needed */
if (!is_dir($destDir)) {
    if (!mkdir($destDir, 0755, true)) {
        respondError('Failed to create upload directory.', 500);
    }
}

$destPath = $destDir . $filename;
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    respondError('Failed to save uploaded file.', 500);
}

respond(['url' => UPLOAD_URL . $filename]);
