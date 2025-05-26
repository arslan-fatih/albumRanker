<?php
    $pageTitle = 'AlbumRanker - Modern Album Reviews';
    require_once 'includes/header.php';

    // Son eklenen albümleri getir
    $stmt = $conn->query("
        SELECT a.*, u.username, 
               (SELECT AVG(rating) FROM ratings WHERE album_id = a.id) as avg_rating,
               (SELECT COUNT(*) FROM ratings WHERE album_id = a.id) as rating_count
        FROM albums a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT 6
    ");
    $latestAlbums = $stmt->fetchAll();

    // Haftanın en çok oy alan albümleri
    $stmt = $conn->query("
        SELECT a.*, u.username,
               (SELECT AVG(rating) FROM ratings WHERE album_id = a.id) as avg_rating,
               (SELECT COUNT(*) FROM ratings WHERE album_id = a.id) as rating_count
        FROM albums a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        HAVING rating_count > 0
        ORDER BY rating_count DESC, avg_rating DESC
        LIMIT 6
    ");
    $topAlbums = $stmt->fetchAll();

    // Az oy almış yüksek puanlı albümler
    $stmt = $conn->query("
        SELECT a.*, u.username,
               (SELECT AVG(rating) FROM ratings WHERE album_id = a.id) as avg_rating,
               (SELECT COUNT(*) FROM ratings WHERE album_id = a.id) as rating_count
        FROM albums a
        LEFT JOIN users u ON a.user_id = u.id
        HAVING rating_count BETWEEN 1 AND 5 AND avg_rating >= 4
        ORDER BY avg_rating DESC
        LIMIT 6
    ");
    $underratedAlbums = $stmt->fetchAll();
?>

    <!-- ##### Hero Area Start ##### -->
    <section class="hero-area">
        <div class="hero-slides owl-carousel">
            <?php foreach ($latestAlbums as $index => $album): ?>
            <!-- Single Hero Slide -->
            <div class="single-hero-slide d-flex align-items-center justify-content-center">
                <!-- Slide Img -->
                <div class="slide-img bg-img" style="background-image: url(<?php echo h(getAlbumCover($album['cover_image'])); ?>);"></div>
                <!-- Slide Content -->
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <div class="hero-slides-content text-center">
                                <h6 data-animation="fadeInUp" data-delay="100ms">Latest album</h6>
                                <h2 data-animation="fadeInUp" data-delay="300ms"><?php echo h($album['title']); ?> <span><?php echo h($album['artist']); ?></span></h2>
                                <a data-animation="fadeInUp" data-delay="500ms" href="album-detail.php?id=<?php echo $album['id']; ?>" class="btn oneMusic-btn mt-50">Discover <i class="fa fa-angle-double-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <!-- ##### Hero Area End ##### -->

    <!-- ##### Latest Albums Area Start ##### -->
    <section class="latest-albums-area section-padding-100">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="section-heading style-2">
                        <h2>Uploaded Albums</h2>
                    </div>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-4 justify-content-center">
                <?php foreach ($latestAlbums as $album): ?>
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
                                    <span><?php echo formatRating($album['avg_rating'] ?? 0); ?></span>
                                    <small>(<?php echo $album['rating_count']; ?> ratings)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <!-- ##### Latest Albums Area End ##### -->

    <!-- ##### Most Voted Albums of the Week ##### -->
    <section class="top-albums-area section-padding-100">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="section-heading style-2">
                        <h2>Most Voted Albums of the Week</h2>
                    </div>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-4 justify-content-center">
                <?php foreach ($topAlbums as $album): ?>
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
                                    <span><?php echo formatRating($album['avg_rating'] ?? 0); ?></span>
                                    <small>(<?php echo $album['rating_count']; ?> ratings)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <!-- ##### Most Voted Albums of the Week End ##### -->

    <!-- ##### High Rated Albums with Few Votes ##### -->
    <section class="underrated-albums-area section-padding-100">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="section-heading style-2">
                        <h2>High Rated Albums with Few Votes</h2>
                    </div>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-4 justify-content-center">
                <?php foreach ($underratedAlbums as $album): ?>
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
                                    <span><?php echo formatRating($album['avg_rating'] ?? 0); ?></span>
                                    <small>(<?php echo $album['rating_count']; ?> ratings)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <!-- ##### High Rated Albums with Few Votes End ##### -->

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>