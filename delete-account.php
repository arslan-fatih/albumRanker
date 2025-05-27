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
    // Transaction başlat
    $conn->beginTransaction();

    // 1. Kullanıcıya ait albümlerin id'lerini al
    $stmt = $conn->prepare("SELECT id FROM albums WHERE user_id = ?");
    $stmt->execute([$userId]);
    $albumIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Albümlere bağlı verileri sil (tracks, reviews, ratings, album_genres, favorites)
    if (!empty($albumIds)) {
        $in = str_repeat('?,', count($albumIds) - 1) . '?';
        $tables = ['tracks', 'reviews', 'ratings', 'album_genres', 'favorites'];
        foreach ($tables as $table) {
            $stmt = $conn->prepare("DELETE FROM $table WHERE album_id IN ($in)");
            $stmt->execute($albumIds);
        }
    }

    // 3. Kullanıcının takipleri, takipçileri, puanları, yorumları sil
    $tables = [
        'followers' => 'follower_id = ? OR following_id = ?',
        'ratings' => 'user_id = ?',
        'reviews' => 'user_id = ?'
    ];

    foreach ($tables as $table => $condition) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE $condition");
        $stmt->execute($table === 'followers' ? [$userId, $userId] : [$userId]);
    }

    // 4. Kullanıcının albümlerini sil
    $stmt = $conn->prepare("DELETE FROM albums WHERE user_id = ?");
    $stmt->execute([$userId]);

    // 5. Kullanıcıyı sil
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    // Transaction'ı onayla
    $conn->commit();

    // 6. Oturumu kapat
    session_destroy();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Hata durumunda transaction'ı geri al
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Hesap silme hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu.']);
} 