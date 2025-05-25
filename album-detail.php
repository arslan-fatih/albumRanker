<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Album Detail - AlbumRanker</title>
    <link rel="icon" href="img/core-img/favicon.ico">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php
require_once 'includes/header.php';

$album_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$album_id) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Album not found.</div></div>';
    require_once 'includes/footer.php';
    exit;
}

// Get album and uploader information
$stmt = $conn->prepare("
    SELECT a.*, u.username, u.id as user_id
    FROM albums a
    JOIN users u ON a.user_id = u.id
    WHERE a.id = ?
");
$stmt->execute([$album_id]);
$album = $stmt->fetch();
if (!$album) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Album not found.</div></div>';
    require_once 'includes/footer.php';
    exit;
}

// Get uploader's first review and rating (stored in ratings table)
$stmt = $conn->prepare("
    SELECT r.rating, r.created_at, u.username, u.id as user_id, r.id as rating_id
    FROM ratings r
    JOIN users u ON r.user_id = u.id
    WHERE r.album_id = ? AND r.user_id = ?
    LIMIT 1
");
$stmt->execute([$album_id, $album['user_id']]);
$first_review = $stmt->fetch();

// Get other user reviews (from reviews table)
$stmt = $conn->prepare("
    SELECT r.*, u.username, u.id as user_id
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.album_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$album_id]);
$other_reviews = $stmt->fetchAll();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    // Check how many reviews the user has made for this album
    $stmt = $conn->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND album_id = ?");
    $stmt->execute([$_SESSION['user_id'], $album_id]);
    $user_review_count = $stmt->fetchColumn();
    if ($user_review_count >= 5) {
        $error = "You can add a maximum of 5 reviews per album.";
    } else {
        $content = trim($_POST['content'] ?? '');
        $rating = isset($_POST['rating']) ? (float)$_POST['rating'] : null;
        if ($content && $rating >= 1 && $rating <= 10) {
            try {
                $conn->beginTransaction();
                // Add the review
                $stmt = $conn->prepare("INSERT INTO reviews (user_id, album_id, content, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute([$_SESSION['user_id'], $album_id, $content]);
                // Add rating if user hasn't rated before
                $stmt = $conn->prepare("SELECT id FROM ratings WHERE user_id = ? AND album_id = ?");
                $stmt->execute([$_SESSION['user_id'], $album_id]);
                if (!$stmt->fetch()) {
                    $stmt = $conn->prepare("INSERT INTO ratings (user_id, album_id, rating, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$_SESSION['user_id'], $album_id, $rating]);
                }
                // Update album's average rating
                updateAlbumAverageRating($album_id);
                $conn->commit();
                header("Location: album-detail.php?id=$album_id");
                exit;
            } catch (PDOException $e) {
                $conn->rollBack();
                error_log("Error while adding review: " . $e->getMessage());
                $error = "An error occurred while adding the review.";
            }
        }
    }
}

// Handle favorite add/remove action
if (isLoggedIn() && isset($_POST['favorite_action'])) {
    $user_id = getCurrentUserId();
    if ($_POST['favorite_action'] === 'add') {
        $stmt = $conn->prepare("INSERT IGNORE INTO favorites (user_id, album_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $album_id]);
    } elseif ($_POST['favorite_action'] === 'remove') {
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND album_id = ?");
        $stmt->execute([$user_id, $album_id]);
    }
    header("Location: album-detail.php?id=$album_id");
    exit;
}

// Check if user has favorited this album
$is_favorited = false;
if (isLoggedIn()) {
    $stmt = $conn->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND album_id = ?");
    $stmt->execute([getCurrentUserId(), $album_id]);
    $is_favorited = $stmt->fetchColumn() ? true : false;
}

// Get current average rating and vote count from ratings table
$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count FROM ratings WHERE album_id = ?");
$stmt->execute([$album_id]);
$rating_stats = $stmt->fetch();
$avg_rating = $rating_stats && $rating_stats['rating_count'] > 0 ? round($rating_stats['avg_rating'], 1) : null;
$rating_count = $rating_stats ? $rating_stats['rating_count'] : 0;
?>

<!-- Hero/Banner Area -->
<section class="hero-area bg-img bg-overlay" style="background-image: url('https://images.pexels.com/photos/257904/pexels-photo-257904.jpeg'); min-height: 340px; display: flex; align-items: center; position: relative;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8">
                <div class="text-center p-4 bg-white bg-opacity-75 rounded shadow" style="margin-top: 60px;">
                    <span class="text-muted">Album Detail</span>
                    <h2 class="display-5 fw-bold mb-0"><?php echo h($album['title']); ?> <small class="text-muted" style="font-size:0.6em;">- <?php echo h($album['artist']); ?></small></h2>
                    <?php if (isLoggedIn()): ?>
                        <form method="post" class="d-inline">
                            <?php if ($is_favorited): ?>
                                <button type="submit" name="favorite_action" value="remove" class="btn btn-danger btn-sm ms-2"><i class="fas fa-heart-broken"></i> Remove from Favorites</button>
                            <?php else: ?>
                                <button type="submit" name="favorite_action" value="add" class="btn btn-outline-danger btn-sm ms-2"><i class="fas fa-heart"></i> Add to Favorites</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container mt-5 mb-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card mb-4 shadow">
                <div class="row g-0">
                    <div class="col-md-4 position-relative">
                        <img src="<?php echo h(getAlbumCover($album['cover_image'])); ?>" class="img-fluid rounded-start" alt="<?php echo h($album['title']); ?>">
                        <?php if ($album['rating']): ?>
                        <span class="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-primary shadow" style="font-size:1.1em; z-index:2; margin-top:10px; margin-right:10px;">
                            <i class="fas fa-star"></i> <?php echo number_format($album['rating'], 1); ?>/10
                            <small>(<?php echo $album['rating_count']; ?>)</small>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <div class="card-body position-relative">
                            <?php if (isLoggedIn() && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $album['user_id']): ?>
                                <button class="delete-album-btn-custom" id="deleteAlbumBtn" title="Delete Album" style="position:absolute;top:10px;right:10px;z-index:2;">
                                    <i class="icon-trash"></i>
                                </button>
                                <a href="edit-album.php?id=<?php echo $album_id; ?>" class="edit-album-btn-custom" title="Edit Album" style="position:absolute;top:10px;right:56px;z-index:2;">
                                    <i class="fas fa-edit"></i>
                                </a>
                            <?php endif; ?>
                            <h3 class="card-title mb-1"><?php echo h($album['title']); ?></h3>
                            <h5 class="card-subtitle mb-2 text-muted"><?php echo h($album['artist']); ?></h5>
                            <?php if ($album['rating']): ?>
                            <div class="mb-2">
                                <span class="badge bg-primary">
                                    <i class="fas fa-star"></i> <?php echo number_format($album['rating'], 1); ?>/10
                                    <small class="ms-1">(<?php echo $album['rating_count']; ?> oy)</small>
                                </span>
                            </div>
                            <?php endif; ?>
                            <p class="mb-1"><strong>Uploaded by:</strong> <a href="profile.php?user=<?php echo $album['user_id']; ?>"><?php echo h($album['username']); ?></a></p>
                            <p class="mb-1"><strong>Release Date:</strong> <?php echo h($album['release_date']); ?></p>
                            <p class="mb-1"><strong>Genres:</strong> <?php
                                $stmt = $conn->prepare("SELECT g.name FROM album_genres ag JOIN genres g ON ag.genre_id = g.id WHERE ag.album_id = ?");
                                $stmt->execute([$album_id]);
                                $genres = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                echo h(implode(', ', $genres));
                            ?></p>
                            <p class="mt-2"><?php echo nl2br(h($album['description'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yükleyenin ilk yorumu ve puanı -->
            <div class="card mb-4 border-primary shadow position-relative">
                <span class="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-primary shadow" style="font-size:1.1em; z-index:2; margin-top:10px; margin-right:10px;">
                    <i class="fas fa-star"></i> <?php echo $avg_rating !== null ? number_format($avg_rating, 1) : 'N/A'; ?>/10
                    <small>(<?php echo $rating_count; ?>)</small>
                </span>
                <div class="card-header bg-primary text-white">
                    <a href="profile.php?user=<?php echo $album['user_id']; ?>" class="text-white fw-bold"><?php echo h($album['username']); ?></a> (Album uploader)
                </div>
                <div class="card-body">
                    <?php if ($first_review): ?>
                        <p class="mb-1"><strong>Rating:</strong> <?php echo h($first_review['rating']); ?>/10</p>
                    <?php endif; ?>
                    <p class="mb-0"><?php echo nl2br(h($album['description'])); ?></p>
                </div>
            </div>

            <!-- Diğer Yorumlar -->
            <h4 class="mb-3">Reviews</h4>

            <!-- Telif Hakkı Uyarısı -->
            <div class="card mb-4 border-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning"><i class="fas fa-copyright"></i> Copyright Notice</h5>
                    <p class="card-text">
                        This album cover and related content are the property of their respective owners. 
                        AlbumRanker uses this content under fair use principles for informational and review purposes only.
                        No commercial use is intended or implied. All rights belong to their original owners.
                    </p>
                    <p class="card-text mb-0">
                        <small class="text-muted">
                            For more information about our copyright policy, please visit our 
                            <a href="copyright.php">Copyright & Usage Rights</a> page.
                        </small>
                    </p>
                </div>
            </div>

            <?php if ($other_reviews): ?>
                <?php foreach ($other_reviews as $review): ?>
                    <div class="card mb-2 shadow-sm">
                        <div class="card-header">
                            <a href="profile.php?id=<?php echo $review['user_id']; ?>"><?php echo h($review['username']); ?></a>
                            <span class="float-end text-muted" style="font-size:0.9em;"><?php echo formatDate($review['created_at']); ?></span>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(h($review['content'])); ?></p>
                            <?php
                            // Kullanıcının puanı (ratings tablosundan)
                            $stmt = $conn->prepare("SELECT rating FROM ratings WHERE user_id = ? AND album_id = ?");
                            $stmt->execute([$review['user_id'], $album_id]);
                            $user_rating = $stmt->fetchColumn();
                            if ($user_rating): ?>
                                <p class="mb-0"><strong>Rating:</strong> <?php echo h($user_rating); ?>/10</p>
                            <?php endif; ?>
                            <!-- Beğeni butonu (dummy) -->
                            <button class="btn btn-outline-success btn-sm mt-2" disabled>Like (<span>0</span>)</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-secondary">No reviews yet. Be the first to review!</div>
            <?php endif; ?>

            <!-- Yorum Ekleme Formu -->
            <?php if (isLoggedIn()): ?>
                <div class="card mt-4 shadow-sm">
                    <div class="card-header">Add Review</div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger mb-3"><?php echo h($error); ?></div>
                        <?php endif; ?>
                        <form action="album-detail.php?id=<?php echo $album_id; ?>" method="post">
                            <div class="mb-2">
                                <textarea name="content" class="form-control" placeholder="Write your review..." required></textarea>
                            </div>
                            <div class="mb-2">
                                <input type="number" name="rating" min="1" max="10" step="0.1" class="form-control" placeholder="Rating (1-10, e.g: 8.5)" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Review</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-4">Please <a href="login.php">login</a> to add a review!</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAlbumModal" tabindex="-1" aria-labelledby="deleteAlbumModalLabel" aria-hidden="true">
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    var deleteBtn = document.getElementById('deleteAlbumBtn');
    if(deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            var modal = new bootstrap.Modal(document.getElementById('deleteAlbumModal'));
            modal.show();
        });
    }
    var confirmBtn = document.getElementById('confirmDeleteAlbumBtn');
    if(confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            window.location.href = 'delete-album.php?id=<?php echo $album_id; ?>';
        });
    }
});
</script>

<style>
.delete-album-btn-custom {
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
.edit-album-btn-custom {
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
    padding: 0;
    text-decoration: none;
}
.edit-album-btn-custom i {
    color: #888;
    font-size: 18px;
    transition: color 0.2s;
}
.edit-album-btn-custom:hover {
    box-shadow: 0 4px 16px rgba(52,152,219,0.10);
}
.edit-album-btn-custom:hover i {
    color: #3498db;
}
</style>

<?php require_once 'includes/footer.php'; ?>
</body>
</html> 