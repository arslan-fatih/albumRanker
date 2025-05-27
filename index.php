<?php
/**
 * AlbumRanker - Ana Sayfa
 * 
 * Bu dosya, AlbumRanker uygulamasının ana sayfasını oluşturur:
 * - En son eklenen albümler
 * - Haftanın en çok oylanan albümleri
 * - Az oylanan ama yüksek puanlı albümler
 * 
 * @author AlbumRanker Team
 * @version 1.0
 */

$pageTitle = 'AlbumRanker - Modern Album Reviews';
require_once 'includes/header.php';

/**
 * Albümleri Puanlarıyla Birlikte Getiren Fonksiyon
 * 
 * Optimize edilmiş SQL sorgusu ile albümleri ve puanlarını getirir
 * 
 * @param string $where WHERE koşulu
 * @param string $orderBy Sıralama kriteri
 * @param string $having HAVING koşulu
 * @param int $limit Getirilecek albüm sayısı
 * @return array Albüm listesi
 */
function getAlbumsWithRatings($where = '', $orderBy = 'a.created_at DESC', $having = '', $limit = 6) {
    global $conn;
    $sql = "
        SELECT 
            a.id,
            a.title,
            a.artist,
            a.cover_image,
            a.created_at,
            a.user_id,
            u.username,
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(r.id) as rating_count
        FROM albums a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN ratings r ON a.id = r.album_id
        $where
        GROUP BY a.id, a.title, a.artist, a.cover_image, a.created_at, a.user_id, u.username
        $having
        ORDER BY $orderBy
        LIMIT $limit
    ";
    return $conn->query($sql)->fetchAll();
}

/**
 * Albüm Kartı Görüntüleme Fonksiyonu
 * 
 * Tek bir albüm kartını HTML olarak oluşturur
 * 
 * @param array $album Albüm bilgileri
 */
function displayAlbumCard($album) { ?>
    <div class="col d-flex align-items-stretch">
        <div class="single-album-area w-100">
            <div class="album-thumb">
                <img src="<?php echo h(getAlbumCover($album['cover_image'])); ?>" alt="<?php echo h($album['title']); ?>">
                <div class="album-info">
                    <a href="album-detail.php?id=<?php echo $album['id']; ?>">
                        <h5><?php echo h($album['title']); ?></h5>
                    </a>
                    <p><?php echo h($album['artist']); ?></p>
                    <div class="album-rating">
                        <i class="fa fa-star"></i>
                        <span><?php echo formatRating($album['avg_rating']); ?></span>
                        <small>(<?php echo $album['rating_count']; ?> ratings)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }

/**
 * Albüm Kartları Görüntüleme Fonksiyonu
 * 
 * Birden fazla albüm kartını görüntüler
 * 
 * @param array $albums Albüm listesi
 */
function displayAlbumCards($albums) {
    foreach ($albums as $album) {
        displayAlbumCard($album);
    }
}

/**
 * Bölüm Görüntüleme Fonksiyonu
 * 
 * Başlık ve albüm listesi ile bir bölüm oluşturur
 * 
 * @param string $title Bölüm başlığı
 * @param array $albums Albüm listesi
 * @param string $sectionClass Ek CSS sınıfı
 */
function displaySection($title, $albums, $sectionClass = '') { ?>
    <section class="section-padding-100 <?php echo $sectionClass; ?>">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="section-heading style-2">
                        <h2><?php echo $title; ?></h2>
                    </div>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-1 justify-content-center">
                <?php displayAlbumCards($albums); ?>
            </div>
        </div>
    </section>
<?php }

/**
 * Hero Bölümü Görüntüleme Fonksiyonu
 * 
 * Ana sayfadaki slider bölümünü oluşturur
 * 
 * @param array $albums Slider'da gösterilecek albümler
 */
function displayHeroSection($albums) { ?>
    <section class="hero-area">
        <div class="hero-slides owl-carousel">
            <?php foreach ($albums as $album): ?>
            <div class="single-hero-slide d-flex align-items-center justify-content-center">
                <div class="slide-img bg-img" style="background-image: url(<?php echo h(getAlbumCover($album['cover_image'])); ?>);"></div>
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <div class="hero-slides-content text-center">
                                <h6 data-animation="fadeInUp" data-delay="100ms">Latest album</h6>
                                <h2 data-animation="fadeInUp" data-delay="300ms">
                                    <?php echo h($album['title']); ?> 
                                    <span><?php echo h($album['artist']); ?></span>
                                </h2>
                                <a data-animation="fadeInUp" data-delay="500ms" 
                                   href="album-detail.php?id=<?php echo $album['id']; ?>" 
                                   class="btn oneMusic-btn mt-50">
                                    Discover <i class="fa fa-angle-double-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php }

/**
 * Veri Hazırlama
 * 
 * Sayfada gösterilecek albüm listelerini hazırla
 */

// En son eklenen albümleri getir
$latestAlbums = getAlbumsWithRatings();

// Haftanın en çok oylanan albümlerini getir
$topAlbums = getAlbumsWithRatings(
    "WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    "rating_count DESC, avg_rating DESC",
    "HAVING rating_count > 0"
);

// Az oylanan ama yüksek puanlı albümleri getir
$underratedAlbums = getAlbumsWithRatings(
    "",
    "avg_rating DESC",
    "HAVING rating_count BETWEEN 1 AND 5 AND avg_rating >= 4"
);

/**
 * Sayfa İçeriğini Görüntüle
 * 
 * Hazırlanan verileri kullanarak sayfa bölümlerini oluştur
 */
displayHeroSection($latestAlbums);
displaySection('Uploaded Albums', $latestAlbums, 'latest-albums-area');
displaySection('Most Voted Albums of the Week', $topAlbums, 'top-albums-area');
displaySection('High Rated Albums with Few Votes', $underratedAlbums, 'underrated-albums-area');
?>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>