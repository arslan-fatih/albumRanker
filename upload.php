<?php
require_once 'config.php';

// Handle file upload
function handleFileUpload($file, $type, $userId) {
    // Validate file
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file'];
    }
    
    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'File too large'];
        case UPLOAD_ERR_PARTIAL:
            return ['success' => false, 'message' => 'File upload incomplete'];
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'message' => 'No file uploaded'];
        default:
            return ['success' => false, 'message' => 'Unknown upload error'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    // Validate file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    $allowedTypes = $type === 'image' ? ALLOWED_IMAGE_TYPES : ALLOWED_AUDIO_TYPES;
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid($userId . '_') . '.' . $extension;
    
    // Set upload directory based on type
    $uploadDir = UPLOAD_DIR . ($type === 'image' ? 'covers/' : 'tracks/');
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    return [
        'success' => true,
        'message' => 'File uploaded successfully',
        'filename' => $filename,
        'path' => $uploadDir . $filename
    ];
}

// Handle profile picture upload
function handleProfilePictureUpload($file, $userId) {
    // Validate file
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file'];
    }
    
    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'File too large'];
        case UPLOAD_ERR_PARTIAL:
            return ['success' => false, 'message' => 'File upload incomplete'];
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'message' => 'No file uploaded'];
        default:
            return ['success' => false, 'message' => 'Unknown upload error'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    // Validate file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid($userId . '_') . '.' . $extension;
    
    // Set upload directory
    $uploadDir = UPLOAD_DIR . 'profiles/';
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    // Update user profile
    try {
        $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
        $stmt->execute([$filename, $userId]);
        
        return [
            'success' => true,
            'message' => 'Profile picture updated successfully',
            'filename' => $filename,
            'path' => $uploadDir . $filename
        ];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'upload_cover':
            if (isLoggedIn() && isset($_FILES['cover'])) {
                $response = handleFileUpload($_FILES['cover'], 'image', getCurrentUserId());
            }
            break;
            
        case 'upload_track':
            if (isLoggedIn() && isset($_FILES['track'])) {
                $response = handleFileUpload($_FILES['track'], 'audio', getCurrentUserId());
            }
            break;
            
        case 'upload_profile':
            if (isLoggedIn() && isset($_FILES['profile'])) {
                $response = handleProfilePictureUpload($_FILES['profile'], getCurrentUserId());
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?> 