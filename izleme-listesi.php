<?php
require_once 'functions.php';
girisGerekli();

$filmler = $_SESSION['filmler'];
$sayilar = filmSayilari($filmler);

$durumSecim = is_string($_GET['durum'] ?? null) ? trim($_GET['durum']) : 'İzlenecek';

if ($durumSecim === 'hepsi') {
    $durumSecim = '';
}

if ($durumSecim !== '' && !in_array($durumSecim, DURUMLAR, true)) {
    $durumSecim = 'İzlenecek';
}

$arama = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$sirala = is_string($_GET['sirala'] ?? null) ? trim($_GET['sirala']) : 'imdb';
$gecerliSiralama = ['yeni', 'eski', 'baslik', 'puan', 'imdb', 'yil', 'sure'];

if (!in_array($sirala, $gecerliSiralama, true)) {
    $sirala = 'imdb';
}

$filtreli = filmleriGetir($filmler, $arama, '', $durumSecim, $sirala);
$sayfalama = sayfalamaBilgisi(count($filtreli), sayfaNumarasi());
$sayfadakiler = array_slice($filtreli, $sayfalama['baslangic'], $sayfalama['limit']);

$sekmeSayilari = [
    'İzlenecek' => $sayilar['izlenecek'],
    'İzleniyor' => $sayilar['izleniyor'],
    'İzlendi'   => $sayilar['izlendi'],
    ''          => $sayilar['toplam'],
];

$urlOlustur = static function (array $degisiklikler = []) use ($durumSecim, $arama, $sirala): string {
    return sorguUrl('izleme-listesi.php', array_merge([
        'durum'  => $durumSecim,
        'q'      => $arama,
        'sirala' => $sirala,
    ], $degisiklikler));
};

$sayfaBaslik = 'İzleme Listesi — CHNStream';
$aktifSayfa  = 'liste';
$sayfaAciklama = 'İzlenecek, izleniyor ve izlenen filmlerini tek bir akışta yönet.';

require 'partials/header.php';
?>

<section class="page-hero page-hero-compact">
    <div>
        <span class="eyebrow"><i class="fa-solid fa-bookmark"></i> Watchlist</span>
        <h1>İzleme <span class="text-gradient">Listesi</span></h1>
        <p>İzlemek istediklerini sıraya koy, başladıklarını işaretle ve tamamladıklarını arşivle.</p>
    </div>
    <div class="page-hero-actions">
        <a href="rastgele.php" class="btn btn-ghost"><i class="fa-solid fa-shuffle"></i> Sürpriz</a>
        <a href="ekle.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Film Ekle</a>
    </div>
</section>

<section class="watchlist-summary" aria-label="Liste özeti">
    <div class="watchlist-progress">
        <div class="watchlist-progress-top">
            <span>İzleme ilerlemesi</span>
            <strong>%<?= e((string) yuzdeHesapla($sayilar['izlendi'], $sayilar['toplam'])) ?></strong>
        </div>
        <div class="progress-track progress-track-sm" role="progressbar" aria-valuemin="0" aria-valuemax="100"
             aria-valuenow="<?= (int) yuzdeHesapla($sayilar['izlendi'], $sayilar['toplam']) ?>">
            <span style="--bar: <?= e((string) yuzdeHesapla($sayilar['izlendi'], $sayilar['toplam'])) ?>%"></span>
        </div>
        <p><?= (int) $sayilar['izlendi'] ?> tamamlandı · <?= (int) $sayilar['izlenecek'] ?> sırada · <?= (int) $sayilar['izleniyor'] ?> devam ediyor</p>
    </div>

    <div class="watchlist-mini-stats">
        <span><i class="fa-solid fa-star"></i> Ortalama <strong><?= e(puanFormat($sayilar['ortalama'])) ?></strong></span>
        <span><i class="fa-brands fa-imdb"></i> IMDb <strong><?= e(puanFormat($sayilar['imdb_ortalama'])) ?></strong></span>
        <span><i class="fa-solid fa-heart"></i> Favori <strong><?= favoriSayisi() ?></strong></span>
    </div>
</section>

<nav class="tab-bar" aria-label="Durum sekmeleri">
    <a href="<?= e($urlOlustur(['durum' => 'İzlenecek', 'sayfa' => ''])) ?>" class="tab <?= $durumSecim === 'İzlenecek' ? 'is-active' : '' ?>">
        <i class="fa-solid fa-bookmark"></i> İzlenecek <span><?= (int) $sekmeSayilari['İzlenecek'] ?></span>
    </a>
    <a href="<?= e($urlOlustur(['durum' => 'İzleniyor', 'sayfa' => ''])) ?>" class="tab <?= $durumSecim === 'İzleniyor' ? 'is-active' : '' ?>">
        <i class="fa-solid fa-circle-play"></i> İzleniyor <span><?= (int) $sekmeSayilari['İzleniyor'] ?></span>
    </a>
    <a href="<?= e($urlOlustur(['durum' => 'İzlendi', 'sayfa' => ''])) ?>" class="tab <?= $durumSecim === 'İzlendi' ? 'is-active' : '' ?>">
        <i class="fa-solid fa-circle-check"></i> İzlendi <span><?= (int) $sekmeSayilari['İzlendi'] ?></span>
    </a>
    <a href="<?= e($urlOlustur(['durum' => 'hepsi', 'sayfa' => ''])) ?>" class="tab <?= $durumSecim === '' ? 'is-active' : '' ?>">
        <i class="fa-solid fa-layer-group"></i> Tümü <span><?= (int) $sekmeSayilari[''] ?></span>
    </a>
