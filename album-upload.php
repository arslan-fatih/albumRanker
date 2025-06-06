<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Upload Album - AlbumRanker</title>
    <link rel="icon" href="img/core-img/favicon.ico">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Select2 CSS - Çoklu seçim için kullanılan kütüphane -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Select2 bileşeninin özelleştirilmiş stilleri */
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
        /* Hero alanı için stil tanımlamaları */
        .hero-area.bg-img {
            min-height: 340px;
            display: flex;
            align-items: center;
            position: relative;
        }
        /* Mobil cihazlar için responsive tasarım */
        @media (max-width: 768px) {
            .hero-area.bg-img {
                min-height: 220px;
            }
        }
        /* Form container'ı için stil tanımlamaları */
        .album-upload-form-container {
            margin-top: -100px;
            position: relative;
            z-index: 2;
        }
        @media (max-width: 768px) {
            .album-upload-form-container {
                margin-top: -60px;
            }
        }
    </style>
</head>
<body>
<?php
$pageTitle = 'Upload Album - AlbumRanker';
require_once 'includes/header.php';

// Kullanıcının giriş yapmış olup olmadığını kontrol et
checkPermission();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token kontrolü - Güvenlik için
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Security verification failed. Please refresh the page and try again.';
    } else {
        // Rate limiting kontrolü - Spam önleme
        if (!checkRateLimit('album_upload', 5, 3600)) { // 1 saatte en fazla 5 albüm
            $error = 'Too many album upload attempts. Please wait a while.';
        } else {
            // Form verilerini temizle ve güvenli hale getir
            $album_title = sanitize($_POST['album_title']);
            $artist = sanitize($_POST['artist']);
            $cover_url = sanitize($_POST['cover_url']);
            $wiki_url = sanitize($_POST['wiki_url']);
            $description = sanitize($_POST['description']);
            $release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : null;
            $rating = !empty($_POST['rating']) ? floatval($_POST['rating']) : null;
            $genres = isset($_POST['genres']) ? array_map('intval', array_filter($_POST['genres'], 'is_numeric')) : [];
            $other_genre = isset($_POST['other_genre']) ? trim($_POST['other_genre']) : '';
            
            // Form verilerinin doğruluğunu kontrol et
            $errors = [];
            
            if (empty($album_title)) {
                $errors[] = 'Album title is required.';
            }
            
            if (empty($artist)) {
                $errors[] = 'Artist name is required.';
            }
            
            if (empty($cover_url) || !filter_var($cover_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'Please enter a valid cover image URL.';
            }
            
            if (!empty($wiki_url) && !filter_var($wiki_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'Please enter a valid Wikipedia album link.';
            }
            
            if ($rating !== null && ($rating < 1 || $rating > 10)) {
                $errors[] = 'Rating must be between 1-10.';
            }
            
            if (!empty($release_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $release_date)) {
                $errors[] = 'Please enter a valid release date (YYYY-MM-DD).';
            }
            
            if (in_array('other', $_POST['genres'] ?? []) && empty($other_genre)) {
                $errors[] = 'Please specify the other genre.';
            }
            
            if (empty($errors)) {
                try {
                    $conn->beginTransaction();
                    
                    // Albüm bilgilerini veritabanına kaydet
                    $stmt = $conn->prepare("
                        INSERT INTO albums (title, artist, cover_image, wiki_url, description, release_date, user_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $stmt->execute([
                        $album_title,
                        $artist,
                        $cover_url,
                        $wiki_url,
                        $description,
                        $release_date,
                        $_SESSION['user_id']
                    ]);
                    
                    $albumId = $conn->lastInsertId();
                    
                    // Kullanıcının verdiği puanı kaydet
                    if ($rating !== null) {
                        $stmt = $conn->prepare("
                            INSERT INTO ratings (user_id, album_id, rating, created_at) 
                            VALUES (?, ?, ?, NOW())
                        ");
                        $stmt->execute([$_SESSION['user_id'], $albumId, $rating]);
                    }
                    
                    // Seçilen müzik türlerini kaydet
                    if (!empty($genres)) {
                        $stmt = $conn->prepare("
                            INSERT INTO album_genres (album_id, genre_id) 
                            VALUES (?, ?)
                        ");
                        foreach ($genres as $genreId) {
                            $stmt->execute([$albumId, $genreId]);
                        }
                    }
                    
                    // "Diğer" türü seçildiyse, açıklamaya ekle
                    if (in_array('other', $_POST['genres'] ?? []) && !empty($other_genre)) {
                        $stmt = $conn->prepare("UPDATE albums SET description = CONCAT(IFNULL(description, ''), '\n[Other Genre: ', ?, ']') WHERE id = ?");
                        $stmt->execute([$other_genre, $albumId]);
                    }
                    
                    $conn->commit();
                    setFlashMessage('success', 'Album uploaded successfully!');
                    header('Location: album-detail.php?id=' . $albumId);
                    exit();
                    
                } catch (Exception $e) {
                    $conn->rollBack();
                    error_log("Album upload error: " . $e->getMessage());
                    $error = 'An error occurred while uploading the album. Please try again later.';
                }
            } else {
                $error = implode('<br>', $errors);
            }
        }
    }
}

// Veritabanından tüm müzik türlerini getir
$stmt = $conn->query("SELECT * FROM genres ORDER BY name");
$genres = $stmt->fetchAll();
?>

<!-- Hero/Banner Alanı -->
<section class="hero-area bg-img bg-overlay" style="background-image: url('https://images.pexels.com/photos/257904/pexels-photo-257904.jpeg'); min-height: 340px; display: flex; align-items: center; position: relative;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8">
                <div class="text-center p-4 bg-white bg-opacity-75 rounded shadow" style="margin-top: 60px;">
                    <span class="text-muted">Upload Album</span>
                    <h2 class="display-5 fw-bold mb-0">ADD NEW ALBUM</h2>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Albüm Yükleme Formu -->
<div class="container album-upload-form-container" style="max-width: 600px;">
    <div class="card shadow p-4 mb-5 bg-white rounded">
        <?php echo showFlashMessage(); ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="album-upload.php" method="post" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <!-- Albüm Başlığı -->
            <div class="mb-3">
                <label for="album_title" class="form-label">Album Title</label>
                <input type="text" class="form-control" id="album_title" name="album_title" required value="<?php echo isset($_POST['album_title']) ? h($_POST['album_title']) : ''; ?>">
                <div class="invalid-feedback">Album title is required.</div>
            </div>
            
            <!-- Sanatçı Adı -->
            <div class="mb-3">
                <label for="artist" class="form-label">Artist</label>
                <input type="text" class="form-control" id="artist" name="artist" required value="<?php echo isset($_POST['artist']) ? h($_POST['artist']) : ''; ?>">
                <div class="invalid-feedback">Artist name is required.</div>
            </div>
            
            <!-- Kapak Resmi URL -->
            <div class="mb-3">
                <label for="cover_url" class="form-label">Cover Image URL</label>
                <input type="url" class="form-control" id="cover_url" name="cover_url" required placeholder="https://..." value="<?php echo isset($_POST['cover_url']) ? h($_POST['cover_url']) : ''; ?>">
                <div class="invalid-feedback">Please enter a valid cover image URL.</div>
                <div class="form-text text-warning mt-2">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Copyright Notice:</strong> By uploading an album cover, you confirm that:
                    <ul class="mb-0 mt-2">
                        <li>You have the right to use this image</li>
                        <li>The image will be used for informational and review purposes only</li>
                        <li>No commercial use is intended</li>
                        <li>You understand and agree to our <a href="copyright.php">Copyright & Usage Rights</a> policy</li>
                    </ul>
                </div>
            </div>
            
            <!-- Wikipedia Bağlantısı -->
            <div class="mb-3">
                <label for="wiki_url" class="form-label">Wikipedia Album Link (optional)</label>
                <input type="url" class="form-control" id="wiki_url" name="wiki_url" placeholder="https://..." value="<?php echo isset($_POST['wiki_url']) ? h($_POST['wiki_url']) : ''; ?>">
                <div class="invalid-feedback">Please enter a valid Wikipedia URL.</div>
            </div>
            
            <!-- Yayın Tarihi -->
            <div class="mb-3">
                <label for="release_date" class="form-label">Release Date</label>
                <input type="date" class="form-control" id="release_date" name="release_date" value="<?php echo isset($_POST['release_date']) ? h($_POST['release_date']) : ''; ?>">
                <div class="form-text">Optional. Enter the album's release date.</div>
            </div>
            
            <!-- Müzik Türleri -->
            <div class="mb-3">
                <label for="genres" class="form-label">Genres</label>
                <select class="form-select select2" id="genres" name="genres[]" multiple onchange="toggleOtherGenre()">
                    <?php foreach ($genres as $genre): ?>
                    <option value="<?php echo $genre['id']; ?>" 
                        <?php echo isset($_POST['genres']) && in_array($genre['id'], $_POST['genres']) ? 'selected' : ''; ?>>
                        <?php echo h($genre['name']); ?>
                    </option>
                    <?php endforeach; ?>
                    <option value="other" <?php echo isset($_POST['genres']) && in_array('other', $_POST['genres']) ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <!-- Diğer Tür Alanı -->
            <div class="mb-3" id="otherGenreContainer" style="display: none;">
                <label for="other_genre" class="form-label">Other Genre</label>
                <input type="text" class="form-control" id="other_genre" name="other_genre" value="<?php echo isset($_POST['other_genre']) ? h($_POST['other_genre']) : ''; ?>">
            </div>
            
            <!-- Albüm Açıklaması -->
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? h($_POST['description']) : ''; ?></textarea>
            </div>
            
            <!-- Puan -->
            <div class="mb-3">
                <label for="rating" class="form-label">Your Rating (1-10)</label>
                <input type="number" class="form-control" id="rating" name="rating" min="1" max="10" step="0.1" value="<?php echo isset($_POST['rating']) ? h($_POST['rating']) : ''; ?>">
                <div class="form-text">Optional. Enter your rating for this album.</div>
            </div>
            
            <!-- Gönder Butonu -->
            <button type="submit" class="btn btn-primary">Upload Album</button>
        </form>
    </div>
</div>

<!-- Select2 ve Form Doğrulama JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// Select2'yi başlat
$(document).ready(function() {
    $('.select2').select2({
        placeholder: "Select genres...",
        allowClear: true
    });
});

// Diğer tür alanını göster/gizle
function toggleOtherGenre() {
    var genres = document.getElementById('genres');
    var otherGenreContainer = document.getElementById('otherGenreContainer');
    var selectedOptions = Array.from(genres.selectedOptions).map(option => option.value);
    
    if (selectedOptions.includes('other')) {
        otherGenreContainer.style.display = 'block';
    } else {
        otherGenreContainer.style.display = 'none';
    }
}

// Form doğrulama
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php require_once 'includes/footer.php'; ?>
</body>
</html> 