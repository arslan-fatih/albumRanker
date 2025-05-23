<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: profile.php');
    exit;
}

$albumId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

// Albüm gerçekten bu kullanıcıya mı ait?
$stmt = $conn->prepare('SELECT * FROM albums WHERE id = ? AND user_id = ?');
$stmt->execute([$albumId, $userId]);
$album = $stmt->fetch();

if (!$album) {
    header('Location: profile.php?error=notfound');
    exit;
}

// Türler
$allGenres = $conn->query('SELECT * FROM genres ORDER BY name ASC')->fetchAll();
$albumGenres = $conn->prepare('SELECT genre_id FROM album_genres WHERE album_id = ?');
$albumGenres->execute([$albumId]);
$selectedGenres = $albumGenres->fetchAll(PDO::FETCH_COLUMN);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $artist = trim($_POST['artist'] ?? '');
    $cover_image = trim($_POST['cover_image'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $genres = $_POST['genres'] ?? [];

    if ($title === '' || $artist === '' || empty($genres)) {
        $error = 'Title, artist and at least one genre are required.';
    } else {
        $stmt = $conn->prepare('UPDATE albums SET title = ?, artist = ?, cover_image = ?, description = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$title, $artist, $cover_image, $description, $albumId, $userId]);
        // Türleri güncelle
        $conn->prepare('DELETE FROM album_genres WHERE album_id = ?')->execute([$albumId]);
        $stmt = $conn->prepare('INSERT INTO album_genres (album_id, genre_id) VALUES (?, ?)');
        foreach ($genres as $gid) {
            $stmt->execute([$albumId, $gid]);
        }
        header('Location: album-detail.php?id=' . $albumId);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Album - AlbumRanker</title>
    <link rel="icon" href="img/core-img/favicon.ico">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #ced4da;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #0d6efd;
            border: none;
            color: #fff;
            padding: 2px 8px;
            margin-top: 4px;
        }
    </style>
</head>
<body>
<!-- Hero/Banner Area -->
<section class="hero-area bg-img bg-overlay" style="background-image: url('https://www.chapmanarchitects.co.uk/wp-content/uploads/2017/08/Abbey_Road_4.jpg'); min-height: 340px; display: flex; align-items: center; position: relative;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8">
                <div class="text-center p-4 bg-white bg-opacity-75 rounded shadow" style="margin-top: 60px;">
                    <span class="text-muted">Edit Album</span>
                    <h2 class="display-5 fw-bold mb-0">EDIT ALBUM</h2>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container album-upload-form-container" style="max-width: 600px;">
    <div class="card shadow p-4 mb-5 bg-white rounded">
        <?php if ($error): ?>
            <div class="alert alert-danger mb-3"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($album['title']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Artist</label>
                <input type="text" name="artist" class="form-control" value="<?php echo htmlspecialchars($album['artist']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Cover Image URL</label>
                <input type="text" name="cover_image" class="form-control" value="<?php echo htmlspecialchars($album['cover_image']); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Genres</label>
                <select name="genres[]" class="form-select select2" id="genres" multiple required onchange="toggleOtherGenre()">
                    <?php foreach ($allGenres as $g): ?>
                        <option value="<?php echo $g['id']; ?>" <?php if (in_array($g['id'], $selectedGenres)) echo 'selected'; ?>><?php echo htmlspecialchars($g['name']); ?></option>
                    <?php endforeach; ?>
                    <option value="other" <?php echo (isset($_POST['genres']) && in_array('other', $_POST['genres'])) ? 'selected' : ''; ?>>Other</option>
                </select>
                <div class="form-text">You can search for genres and select multiple. Use "Other" option to enter a new genre.</div>
                <input type="text" class="form-control mt-2" id="other_genre_input" name="other_genre" placeholder="Enter other genre..." style="display:<?php echo (isset($_POST['genres']) && in_array('other', $_POST['genres'])) ? 'block' : (in_array('other', $selectedGenres) ? 'block' : 'none'); ?>;" value="<?php echo isset($_POST['other_genre']) ? htmlspecialchars($_POST['other_genre']) : ''; ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($album['description']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="album-detail.php?id=<?php echo $albumId; ?>" class="btn btn-secondary ms-2">Cancel</a>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
function toggleOtherGenre() {
    var genres = document.getElementById('genres');
    var otherInput = document.getElementById('other_genre_input');
    var selected = Array.from(genres.selectedOptions).map(function(opt){ return opt.value; });
    if (selected.includes('other')) {
        otherInput.style.display = 'block';
    } else {
        otherInput.style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    $('.select2').select2({
        width: '100%',
        placeholder: 'Select genres',
        allowClear: true
    });
    toggleOtherGenre();
});
</script>
</body>
</html> 