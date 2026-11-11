<?php
require_once 'functions.php';
girisGerekli();

$filmler     = $_SESSION['filmler'];
$sayilar     = filmSayilari($filmler);
$oneriler    = filmOnerileri($filmler, 8);
$rastgele    = rastgeleFilm($filmler);
$turler      = turDagilimi($filmler, 10);
$enIyi       = enIyiFilmler($filmler, 6, 'imdb');
$sonEklenen  = sonEklenenler($filmler, 4);
$favoriler   = favoriFilmler($filmler);
$ilgiAlanlari = ilgiAlanlari($filmler, 5);
$izlenecekler = array_values(array_filter(
    $filmler,
    static fn (array $film): bool => (string) ($film['durum'] ?? '') === 'İzlenecek'
));
usort($izlenecekler, static fn (array $a, array $b): int => ((float) ($b['imdb'] ?? 0)) <=> ((float) ($a['imdb'] ?? 0)));
$izlenecekler = array_slice($izlenecekler, 0, 4);

$sayfaBaslik = 'Keşfet — CHNStream';
$aktifSayfa  = 'kesfet';
$sayfaAciklama = 'Zevkine göre öneriler, türler, yüksek puanlı filmler ve rastgele keşif.';

require 'partials/header.php';
?>

<section class="page-hero page-hero-discover">
    <div>
        <span class="eyebrow"><i class="fa-solid fa-compass"></i> Keşif Merkezi</span>
        <h1>Yeni bir film,<br><span class="text-gradient">yeni bir hikâye.</span></h1>
        <p>Arşivindeki izlere göre şekillenen öneriler, türler ve sürpriz seçkilerle bir sonraki filmini bul.</p>

        <form action="index.php" method="GET" class="discover-search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" placeholder="Film, yönetmen, ülke veya etiket ara…" aria-label="Arşivde ara" autocomplete="off">
            <button type="submit" class="btn btn-primary">Ara</button>
        </form>

        <?php if ($ilgiAlanlari !== []): ?>
            <div class="taste-chips" aria-label="İlgi alanların">
                <span class="taste-label"><i class="fa-solid fa-heart"></i> İlgi alanların:</span>
                <?php foreach ($ilgiAlanlari as $tur): ?>
                    <a class="taste-chip" href="index.php?kategori=<?= e(urlencode($tur)) ?>"><?= e($tur) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($rastgele !== null): ?>
        <aside class="surprise-card">
            <span class="surprise-label"><i class="fa-solid fa-shuffle"></i> Sürpriz Seçim</span>
            <a href="detay.php?id=<?= (int) $rastgele['id'] ?>" class="surprise-poster <?= e(posterSinifi((string) $rastgele['kategori'])) ?>">
                <div class="poster-fallback" aria-hidden="true">
                    <span><?= e(filmBasHarfleri((string) $rastgele['baslik'])) ?></span>
                    <i class="fa-solid fa-film"></i>
                </div>
                <?php if ((string) ($rastgele['poster'] ?? '') !== ''): ?>
                    <img src="<?= e((string) $rastgele['poster']) ?>" alt="<?= e((string) $rastgele['baslik']) ?> afişi" loading="lazy" data-poster-img>
                <?php endif; ?>
                <span class="feature-rating"><i class="fa-solid fa-star"></i> <?= e(puanFormat((float) $rastgele['puan'])) ?></span>
            </a>
            <h2><?= e((string) $rastgele['baslik']) ?></h2>
            <p><?= e((string) $rastgele['kategori']) ?> · <?= (int) $rastgele['yil'] ?></p>
            <div class="surprise-actions">
                <a href="detay.php?id=<?= (int) $rastgele['id'] ?>" class="btn btn-primary btn-sm">Detayları gör</a>
                <a href="rastgele.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate"></i> Başka</a>
            </div>
        </aside>
    <?php endif; ?>
</section>

