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
    <style>
    .delete-album-btn-custom {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #fff;
        border: none;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        cursor: pointer;
        transition: box-shadow 0.2s;
        z-index: 2;
        padding: 0;
    }
    .delete-album-btn-custom i {
        color: #888;
        font-size: 18px;
        transition: color 0.2s;
    }
    .delete-album-btn-custom:hover {
        box-shadow: 0 4px 16px rgba(255,0,0,0.10);
    }
    .delete-album-btn-custom:hover i {
        color: #e74c3c;
    }
    </style>
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
    <section class="hero-area bg-img bg-overlay" style="background-image: url('https://www.chapmanarchitects.co.uk/wp-content/uploads/2017/08/Abbey_Road_4.jpg'); min-height: 340px; display: flex; align-items: center; position: relative;">
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
                            <img id="profilePic" src="<?php echo htmlspecialchars($user['profile_pic'] ?: 'img/bg-img/profile-pic.jpg'); ?>" alt="Profile Picture" class="rounded-circle" style="width: 200px; height: 200px; object-fit: cover;">
                            <?php if (isset(
                                $isOwnProfile
                            ) && $isOwnProfile): ?>
                                <?php if ($user['profile_pic']): ?>
                                    <button type="button" class="btn btn-outline-secondary mt-3" id="editProfilePicBtn">Edit Profile Picture</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-primary mt-3" id="editProfilePicBtn">Add Profile Picture</button>
                                <?php endif; ?>
                                <!-- Modal -->
                                <div class="modal fade" id="profilePicModal" tabindex="-1" aria-labelledby="profilePicModalLabel" aria-hidden="true">
                                  <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                      <div class="modal-header border-0">
                                        <h5 class="modal-title w-100 text-center" id="profilePicModalLabel">Profile Picture</h5>
                                      </div>
                                      <div class="modal-body text-center">
                                        <button type="button" class="btn btn-outline-primary w-100 mb-2" id="changeProfilePicBtn">Change Profile Picture</button>
                                        <?php if ($user['profile_pic']): ?>
                                        <button type="button" class="btn btn-outline-danger w-100" id="removeProfilePicBtn">Remove Profile Picture</button>
                                        <?php endif; ?>
                                        <form id="profilePictureForm" enctype="multipart/form-data" class="mt-3" style="display:none;">
                                            <input type="file" class="form-control mb-2" id="profilePicture" name="profile" accept="image/jpeg,image/png,image/gif" required>
                                            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                                            <div class="form-text">JPG, PNG, GIF. Maks: 5MB</div>
                                            <div id="profilePicMessage" class="mt-2"></div>
                                        </form>
                                      </div>
                                    </div>
                                  </div>
                                </div>
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
                                        <div class="card h-100 shadow-sm position-relative">
                                            <?php if ($isOwnProfile) { ?>
                                                <button class="delete-album-btn-custom" data-album-id="<?php echo $album['id']; ?>" title="Delete">
                                                    <i class="icon-trash"></i>
                                                </button>
                                            <?php } ?>
                                            <a href="album-detail.php?id=<?php echo $album['id']; ?>">
                                                <img src="<?php echo htmlspecialchars(getAlbumCover($album['cover_image'])); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($album['title']); ?>" style="height: 220px; object-fit: cover;">
                                            </a>
                                            <div class="card-body">
                                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($album['title']); ?></h5>
                                                <p class="card-text text-muted mb-1"><?php echo htmlspecialchars($album['artist']); ?></p>
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
                    <a href="#"><span style="font-size:1.5rem;font-weight:bold;color:#fff;letter-spacing:2px;">AlbumRanker</span></a>
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
        // --- PROFİL FOTOĞRAF DEĞİŞKENLERİ ---
        const form = document.getElementById('profilePictureForm');
        const fileInput = document.getElementById('profilePicture');
        const message = document.getElementById('profilePicMessage');
        const profilePic = document.getElementById('profilePic');
        // Modal kapandığında formu ve mesajı sıfırla
        document.getElementById('profilePicModal').addEventListener('hidden.bs.modal', function () {
            if (form) {
                form.style.display = 'none';
                form.reset();
            }
            if (message) message.innerHTML = '';
        });
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

        const editBtn = document.getElementById('editProfilePicBtn');
        const modal = new bootstrap.Modal(document.getElementById('profilePicModal'));
        const changeBtn = document.getElementById('changeProfilePicBtn');
        const removeBtn = document.getElementById('removeProfilePicBtn');
        if (editBtn) {
            editBtn.addEventListener('click', function() {
                modal.show();
            });
        }
        if (changeBtn) {
            changeBtn.addEventListener('click', function() {
                form.style.display = 'block';
            });
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                fetch('upload.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=remove_profile'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('profilePic').src = 'img/bg-img/profile-pic.jpg?t=' + Date.now();
                        modal.hide();
                    } else {
                        alert(data.message);
                    }
                });
            });
        }

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
                    const imgPath = '/AlbumRanker' + data.file;
                    profilePic.src = imgPath + '?t=' + Date.now();
                } else {
                    message.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                message.innerHTML = '<div class="alert alert-danger">Bir hata oluştu. Lütfen tekrar deneyin.</div>';
            });
        });

        let albumToDelete = null;
        document.querySelectorAll('.delete-album-btn-custom').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                albumToDelete = this.getAttribute('data-album-id');
                var modal = new bootstrap.Modal(document.getElementById('deleteAlbumModal'));
                modal.show();
            });
        });
        document.getElementById('confirmDeleteAlbumBtn').addEventListener('click', function() {
            if (albumToDelete) {
                window.location.href = 'delete-album.php?id=' + albumToDelete;
            }
        });
    });
    </script>
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteAlbumModal" tabindex="-1" aria-labelledby="deleteAlbumModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title w-100 text-center" id="deleteAlbumModalLabel">Delete Album</h5>
          </div>
          <div class="modal-body text-center">
            Are you sure you want to delete this album?
          </div>
          <div class="modal-footer justify-content-center border-0">
            <button type="button" class="btn btn-danger" id="confirmDeleteAlbumBtn">Delete</button>
          </div>
        </div>
      </div>
    </div>
</body>
</html> 