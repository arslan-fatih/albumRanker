<?php
/**
 * Secure file uploader for AlbumRanker – v1.4 (24 May 2025)
 *
 *  • `remove_profile` artık DB'de `profile_pic = 'default.jpg'` yazar.
 *  • Upload sırasında DB'ye **yalnızca dosya adı** kaydedilir (URL değil) → front‑end `uploads/profile/<file>` mantığıyla tutarlı.
 */

declare(strict_types=1);
header('Content-Type: application/json');

session_start();
require_once 'config.php';

$response = ['success' => false, 'message' => 'Unknown error'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Invalid request method');
    }

    $action = $_POST['action'] ?? null;
    if (!$action) throw new RuntimeException('Invalid action');

    /* ------------------------------------------------------------
       1) PROFİL FOTOĞRAFI SİLME (remove_profile)
    ------------------------------------------------------------ */
    if ($action === 'remove_profile') {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) throw new RuntimeException('Not logged in');

        // Eski foto ismini al (yalnızca dosya adı ya da URL?)
        $stmt = $conn->prepare('SELECT profile_pic FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $oldPic = $stmt->fetchColumn();

        // Varsayılan dosya adı
        $defaultFilename = 'default.jpg';

        // Eski resim custom ise sil (default.jpg dosyasını silme!)
        if ($oldPic && $oldPic !== $defaultFilename) {
            $path = __DIR__ . '/uploads/profile/' . basename($oldPic);
            if (is_file($path)) @unlink($path);
        }

        // Veritabanında default.jpg olarak güncelle
        $conn->prepare('UPDATE users SET profile_pic = ? WHERE id = ?')
             ->execute([$defaultFilename, $userId]);

        echo json_encode(['success' => true, 'message' => 'Profile picture reset to default.']);
        exit;
    }

    /* ------------------------------------------------------------
       2) UPLOAD İŞLEMLERİ (profile / cover / track)
    ------------------------------------------------------------ */
    $cfg = [
        'upload_profile' => [
            'input' => 'profile',
            'dir'   => __DIR__ . '/uploads/profile/',
            'types' => ['image/jpeg','image/png','image/webp','image/gif'],
            'max'   => 5 * 1024 * 1024,
        ],
        'upload_cover' => [
            'input' => 'cover',
            'dir'   => __DIR__ . '/uploads/cover/',
            'types' => ['image/jpeg','image/png','image/webp'],
            'max'   => 8 * 1024 * 1024,
        ],
        'upload_track' => [
            'input' => 'track',
            'dir'   => __DIR__ . '/uploads/tracks/',
            'types' => ['audio/mpeg','audio/mp3'],
            'max'   => 15 * 1024 * 1024,
        ],
    ];

    if (!isset($cfg[$action])) throw new RuntimeException('Invalid action');
    $s = $cfg[$action];

    if (!isset($_FILES[$s['input']])) throw new RuntimeException('No file provided');
    $f = $_FILES[$s['input']];
    if ($f['error'] !== UPLOAD_ERR_OK)        throw new RuntimeException('Upload error: '.$f['error']);
    if ($f['size'] > $s['max'])               throw new RuntimeException('File too large');

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!in_array($mime, $s['types'], true))  throw new RuntimeException('Invalid file type');

    if (!is_dir($s['dir']) && !mkdir($s['dir'], 0775, true))
        throw new RuntimeException('Failed to create directory');

    $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $name = bin2hex(random_bytes(8)).'.'.$ext;
    $dest = $s['dir'].$name;
    if (!move_uploaded_file($f['tmp_name'], $dest))
        throw new RuntimeException('Failed to move uploaded file');
    chmod($dest, 0644);

    /* ----- DB güncelle (profil) ----- */
    if ($action === 'upload_profile') {
        $uid = $_SESSION['user_id'] ?? null;
        if ($uid) {
            // Yalnızca dosya adını kaydet
            $conn->prepare('UPDATE users SET profile_pic = ? WHERE id = ?')
                 ->execute([$name, $uid]);
        }
    }

    // Yanıt
    $relativePath = str_replace(__DIR__, '', $s['dir']) . $name;

    $response['success'] = true;
    $response['file']    = $relativePath;
    $response['message'] = 'File uploaded successfully';

} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
