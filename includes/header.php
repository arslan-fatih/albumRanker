<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

// CSRF token kontrolü
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Kullanıcı oturum kontrolü
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $_SESSION['user_id'] ?? null;
?>
<!-- Header Area -->
<header class="header-area">
    <div class="oneMusic-main-menu">
        <div class="classy-nav-container breakpoint-off">
            <div class="container">
                <nav class="classy-navbar justify-content-between" id="oneMusicNav">
                    <!-- Nav brand -->
                    <a href="index.php" class="nav-brand"><span style="font-size:2rem;font-weight:bold;color:#fff;letter-spacing:2px;">AlbumRanker</span></a>
                    <!-- Navbar Toggler -->
                    <div class="classy-navbar-toggler">
                        <span class="navbarToggler"><span></span><span></span><span></span></span>
                    </div>
                    <!-- Menu -->
                    <div class="classy-menu">
                        <!-- Close Button -->
                        <div class="classycloseIcon">
                            <div class="cross-wrap"><span class="top"></span><span class="bottom"></span></div>
                        </div>
                        <!-- Nav Start -->
                        <div class="classynav">
                            <ul>
                                <li><a href="index.php">Home</a></li>
                                <li><a href="albums-store.php">Discover</a></li>
                                <?php if ($isLoggedIn): ?>
                                <li><a href="album-upload.php">Upload Album</a></li>
                                <?php endif; ?>
                            </ul>
                            <div class="login-register-cart-button d-flex align-items-center">
                                <div class="login-register-btn mr-50" id="userMenu">
                                    <?php if ($isLoggedIn): ?>
                                        <a href="profile.php" class="btn btn-primary">My Profile</a>
                                        <a href="logout.php" class="btn btn-outline-primary ml-2">Logout</a>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-primary">Login / Register</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Nav End -->
                    </div>
                </nav>
            </div>
        </div>
    </div>
</header>
<!-- Header Area End -->
<title><?php echo isset($pageTitle) ? h($pageTitle) : 'AlbumRanker'; ?></title>
<link rel="icon" href="img/core-img/favicon.ico">
<link rel="stylesheet" href="style.css">
<script src="js/main.js" defer></script> 