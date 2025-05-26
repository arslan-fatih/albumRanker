<?php
session_start();
require_once 'config.php';

// JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to like reviews.']);
    exit;
}

// Check if review_id is provided
if (!isset($_POST['review_id']) || !is_numeric($_POST['review_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid review ID.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$review_id = (int)$_POST['review_id'];

try {
    // Check if like already exists
    $stmt = $conn->prepare("SELECT 1 FROM review_likes WHERE user_id = ? AND review_id = ?");
    $stmt->execute([$user_id, $review_id]);
    $isLiked = $stmt->fetch();

    if ($isLiked) {
        // Unlike
        $stmt = $conn->prepare("DELETE FROM review_likes WHERE user_id = ? AND review_id = ?");
        $stmt->execute([$user_id, $review_id]);
        $action = 'unliked';
    } else {
        // Like
        $stmt = $conn->prepare("INSERT INTO review_likes (user_id, review_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $review_id]);
        $action = 'liked';
    }

    // Get updated like count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM review_likes WHERE review_id = ?");
    $stmt->execute([$review_id]);
    $likeCount = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'action' => $action,
        'likeCount' => $likeCount,
        'message' => $action === 'liked' ? 'Review liked!' : 'Review unliked!'
    ]);
} catch (PDOException $e) {
    error_log("Review like error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred.']);
} 