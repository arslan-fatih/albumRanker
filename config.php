<?php
/**
 * AlbumRanker - Uygulama Yapılandırma Dosyası
 * 
 * Bu dosya, AlbumRanker uygulamasının temel yapılandırma ayarlarını içerir:
 * - Hata raporlama ayarları
 * - Oturum güvenlik ayarları
 * - Veritabanı bağlantı ayarları
 * - Dosya yükleme yapılandırması
 * - Güvenlik önlemleri
 * 
 * @author AlbumRanker Team
 * @version 1.0
 */

/**
 * Hata Raporlama Ayarları
 * 
 * - E_ALL: Tüm hata türlerini raporla
 * - display_errors: Hataları ekranda gösterme (güvenlik için)
 * - log_errors: Hataları log dosyasına kaydet
 * - error_log: Hata log dosyasının konumu
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Zaman Dilimi Ayarı
date_default_timezone_set('Europe/Istanbul');

/**
 * Oturum Güvenlik Ayarları
 * 
 * - secure: HTTPS bağlantısı kontrolü
 * - httponly: JavaScript'in çerez değerlerine erişimini engelle
 * - samesite: CSRF saldırılarına karşı koruma
 */
$secure = isset($_SERVER['HTTPS']);
$httponly = true;
$samesite = 'Lax';

/**
 * Oturum Çerez Parametrelerini Ayarla
 * Oturum başlamadan önce güvenlik ayarlarını yapılandır
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,        // Tarayıcı kapanınca oturum sonlansın
        'path' => '/',          // Tüm dizinlerde geçerli
        'domain' => '',         // Mevcut domain
        'secure' => $secure,    // HTTPS kontrolü
        'httponly' => $httponly,// JavaScript erişim engeli
        'samesite' => $samesite // CSRF koruması
    ]);
    session_start();
}

/**
 * İstek Sınırlama Fonksiyonu
 * 
 * Belirli bir süre içinde yapılabilecek istek sayısını sınırlar
 * Brute force saldırılarına karşı koruma sağlar
 * 
 * @param string $action İşlem adı
 * @param int $maxAttempts İzin verilen maksimum deneme sayısı
 * @param int $timeWindow Zaman penceresi (saniye)
 * @return bool İstek limiti aşıldı mı?
 */
function checkRateLimit($action, $maxAttempts, $timeWindow) {
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    $now = time();
    $key = $action . '_' . $_SESSION['user_id'];
    
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = [
            'attempts' => 0,
            'first_attempt' => $now
        ];
    }
    
    $limit = &$_SESSION['rate_limits'][$key];
    
    // Zaman penceresi geçtiyse sayacı sıfırla
    if ($now - $limit['first_attempt'] > $timeWindow) {
        $limit['attempts'] = 0;
        $limit['first_attempt'] = $now;
    }
    
    // Limit kontrolü
    if ($limit['attempts'] >= $maxAttempts) {
        return false;
    }
    
    // Deneme sayısını artır
    $limit['attempts']++;
    return true;
}

/**
 * Veritabanı Yapılandırması
 * 
 * Veritabanı bağlantı bilgileri ve ayarları
 * Güvenlik için production ortamında değiştirilmeli
 */
$dbConfig = [
    'host' => 'localhost',      // Veritabanı sunucusu
    'name' => 'album_ranker',   // Veritabanı adı
    'user' => 'root',          // Veritabanı kullanıcısı
    'pass' => '',              // Veritabanı şifresi
    'charset' => 'utf8mb4'     // Karakter seti
];

/**
 * Veritabanı Bağlantısı
 * 
 * PDO kullanarak güvenli veritabanı bağlantısı oluştur
 * Hata durumunda log kaydı tut ve kullanıcıya bilgi ver
 */
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,           // Hata modu
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // Varsayılan fetch modu
        PDO::ATTR_EMULATE_PREPARES => false                    // Gerçek prepared statements kullan
    ];
    $conn = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Could not connect to database. Please try again later.");
}

/**
 * Dosya Yükleme Yapılandırması
 * 
 * - UPLOAD_DIR: Yükleme dizini
 * - MAX_FILE_SIZE: Maksimum dosya boyutu (5MB)
 * - ALLOWED_IMAGE_TYPES: İzin verilen resim formatları
 * - ALLOWED_AUDIO_TYPES: İzin verilen ses formatları
 */
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/x-png', 'image/gif']);
define('ALLOWED_AUDIO_TYPES', ['audio/mpeg', 'audio/wav', 'audio/ogg']);

/**
 * Yükleme Dizinlerini Oluştur
 * 
 * Gerekli dizinler yoksa oluştur
 * Dizinler: covers (kapak resimleri), tracks (müzik dosyaları), profiles (profil resimleri)
 */
array_map(function($dir) {
    $path = UPLOAD_DIR . $dir;
    !file_exists($path) && mkdir($path, 0777, true);
}, ['covers', 'tracks', 'profiles']);

/**
 * Yardımcı Fonksiyonları Yükle
 * 
 * - functions.php: Genel yardımcı fonksiyonlar
 * - validation.php: Veri doğrulama fonksiyonları
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/validation.php';

/**
 * CSRF Koruması
 * 
 * Her oturum için benzersiz bir CSRF token oluştur
 * Form gönderimlerinde bu token kontrol edilir
 */
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Veritabanı İlişki Ayarları
 * 
 * Kullanıcı silindiğinde ilgili albümlerin de silinmesi için
 * foreign key constraint ayarı
 * 
 * Not: Gerekirse aşağıdaki SQL komutlarını çalıştırın:
 * ALTER TABLE albums DROP FOREIGN KEY albums_ibfk_1;
 * ALTER TABLE albums ADD CONSTRAINT albums_ibfk_1 
 * FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
 */
?> 