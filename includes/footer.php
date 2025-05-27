/**
 * footer.php
 * 
 * Bu dosya, web sitesinin alt kısmını (footer) oluşturan temel şablon dosyasıdır.
 * Tüm sayfalarda ortak olarak kullanılan alt menü, telif hakkı bilgisi ve JavaScript dosyalarını içerir.
 * 
 * İçerik:
 * 1. Telif hakkı bilgisi
 * 2. Alt navigasyon menüsü
 * 3. Gerekli JavaScript dosyaları
 * 4. Responsive tasarım için gerekli yapı
 */

    <!-- Footer Bölümü Başlangıcı -->
    <footer class="footer-area">
        <!-- Container: Footer içeriğini sayfa genişliğine göre sınırlar -->
        <div class="container">
            <!-- Row: Flex yapısı ile içeriği düzenler ve hizalar -->
            <div class="row d-flex flex-wrap align-items-center">
                <!-- Sol Kolon: Logo ve telif hakkı bilgisi -->
                <div class="col-12 col-md-6">
                    <!-- Site logosu ve başlığı -->
                    <a href="index.php"><span style="font-size:1.5rem;font-weight:bold;color:#fff;letter-spacing:2px;">AlbumRanker</span></a>
                    <!-- Dinamik yıl ile telif hakkı metni -->
                    <p class="copywrite-text">
                        Copyright &copy;<?php echo date('Y'); ?> All rights reserved | AlbumRanker
                    </p>
                </div>
                <!-- Sağ Kolon: Navigasyon menüsü -->
                <div class="col-12 col-md-6">
                    <!-- Footer navigasyon menüsü -->
                    <div class="footer-nav">
                        <ul>
                            <!-- Ana sayfa linki -->
                            <li><a href="index.php">Home</a></li>
                            <!-- Albüm keşfetme sayfası linki -->
                            <li><a href="albums-store.php">Discover</a></li>
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- Kullanıcı giriş yapmışsa albüm yükleme sayfası linki gösterilir -->
                            <li><a href="album-upload.php">Upload Album</a></li>
                            <?php endif; ?>
                            <!-- Telif hakkı ve kullanım şartları sayfası linki -->
                            <li><a href="copyright.php">Copyright & Usage</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- Footer Bölümü Sonu -->

    <!-- JavaScript Dosyaları -->
    <!-- jQuery kütüphanesi - Temel JavaScript işlevselliği için gerekli -->
    <script src="js/jquery/jquery-2.2.4.min.js"></script>
    <!-- Bootstrap JavaScript dosyası (şu an devre dışı) -->
    <!-- <script src="js/bootstrap/bootstrap.bundle.min.js"></script> -->
    <!-- Özel eklentiler ve eklenti yönetimi -->
    <script src="js/plugins/plugins.js"></script>
    <!-- Ana JavaScript dosyası - Özel fonksiyonlar ve etkileşimler -->
    <script src="js/active.js"></script>
</body>
</html> 