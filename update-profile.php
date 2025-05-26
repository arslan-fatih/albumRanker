<?php
session_start();
require_once 'config.php';

// JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to update your profile.']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get and validate input
$username = trim($_POST['username'] ?? '');
$bio = trim($_POST['bio'] ?? '');

// Validate username
if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Username cannot be empty.']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores.']);
    exit;
}

// Check if username is already taken (excluding current user)
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
$stmt->execute([$username, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'This username is already taken.']);
    exit;
}

// Validate bio length
if (strlen($bio) > 500) {
    echo json_encode(['success' => false, 'message' => 'Bio cannot be longer than 500 characters.']);
    exit;
}

try {
    // Update user profile
    $stmt = $conn->prepare("UPDATE users SET username = ?, bio = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$username, $bio, $_SESSION['user_id']]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes were made to your profile.']);
    }
} catch (PDOException $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating your profile.']);
}
?> 