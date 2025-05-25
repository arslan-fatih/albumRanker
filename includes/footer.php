    <!-- Footer Area Start -->
    <footer class="footer-area">
        <div class="container">
            <div class="row d-flex flex-wrap align-items-center">
                <div class="col-12 col-md-6">
                    <a href="index.php"><span style="font-size:1.5rem;font-weight:bold;color:#fff;letter-spacing:2px;">AlbumRanker</span></a>
                    <p class="copywrite-text">
                        Copyright &copy;<?php echo date('Y'); ?> All rights reserved | AlbumRanker
                    </p>
                </div>
                <div class="col-12 col-md-6">
                    <div class="footer-nav">
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="albums-store.php">Discover</a></li>
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="album-upload.php">Upload Album</a></li>
                            <?php endif; ?>
                            <li><a href="copyright.php">Copyright & Usage</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- Footer Area End -->

    <!-- All Javascript Script -->
    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <script src="js/bootstrap/popper.min.js"></script>
    <script src="js/bootstrap/bootstrap.min.js"></script>
    <script src="js/plugins/plugins.js"></script>
    <script src="js/active.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script> 