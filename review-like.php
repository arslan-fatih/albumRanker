<?php
session_start();
require_once 'config.php';

// JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

// Check if review_id is provided
if (!isset($_POST['review_id']) || !is_numeric($_POST['review_id'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz yorum ID.']);
    exit;
}

$userId = $_SESSION['user_id'];
$reviewId = (int)$_POST['review_id'];

try {
    // Yorumun var olup olmadığını kontrol et
    $stmt = $conn->prepare("SELECT 1 FROM reviews WHERE id = ?");
    $stmt->execute([$reviewId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Yorum bulunamadı.']);
        exit;
    }

    // Kullanıcının yorumu daha önce beğenip beğenmediğini kontrol et
    $stmt = $conn->prepare("SELECT 1 FROM review_likes WHERE user_id = ? AND review_id = ?");
    $stmt->execute([$userId, $reviewId]);
    $isLiked = $stmt->fetch();

    if ($isLiked) {
        // Beğeniyi kaldır
        $stmt = $conn->prepare("DELETE FROM review_likes WHERE user_id = ? AND review_id = ?");
        $stmt->execute([$userId, $reviewId]);
    } else {
        // Beğeni ekle
        $stmt = $conn->prepare("INSERT INTO review_likes (user_id, review_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $reviewId]);
    }

    // Güncel beğeni sayısını al
    $stmt = $conn->prepare("SELECT COUNT(*) FROM review_likes WHERE review_id = ?");
    $stmt->execute([$reviewId]);
    $likeCount = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'like_count' => $likeCount,
        'message' => $isLiked ? 'Beğeni kaldırıldı.' : 'Beğeni eklendi.'
    ]);
} catch (PDOException $e) {
    error_log("Beğeni işlemi hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu.']);
} 