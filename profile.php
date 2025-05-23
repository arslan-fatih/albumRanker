<?php session_start(); ?>
<?php
require_once 'config.php';

// Eğer user parametresi varsa, o kullanıcıyı göster
if (isset($_GET['user'])) {
    $userId = intval($_GET['user']);
    // Eğer kendi profilini açmaya çalışıyorsa, kendi profilini göster
    if (isset($_SESSION['user_id']) && $userId == $_SESSION['user_id']) {
        $userId = $_SESSION['user_id'];
    }
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        echo '<p class="text-center mt-5">User not found.</p>';
        exit;
    }
} else {
    // user parametresi yoksa, oturumdaki kullanıcıyı göster
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        echo '<p class="text-center mt-5">User not found.</p>';
        exit;
    }
}

// Kullanıcının yüklediği albüm sayısını çek
$stmtAlbumCount = $conn->prepare("SELECT COUNT(*) FROM albums WHERE user_id = ?");
$stmtAlbumCount->execute([$user['id']]);
$albumCount = $stmtAlbumCount->fetchColumn();

$isOwnProfile = isset($_SESSION['user_id']) && $user['id'] == $_SESSION['user_id'];
$isFollowing = false;
if (!$isOwnProfile && isset($_SESSION['user_id'])) {
    $stmtFollow = $conn->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmtFollow->execute([$_SESSION['user_id'], $user['id']]);
    $isFollowing = $stmtFollow->fetch() ? true : false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>AlbumRanker - User Profile</title>
    <link rel="icon" href="img/core-img/favicon.ico">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="preloader d-flex align-items-center justify-content-center">
        <div class="lds-ellipsis">
            <div></div><div></div><div></div><div></div>
        </div>
    </div>
    <header class="header-area">
        <div class="oneMusic-main-menu">
            <div class="classy-nav-container breakpoint-off">
                <div class="container">
                    <nav class="classy-navbar justify-content-between" id="oneMusicNav">
                        <a href="index.php" class="nav-brand"><span style="font-size:2rem;font-weight:bold;color:#fff;letter-spacing:2px;">AlbumRanker</span></a>
                        <div class="classy-navbar-toggler">
                            <span class="navbarToggler"><span></span><span></span><span></span></span>
                        </div>
                        <div class="classy-menu">
                            <div class="classycloseIcon">
                                <div class="cross-wrap"><span class="top"></span><span class="bottom"></span></div>
                            </div>
                            <div class="classynav">
                                <ul>
                                    <li><a href="index.php">Home</a></li>
                                    <li><a href="albums-store.php">Discover</a></li>
                                </ul>
                                <div class="login-register-cart-button d-flex align-items-center">
                                    <div class="login-register-btn mr-50" id="userMenu">
<?php if (isset($_SESSION['user_id'])): ?>
    <a href="profile.php" class="btn btn-primary">My Profile</a>
    <a href="logout.php" class="btn btn-outline-primary ml-2">Logout</a>
<?php else: ?>
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
    <section class="breadcumb-area bg-img bg-overlay" style="background-image: url(img/bg-img/breadcumb3.jpg);">
        <div class="bradcumbContent">
            <p>User Profile</p>
            <h2 id="profileUsername"><?php echo htmlspecialchars($user['username']); ?></h2>
        </div>
    </section>
    <section class="profile-area section-padding-100">
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-4">
                    <div class="profile-info">
                        <div class="profile-pic text-center mb-30">
                            <img id="profilePic" src="<?php echo htmlspecialchars($user['profile_pic'] && $user['profile_pic'] !== '' ? 'uploads/profiles/' . $user['profile_pic'] : 'img/bg-img/profile-pic.jpg'); ?>" alt="Profile Picture" class="rounded-circle" style="width: 200px; height: 200px; object-fit: cover;">
                            <?php if (
                                isset(
                                    $isOwnProfile
                                ) && $isOwnProfile): ?>
                            <form id="profilePictureForm" enctype="multipart/form-data" class="mt-3">
                                <input type="file" class="form-control mb-2" id="profilePicture" name="profile" accept="image/jpeg,image/png,image/gif" required>
                                <button type="submit" class="btn btn-sm btn-primary">Upload Profile Picture</button>
                                <button type="submit" class="btn btn-sm btn-primary">Profil Fotoğrafı Yükle</button>
                                <div class="form-text">JPG, PNG, GIF. Maks: 5MB</div>
                                <div id="profilePicMessage" class="mt-2"></div>
                            </form>
                            <?php endif; ?>
                        </div>
                        <div class="profile-bio text-center mb-30">
                            <h4 id="profileName"><?php echo htmlspecialchars($user['username']); ?></h4>
                            <p class="text-muted" id="profileUsernameTag">@<?php echo htmlspecialchars($user['username']); ?></p>
                            <p class="bio-text" id="profileBio"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></p>
                        </div>
                        <div class="user-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $albumCount; ?></span>
                                <span class="stat-label">Albums</span>
                            </div>
                            <div class="stat-item">
                                <a href="followers.php?user_id=<?php echo $user['id']; ?>&type=followers" class="text-decoration-none">
                                    <span class="stat-value"><?php 
                                        $stmt = $conn->prepare("SELECT COUNT(*) FROM followers WHERE following_id = ?");
                                        $stmt->execute([$user['id']]);
                                        echo $stmt->fetchColumn();
                                    ?></span>
                                    <span class="stat-label">Followers</span>
                                </a>
                            </div>
                            <div class="stat-item">
                                <a href="followers.php?user_id=<?php echo $user['id']; ?>&type=following" class="text-decoration-none">
                                    <span class="stat-value"><?php 
                                        $stmt = $conn->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
                                        $stmt->execute([$user['id']]);
                                        echo $stmt->fetchColumn();
                                    ?></span>
                                    <span class="stat-label">Following</span>
                                </a>
                            </div>
                        </div>
                        <div class="profile-achievements text-center mb-30" style="display:none;"></div>
                        <?php if (!$isOwnProfile && isset($_SESSION['user_id'])): ?>
                        <div class="profile-actions text-center mb-3">
                            <button class="btn btn-outline-primary follow-btn" data-user-id="<?php echo $user['id']; ?>"><?php echo $isFollowing ? 'Unfollow' : 'Follow'; ?></button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-12 col-lg-8">
                    <div class="profile-tabs">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#albums" role="tab">Albums</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#reviews" role="tab">Reviews</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#favorites" role="tab">Favorites</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#followers" role="tab">Followers</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#followed" role="tab">Followed</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="albums" role="tabpanel">
                                <div class="row" id="userAlbums">
                                    <?php
                                    $stmt = $conn->prepare("SELECT * FROM albums WHERE user_id = ? ORDER BY created_at DESC");
                                    $stmt->execute([$user['id']]);
                                    $albums = $stmt->fetchAll();
                                    if ($albums) {
                                        foreach ($albums as $album) {
                                    ?>
                                    <div class="col-12 col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100 shadow-sm">
                                            <a href="album-detail.php?id=<?php echo $album['id']; ?>">
                                                <img src="<?php echo htmlspecialchars(getAlbumCover($album['cover_image'])); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($album['title']); ?>" style="height: 220px; object-fit: cover;">
                                            </a>
                                            <div class="card-body">
                                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($album['title']); ?></h5>
                                                <p class="card-text text-muted mb-1"><?php echo htmlspecialchars($album['artist']); ?></p>
                                                <a href="album-detail.php?id=<?php echo $album['id']; ?>" class="btn btn-outline-primary btn-sm">Details</a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                        }
                                    } else {
                                    ?>
                                    <div class="col-12"><p class="text-center text-muted">No albums found.</p></div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="reviews" role="tabpanel">
                                <div class="reviews-list" id="userReviews">
                                    <?php
                                    // Kullanıcının yaptığı review'ları (reviews tablosundan) ve (varsa) puanını (ratings tablosundan) çekiyoruz.
                                    $stmt = $conn->prepare("
                                        SELECT r.id, r.album_id, r.content, r.created_at, a.title AS album_title, a.artist AS album_artist, rt.rating
                                        FROM reviews r
                                        JOIN albums a ON r.album_id = a.id
                                        LEFT JOIN ratings rt ON (rt.user_id = r.user_id AND rt.album_id = r.album_id)
                                        WHERE r.user_id = ?
                                        ORDER BY r.created_at DESC
                                    ");
                                    $stmt->execute([$user['id']]);
                                    $reviews = $stmt->fetchAll();
                                    if ($reviews) {
                                        foreach ($reviews as $review) {
                                    ?>
                                    <div class="card mb-3 shadow-sm">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <a href="album-detail.php?id=<?php echo $review['album_id']; ?>" class="text-decoration-none">
                                                <strong><?php echo htmlspecialchars($review['album_title']); ?> (<?php echo htmlspecialchars($review['album_artist']); ?>)</strong>
                                            </a>
                                            <small class="text-muted"><?php echo date("d.m.Y H:i", strtotime($review['created_at'])); ?></small>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text"><?php echo nl2br(htmlspecialchars($review['content'])); ?></p>
                                            <?php if (isset($review['rating'])) { ?>
                                            <p class="card-text text-muted mb-0">Rating: <?php echo htmlspecialchars($review['rating']); ?>/10</p>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <?php
                                        }
                                    } else {
                                    ?>
                                    <p class="text-center text-muted">No reviews found.</p>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="favorites" role="tabpanel">
                                <div class="row" id="userFavorites">
                                    <?php
                                    $stmt = $conn->prepare("SELECT a.* FROM favorites f JOIN albums a ON f.album_id = a.id WHERE f.user_id = ? ORDER BY f.created_at DESC");
                                    $stmt->execute([$user['id']]);
                                    $favorites = $stmt->fetchAll();
                                    if ($favorites) {
                                        foreach ($favorites as $album) {
                                    ?>
                                    <div class="col-12 col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100 shadow-sm">
                                            <a href="album-detail.php?id=<?php echo $album['id']; ?>">
                                                <img src="<?php echo htmlspecialchars(getAlbumCover($album['cover_image'])); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($album['title']); ?>" style="height: 220px; object-fit: cover;">
                                            </a>
                                            <div class="card-body">
                                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($album['title']); ?></h5>
                                                <p class="card-text text-muted mb-1"><?php echo htmlspecialchars($album['artist']); ?></p>
                                                <a href="album-detail.php?id=<?php echo $album['id']; ?>" class="btn btn-outline-primary btn-sm">Details</a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                        }
                                    } else {
                                    ?>
                                    <div class="col-12"><p class="text-center text-muted">No favorites found.</p></div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="followers" role="tabpanel">
                                <ul class="list-group list-group-flush">
                                    <?php
                                    $stmt = $conn->prepare("SELECT u.id, u.username, u.profile_pic FROM followers f JOIN users u ON f.follower_id = u.id WHERE f.following_id = ?");
                                    $stmt->execute([$user['id']]);
                                    $followers = $stmt->fetchAll();
                                    if ($followers) {
                                        foreach ($followers as $follower) {
                                    ?>
                                    <li class="list-group-item d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($follower['profile_pic'] ?? 'img/core-img/default.jpg'); ?>" class="rounded-circle me-2" alt="Profil" style="width:32px;height:32px;object-fit:cover;">
                                        <a href="profile.php?user=<?php echo $follower['id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($follower['username']); ?></a>
                                    </li>
                                    <?php }} else { echo '<li class="list-group-item text-center">No followers.</li>'; } ?>
                                </ul>
                            </div>
                            <div class="tab-pane fade" id="followed" role="tabpanel">
                                <ul class="list-group list-group-flush">
                                    <?php
                                    $stmt = $conn->prepare("SELECT u.id, u.username, u.profile_pic FROM followers f JOIN users u ON f.following_id = u.id WHERE f.follower_id = ?");
                                    $stmt->execute([$user['id']]);
                                    $followed = $stmt->fetchAll();
                                    if ($followed) {
                                        foreach ($followed as $f) {
                                    ?>
                                    <li class="list-group-item d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($f['profile_pic'] ?? 'img/core-img/default.jpg'); ?>" class="rounded-circle me-2" alt="Profil" style="width:32px;height:32px;object-fit:cover;">
                                        <a href="profile.php?user=<?php echo $f['id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($f['username']); ?></a>
                                    </li>
                                    <?php }} else { echo '<li class="list-group-item text-center">No following.</li>'; } ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <footer class="footer-area">
        <div class="container">
            <div class="row d-flex flex-wrap align-items-center">
                <div class="col-12 col-md-6">
                    <a href="#"><img src="img/core-img/logo.png" alt=""></a>
                    <p class="copywrite-text"><a href="#">Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved | AlbumRanker</a></p>
                </div>
                <div class="col-12 col-md-6">
                    <div class="footer-nav">
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="albums-store.php">Discover</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <script src="js/bootstrap/popper.min.js"></script>
    <script src="js/bootstrap/bootstrap.min.js"></script>
    <script src="js/plugins/plugins.js"></script>
    <script src="js/active.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Takip Et / Takipten Çık butonu
        var followBtn = document.querySelector('.follow-btn');
        if (followBtn) {
            followBtn.addEventListener('click', function() {
                var userId = this.getAttribute('data-user-id');
                var button = this;
                fetch('follow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'user_id=' + encodeURIComponent(userId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        button.textContent = data.following ? 'Unfollow' : 'Follow';
                    } else {
                        alert(data.message || 'Bir hata oluştu.');
                    }
                })
                .catch(() => alert('Bir hata oluştu.'));
            });
        }

        const form = document.getElementById('profilePictureForm');
        if (!form) return;
        const fileInput = document.getElementById('profilePicture');
        const message = document.getElementById('profilePicMessage');
        const profilePic = document.getElementById('profilePic');
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (!allowedTypes.includes(file.type)) {
                    message.innerHTML = '<div class="alert alert-danger">Sadece JPG, PNG veya GIF dosyası yükleyebilirsiniz.</div>';
                    fileInput.value = '';
                    return;
                }
                if (file.size > maxSize) {
                    message.innerHTML = '<div class="alert alert-danger">Dosya boyutu 5MB\'dan büyük olamaz.</div>';
                    fileInput.value = '';
                    return;
                }
            }
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const file = fileInput.files[0];
            if (!file) {
                message.innerHTML = '<div class="alert alert-danger">Lütfen bir dosya seçin.</div>';
                return;
            }
            if (!allowedTypes.includes(file.type)) {
                message.innerHTML = '<div class="alert alert-danger">Sadece JPG, PNG veya GIF dosyası yükleyebilirsiniz.</div>';
                return;
            }
            if (file.size > maxSize) {
                message.innerHTML = '<div class="alert alert-danger">Dosya boyutu 5MB\'dan büyük olamaz.</div>';
                return;
            }
            const formData = new FormData();
            formData.append('action', 'upload_profile');
            formData.append('profile', file);
            message.innerHTML = '<div class="alert alert-info">Yükleniyor...</div>';
            fetch('upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    message.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    // Profil fotoğrafını anında güncelle
                    profilePic.src = data.path + '?t=' + new Date().getTime();
                } else {
                    message.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                message.innerHTML = '<div class="alert alert-danger">Bir hata oluştu. Lütfen tekrar deneyin.</div>';
            });
        });
    });
    </script>
</body>
</html> 