<?php
/**
 * functions.php
 * 
 * Bu dosya, web sitesinin genelinde kullanılan yardımcı fonksiyonları içerir.
 * Tüm sayfalarda ortak olarak kullanılan işlevleri barındırır.
 * 
 * İçerik:
 * 1. Veri temizleme ve güvenlik fonksiyonları
 * 2. Tarih ve süre formatlama
 * 3. Dosya yükleme ve doğrulama
 * 4. Kullanıcı oturum yönetimi
 * 5. Albüm puanlama sistemi
 * 6. Sayfalama işlemleri
 * 7. Kullanıcı profil yönetimi
 * 8. XSS koruması
 * 9. Menü yönetimi
 * 10. Kullanıcı istatistikleri
 */

/**
 * Veri temizleme fonksiyonu
 * Kullanıcı girdilerini güvenli hale getirir ve XSS saldırılarına karşı korur
 * @param mixed $input Temizlenecek veri (string veya array)
 * @return mixed Temizlenmiş veri
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Tarih formatlama fonksiyonu
 * Veritabanından gelen tarihi okunabilir formata çevirir
 * @param string $date Formatlanacak tarih
 * @param string $format İstenen tarih formatı (varsayılan: 'F j, Y')
 * @return string Formatlanmış tarih
 */
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

/**
 * Süre formatlama fonksiyonu
 * Saniye cinsinden süreyi MM:SS formatına çevirir
 * @param int $seconds Saniye cinsinden süre
 * @return string Formatlanmış süre (MM:SS)
 */
function formatDuration($seconds) {
    return sprintf("%d:%02d", floor($seconds / 60), $seconds % 60);
}

/**
 * Dosya yükleme doğrulama fonksiyonu
 * Yüklenen dosyanın güvenliğini ve türünü kontrol eder
 * @param array $file Yüklenen dosya bilgileri
 * @param array $allowedTypes İzin verilen dosya türleri
 * @param int $maxSize Maksimum dosya boyutu (byte)
 * @return array Hata mesajları dizisi
 */
function validateFileUpload($file, $allowedTypes, $maxSize) {
    $errors = [];
    
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['Geçersiz dosya parametresi.'];
    }

    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'Dosya boyutu çok büyük.',
        UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu çok büyük.',
        UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi.',
        UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi.'
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [$errorMessages[$file['error']] ?? 'Bilinmeyen bir hata oluştu.'];
    }

    if ($file['size'] > $maxSize) {
        $errors[] = 'Dosya boyutu izin verilen limiti aşıyor.';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = 'Geçersiz dosya türü.';
    }

    return $errors;
}

/**
 * Güvenli dosya adı oluşturma fonksiyonu
 * Yüklenen dosyalar için benzersiz ve güvenli isimler oluşturur
 * @param string $originalName Orijinal dosya adı
 * @return string Güvenli dosya adı
 */
function generateSafeFileName($originalName) {
    $info = pathinfo($originalName);
    return preg_replace('/[^a-zA-Z0-9]/', '_', basename($originalName, '.' . $info['extension'])) . '_' . time() . '.' . $info['extension'];
}

/**
 * Albüm kapak resmi yolu alma fonksiyonu
 * Albüm kapağının tam yolunu döndürür, kapak yoksa varsayılan resmi kullanır
 * @param string $coverImage Kapak resmi adı
 * @return string Kapak resmi yolu
 */
function getAlbumCover($coverImage) {
    if (!$coverImage) {
        return 'img/default-album.jpg';
    }
    return preg_match('/^https?:\/\//i', $coverImage) ? $coverImage : 'uploads/covers/' . $coverImage;
}

/**
 * Kullanıcı giriş kontrolü
 * Kullanıcının oturum açıp açmadığını kontrol eder
 * @return bool Giriş yapılmışsa true, yapılmamışsa false
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Mevcut kullanıcı ID'sini alma
 * Oturum açmış kullanıcının ID'sini döndürür
 * @return int|null Kullanıcı ID'si veya null
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Albüm ortalama puanını hesaplama ve güncelleme
 * Albümün tüm puanlarının ortalamasını hesaplar ve veritabanını günceller
 * @param int $albumId Albüm ID'si
 * @return float|null Ortalama puan veya null
 */
