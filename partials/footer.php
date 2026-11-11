<?php
/**
 * Ortak alt bilgi, komut paleti, silme onay penceresi ve JavaScript bağlantısı.
 */

$komutFilmleri = [];

foreach (($_SESSION['filmler'] ?? []) as $komutFilm) {
    $komutFilmleri[] = [
        'baslik' => (string) ($komutFilm['baslik'] ?? ''),
        'id'     => (int) ($komutFilm['id'] ?? 0),
        'yil'    => (int) ($komutFilm['yil'] ?? 0),
    ];
}
?>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <div class="footer-brand">
                <a href="index.php" class="brand brand-sm" aria-label="CHNStream ana sayfa">
                    <span class="brand-mark"><i class="fa-solid fa-film"></i></span>
                    <span class="brand-text">CHN<span>STREAM</span></span>
                </a>
                <p>İzlediğin ve izleyeceğin filmleri tek bir yerde sakla, puanla ve keşfet.</p>
                <span class="footer-version">Sürüm <?= e(UYGULAMA_SURUMU) ?></span>
            </div>

            <nav class="footer-links" aria-label="Alt menü">
                <a href="index.php"><i class="fa-solid fa-layer-group"></i> Arşiv</a>
                <a href="kesfet.php"><i class="fa-solid fa-compass"></i> Keşfet</a>
                <a href="istatistikler.php"><i class="fa-solid fa-chart-simple"></i> İstatistikler</a>
                <a href="izleme-listesi.php"><i class="fa-solid fa-bookmark"></i> İzleme Listesi</a>
                <a href="favori.php"><i class="fa-solid fa-heart"></i> Favoriler</a>
                <a href="ekle.php"><i class="fa-solid fa-plus"></i> Film Ekle</a>
                <a href="disa-aktar.php"><i class="fa-solid fa-file-export"></i> Verileri Dışa Aktar</a>
                <a href="hakkinda.php"><i class="fa-solid fa-circle-info"></i> Hakkında</a>
            </nav>

            <div class="footer-tech" aria-label="Kullanılan teknolojiler">
                <span><i class="fa-brands fa-php"></i> PHP 8</span>
                <span><i class="fa-brands fa-html5"></i> HTML5</span>
                <span><i class="fa-brands fa-css3-alt"></i> CSS3</span>
                <span><i class="fa-brands fa-js"></i> Vanilla JS</span>
            </div>
        </div>

        <div class="container footer-bottom">
            <span>© <?= date('Y') ?> CHNStream · İnternet Programcılığı I Projesi</span>
            <span>Veriler <code>$_SESSION</code> ile saklanır.</span>
        </div>
    </footer>

    <button type="button" class="back-to-top" data-back-to-top aria-label="Sayfanın başına dön" title="Başa dön" hidden>
        <i class="fa-solid fa-arrow-up"></i>
    </button>

    <!-- Komut paleti: Ctrl/Cmd + K -->
    <div class="command-palette" data-command-palette hidden>
        <div class="command-backdrop" data-command-close></div>
        <div class="command-dialog" role="dialog" aria-modal="true" aria-labelledby="command-title">
            <h2 id="command-title" class="sr-only">Komut paleti</h2>
            <div class="command-search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" data-command-input placeholder="Sayfa, film veya komut ara…" autocomplete="off" spellcheck="false">
                <kbd>ESC</kbd>
            </div>
            <div class="command-results" data-command-results role="listbox" aria-label="Komut sonuçları">
                <span class="command-group-label">Sayfalar</span>
                <a href="index.php" class="command-item" data-command-item data-command-text="arşiv filmler koleksiyon ana sayfa">
                    <i class="fa-solid fa-layer-group"></i>
                    <span><strong>Arşiv</strong><small>Tüm filmler ve koleksiyon</small></span>
                </a>
                <a href="kesfet.php" class="command-item" data-command-item data-command-text="keşfet öneriler rastgele türler">
                    <i class="fa-solid fa-compass"></i>
                    <span><strong>Keşfet</strong><small>Öneriler, türler ve rastgele film</small></span>
                </a>
                <a href="istatistikler.php" class="command-item" data-command-item data-command-text="istatistik grafik dağılım analiz">
                    <i class="fa-solid fa-chart-simple"></i>
                    <span><strong>İstatistikler</strong><small>Arşivinin sayısal analizi</small></span>
                </a>
                <a href="izleme-listesi.php" class="command-item" data-command-item data-command-text="izleme listesi izlenecek watchlist">
                    <i class="fa-solid fa-bookmark"></i>
                    <span><strong>İzleme Listesi</strong><small>İzlenecek ve izlenen filmler</small></span>
                </a>
                <a href="favori.php" class="command-item" data-command-item data-command-text="favoriler beğenilen kalp">
                    <i class="fa-solid fa-heart"></i>
                    <span><strong>Favoriler</strong><small>Kalp ile işaretlediğin filmler</small></span>
                </a>
                <a href="ekle.php" class="command-item" data-command-item data-command-text="film ekle yeni kayıt">
                    <i class="fa-solid fa-plus"></i>
                    <span><strong>Film Ekle</strong><small>Arşive yeni bir kayıt ekle</small></span>
                </a>
                <a href="profil.php" class="command-item" data-command-item data-command-text="profil hesap şifre ayarlar">
                    <i class="fa-solid fa-user-gear"></i>
                    <span><strong>Profil</strong><small>Hesap bilgileri ve şifre değiştir</small></span>
                </a>
                <a href="disa-aktar.php" class="command-item" data-command-item data-command-text="dışa aktar csv json yedek indir">
                    <i class="fa-solid fa-file-export"></i>
                    <span><strong>Verileri Dışa Aktar</strong><small>CSV veya JSON yedeği indir</small></span>
                </a>
                <a href="hakkinda.php" class="command-item" data-command-item data-command-text="hakkında proje bilgi teknolojiler">
                    <i class="fa-solid fa-circle-info"></i>
                    <span><strong>Hakkında</strong><small>Proje ve teknik bilgiler</small></span>
                </a>
                <button type="button" class="command-item" data-command-item data-command-theme data-command-text="tema koyu açık değiştir gece">
                    <i class="fa-solid fa-circle-half-stroke"></i>
                    <span><strong>Temayı değiştir</strong><small>Açık ve koyu tema arasında geç</small></span>
                </button>

                <?php if ($komutFilmleri !== []): ?>
                    <span class="command-group-label">Filmler</span>
                    <?php foreach ($komutFilmleri as $komutFilm): ?>
                        <a href="detay.php?id=<?= (int) $komutFilm['id'] ?>" class="command-item" data-command-item
                           data-command-text="<?= e(kucukHarf($komutFilm['baslik'] . ' ' . $komutFilm['yil'])) ?>">
                            <i class="fa-solid fa-film"></i>
                            <span>
                                <strong><?= e($komutFilm['baslik']) ?></strong>
                                <small><?= (int) $komutFilm['yil'] ?> · Detayları aç</small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

                <p class="command-empty" data-command-empty hidden>Sonuç bulunamadı. Farklı bir kelime dene.</p>
            </div>
        </div>
    </div>

    <!-- Silme işlemi için erişilebilir onay penceresi -->
    <div class="modal-backdrop" id="delete-modal" data-delete-modal hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title" aria-describedby="delete-modal-desc">
            <button type="button" class="modal-close" data-modal-close aria-label="Pencereyi kapat">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="modal-icon"><i class="fa-solid fa-trash-can"></i></div>
            <h2 id="delete-modal-title">Kaydı silmek istiyor musun?</h2>
            <p id="delete-modal-desc">
                <strong data-delete-name>Bu kayıt</strong> arşivden kalıcı olarak kaldırılacak.
                Bu işlem geri alınamaz.
            </p>

            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" data-modal-close>Vazgeç</button>
                <button type="button" class="btn btn-danger" data-delete-confirm>
                    <i class="fa-solid fa-trash-can"></i> Evet, Sil
                </button>
            </div>
        </div>
    </div>

    <script src="assets/app.js?v=<?= e(varlikSurumu('assets/app.js')) ?>"></script>
</body>
</html>