<?php if ($turler !== []): ?>
    <section class="genre-section" aria-labelledby="turler-baslik">
        <div class="section-head">
            <div>
                <span class="section-kicker"><i class="fa-solid fa-tags"></i> Koleksiyondan</span>
                <h2 id="turler-baslik">Türlere Göz At</h2>
            </div>
            <a href="istatistikler.php" class="btn btn-ghost btn-sm">Dağılımı gör <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="genre-grid">
            <?php foreach ($turler as $tur => $adet): ?>
                <a href="index.php?kategori=<?= e(urlencode((string) $tur)) ?>" class="genre-tile">
                    <span class="genre-icon"><i class="fa-solid fa-clapperboard"></i></span>
                    <span class="genre-name"><?= e((string) $tur) ?></span>
                    <span class="genre-count"><?= (int) $adet ?> film</span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($oneriler !== []): ?>
    <section class="catalog" id="oneriler" aria-labelledby="oneriler-baslik">
        <div class="section-head">
            <div>
                <span class="section-kicker"><i class="fa-solid fa-wand-magic-sparkles"></i> Sana özel</span>
                <h2 id="oneriler-baslik">Senin İçin Öneriler <span class="count-pill"><?= count($oneriler) ?> öneri</span></h2>
                <p class="section-desc">İzlediğin ve puanladığın filmlere göre seçildi. Yeni bir kayıt eklediğinde liste otomatik yenilenir.</p>
            </div>
            <a href="index.php?sirala=imdb" class="btn btn-ghost btn-sm">Tümünü sırala <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="movie-grid">
            <?php foreach ($oneriler as $film): ?>
                <?php require 'partials/movie_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="discover-split">
    <div class="catalog">
        <div class="section-head section-head-sm">
            <div>
                <span class="section-kicker"><i class="fa-brands fa-imdb"></i> Yüksek puanlı</span>
                <h2>IMDb Zirvesi</h2>
            </div>
        </div>
        <div class="movie-grid movie-grid-compact">
            <?php foreach ($enIyi as $film): ?>
                <?php require 'partials/movie_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <aside class="side-stack">
        <?php if ($izlenecekler !== []): ?>
            <section class="panel side-panel">
                <div class="panel-head panel-head-compact">
                    <div>
                        <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-bookmark"></i> Sıradaki</span>
                        <h2>İzleme Listenden</h2>
                    </div>
                </div>
                <ul class="side-list">
                    <?php foreach ($izlenecekler as $film): ?>
                        <li>
                            <a href="detay.php?id=<?= (int) $film['id'] ?>">
                                <span class="side-list-title"><?= e((string) $film['baslik']) ?></span>
                                <span class="side-list-meta"><?= (int) $film['yil'] ?> · IMDb <?= e(puanFormat(filmImdbPuan($film))) ?></span>
                            </a>
                            <form method="POST" action="durum.php">
                                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $film['id'] ?>">
                                <input type="hidden" name="yonlendir" value="kesfet.php">
                                <button type="submit" class="icon-btn" title="İzlemeye başla" aria-label="<?= e((string) $film['baslik']) ?> durumunu değiştir">
                                    <i class="fa-solid fa-circle-play"></i>
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="izleme-listesi.php" class="text-link">Tüm izleme listesi <i class="fa-solid fa-arrow-right"></i></a>
            </section>
        <?php endif; ?>

        <?php if ($favoriler !== []): ?>
            <section class="panel side-panel">
                <div class="panel-head panel-head-compact">
                    <div>
                        <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-heart"></i> Favoriler</span>
                        <h2>Kalptekiler</h2>
                    </div>
                </div>
                <div class="mini-poster-grid">
                    <?php foreach (array_slice($favoriler, 0, 4) as $film): ?>
                        <a href="detay.php?id=<?= (int) $film['id'] ?>" class="mini-poster <?= e(posterSinifi((string) $film['kategori'])) ?>" title="<?= e((string) $film['baslik']) ?>">
                            <div class="poster-fallback" aria-hidden="true">
                                <span><?= e(filmBasHarfleri((string) $film['baslik'])) ?></span>
                            </div>
                            <?php if ((string) ($film['poster'] ?? '') !== ''): ?>
                                <img src="<?= e((string) $film['poster']) ?>" alt="" loading="lazy" data-poster-img>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <a href="favori.php" class="text-link">Favorileri aç <i class="fa-solid fa-arrow-right"></i></a>
            </section>
        <?php endif; ?>

        <section class="panel side-panel">
            <div class="panel-head panel-head-compact">
                <div>
                    <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-clock-rotate-left"></i> Güncel</span>
                    <h2>Son Eklenenler</h2>
                </div>
            </div>
            <ul class="side-list">
                <?php foreach ($sonEklenen as $film): ?>
                    <li>
                        <a href="detay.php?id=<?= (int) $film['id'] ?>">
                            <span class="side-list-title"><?= e((string) $film['baslik']) ?></span>
                            <span class="side-list-meta"><?= e(tarihFormat((string) ($film['eklenme'] ?? ''))) ?> · <?= e((string) $film['kategori']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a href="index.php?sirala=yeni" class="text-link">Tüm arşiv <i class="fa-solid fa-arrow-right"></i></a>
        </section>
    </aside>
</section>

<?php require 'partials/footer.php'; ?>
