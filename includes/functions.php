<?php
/**
 * Yardımcı fonksiyonlar
 */

// Güvenli input temizleme
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Tarih formatla
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

// Süre formatla (saniye -> MM:SS)
function formatDuration($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return sprintf("%d:%02d", $minutes, $remainingSeconds);
}

// Dosya yükleme kontrolü
function validateFileUpload($file, $allowedTypes, $maxSize) {
    $errors = [];
    
    if (!isset($file['error']) || is_array($file['error'])) {
        $errors[] = 'Geçersiz dosya parametresi.';
        return $errors;
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors[] = 'Dosya boyutu çok büyük.';
            break;
        case UPLOAD_ERR_PARTIAL:
            $errors[] = 'Dosya tam yüklenemedi.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $errors[] = 'Dosya seçilmedi.';
            break;
        default:
            $errors[] = 'Bilinmeyen bir hata oluştu.';
    }

    if ($file['size'] > $maxSize) {
        $errors[] = 'Dosya boyutu izin verilenden büyük.';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = 'Geçersiz dosya türü.';
    }

    return $errors;
}

// Güvenli dosya adı oluştur
function generateSafeFileName($originalName) {
    $info = pathinfo($originalName);
    $name = basename($originalName, '.' . $info['extension']);
    $safeName = preg_replace('/[^a-zA-Z0-9]/', '_', $name);
    return $safeName . '_' . time() . '.' . $info['extension'];
}

// Kullanıcı yetkisi kontrolü
function checkPermission($requiredRole = null) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    if ($requiredRole !== null && $_SESSION['role'] !== $requiredRole) {
        header('Location: index.php');
        exit();
    }
}

// Flash mesaj oluştur
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Flash mesajı göster ve temizle
function showFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];
        unset($_SESSION['flash']);
        return "<div class='alert alert-{$type}'>{$message}</div>";
    }
    return '';
}

// Sayfalama fonksiyonu
function paginate($totalItems, $itemsPerPage, $currentPage) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    
    $start = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'start' => $start,
        'itemsPerPage' => $itemsPerPage
    ];
}

// XSS koruması için çıktı temizleme
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Aktif menü öğesini belirle
function isActiveMenu($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return $currentPage === $page ? 'active' : '';
}

// Albüm puanını formatla
function formatRating($rating) {
    return number_format($rating, 1);
}

// Kullanıcı profil resmi URL'si
function getUserProfilePic($profilePic) {
    return $profilePic ? 'uploads/profiles/' . $profilePic : 'img/default-profile.jpg';
}

// Albüm kapak resmi URL'si
function getAlbumCover($coverImage) {
    if (!$coverImage) {
        return 'img/default-album.jpg';
    }
    // Eğer tam URL ise direkt döndür
    if (preg_match('/^https?:\/\//i', $coverImage)) {
        return $coverImage;
    }
    // Dosya adı ise uploads/covers/ altından döndür
    return 'uploads/covers/' . $coverImage;
}

// Kullanıcı giriş kontrolü
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Giriş yapan kullanıcının ID'si
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Albümün ortalama puanını hesaplar ve günceller
 * @param int $albumId Albüm ID
 * @return float|null Ortalama puan veya null
 */
function updateAlbumAverageRating($albumId) {
    global $conn;
    
    try {
        // Önce ortalama puanı hesapla
        $stmt = $conn->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count 
            FROM ratings 
            WHERE album_id = ?
        ");
        $stmt->execute([$albumId]);
        $result = $stmt->fetch();
        
        if ($result && $result['rating_count'] > 0) {
            // Albüm tablosundaki rating alanını güncelle
            $stmt = $conn->prepare("
                UPDATE albums 
                SET rating = ?, 
                    rating_count = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                round($result['avg_rating'], 1),
                $result['rating_count'],
                $albumId
            ]);
            
            return round($result['avg_rating'], 1);
        }
        
        return null;
    } catch (PDOException $e) {
        error_log("Albüm puanı güncellenirken hata: " . $e->getMessage());
        return null;
    }
} 