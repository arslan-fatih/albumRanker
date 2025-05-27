<?php session_start(); ?>
<?php
require_once 'config.php';
require_once 'includes/functions.php'; // Make sure functions are included

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

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

// Kullanıcı istatistiklerini çek (albüm, takipçi, takip edilen sayısı)
$userStats = getUserStats($user['id']);
$albumCount = $userStats['album_count'];
$followerCount = $userStats['follower_count'];
$followingCount = $userStats['following_count'];

$isOwnProfile = isset($_SESSION['user_id']) && $user['id'] == $_SESSION['user_id'];
$isFollowing = false;
if (!$isOwnProfile && isset($_SESSION['user_id'])) {
    $stmtFollow = $conn->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmtFollow->execute([$_SESSION['user_id'], $user['id']]);
    $isFollowing = $stmtFollow->fetch() ? true : false;
}
?>

    <?php 
    $pageTitle = 'AlbumRanker - User Profile';
    require_once 'includes/header.php';
    ?>
    <section class="hero-area bg-img bg-overlay" style="background-image: url('https://images.pexels.com/photos/257904/pexels-photo-257904.jpeg'); min-height: 340px; display: flex; align-items: center; position: relative;">
        <div class="bradcumbContent">
            
            <h2 id="profileUsername"><?php echo htmlspecialchars($user['username']); ?></h2>
        </div>
    </section>
    <section class="profile-area section-padding-100">
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-4">
                    <div class="profile-info">
                        <div class="profile-pic text-center mb-30">
                            <img id="profilePic" src="<?php echo htmlspecialchars(getProfilePicturePath($user['profile_pic'])); ?>" alt="Profile Picture" class="rounded-circle" style="width: 200px; height: 200px; object-fit: cover;">
                            <?php if (isset(
                                $isOwnProfile
                            ) && $isOwnProfile): ?>
                                <?php if ($user['profile_pic'] && $user['profile_pic'] !== 'default.jpg'): ?>
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
                                        <?php if ($user['profile_pic'] && $user['profile_pic'] !== 'default.jpg'): ?>
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
                            <?php if ($isOwnProfile): ?>
                            <button type="button" class="btn btn-outline-primary mt-2" id="editProfileBtn">
                                <i class="icon-edit"></i> Edit Profile
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="user-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $albumCount; ?></span>
                                <span class="stat-label">Albums</span>
                            </div>
                            <div class="stat-item">
                                <a href="followers.php?user_id=<?php echo $user['id']; ?>&type=followers" class="text-decoration-none">
                                    <span class="stat-value"><?php echo $followerCount; ?></span>
                                    <span class="stat-label">Followers</span>
                                </a>
                            </div>
                            <div class="stat-item">
                                <a href="followers.php?user_id=<?php echo $user['id']; ?>&type=following" class="text-decoration-none">
                                    <span class="stat-value"><?php echo $followingCount; ?></span>
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
                        <?php if ($isOwnProfile): ?>
                        <button type="button" class="btn btn-outline-danger mt-2" id="deleteAccountBtn">
                            <i class="icon-trash"></i> Delete Account
                        </button>
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
                                        SELECT r.id, r.album_id, r.content, r.created_at, a.title AS album_title, a.artist AS album_artist, rt.rating,
                                               (SELECT COUNT(*) FROM review_likes WHERE review_id = r.id) as like_count
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
                                            <div class="d-flex justify-content-between align-items-center">
                                                <?php if (isset($review['rating'])) { ?>
                                                <p class="card-text text-muted mb-0">Rating: <?php echo htmlspecialchars($review['rating']); ?>/10</p>
                                                <?php } ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-heart"></i> <?php echo $review['like_count']; ?> likes
                                                </small>
                                            </div>
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
                                <div class="row">
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
                                        <img src="<?php echo htmlspecialchars(getProfilePicturePath($follower['profile_pic'])); ?>" class="rounded-circle me-2" alt="Profil" style="width:32px;height:32px;object-fit:cover;">
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
                                        <img src="<?php echo htmlspecialchars(getProfilePicturePath($f['profile_pic'])); ?>" class="rounded-circle me-2" alt="Profil" style="width:32px;height:32px;object-fit:cover;">
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

    <!-- Modal for Delete Account Confirmation -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteAccountModalLabel">Confirm Account Deletion</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete your account? This action cannot be undone.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteAccountBtn">Delete Account</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Profile Edit Modal -->
    <?php if ($isOwnProfile): ?>
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editProfileForm">
                        <div class="form-group">
                            <label for="editUsername">Username</label>
                            <input type="text" class="form-control" id="editUsername" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            <small class="form-text text-muted">Username can only contain letters, numbers, and underscores.</small>
                        </div>
                        <div class="form-group">
                            <label for="editBio">Bio</label>
                            <textarea class="form-control" id="editBio" name="bio" rows="4" maxlength="500"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Tell us about yourself (max 500 characters).</small>
                        </div>
                        <div id="editProfileMessage" class="alert" style="display: none;"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" form="editProfileForm" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>


    <?php require_once 'includes/footer.php'; ?> 

    <!-- **** All JS Files ***** -->
    <!-- jQuery-2.2.4 js -->
    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <!-- Popper js -->
    <script src="js/bootstrap/popper.min.js"></script>
    <!-- Bootstrap js -->
    <script src="js/bootstrap/bootstrap.min.js"></script>
    <!-- All Plugins js -->
    <script src="js/plugins/plugins.js"></script>
    <!-- Active js -->
    <script src="js/active.js"></script>
    <!-- Custom JS for Profile Page -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- PROFİL FOTOĞRAF MODALI VE İŞLEMLERİ ---
        const profilePicModal = document.getElementById('profilePicModal');
        const editProfilePicBtn = document.getElementById('editProfilePicBtn');
        const changeProfilePicBtn = document.getElementById('changeProfilePicBtn');
        const removeProfilePicBtn = document.getElementById('removeProfilePicBtn');
        const profilePictureForm = document.getElementById('profilePictureForm');
        const profilePictureInput = document.getElementById('profilePicture');
        const profilePicMessage = document.getElementById('profilePicMessage');
        const profilePicImg = document.getElementById('profilePic');

        if (profilePicModal) {
            const modal = new bootstrap.Modal(profilePicModal);

            if (editProfilePicBtn) {
                editProfilePicBtn.addEventListener('click', function() {
                    modal.show();
                });
            }

            if (changeProfilePicBtn) {
                changeProfilePicBtn.addEventListener('click', function() {
                    if (profilePictureForm) profilePictureForm.style.display = 'block';
                    if (profilePicMessage) profilePicMessage.innerHTML = ''; // Clear previous messages
                });
            }

            if (removeProfilePicBtn) {
                removeProfilePicBtn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to remove your profile picture?')) {
                        fetch('upload.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'action=remove_profile'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update the image source to default
                                if (profilePicImg) profilePicImg.src = 'img/core-img/default.jpg';
                                // Hide the remove button as there is no custom pic anymore
                                if (removeProfilePicBtn) removeProfilePicBtn.style.display = 'none';
                                // Optionally, change the edit button text
                                if (editProfilePicBtn) editProfilePicBtn.textContent = 'Add Profile Picture';
                                modal.hide();
                                // Sayfayı yenile
                                setTimeout(() => { window.location.reload(); }, 1000);
                            } else {
                                alert(data.message || 'Error removing profile picture.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while removing the profile picture.');
                        });
                    }
                });
            }

            if (profilePictureForm) {
                 profilePictureForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const file = profilePictureInput.files[0];
                    if (!file) {
                        if (profilePicMessage) profilePicMessage.innerHTML = '<div class="alert alert-danger">Lütfen bir dosya seçin.</div>';
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'upload_profile');
                    formData.append('profile', file);

                    if (profilePicMessage) profilePicMessage.innerHTML = '<div class="alert alert-info">Uploading...</div>';

                    fetch('upload.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the image source
                            if (profilePicImg) profilePicImg.src = data.file + '?t=' + new Date().getTime(); // Add timestamp to bust cache
                            if (profilePicMessage) profilePicMessage.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                            // Show the remove button if it was hidden
                            if (removeProfilePicBtn) removeProfilePicBtn.style.display = 'block';
                            // Optionally, change the edit button text
                            if (editProfilePicBtn) editProfilePicBtn.textContent = 'Edit Profile Picture';
                            // Hide the file input form after successful upload
                            profilePictureForm.style.display = 'none';
                            // Sayfayı yenile
                            setTimeout(() => { window.location.reload(); }, 1000);
                        } else {
                            if (profilePicMessage) profilePicMessage.innerHTML = '<div class="alert alert-danger">' + (data.message || 'An error occurred during upload.') + '</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (profilePicMessage) profilePicMessage.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
                    });
                });
            }

            // Clear form and message when modal is closed
            profilePicModal.addEventListener('hidden.bs.modal', function () {
                if (profilePictureForm) {
                    profilePictureForm.style.display = 'none';
                    profilePictureForm.reset();
                }
                if (profilePicMessage) profilePicMessage.innerHTML = '';
            });
        }

        // --- PROFİL DÜZENLEME MODALI VE İŞLEMLERİ ---
        const editProfileModal = document.getElementById('editProfileModal');
        const editProfileBtn = document.getElementById('editProfileBtn');
        const editProfileForm = document.getElementById('editProfileForm');
        const editProfileMessage = document.getElementById('editProfileMessage');
        const profileUsernameElement = document.getElementById('profileUsername');
        const profileNameElement = document.getElementById('profileName');
        const profileUsernameTagElement = document.getElementById('profileUsernameTag');
        const profileBioElement = document.getElementById('profileBio');

        if (editProfileModal && editProfileBtn && editProfileForm) {
             const modalEdit = new bootstrap.Modal(editProfileModal);

             editProfileBtn.addEventListener('click', function() {
                 // Set initial values in the form (already done by PHP)
                 // const usernameInput = document.getElementById('editUsername');
                 // const bioInput = document.getElementById('editBio');
                 // if (usernameInput && profileUsernameElement) usernameInput.value = profileUsernameElement.textContent;
                 // if (bioInput && profileBioElement) bioInput.value = profileBioElement.textContent;
                 
                 modalEdit.show();
             });

             editProfileForm.addEventListener('submit', function(e) {
                 e.preventDefault();

                 if (editProfileMessage) {
                     editProfileMessage.style.display = 'none';
                     editProfileMessage.className = 'alert';
                     editProfileMessage.textContent = '';
                 }

                 const formData = new FormData(editProfileForm);

                 fetch('update-profile.php', {
                     method: 'POST',
                     body: formData
                 })
                 .then(response => response.json())
                 .then(data => {
                     if (editProfileMessage) {
                         editProfileMessage.style.display = 'block';
                         editProfileMessage.textContent = data.message;
                         if (data.success) {
                             editProfileMessage.className = 'alert alert-success';
                             // Update profile info on the page
                             if (profileUsernameElement && data.username) profileUsernameElement.textContent = data.username;
                             if (profileNameElement && data.username) profileNameElement.textContent = data.username; // Assuming profileName is same as username
                             if (profileUsernameTagElement && data.username) profileUsernameTagElement.textContent = '@' + data.username;
                             if (profileBioElement && data.bio !== undefined) profileBioElement.textContent = data.bio; // Use data.bio for bio

                             // Close modal after successful update (optional)
                             // setTimeout(() => { modalEdit.hide(); }, 1000);
                         } else {
                             editProfileMessage.className = 'alert alert-danger';
                         }
                     }
                 })
                 .catch(error => {
                     console.error('Error:', error);
                     if (editProfileMessage) {
                         editProfileMessage.style.display = 'block';
                         editProfileMessage.className = 'alert alert-danger';
                         editProfileMessage.textContent = 'An error occurred. Please try again.';
                     }
                 });
             });

              // Clear message when modal is closed
            editProfileModal.addEventListener('hidden.bs.modal', function () {
                 if (editProfileMessage) {
                     editProfileMessage.style.display = 'none';
                     editProfileMessage.className = 'alert';
                     editProfileMessage.textContent = '';
                 }
            });
        }

        // --- HESAP SİLME İŞLEMLERİ ---
        const deleteAccountBtn = document.getElementById('deleteAccountBtn');
        const confirmDeleteAccountBtn = document.getElementById('confirmDeleteAccountBtn');
        const deleteAccountModal = document.getElementById('deleteAccountModal');

        if (deleteAccountBtn && deleteAccountModal && confirmDeleteAccountBtn) {
            const modalDelete = new bootstrap.Modal(deleteAccountModal);

            deleteAccountBtn.addEventListener('click', function() {
                modalDelete.show();
            });

            confirmDeleteAccountBtn.addEventListener('click', function() {
                // Perform account deletion via AJAX
                fetch('upload.php', { // Assuming upload.php handles account deletion
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=delete_account' // Assuming 'delete_account' action
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Account deleted successfully.');
                        window.location.href = 'index.php'; // Redirect to homepage or login page
                    } else {
                        alert(data.message || 'Error deleting account.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the account.');
                });
                modalDelete.hide();
            });
        }

         // --- FOLLOW/UNFOLLOW İŞLEMLERİ ---
         document.querySelectorAll('.follow-btn').forEach(function(button) {
             button.addEventListener('click', function() {
                 const userIdToFollow = this.getAttribute('data-user-id');
                 const currentButton = this;

                 fetch('follow.php', {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                     body: 'user_id=' + encodeURIComponent(userIdToFollow)
                 })
                 .then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         // Update button text based on new follow status
                         currentButton.textContent = data.following ? 'Unfollow' : 'Follow';
                         // Optional: Update follower count on the page without full reload
                         // This would require an element to display the follower count and updating it here
                     } else {
                         alert(data.message || 'Bir hata oluştu.');
                     }
                 })
                 .catch(error => {
                     console.error('Error:', error);
                     alert('Bir hata oluştu.');
                 });
             });
         });

        // --- ALBUM SİLME İŞLEMLERİ ---
        document.querySelectorAll('.delete-album-btn-custom').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const albumIdToDelete = this.getAttribute('data-album-id');
                if (confirm('Are you sure you want to delete this album?')) {
                    fetch('upload.php', { // Assuming upload.php handles album deletion
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=delete_album&album_id=' + encodeURIComponent(albumIdToDelete)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message || 'Album deleted successfully.');
                            // Remove the album card from the DOM
                            const albumCard = button.closest('.col-12.col-md-6.col-lg-4.mb-4');
                            if (albumCard) albumCard.remove();
                        } else {
                            alert(data.message || 'Error deleting album.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the album.');
                    });
                }
            });
        });
    });
    </script>

<?php require_once 'includes/footer.php'; ?>
