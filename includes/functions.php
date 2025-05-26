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
    return sprintf("%d:%02d", floor($seconds / 60), $seconds % 60);
}

// Validate file uploads for security and type checking
function validateFileUpload($file, $allowedTypes, $maxSize) {
    $errors = [];
    
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['Invalid file parameter.'];
    }

    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File size is too large.',
        UPLOAD_ERR_FORM_SIZE => 'File size is too large.',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was selected.'
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [$errorMessages[$file['error']] ?? 'An unknown error occurred.'];
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
    return preg_replace('/[^a-zA-Z0-9]/', '_', basename($originalName, '.' . $info['extension'])) . '_' . time() . '.' . $info['extension'];
}

// Get album cover image path
function getAlbumCover($coverImage) {
    if (!$coverImage) {
        return 'img/default-album.jpg';
    }
    return preg_match('/^https?:\/\//i', $coverImage) ? $coverImage : 'uploads/covers/' . $coverImage;
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
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
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
    
    return [
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'start' => ($currentPage - 1) * $itemsPerPage,
        'itemsPerPage' => $itemsPerPage
    ];
}

// XSS koruması için çıktı temizleme
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Aktif menü öğesini belirle
function isActiveMenu($page) {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}

// Albüm puanını formatla
function formatRating($rating) {
    return number_format($rating, 1);
}

// Kullanıcı profil resmi URL'si
function getUserProfilePic($profilePic) {
    $filePath = 'uploads/profiles/' . $profilePic;
    return ($profilePic && file_exists($filePath)) ? $filePath : 'img/default-profile.jpg';
} 