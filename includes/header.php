<?php
/**
 * header.php
 * 
 * Bu dosya, web sitesinin üst kısmını (header) oluşturan temel şablon dosyasıdır.
 * Tüm sayfalarda ortak olarak kullanılan üst menü, navigasyon ve temel HTML yapısını içerir.
 * 
 * İçerik:
 * 1. Oturum yönetimi
 * 2. CSRF koruması
 * 3. Temel HTML yapısı
 * 4. Responsive menü sistemi
 * 5. Kullanıcı giriş durumuna göre dinamik menü öğeleri
 */

// Oturum başlatma kontrolü - Eğer oturum başlatılmamışsa başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı bağlantısı ve diğer yapılandırma ayarlarını içeren dosyayı dahil et
require_once __DIR__ . '/../config.php';

/**
 * CSRF (Cross-Site Request Forgery) koruması için token oluşturma
 * Eğer token yoksa, güvenli bir şekilde yeni token oluştur
 * Bu token, form gönderimlerinde güvenlik kontrolü için kullanılır
 */
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Kullanıcı oturum durumunu kontrol et
 * $isLoggedIn: Kullanıcının giriş yapıp yapmadığını belirler
 * $currentUserId: Giriş yapmış kullanıcının ID'sini tutar
 */
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Temel meta etiketleri -->
    <meta charset="UTF-8">
    <!-- Mobil cihazlar için görünüm ayarları -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Sayfa başlığı - Eğer $pageTitle tanımlıysa onu, değilse 'AlbumRanker' yazısını göster -->
    <title><?php echo isset($pageTitle) ? h($pageTitle) : 'AlbumRanker'; ?></title>
    <!-- Site favicon'u -->
    <link rel="icon" href="img/core-img/favicon.ico">
    <!-- Ana stil dosyası -->
    <link rel="stylesheet" href="style.css">
    <!-- JavaScript dosyası - defer özelliği ile sayfa yüklendikten sonra çalışır -->
    <script src="js/main.js" defer></script>
</head>
<body>
<!-- Üst Menü Bölümü Başlangıcı -->
<header class="header-area">
    <!-- Ana menü container'ı -->
    <div class="oneMusic-main-menu">
        <!-- Responsive tasarım için breakpoint ayarları -->
        <div class="classy-nav-container breakpoint-off">
            <div class="container">
                <!-- Navigasyon çubuğu - Logo ve menü öğelerini içerir -->
                <nav class="classy-navbar justify-content-between" id="oneMusicNav">
                    <!-- Site logosu ve başlığı -->
                    <a href="index.php" class="nav-brand"><span style="font-size:2rem;font-weight:bold;color:#fff;letter-spacing:2px;">AlbumRanker</span></a>
                    <!-- Mobil menü için hamburger menü butonu -->
                    <div class="classy-navbar-toggler">
                        <span class="navbarToggler"><span></span><span></span><span></span></span>
                    </div>
                    <!-- Ana menü içeriği -->
                    <div class="classy-menu">
                        <!-- Mobil menü kapatma butonu -->
                        <div class="classycloseIcon">
                            <div class="cross-wrap"><span class="top"></span><span class="bottom"></span></div>
                        </div>
                        <!-- Menü öğeleri -->
                        <div class="classynav">
                            <ul>
                                <!-- Ana sayfa linki -->
                                <li><a href="index.php">Home</a></li>
                                <!-- Albüm keşfetme sayfası linki -->
                                <li><a href="albums-store.php">Discover</a></li>
                                <!-- Kullanıcı giriş yapmışsa albüm yükleme linkini göster -->
                                <?php if ($isLoggedIn): ?>
                                <li><a href="album-upload.php">Upload Album</a></li>
                                <?php endif; ?>
                            </ul>
                            <!-- Kullanıcı giriş/kayıt butonları -->
                            <div class="login-register-cart-button d-flex align-items-center">
                                <div class="login-register-btn" id="userMenu">
                                    <?php if ($isLoggedIn): ?>
                                        <!-- Giriş yapmış kullanıcı için profil ve çıkış butonları -->
                                        <a href="profile.php" class="btn btn-primary">My Profile</a>
                                        <a href="#" onclick="handleLogout(event)" class="btn btn-outline-primary ml-2">Logout</a>
                                    <?php else: ?>
                                        <!-- Giriş yapmamış kullanıcı için giriş/kayıt butonu -->
                                        <a href="login.php" class="btn btn-primary">Login / Register</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
</header>
<!-- Üst Menü Bölümü Sonu --> 