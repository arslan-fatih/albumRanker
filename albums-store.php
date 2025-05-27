<?php 
/**
 * AlbumRanker - Albüm Arama ve Listeleme Sayfası
 * 
 * Bu sayfa kullanıcıların albümleri aramasını ve listelemesini sağlar.
 * Aynı zamanda kullanıcı araması da yapılabilir.
 * 
 * @author AlbumRanker Team
 * @version 1.0
 */

session_start(); 
require_once 'includes/header.php';

// Temel değişkenlerin tanımlanması
$currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; // Giriş yapmış kullanıcının ID'si
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : ''; // Arama sorgusu
$searchType = isset($_GET['type']) ? $_GET['type'] : 'album'; // Arama tipi (albüm veya kullanıcı)

/**
 * Kullanıcı kartını oluşturan yardımcı fonksiyon
 * 
 * @param array $user Kullanıcı bilgileri
 * @param bool $isFollowing Takip durumu
 * @param int|null $currentUserId Giriş yapmış kullanıcının ID'si
 */
function renderUserCard($user, $isFollowing, $currentUserId) {
    ?>
    <div class="col">
        <div class="card h-100 shadow-sm text-center">
            <img src="<?php echo htmlspecialchars($user['profile_pic'] ? 'uploads/profile/' . $user['profile_pic'] : 'img/core-img/default.jpg'); ?>" 
                 class="rounded-circle mx-auto mt-3" alt="Profile" style="width:100px;height:100px;object-fit:cover;">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($user['username']); ?></h5>
                <p class="card-text text-muted"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></p>
                <div class="d-flex justify-content-center gap-2 mt-2">
                    <?php if ($currentUserId): ?>
                        <button class="btn btn-outline-primary btn-sm follow-btn" data-user-id="<?php echo $user['id']; ?>">
                            <?php echo $isFollowing ? 'Unfollow' : 'Follow'; ?>
                        </button>
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

/**
 * Albüm kartını oluşturan yardımcı fonksiyon
 * 
 * @param array $album Albüm bilgileri
 */
function renderAlbumCard($album) {
    ?>
    <div class="col">
        <div class="card h-100 shadow-sm">
            <a href="album-detail.php?id=<?php echo $album['id']; ?>">
                <img src="<?php echo htmlspecialchars($album['cover_image']); ?>" 
                     class="card-img-top" alt="<?php echo htmlspecialchars($album['title']); ?>" 
                     style="height: 300px; object-fit: cover;">
            </a>
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($album['title']); ?></h5>
                <p class="card-text text-muted"><?php echo htmlspecialchars($album['artist_name']); ?></p>
                <div class="d-flex align-items-center mb-2">
                    <div class="album-rating">
                        <i class="fa fa-star"></i>
                        <span><?php echo number_format($album['avg_rating'], 1); ?></span>
                        <small>(<?php echo $album['rating_count']; ?> ratings)</small>
                    </div>
                </div>
                <p class="card-text"><?php echo htmlspecialchars($album['description']); ?></p>
            </div>
        </div>
    </div>
    <?php
}
?>

    <!-- Hero Alanı - Sayfa başlığı ve arka plan görseli -->
    <section class="breadcumb-area bg-img bg-overlay" style="background-image: url('https://images.pexels.com/photos/257904/pexels-photo-257904.jpeg'); background-size: cover; background-position: center; height: 400px;">
        <div class="bradcumbContent">
            <h2>Uploaded Albums</h2>
        </div>
    </section>

    <!-- Arama ve Filtreleme Alanı -->
    <section class="search-filter-area py-4" style="background:rgba(255,255,255,0.95);">
        <div class="container">
            <form id="searchForm" method="get" class="row g-2 align-items-center justify-content-center">
                <div class="col-12 col-md-6 col-lg-5">
                    <input type="text" class="form-control" name="q" placeholder="Search for album or user..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select class="form-select" name="type">
                        <option value="album" <?php echo $searchType === 'album' ? 'selected' : ''; ?>>Album</option>
                        <option value="user" <?php echo $searchType === 'user' ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search me-1"></i> Search</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Ana İçerik Alanı -->
    <main class="container my-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Uploaded Albums</h1>
            </div>
        </div>
        <?php if ($searchQuery !== ''): ?>
            <!-- Arama Sonuçları Başlığı -->
            <div class="row">
                <div class="col-12 mb-3">
                    <h5>Search Results: <span class="text-primary"><?php echo htmlspecialchars($searchQuery); ?></span> (<?php echo $searchType === 'user' ? 'User' : 'Album'; ?>)</h5>
                </div>
            </div>
            <!-- Arama Sonuçları Listesi -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="searchResults">
                <?php
                require_once 'config.php';
                try {
                    if ($searchType === 'user') {
                        // Kullanıcı arama sorgusu
                        $userQuery = "SELECT id, username, profile_pic, bio FROM users WHERE username LIKE ?";
                        $userParams = ['%' . $searchQuery . '%'];
                        
                        // Mevcut kullanıcıyı sonuçlardan hariç tut
                        if ($currentUserId) {
                            $userQuery .= " AND id != ?";
                            $userParams[] = $currentUserId;
                        }
                        
                        $userQuery .= " LIMIT 24";
                        $stmt = $conn->prepare($userQuery);
                        $stmt->execute($userParams);
                        $users = $stmt->fetchAll();

                        if ($users) {
                            foreach ($users as $user) {
                                // Kullanıcının takip durumunu kontrol et
                                $isFollowing = false;
                                if ($currentUserId) {
                                    $stmtFollow = $conn->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND following_id = ?");
                                    $stmtFollow->execute([$currentUserId, $user['id']]);
                                    $isFollowing = $stmtFollow->fetch() ? true : false;
                                }
                                renderUserCard($user, $isFollowing, $currentUserId);
                            }
                        } else {
                            echo '<div class="col-12 text-center"><p>User not found.</p></div>';
                        }
                    } else {
                        // Albüm arama sorgusu
                        $albumQuery = "SELECT a.*, u.username as artist_name,
                                     AVG(r.rating) as avg_rating,
                                     COUNT(r.id) as rating_count
                              FROM albums a 
                              INNER JOIN users u ON a.user_id = u.id 
                              LEFT JOIN ratings r ON a.id = r.album_id
                              WHERE a.title LIKE ? OR a.artist LIKE ? 
                              GROUP BY a.id
                              ORDER BY a.created_at DESC 
                              LIMIT 24";
                        
                        $stmt = $conn->prepare($albumQuery);
                        $stmt->execute(['%' . $searchQuery . '%', '%' . $searchQuery . '%']);
                        $albums = $stmt->fetchAll();

                        if ($albums) {
                            foreach ($albums as $album) {
                                renderAlbumCard($album);
                            }
                        } else {
                            echo '<div class="col-12 text-center"><p>Album not found.</p></div>';
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Database error: " . $e->getMessage());
                    echo '<div class="col-12 text-center"><p>An error occurred while searching. Please try again later.</p></div>';
                }
                ?>
            </div>
        <?php else: ?>
            <!-- Arama yapılmadığında gösterilecek mesaj -->
            <div class="row">
                <div class="col-12 text-center py-5">
                    <p class="text-muted" style="font-size:1.2rem;">No results yet.</p>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php require_once 'includes/footer.php'; ?>
    
    <!-- Takip/Takibi Bırak işlevselliği için JavaScript kodu -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchResults = document.getElementById('searchResults');
        if (searchResults) {
            searchResults.addEventListener('click', function(e) {
                if (e.target.classList.contains('follow-btn')) {
                    const userId = e.target.getAttribute('data-user-id');
                    const button = e.target;
                    
                    // Takip/Takibi bırak isteği gönder
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
                }
            });
        }
    });
    </script>
</body>

</html>