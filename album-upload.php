<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Albüm Yükle - AlbumRanker</title>
    <link rel="icon" href="img/core-img/favicon.ico">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Select2 CSS -->
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
        .hero-area.bg-img {
            min-height: 340px;
            display: flex;
            align-items: center;
            position: relative;
        }
        @media (max-width: 768px) {
            .hero-area.bg-img {
                min-height: 220px;
            }
        }
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
$pageTitle = 'Albüm Yükle - AlbumRanker';
require_once 'includes/header.php';

// Oturum kontrolü
checkPermission();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyip tekrar deneyin.';
    } else {
        // Rate limiting kontrolü
        if (!checkRateLimit('album_upload', 5, 3600)) { // 1 saatte en fazla 5 albüm
            $error = 'Çok fazla albüm yükleme denemesi. Lütfen bir süre bekleyin.';
        } else {
            // Input temizleme
            $album_title = sanitize($_POST['album_title']);
            $artist = sanitize($_POST['artist']);
            $cover_url = sanitize($_POST['cover_url']);
            $wiki_url = sanitize($_POST['wiki_url']);
            $description = sanitize($_POST['description']);
            $release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : null;
            $rating = !empty($_POST['rating']) ? floatval($_POST['rating']) : null;
            $genres = isset($_POST['genres']) ? array_map('intval', array_filter($_POST['genres'], 'is_numeric')) : [];
            $other_genre = isset($_POST['other_genre']) ? trim($_POST['other_genre']) : '';
            
            // Validasyon
            $errors = [];
            
            if (empty($album_title)) {
                $errors[] = 'Albüm adı gereklidir.';
            }
            
            if (empty($artist)) {
                $errors[] = 'Sanatçı adı gereklidir.';
            }
            
            if (empty($cover_url) || !filter_var($cover_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'Geçerli bir kapak görseli URL\'si giriniz.';
            }
            
            if (!empty($wiki_url) && !filter_var($wiki_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'Geçerli bir Wikipedia albüm linki giriniz.';
            }
            
            if ($rating !== null && ($rating < 1 || $rating > 10)) {
                $errors[] = 'Puan 1-10 arasında olmalıdır.';
            }
            
            if (!empty($release_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $release_date)) {
                $errors[] = 'Geçerli bir çıkış tarihi giriniz (YYYY-AA-GG).';
            }
            
            if (in_array('other', $_POST['genres'] ?? []) && empty($other_genre)) {
                $errors[] = 'Lütfen diğer türü belirtin.';
            }
            
            if (empty($errors)) {
                try {
                    $conn->beginTransaction();
                    
                    // Albümü kaydet
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
                    
                    // Puanı kaydet
                    if ($rating !== null) {
                        $stmt = $conn->prepare("
                            INSERT INTO ratings (user_id, album_id, rating, created_at) 
                            VALUES (?, ?, ?, NOW())
                        ");
                        $stmt->execute([$_SESSION['user_id'], $albumId, $rating]);
                    }
                    
                    // Türleri kaydet
                    if (!empty($genres)) {
                        $stmt = $conn->prepare("
                            INSERT INTO album_genres (album_id, genre_id) 
                            VALUES (?, ?)
                        ");
                        foreach ($genres as $genreId) {
                            $stmt->execute([$albumId, $genreId]);
                        }
                    }
                    
                    // Diğer türü açıklamaya ekle
                    if (in_array('other', $_POST['genres'] ?? []) && !empty($other_genre)) {
                        $stmt = $conn->prepare("UPDATE albums SET description = CONCAT(IFNULL(description, ''), '\n[Other Genre: ', ?, ']') WHERE id = ?");
                        $stmt->execute([$other_genre, $albumId]);
                    }
                    
                    $conn->commit();
                    setFlashMessage('success', 'Albüm başarıyla yüklendi!');
                    header('Location: album-detail.php?id=' . $albumId);
                    exit();
                    
                } catch (Exception $e) {
                    $conn->rollBack();
                    error_log("Albüm yükleme hatası: " . $e->getMessage());
                    $error = 'Albüm yüklenirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
                }
            } else {
                $error = implode('<br>', $errors);
            }
        }
    }
}

// Türleri getir
$stmt = $conn->query("SELECT * FROM genres ORDER BY name");
$genres = $stmt->fetchAll();
?>

<!-- Hero/Banner Area Discover'daki gibi -->
<section class="hero-area bg-img bg-overlay" style="background-image: url('img/bg-img/bg-1.jpg');">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8">
                <div class="text-center p-4 bg-white bg-opacity-75 rounded shadow" style="margin-top: 60px;">
                    <span class="text-muted">Albüm Yükle</span>
                    <h2 class="display-5 fw-bold mb-0">YENİ ALBÜM EKLE</h2>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container album-upload-form-container" style="max-width: 600px;">
    <div class="card shadow p-4 mb-5 bg-white rounded">
        <?php echo showFlashMessage(); ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="album-upload.php" method="post" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="mb-3">
                <label for="album_title" class="form-label">Albüm Adı</label>
                <input type="text" class="form-control" id="album_title" name="album_title" required value="<?php echo isset($_POST['album_title']) ? h($_POST['album_title']) : ''; ?>">
                <div class="invalid-feedback">Albüm adı gereklidir.</div>
            </div>
            
            <div class="mb-3">
                <label for="artist" class="form-label">Sanatçı</label>
                <input type="text" class="form-control" id="artist" name="artist" required value="<?php echo isset($_POST['artist']) ? h($_POST['artist']) : ''; ?>">
                <div class="invalid-feedback">Sanatçı adı gereklidir.</div>
            </div>
            
            <div class="mb-3">
                <label for="cover_url" class="form-label">Kapak Görseli URL'si</label>
                <input type="url" class="form-control" id="cover_url" name="cover_url" required placeholder="https://..." value="<?php echo isset($_POST['cover_url']) ? h($_POST['cover_url']) : ''; ?>">
                <div class="invalid-feedback">Geçerli bir kapak görseli URL'si giriniz.</div>
            </div>
            
            <div class="mb-3">
                <label for="wiki_url" class="form-label">Wikipedia Albüm Linki (isteğe bağlı)</label>
                <input type="url" class="form-control" id="wiki_url" name="wiki_url" placeholder="https://..." value="<?php echo isset($_POST['wiki_url']) ? h($_POST['wiki_url']) : ''; ?>">
                <div class="invalid-feedback">Geçerli bir Wikipedia URL'si giriniz.</div>
            </div>
            
            <div class="mb-3">
                <label for="release_date" class="form-label">Çıkış Tarihi</label>
                <input type="date" class="form-control" id="release_date" name="release_date" value="<?php echo isset($_POST['release_date']) ? h($_POST['release_date']) : ''; ?>">
                <div class="form-text">Opsiyonel. Albümün çıkış tarihini giriniz.</div>
            </div>
            
            <div class="mb-3">
                <label for="genres" class="form-label">Türler</label>
                <select class="form-select select2" id="genres" name="genres[]" multiple onchange="toggleOtherGenre()">
                    <?php foreach ($genres as $genre): ?>
                    <option value="<?php echo $genre['id']; ?>" 
                        <?php echo isset($_POST['genres']) && in_array($genre['id'], $_POST['genres']) ? 'selected' : ''; ?>>
                        <?php echo h($genre['name']); ?>
                    </option>
                    <?php endforeach; ?>
                    <option value="other" <?php echo (isset($_POST['genres']) && in_array('other', $_POST['genres'])) ? 'selected' : ''; ?>>Other (Diğer)</option>
                </select>
                <div class="form-text">Tür arayabilir ve birden fazla seçebilirsiniz. "Other" seçeneği ile yeni tür girebilirsiniz.</div>
                <input type="text" class="form-control mt-2" id="other_genre_input" name="other_genre" placeholder="Diğer türü yazınız..." style="display:<?php echo (isset($_POST['genres']) && in_array('other', $_POST['genres'])) ? 'block' : 'none'; ?>;" value="<?php echo isset($_POST['other_genre']) ? h($_POST['other_genre']) : ''; ?>">
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Açıklama</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php 
                    echo isset($_POST['description']) ? h($_POST['description']) : ''; 
                ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="rating" class="form-label">Puan (1-10)</label>
                <input type="number" class="form-control" id="rating" name="rating" min="1" max="10" step="0.1"
                       value="<?php echo isset($_POST['rating']) ? h($_POST['rating']) : ''; ?>">
                <div class="invalid-feedback">Puan 1-10 arasında olmalıdır.</div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Albümü Yükle</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<!-- Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// Form validasyonu
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
// Other genre input toggle
function toggleOtherGenre() {
    var select = document.getElementById('genres');
    var otherInput = document.getElementById('other_genre_input');
    var selected = Array.from(select.selectedOptions).map(function(opt) { return opt.value; });
    if (selected.includes('other')) {
        otherInput.style.display = 'block';
    } else {
        otherInput.style.display = 'none';
        otherInput.value = '';
    }
}
// Select2 başlat
$(document).ready(function() {
    $('#genres').select2({
        placeholder: 'Tür seçin',
        allowClear: true,
        width: '100%'
    });
});
</script>
</body>
</html> 