</nav>

<section class="filter-panel filter-panel-slim" aria-label="Liste filtreleri">
    <form method="GET" action="izleme-listesi.php" class="filter-form">
        <input type="hidden" name="durum" value="<?= e($durumSecim) ?>">

        <div class="field field-search">
            <label for="q" class="sr-only">Listede ara</label>
            <i class="fa-solid fa-magnifying-glass field-icon" aria-hidden="true"></i>
            <input type="search" id="q" name="q" value="<?= e($arama) ?>" placeholder="Listede ara…" autocomplete="off">
        </div>

        <div class="field">
            <label for="sirala" class="sr-only">Sıralama</label>
            <i class="fa-solid fa-arrow-down-wide-short field-icon" aria-hidden="true"></i>
            <select id="sirala" name="sirala" data-auto-submit>
                <option value="imdb" <?= $sirala === 'imdb' ? 'selected' : '' ?>>IMDb puanı</option>
                <option value="puan" <?= $sirala === 'puan' ? 'selected' : '' ?>>Kendi puanım</option>
                <option value="yeni" <?= $sirala === 'yeni' ? 'selected' : '' ?>>En yeni eklenen</option>
                <option value="eski" <?= $sirala === 'eski' ? 'selected' : '' ?>>En eski eklenen</option>
                <option value="baslik" <?= $sirala === 'baslik' ? 'selected' : '' ?>>Başlık (A–Z)</option>
                <option value="yil" <?= $sirala === 'yil' ? 'selected' : '' ?>>Yıl (yeni → eski)</option>
                <option value="sure" <?= $sirala === 'sure' ? 'selected' : '' ?>>Süre (uzun → kısa)</option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Uygula</button>
            <?php if ($arama !== ''): ?>
                <a href="<?= e($urlOlustur(['q' => '', 'sayfa' => ''])) ?>" class="btn btn-ghost btn-icon" title="Aramayı temizle">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="catalog">
    <div class="section-head">
        <div>
            <span class="section-kicker">
                <i class="fa-solid <?= e($durumSecim !== '' ? durumIkonu($durumSecim) : 'fa-layer-group') ?>"></i>
                <?= $durumSecim !== '' ? e($durumSecim) : 'Tüm kayıtlar' ?>
            </span>
            <h2>
                <?= $durumSecim !== '' ? e($durumSecim) . ' Filmler' : 'Tüm Filmler' ?>
                <span class="count-pill"><?= count($filtreli) ?> kayıt</span>
            </h2>
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

    <?php if ($sayfadakiler === []): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-bookmark"></i></div>
            <h3><?= $arama !== '' ? 'Aramana uygun kayıt yok' : 'Bu listede henüz film yok' ?></h3>
            <p><?= $arama !== ''
                ? 'Farklı bir kelimeyle aramayı tekrar dene.'
                : 'Durumu değiştirerek veya yeni bir film ekleyerek listeyi doldurabilirsin.' ?></p>
            <div class="empty-actions">
                <a href="ekle.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Film Ekle</a>
                <a href="index.php" class="btn btn-ghost"><i class="fa-solid fa-layer-group"></i> Arşive Dön</a>
            </div>
        </div>
    <?php else: ?>
        <div class="movie-grid" data-movie-grid>
            <?php foreach ($sayfadakiler as $film): ?>
                <?php require 'partials/movie_card.php'; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($sayfalama['sayfa_sayisi'] > 1): ?>
            <nav class="pagination" aria-label="Sayfalar">
                <a class="page-btn <?= $sayfalama['sayfa'] <= 1 ? 'is-disabled' : '' ?>"
                   href="<?= e($urlOlustur(['sayfa' => $sayfalama['onceki']])) ?>"
                   aria-label="Önceki sayfa"
                   <?= $sayfalama['sayfa'] <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </a>

                <?php for ($i = 1; $i <= $sayfalama['sayfa_sayisi']; $i++): ?>
                    <?php if ($i === 1 || $i === $sayfalama['sayfa_sayisi'] || abs($i - $sayfalama['sayfa']) <= 1): ?>
                        <a class="page-btn <?= $i === $sayfalama['sayfa'] ? 'is-active' : '' ?>"
                           href="<?= e($urlOlustur(['sayfa' => $i])) ?>"
                           <?= $i === $sayfalama['sayfa'] ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                    <?php elseif (abs($i - $sayfalama['sayfa']) === 2): ?>
                        <span class="page-gap">…</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <a class="page-btn <?= $sayfalama['sayfa'] >= $sayfalama['sayfa_sayisi'] ? 'is-disabled' : '' ?>"
                   href="<?= e($urlOlustur(['sayfa' => $sayfalama['sonraki']])) ?>"
                   aria-label="Sonraki sayfa"
                   <?= $sayfalama['sayfa'] >= $sayfalama['sayfa_sayisi'] ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require 'partials/footer.php'; ?>
