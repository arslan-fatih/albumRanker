<?php
session_start();
require_once 'config.php';

// JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You need to be logged in.']);
    exit;
}

// Check if user_id is provided
if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

$follower_id = $_SESSION['user_id'];
$following_id = (int)$_POST['user_id'];

// Check if user is trying to follow themselves
if ($follower_id === $following_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot follow yourself.']);
    exit;
}

try {
    // Check if follow relationship already exists
    $stmt = $conn->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$follower_id, $following_id]);
    $isFollowing = $stmt->fetch();

    if ($isFollowing) {
        // Unfollow
        $stmt = $conn->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        echo json_encode(['success' => true, 'following' => false, 'message' => 'Unfollowed successfully.']);
    } else {
        // Follow
        $stmt = $conn->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $following_id]);
        echo json_encode(['success' => true, 'following' => true, 'message' => 'Followed successfully.']);
    }
} catch (PDOException $e) {
    // Log error and return generic message
    error_log("Follow error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred.']);
} 