function updateAlbumAverageRating($albumId) {
    global $conn;
    
    try {
        // Ortalama puanı hesapla
        $stmt = $conn->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count 
            FROM ratings 
            WHERE album_id = ?
        ");
        $stmt->execute([$albumId]);
        $result = $stmt->fetch();
        
        if ($result && $result['rating_count'] > 0) {
            // Veritabanındaki albüm puanını güncelle
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

/**
 * Kullanıcı yetkisi kontrolü
 * Kullanıcının belirli bir sayfaya erişim yetkisini kontrol eder
 * @param string|null $requiredRole Gerekli kullanıcı rolü
 */
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

/**
 * Flash mesaj oluşturma
 * Kullanıcıya gösterilecek geçici mesaj oluşturur
 * @param string $type Mesaj türü (success, error, warning, info)
 * @param string $message Mesaj içeriği
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Flash mesaj gösterme
 * Oluşturulan flash mesajı gösterir ve temizler
 * @return string HTML formatında mesaj
 */
function showFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];
        unset($_SESSION['flash']);
        return "<div class='alert alert-{$type}'>{$message}</div>";
    }
    return '';
}

/**
 * Sayfalama fonksiyonu
 * Veri listelerini sayfalara böler
 * @param int $totalItems Toplam öğe sayısı
 * @param int $itemsPerPage Sayfa başına öğe sayısı
 * @param int $currentPage Mevcut sayfa numarası
 * @return array Sayfalama bilgileri
 */
function paginate($totalItems, $itemsPerPage, $currentPage) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    
    return [
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'start' => ($currentPage - 1) * $itemsPerPage,
        'itemsPerPage' => $itemsPerPage
    ];
}

/**
 * XSS koruması için çıktı temizleme
 * HTML karakterlerini güvenli hale getirir
 * @param string $string Temizlenecek metin
 * @return string Temizlenmiş metin
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Aktif menü öğesini belirleme
 * Mevcut sayfaya göre menü öğesinin aktif olup olmadığını kontrol eder
 * @param string $page Sayfa adı
 * @return string 'active' veya boş string
 */
function isActiveMenu($page) {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}

/**
 * Albüm puanını formatlama
 * Puanı ondalıklı sayı formatına çevirir
 * @param float $rating Puan
 * @return string Formatlanmış puan
 */
function formatRating($rating) {
    return number_format($rating, 1);
}

/**
 * Puanlama rozeti oluşturma
 * Albüm puanını görsel bir rozet olarak oluşturur
 * @param float $rating Puan
 * @param int|null $count Oy sayısı
 * @param string $size Rozet boyutu (normal/large)
 * @return string HTML formatında rozet
 */
function createRatingBadge($rating, $count = null, $size = 'normal') {
    $sizeClass = $size === 'large' ? 'fs-4' : '';
    $badge = '<span class="badge bg-primary ' . $sizeClass . '">';
    $badge .= '<i class="fas fa-star"></i> ';
    $badge .= formatRating($rating) . '/10';
    if ($count !== null) {
        $badge .= ' <small class="ms-1">(' . $count . ' oy)</small>';
    }
    $badge .= '</span>';
    return $badge;
}

/**
 * Kullanıcı profil resmi URL'si alma
 * Kullanıcının profil resminin tam yolunu döndürür
 * @param string $profilePic Profil resmi adı
 * @return string Profil resmi yolu
 */
function getUserProfilePic($profilePic) {
    $filePath = 'uploads/profiles/' . $profilePic;
    return ($profilePic && file_exists($filePath)) ? $filePath : 'img/default-profile.jpg';
}

/**
 * Kullanıcı istatistiklerini alma
 * Kullanıcının albüm, takipçi ve takip ettiği kişi sayılarını döndürür
 * @param int $userId Kullanıcı ID'si
 * @return array İstatistik bilgileri
 */
function getUserStats($userId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM albums WHERE user_id = ?) as album_count,
            (SELECT COUNT(*) FROM followers WHERE following_id = ?) as follower_count,
            (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following_count
    ");
    $stmt->execute([$userId, $userId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Profil resmi yolu alma
 * Kullanıcının profil resminin tam yolunu döndürür
 * @param string $profilePic Profil resmi adı
 * @return string Profil resmi yolu
 */
function getProfilePicturePath($profilePic) {
    $profilePicPath = 'uploads/profile/' . $profilePic;
    // Profil resmi varsayılan değilse, dosya varsa ve geçerliyse
    if ($profilePic && $profilePic !== 'default.jpg' && file_exists($profilePicPath) && is_file($profilePicPath)) {
        return $profilePicPath;
    }
    // Varsayılan resmi döndür
    return 'img/core-img/default.jpg';
} 