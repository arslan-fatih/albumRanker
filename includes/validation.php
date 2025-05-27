<?php
/**
 * validation.php
 * 
 * Bu dosya, web sitesindeki form verilerinin doğrulama işlemlerini yapan fonksiyonları içerir.
 * Tüm form gönderimlerinde kullanılan veri doğrulama ve güvenlik kontrollerini sağlar.
 * 
 * İçerik:
 * 1. Albüm verilerinin doğrulaması
 * 2. Yorum ve puanlama verilerinin doğrulaması
 * 3. Kullanıcı verilerinin doğrulaması
 * 4. Profil resmi yükleme doğrulaması
 * 
 * Her fonksiyon, ilgili veri türü için gerekli tüm kontrolleri yapar ve
 * hata durumunda uygun hata mesajlarını döndürür.
 */

function validateAlbumData($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors[] = 'Album title is required.';
    }
    
    if (empty($data['artist'])) {
        $errors[] = 'Artist name is required.';
    }
    
    if (empty($data['cover_url'])) {
        $errors[] = 'Cover image URL is required.';
    } elseif (!filter_var($data['cover_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Invalid cover image URL.';
    }
    
    if (empty($data['genres'])) {
        $errors[] = 'At least one genre is required.';
    }
    
    return $errors;
}

function validateReviewData($data) {
    $errors = [];
    
    if (empty($data['content'])) {
        $errors[] = 'Review content is required.';
    }
    
    if (empty($data['rating'])) {
        $errors[] = 'Rating is required.';
    } elseif (!is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 10) {
        $errors[] = 'Rating must be a number between 1 and 10.';
    }
    
    return $errors;
}

function validateUserData($data) {
    $errors = [];
    
    if (empty($data['username'])) {
        $errors[] = 'Username is required.';
    } elseif (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
        $errors[] = 'Username must be between 3 and 50 characters.';
    }
    
    if (empty($data['email'])) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    
    if (!empty($data['password']) && strlen($data['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    return $errors;
}

function validateProfilePicture($file) {
    $errors = [];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (empty($file['name'])) {
        return ['Profile picture is required.'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        $errors[] = 'Only JPG, PNG and GIF images are allowed.';
    }
    
    if ($file['size'] > $maxSize) {
        $errors[] = 'Image size must be less than 5MB.';
    }
    
    return $errors;
} 