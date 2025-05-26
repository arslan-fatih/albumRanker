<?php
/**
 * Application Configuration File
 * This file contains all the essential settings and configurations for the AlbumRanker application
 */

// Error Reporting Settings
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Time Zone Setting
date_default_timezone_set('Europe/Istanbul');

// Session Security Settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Database Configuration
$dbConfig = [
    'host' => 'localhost',
    'name' => 'album_ranker',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4'
];

// Database Connection
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    $conn = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Could not connect to database. Please try again later.");
}

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/x-png', 'image/gif']);
define('ALLOWED_AUDIO_TYPES', ['audio/mpeg', 'audio/wav', 'audio/ogg']);

// Create Upload Directories
$uploadDirs = ['covers', 'tracks', 'profiles'];
foreach ($uploadDirs as $dir) {
    $path = UPLOAD_DIR . $dir;
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
}

// Load Helper Functions
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/validation.php';

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate Limiting Function
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

// Note: If needed, run the following SQL in the database:
// ALTER TABLE albums DROP FOREIGN KEY albums_ibfk_1;
// ALTER TABLE albums ADD CONSTRAINT albums_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
?> 