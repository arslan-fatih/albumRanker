<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user ID from URL parameter
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];
$type = isset($_GET['type']) ? $_GET['type'] : 'followers'; // 'followers' or 'following'

// Get user info
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php');
    exit;
}

// Get followers/following list
if ($type === 'followers') {
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.profile_pic, u.bio,
        EXISTS(SELECT 1 FROM followers WHERE follower_id = ? AND following_id = u.id) as is_following
        FROM followers f
        JOIN users u ON f.follower_id = u.id
        WHERE f.following_id = ?
        ORDER BY f.created_at DESC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.profile_pic, u.bio,
        EXISTS(SELECT 1 FROM followers WHERE follower_id = ? AND following_id = u.id) as is_following
        FROM followers f
        JOIN users u ON f.following_id = u.id
        WHERE f.follower_id = ?
        ORDER BY f.created_at DESC
    ");
}
$stmt->execute([$_SESSION['user_id'], $userId]);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> - <?php echo $type === 'followers' ? 'Takipçiler' : 'Takip Edilenler'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <?php echo htmlspecialchars($user['username']); ?> - 
                    <?php echo $type === 'followers' ? 'Takipçiler' : 'Takip Edilenler'; ?>
                </h2>
                
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($users as $user): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <img src="<?php echo htmlspecialchars($user['profile_pic'] ?? 'img/core-img/default.jpg'); ?>" 
                                     class="rounded-circle mb-3" 
                                     alt="<?php echo htmlspecialchars($user['username']); ?>"
                                     style="width: 100px; height: 100px; object-fit: cover;">
                                <h5 class="card-title"><?php echo htmlspecialchars($user['username']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></p>
                                <div class="d-flex justify-content-center gap-2">
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-outline-primary btn-sm follow-btn" 
                                                data-user-id="<?php echo $user['id']; ?>">
                                            <?php echo $user['is_following'] ? 'Takipten Çık' : 'Takip Et'; ?>
                                        </button>
                                        <a href="profile.php?user=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">Profili Gör</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

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
                        button.textContent = data.following ? 'Takipten Çık' : 'Takip Et';
                    } else {
                        alert(data.message || 'Bir hata oluştu.');
                    }
                })
                .catch(() => alert('Bir hata oluştu.'));
            });
        });
    });
    </script>
</body>
</html> 