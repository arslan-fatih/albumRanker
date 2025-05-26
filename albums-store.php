<?php session_start(); ?>
<?php 
$pageTitle = 'AlbumRanker - Discover Albums';
require_once 'includes/header.php';
?>

    <!-- ##### Breadcumb Area Start ##### -->
    <section class="breadcumb-area bg-img bg-overlay" style="background-image: url('https://images.pexels.com/photos/257904/pexels-photo-257904.jpeg');">
        <div class="bradcumbContent">
            <p>Discover</p>
            <h2>Uploaded Albums</h2>
        </div>
    </section>
    <!-- ##### Breadcumb Area End ##### -->
    <!-- Search & Filter Bar Start -->
    <section class="search-filter-area py-4" style="background:rgba(255,255,255,0.95);">
        <div class="container">
            <form id="searchForm" method="get" class="row g-2 align-items-center justify-content-center">
                <div class="col-12 col-md-6 col-lg-5">
                    <input type="text" class="form-control" name="q" placeholder="Search for album or user...">
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select class="form-select" name="type">
                        <option value="album">Album</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search me-1"></i> Search</button>
                </div>
            </form>
        </div>
    </section>
    <!-- Search & Filter Bar End -->
    <!-- Main Content -->
    <main class="container my-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Uploaded Albums</h1>
            </div>
        </div>
        <?php
        // Arama parametrelerini al
        $searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
        $searchType = isset($_GET['type']) ? $_GET['type'] : 'album';
        $currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        ?>
        <?php if ($searchQuery !== ''): ?>
            <div class="row">
                <div class="col-12 mb-3">
                    <h5>Search Results: <span class="text-primary"><?php echo htmlspecialchars($searchQuery); ?></span> (<?php echo $searchType === 'user' ? 'User' : 'Album'; ?>)</h5>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php
                require_once 'config.php';
                if ($searchType === 'user') {
                    // Kullanıcı araması, kendini hariç tut
                    if ($currentUserId) {
                        $stmt = $conn->prepare("SELECT id, username, profile_pic, bio FROM users WHERE username LIKE ? AND id != ? LIMIT 24");
                        $stmt->execute(['%' . $searchQuery . '%', $currentUserId]);
                    } else {
                        $stmt = $conn->prepare("SELECT id, username, profile_pic, bio FROM users WHERE username LIKE ? LIMIT 24");
                        $stmt->execute(['%' . $searchQuery . '%']);
                    }
                    $users = $stmt->fetchAll();
                    if ($users) {
                        foreach ($users as $user) {
                            // Takip durumu kontrolü
                            $isFollowing = false;
                            if ($currentUserId) {
                                $stmtFollow = $conn->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND following_id = ?");
                                $stmtFollow->execute([$currentUserId, $user['id']]);
                                $isFollowing = $stmtFollow->fetch() ? true : false;
                            }
                            ?>
                            <div class="col">
                                <div class="card h-100 shadow-sm text-center">
                                    <img src="<?php echo htmlspecialchars($user['profile_pic'] ? 'uploads/profile/' . $user['profile_pic'] : 'img/core-img/default.jpg'); ?>" class="rounded-circle mx-auto mt-3" alt="Profile" style="width:100px;height:100px;object-fit:cover;">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($user['username']); ?></h5>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></p>
                                        <div class="d-flex justify-content-center gap-2 mt-2">
                                            <?php if ($currentUserId): ?>
                                                <button class="btn btn-outline-primary btn-sm follow-btn" data-user-id="<?php echo $user['id']; ?>"><?php echo $isFollowing ? 'Unfollow' : 'Follow'; ?></button>
                                            <?php endif; ?>
                                            <?php if (!isset($currentUserId) || $user['id'] != $currentUserId): ?>
                                                <a href="profile.php?user=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">View Profile</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="col-12 text-center"><p>User not found.</p></div>';
                    }
                } else {
                    // Albüm araması
                    $stmt = $conn->prepare("
                        SELECT a.*, u.username as artist_name,
                               (SELECT AVG(rating) FROM ratings WHERE album_id = a.id) as avg_rating,
                               (SELECT COUNT(*) FROM ratings WHERE album_id = a.id) as rating_count
                        FROM albums a 
                        LEFT JOIN users u ON a.user_id = u.id 
                        WHERE a.title LIKE ? OR a.artist LIKE ? 
                        ORDER BY a.created_at DESC 
                        LIMIT 24
                    ");
                    $stmt->execute(['%' . $searchQuery . '%', '%' . $searchQuery . '%']);
                    $albums = $stmt->fetchAll();
                    if ($albums) {
                        foreach ($albums as $row) {
                            ?>
                            <div class="col">
                                <div class="card h-100 shadow-sm">
                                    <a href="album-detail.php?id=<?php echo $row['id']; ?>">
                                        <img src="<?php echo htmlspecialchars($row['cover_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['title']); ?>" style="height: 300px; object-fit: cover;">
                                    </a>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($row['artist_name']); ?></p>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="album-rating">
                                                <i class="fa fa-star"></i>
                                                <span><?php echo formatRating($row['avg_rating'] ?? 0); ?></span>
                                                <small>(<?php echo $row['rating_count']; ?> ratings)</small>
                                            </div>
                                        </div>
                                        <p class="card-text"><?php echo htmlspecialchars($row['description']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="col-12 text-center"><p>Album not found.</p></div>';
                    }
                }
                ?>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12 text-center py-5">
                    <p class="text-muted" style="font-size:1.2rem;">No results yet.</p>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <!-- Buraya dinamik albüm kartları gelecek (varsayılan liste) -->
            </div>
        <?php endif; ?>
    </main>
    <!-- Footer -->
    <?php require_once 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.follow-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
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
                        alert(data.message || 'An error occurred.');
                    }
                })
                .catch(() => alert('An error occurred.'));
            });
        });
    });
    </script>
</body>

</html>