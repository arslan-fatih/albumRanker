<?php
/**
 * Helper Functions
 * This file contains utility functions used throughout the application
 */

// Sanitize input data for security
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Format date to a readable string
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

// Format duration from seconds to MM:SS format
function formatDuration($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return sprintf("%d:%02d", $minutes, $remainingSeconds);
}

// Validate file uploads for security and type checking
function validateFileUpload($file, $allowedTypes, $maxSize) {
    $errors = [];
    
    if (!isset($file['error']) || is_array($file['error'])) {
        $errors[] = 'Invalid file parameter.';
        return $errors;
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors[] = 'File size is too large.';
            break;
        case UPLOAD_ERR_PARTIAL:
            $errors[] = 'File was only partially uploaded.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $errors[] = 'No file was selected.';
            break;
        default:
            $errors[] = 'An unknown error occurred.';
    }

    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds the allowed limit.';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = 'Invalid file type.';
    }

    return $errors;
}

// Generate a safe filename for uploaded files
function generateSafeFileName($originalName) {
    $info = pathinfo($originalName);
    $name = basename($originalName, '.' . $info['extension']);
    $safeName = preg_replace('/[^a-zA-Z0-9]/', '_', $name);
    return $safeName . '_' . time() . '.' . $info['extension'];
}

// Get album cover image path
function getAlbumCover($coverImage) {
    if (!$coverImage) {
        return 'img/default-album.jpg';
    }
    // Return direct URL if it's a full URL
    if (preg_match('/^https?:\/\//i', $coverImage)) {
        return $coverImage;
    }
    // Return path from uploads/covers/ if it's a filename
    return 'uploads/covers/' . $coverImage;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current logged-in user's ID
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Calculate and update album's average rating
 * @param int $albumId Album ID
 * @return float|null Average rating or null if no ratings
 */
function updateAlbumAverageRating($albumId) {
    global $conn;
    
    try {
        // Calculate average rating
        $stmt = $conn->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count 
            FROM ratings 
            WHERE album_id = ?
        ");
        $stmt->execute([$albumId]);
        $result = $stmt->fetch();
        
        if ($result && $result['rating_count'] > 0) {
            // Update album's rating in the database
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
        error_log("Error updating album rating: " . $e->getMessage());
        return null;
    }
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
    $filePath = 'uploads/profiles/' . $profilePic;
    if ($profilePic && file_exists($filePath)) {
        return $filePath;
    } else {
        return 'img/default-profile.jpg';
    }
} 