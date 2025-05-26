<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız.']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // 1. Kullanıcıya ait albümlerin id'lerini al
    $stmt = $conn->prepare("SELECT id FROM albums WHERE user_id = ?");
    $stmt->execute([$userId]);
    $albumIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Albümlere bağlı verileri sil (tracks, reviews, ratings, album_genres, favorites)
    if (!empty($albumIds)) {
        $in = str_repeat('?,', count($albumIds) - 1) . '?';
        $conn->prepare("DELETE FROM tracks WHERE album_id IN ($in)")->execute($albumIds);
        $conn->prepare("DELETE FROM reviews WHERE album_id IN ($in)")->execute($albumIds);
        $conn->prepare("DELETE FROM ratings WHERE album_id IN ($in)")->execute($albumIds);
        $conn->prepare("DELETE FROM album_genres WHERE album_id IN ($in)")->execute($albumIds);
        $conn->prepare("DELETE FROM favorites WHERE album_id IN ($in)")->execute($albumIds);
    }

    // 3. Kullanıcının favorileri, takipleri, takipçileri, puanları, yorumları sil
    $conn->prepare("DELETE FROM favorites WHERE user_id = ?")->execute([$userId]);
    $conn->prepare("DELETE FROM followers WHERE follower_id = ? OR following_id = ?")->execute([$userId, $userId]);
    $conn->prepare("DELETE FROM ratings WHERE user_id = ?")->execute([$userId]);
    $conn->prepare("DELETE FROM reviews WHERE user_id = ?")->execute([$userId]);

    // 4. Kullanıcının albümlerini sil
    $conn->prepare("DELETE FROM albums WHERE user_id = ?")->execute([$userId]);

    // 5. Kullanıcıyı sil
    $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);

    // 6. Oturumu kapat
    session_destroy();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Hesap silme hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu.']);
} 