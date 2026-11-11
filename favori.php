<?php
require_once 'functions.php';
girisGerekli();

/* --------------------------------------------------------------------------
 * POST: favori ekle / çıkar
 * ----------------------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : null;

    if (!csrfDogrula($token)) {
        flashAyarla('hata', 'Güvenlik doğrulaması başarısız oldu. Favori değiştirilmedi.');
        yonlendir('favori.php');
    }

    $id   = (int) ($_POST['id'] ?? 0);
    $film = filmBul($_SESSION['filmler'], $id);

    if ($film === null) {
        flashAyarla('hata', 'Favorilere eklenecek kayıt bulunamadı.');
        yonlendir('index.php');
    }

    $eklendi = favoriDegistir($id);
    $baslik  = (string) ($film['baslik'] ?? 'Film');

    flashAyarla(
        'basari',
        $eklendi
            ? '“' . $baslik . '” favorilerine eklendi.'
            : '“' . $baslik . '” favorilerinden çıkarıldı.'
    );

    yonlendir(guvenliDonus($_POST['yonlendir'] ?? null, 'favori.php'));
}

/* --------------------------------------------------------------------------
 * GET: favoriler sayfası
 * ----------------------------------------------------------------------- */

$filmler   = $_SESSION['filmler'];
$favoriler = favoriFilmler($filmler);
$sayilar   = filmSayilari($filmler);

$favoriTurler = turDagilimi($favoriler, 6);
$ortalama = 0.0;
$imdbOrtalama = 0.0;

foreach ($favoriler as $favori) {
    $ortalama += (float) ($favori['puan'] ?? 0);
    $imdbOrtalama += filmImdbPuan($favori);
}

if ($favoriler !== []) {
    $ortalama = round($ortalama / count($favoriler), 1);
    $imdbOrtalama = round($imdbOrtalama / count($favoriler), 1);
}

$sayfaBaslik = 'Favoriler — CHNStream';
$aktifSayfa  = 'favori';
$sayfaAciklama = 'Kalp ile işaretlediğin filmler ve favori koleksiyonunun özeti.';

require 'partials/header.php';
?>

<section class="page-hero page-hero-compact">
    <div>
        <span class="eyebrow"><i class="fa-solid fa-heart"></i> Kalptekiler</span>
        <h1>Favori <span class="text-gradient">Filmler</span></h1>
        <p>Senin için özel olan filmler tek bir yerde. Kartlardaki kalp simgesiyle listeyi istediğin an güncelle.</p>
    </div>
    <div class="page-hero-actions">
        <a href="kesfet.php" class="btn btn-ghost"><i class="fa-solid fa-compass"></i> Keşfet</a>
        <a href="ekle.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Film Ekle</a>
    </div>
</section>

<section class="favorite-summary" aria-label="Favori özeti">
    <div class="favorite-big">
        <span class="favorite-heart"><i class="fa-solid fa-heart"></i></span>
        <div>
            <strong data-counter="<?= count($favoriler) ?>"><?= count($favoriler) ?></strong>
            <span>favori film</span>
        </div>
    </div>

    <div class="favorite-metrics">
        <div>
            <span>Ortalama puan</span>
            <strong><?= e(puanFormat($ortalama)) ?></strong>
        </div>
        <div>
            <span>IMDb ortalaması</span>
            <strong><?= e(puanFormat($imdbOrtalama)) ?></strong>
        </div>
        <div>
            <span>Favori tür</span>
            <strong><?= e($favoriTurler !== [] ? (string) array_key_first($favoriTurler) : '—') ?></strong>
        </div>
        <div>
            <span>Arşivdeki payı</span>
            <strong>%<?= e((string) yuzdeHesapla(count($favoriler), $sayilar['toplam'])) ?></strong>
        </div>
    </div>
</section>

<?php if ($favoriler === []): ?>
    <div class="empty-state empty-state-large">
        <div class="empty-icon"><i class="fa-regular fa-heart"></i></div>
        <h3>Henüz favori film eklemedin</h3>
        <p>Arşivdeki bir filmin kartındaki kalp simgesine dokunarak favorilerine ekleyebilirsin.</p>
        <div class="empty-actions">
            <a href="index.php" class="btn btn-primary"><i class="fa-solid fa-layer-group"></i> Arşivi Gez</a>
            <a href="kesfet.php" class="btn btn-ghost"><i class="fa-solid fa-compass"></i> Keşfet</a>
        </div>
    </div>
<?php else: ?>
    <section class="catalog">
        <div class="section-head">
            <div>
                <span class="section-kicker"><i class="fa-solid fa-heart"></i> Koleksiyon</span>
                <h2>Favori Listesi <span class="count-pill"><?= count($favoriler) ?> film</span></h2>
                <p class="section-desc">Kartlardaki kalbe tekrar dokunarak bir filmi favorilerinden çıkarabilirsin.</p>
            </div>

            <div class="view-toggle" role="group" aria-label="Görünüm seçimi">
                <button type="button" class="view-btn is-active" data-view="grid" aria-label="Izgara görünümü" title="Izgara görünümü">
                    <i class="fa-solid fa-grip"></i>
                </button>
                <button type="button" class="view-btn" data-view="list" aria-label="Liste görünümü" title="Liste görünümü">
                    <i class="fa-solid fa-list"></i>
                </button>
            </div>
        </div>

        <div class="movie-grid" data-movie-grid>
            <?php foreach ($favoriler as $film): ?>
                <?php require 'partials/movie_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($favoriTurler !== []): ?>
        <section class="panel favorite-genres">
            <div class="panel-head panel-head-compact">
                <div>
                    <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-tags"></i> Dağılım</span>
                    <h2>Favorilerinin Türleri</h2>
                </div>
            </div>
            <div class="genre-chip-row">
                <?php foreach ($favoriTurler as $tur => $adet): ?>
                    <a class="genre-chip" href="index.php?kategori=<?= e(urlencode((string) $tur)) ?>">
                        <?= e((string) $tur) ?> <span><?= (int) $adet ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php require 'partials/footer.php'; ?>
