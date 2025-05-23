<?php
/**
 * Uygulama yapılandırma dosyası
 */

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Oturum güvenliği
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Veritabanı yapılandırması
$dbConfig = [
    'host' => 'localhost',
    'name' => 'album_ranker',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4'
];

// Veritabanı bağlantısı
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    $conn = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
} catch(PDOException $e) {
    error_log("Veritabanı bağlantı hatası: " . $e->getMessage());
    die("Veritabanına bağlanılamadı. Lütfen daha sonra tekrar deneyin.");
}

// Dosya yükleme yapılandırması
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/x-png', 'image/gif']);
define('ALLOWED_AUDIO_TYPES', ['audio/mpeg', 'audio/wav', 'audio/ogg']);

// Yükleme dizinlerini oluştur
$uploadDirs = ['covers', 'tracks', 'profiles'];
foreach ($uploadDirs as $dir) {
    $path = UPLOAD_DIR . $dir;
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
}

// Yardımcı fonksiyonları yükle
require_once __DIR__ . '/includes/functions.php';

// CSRF koruması
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate limiting
function checkRateLimit($key, $limit = 60, $period = 60) {
    $current = time();
    $rateLimit = isset($_SESSION['rate_limit'][$key]) ? $_SESSION['rate_limit'][$key] : ['count' => 0, 'reset' => $current + $period];
    
    if ($current > $rateLimit['reset']) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'reset' => $current + $period];
        return true;
    }
    
    if ($rateLimit['count'] >= $limit) {
        return false;
    }
    
    $_SESSION['rate_limit'][$key]['count']++;
    return true;
}
?> 