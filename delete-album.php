<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: profile.php');
    exit;
}

$albumId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

// Albüm gerçekten bu kullanıcıya mı ait?
$stmt = $conn->prepare('SELECT * FROM albums WHERE id = ? AND user_id = ?');
$stmt->execute([$albumId, $userId]);
$album = $stmt->fetch();

if (!$album) {
    // Albüm yok veya bu kullanıcıya ait değil
    header('Location: profile.php?error=notfound');
    exit;
}

// Albümü sil
$stmt = $conn->prepare('DELETE FROM albums WHERE id = ? AND user_id = ?');
$stmt->execute([$albumId, $userId]);

// Albümle ilgili diğer verileri de sil (opsiyonel)
$conn->prepare('DELETE FROM reviews WHERE album_id = ?')->execute([$albumId]);
$conn->prepare('DELETE FROM ratings WHERE album_id = ?')->execute([$albumId]);
$conn->prepare('DELETE FROM favorites WHERE album_id = ?')->execute([$albumId]);

header('Location: profile.php?deleted=1');
exit; 