<?php
require_once 'functions.php';
girisGerekli();

$filmler    = $_SESSION['filmler'];
$sayilar    = filmSayilari($filmler);
$favoriler  = favoriFilmler($filmler);
$turler     = turDagilimi($filmler, 8);
$onYillar   = onYilDagilimi($filmler);
$puanlar    = puanDagilimi($filmler);
$enIyiImdb  = enIyiFilmler($filmler, 5, 'imdb');
$enIyiPuan  = enIyiFilmler($filmler, 5, 'puan');
$enCokYorum = enCokYorumlananlar($filmler, 5);
$sonEklenen = sonEklenenler($filmler, 5);

$enYuksekTur = $turler !== [] ? (string) array_key_first($turler) : '—';
$enYuksekTurAdet = $turler !== [] ? (int) reset($turler) : 0;
$enUzunFilm = null;
$enKisaFilm = null;

foreach ($filmler as $film) {
    $sure = (int) ($film['sure'] ?? 0);

    if ($sure <= 0) {
        continue;
    }

    if ($enUzunFilm === null || $sure > (int) ($enUzunFilm['sure'] ?? 0)) {
        $enUzunFilm = $film;
    }

    if ($enKisaFilm === null || $sure < (int) ($enKisaFilm['sure'] ?? 0)) {
        $enKisaFilm = $film;
    }
}

$toplamSure = 0;
$sureliAdet = 0;

foreach ($filmler as $film) {
    $sure = (int) ($film['sure'] ?? 0);

    if ($sure > 0) {
        $toplamSure += $sure;
        $sureliAdet++;
    }
}

$izlemeOrani = yuzdeHesapla($sayilar['izlendi'], $sayilar['toplam']);
$favoriOrani = yuzdeHesapla(count($favoriler), $sayilar['toplam']);
$maksTur     = $turler !== [] ? max($turler) : 1;
$maksOnYil   = $onYillar !== [] ? max($onYillar) : 1;
$maksPuan    = $puanlar !== [] ? max($puanlar) : 1;

$sayfaBaslik = 'İstatistikler — CHNStream';
$aktifSayfa  = 'istatistik';
$sayfaAciklama = 'Arşivinin sayısal analizi: tür dağılımı, puan grafikleri, en iyi filmler ve izleme oranı.';

require 'partials/header.php';
?>

<section class="page-hero">
    <div>
        <span class="eyebrow"><i class="fa-solid fa-chart-simple"></i> Arşiv Analizi</span>
        <h1>İstatistikler<span class="text-gradient">.</span></h1>
        <p>Koleksiyonunun nabzını tut. Tür dağılımı, puan eğilimleri ve izleme alışkanlıkların tek bir sayfada.</p>
    </div>
    <div class="page-hero-actions">
        <a href="disa-aktar.php" class="btn btn-ghost">
            <i class="fa-solid fa-file-export"></i> Dışa Aktar
        </a>
        <a href="index.php" class="btn btn-primary">
            <i class="fa-solid fa-layer-group"></i> Arşive Dön
        </a>
    </div>
</section>

<section class="kpi-grid" aria-label="Öne çıkan sayılar">
    <article class="kpi-card">
        <span class="kpi-icon kpi-red"><i class="fa-solid fa-clapperboard"></i></span>
        <div>
            <strong data-counter="<?= (int) $sayilar['toplam'] ?>"><?= (int) $sayilar['toplam'] ?></strong>
            <span>Toplam kayıt</span>
        </div>
        <small><?= (int) $sayilar['kategori'] ?> farklı tür</small>
    </article>

    <article class="kpi-card">
        <span class="kpi-icon kpi-green"><i class="fa-solid fa-circle-check"></i></span>
        <div>
            <strong data-counter="<?= (int) $sayilar['izlendi'] ?>"><?= (int) $sayilar['izlendi'] ?></strong>
            <span>İzlenen film</span>
        </div>
        <small>%<?= e((string) $izlemeOrani) ?> tamamlandı</small>
    </article>

    <article class="kpi-card">
        <span class="kpi-icon kpi-gold"><i class="fa-solid fa-star"></i></span>
        <div>
            <strong data-counter="<?= e(puanFormat($sayilar['ortalama'])) ?>" data-decimals="1"><?= e(puanFormat($sayilar['ortalama'])) ?></strong>
            <span>Ortalama puanın</span>
        </div>
        <small>IMDb <?= e(puanFormat($sayilar['imdb_ortalama'])) ?></small>
    </article>

    <article class="kpi-card">
        <span class="kpi-icon kpi-blue"><i class="fa-regular fa-comments"></i></span>
        <div>
            <strong data-counter="<?= (int) $sayilar['yorum'] ?>"><?= (int) $sayilar['yorum'] ?></strong>
            <span>Yorum</span>
        </div>
        <small><?= count($favoriler) ?> favori · %<?= e((string) $favoriOrani) ?></small>
    </article>
</section>

<section class="insight-strip" aria-label="Öne çıkan içgörüler">
    <article>
        <i class="fa-solid fa-fire"></i>
        <span><strong><?= e($enYuksekTur) ?></strong> en çok biriktirdiğin tür <em>(<?= $enYuksekTurAdet ?> kayıt)</em></span>
    </article>
    <article>
        <i class="fa-solid fa-hourglass-half"></i>
        <span><strong><?= e(sureFormat($toplamSure)) ?></strong> toplam süre · <?= $sureliAdet ?> kayıtta süre bilgisi var</span>
    </article>
    <article>
        <i class="fa-solid fa-heart"></i>
        <span><strong><?= count($favoriler) ?></strong> film favorilerinde · <a href="favori.php">listeyi gör</a></span>
    </article>
</section>

<div class="analytics-grid">
    <section class="panel chart-card" aria-labelledby="tur-baslik">
        <div class="panel-head">
            <div>
                <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-tags"></i> Dağılım</span>
                <h2 id="tur-baslik">Türler</h2>
            </div>
            <span class="panel-badge"><?= count($turler) ?> tür</span>
        </div>

        <?php if ($turler === []): ?>
            <p class="muted-text">Henüz tür verisi yok.</p>
        <?php else: ?>
            <div class="bar-list">
                <?php foreach ($turler as $tur => $adet): ?>
                    <div class="bar-row">
                        <span class="bar-label"><?= e((string) $tur) ?></span>
                        <div class="bar-track">
                            <span class="bar-fill" style="--bar: <?= e((string) yuzdeHesapla($adet, $maksTur)) ?>%"></span>
                        </div>
                        <span class="bar-value"><?= (int) $adet ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel chart-card" aria-labelledby="puan-baslik">
        <div class="panel-head">
            <div>
                <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-star-half-stroke"></i> Puanlar</span>
                <h2 id="puan-baslik">Puan Dağılımı</h2>
            </div>
            <span class="panel-badge">1–10</span>
        </div>

        <div class="bar-list bar-list-alt">
            <?php foreach ($puanlar as $dilim => $adet): ?>
                <div class="bar-row">
                    <span class="bar-label"><?= e((string) $dilim) ?></span>
                    <div class="bar-track">
                        <span class="bar-fill bar-fill-gold" style="--bar: <?= e((string) yuzdeHesapla($adet, $maksPuan)) ?>%"></span>
                    </div>
                    <span class="bar-value"><?= (int) $adet ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="chart-foot">
            <span><i class="fa-solid fa-arrow-trend-up"></i> En yüksek puanın: <?= e(puanFormat($enIyiPuan[0]['puan'] ?? 0)) ?></span>
            <a href="index.php?sirala=puan">Sırala <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </section>

    <section class="panel chart-card" aria-labelledby="onyil-baslik">
        <div class="panel-head">
            <div>
                <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-timeline"></i> Dönemler</span>
                <h2 id="onyil-baslik">On Yıllara Göre</h2>
            </div>
            <span class="panel-badge"><?= count($onYillar) ?> dönem</span>
        </div>

        <?php if ($onYillar === []): ?>
            <p class="muted-text">Yıl bilgisi olan kayıt bulunmuyor.</p>
        <?php else: ?>
            <div class="decade-chart" role="img" aria-label="On yıllara göre film sayısı">
                <?php foreach ($onYillar as $donem => $adet): ?>
                    <div class="decade-col" title="<?= e((string) $donem) ?>: <?= (int) $adet ?> film">
                        <span class="decade-value"><?= (int) $adet ?></span>
                        <span class="decade-bar" style="--bar: <?= e((string) max(6, yuzdeHesapla($adet, $maksOnYil))) ?>%"></span>
                        <span class="decade-label"><?= e((string) $donem) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel chart-card" aria-labelledby="sure-baslik">
        <div class="panel-head">
            <div>
                <span class="eyebrow eyebrow-sm"><i class="fa-regular fa-clock"></i> Süreler</span>
                <h2 id="sure-baslik">Süre Özeti</h2>
            </div>
        </div>

        <div class="mini-stats">
            <div>
                <span>Toplam</span>
                <strong><?= e(sureFormat($toplamSure)) ?></strong>
            </div>
            <div>
                <span>Ortalama</span>
                <strong><?= e($sureliAdet > 0 ? sureFormat((int) round($toplamSure / $sureliAdet)) : '—') ?></strong>
            </div>
            <div>
                <span>En uzun</span>
                <strong><?= e($enUzunFilm !== null ? sureFormat((int) $enUzunFilm['sure']) : '—') ?></strong>
                <small><?= e((string) ($enUzunFilm['baslik'] ?? '')) ?></small>
            </div>
            <div>
                <span>En kısa</span>
                <strong><?= e($enKisaFilm !== null ? sureFormat((int) $enKisaFilm['sure']) : '—') ?></strong>
                <small><?= e((string) ($enKisaFilm['baslik'] ?? '')) ?></small>
            </div>
        </div>
    </section>
</div>

<div class="analytics-grid analytics-grid-two">
    <section class="panel chart-card" aria-labelledby="imdb-top">
        <div class="panel-head">
            <div>
                <span class="eyebrow eyebrow-sm"><i class="fa-brands fa-imdb"></i> IMDb</span>
                <h2 id="imdb-top">En Yüksek IMDb</h2>
            </div>
        </div>
        <ol class="rank-list">
            <?php foreach ($enIyiImdb as $sira => $film): ?>
                <li>
                    <span class="rank-no"><?= $sira + 1 ?></span>
                    <a href="detay.php?id=<?= (int) $film['id'] ?>" class="rank-title"><?= e((string) $film['baslik']) ?></a>
                    <span class="rank-meta"><?= (int) $film['yil'] ?></span>
                    <span class="rank-score"><?= e(puanFormat(filmImdbPuan($film))) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

    <section class="panel chart-card" aria-labelledby="kendi-top">
        <div class="panel-head">
            <div>
                <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-heart"></i> Senin Puanların</span>
                <h2 id="kendi-top">En Sevdiğin Filmler</h2>
            </div>
        </div>
        <ol class="rank-list">
            <?php foreach ($enIyiPuan as $sira => $film): ?>
                <li>
                    <span class="rank-no"><?= $sira + 1 ?></span>
                    <a href="detay.php?id=<?= (int) $film['id'] ?>" class="rank-title"><?= e((string) $film['baslik']) ?></a>
                    <span class="rank-meta"><?= e((string) $film['kategori']) ?></span>
                    <span class="rank-score rank-score-gold"><?= e(puanFormat((float) $film['puan'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

    <section class="panel chart-card" aria-labelledby="yorum-top">
        <div class="panel-head">
            <div>
                <span class="eyebrow eyebrow-sm"><i class="fa-regular fa-comments"></i> Topluluk</span>
                <h2 id="yorum-top">En Çok Yorumlanan</h2>
            </div>
        </div>
        <ol class="rank-list">
            <?php foreach ($enCokYorum as $sira => $film): ?>
                <li>
                    <span class="rank-no"><?= $sira + 1 ?></span>
                    <a href="detay.php?id=<?= (int) $film['id'] ?>#yorumlar" class="rank-title"><?= e((string) $film['baslik']) ?></a>
                    <span class="rank-meta"><?= e((string) $film['kategori']) ?></span>
                    <span class="rank-score"><?= yorumSayisi((int) $film['id']) ?> <i class="fa-regular fa-comment"></i></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

    <section class="panel chart-card" aria-labelledby="son-top">
        <div class="panel-head">
            <div>
                <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-clock-rotate-left"></i> Güncel</span>
                <h2 id="son-top">Son Eklenenler</h2>
            </div>
        </div>
        <ol class="rank-list">
            <?php foreach ($sonEklenen as $sira => $film): ?>
                <li>
                    <span class="rank-no"><?= $sira + 1 ?></span>
                    <a href="detay.php?id=<?= (int) $film['id'] ?>" class="rank-title"><?= e((string) $film['baslik']) ?></a>
                    <span class="rank-meta"><?= e(tarihFormat((string) ($film['eklenme'] ?? ''))) ?></span>
                    <span class="rank-score"><i class="fa-solid <?= e(durumIkonu((string) $film['durum'])) ?>"></i></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
</div>

<section class="panel progress-panel" aria-labelledby="hedef-baslik">
    <div class="panel-head">
        <div>
            <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-bullseye"></i> İzleme Hedefi</span>
            <h2 id="hedef-baslik">Koleksiyonunun Durumu</h2>
        </div>
        <span class="panel-badge">%<?= e((string) $izlemeOrani) ?></span>
    </div>

    <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $izlemeOrani ?>" aria-label="İzlenen film oranı">
        <span style="--bar: <?= e((string) $izlemeOrani) ?>%"></span>
    </div>

    <div class="progress-legend">
        <span><i class="legend-dot dot-green"></i> İzlendi <strong><?= (int) $sayilar['izlendi'] ?></strong></span>
        <span><i class="legend-dot dot-orange"></i> İzlenecek <strong><?= (int) $sayilar['izlenecek'] ?></strong></span>
        <span><i class="legend-dot dot-blue"></i> İzleniyor <strong><?= (int) $sayilar['izleniyor'] ?></strong></span>
    </div>

    <p class="progress-note">
        <?= $sayilar['izlenecek'] > 0
            ? 'İzleme listende ' . (int) $sayilar['izlenecek'] . ' film seni bekliyor. Bir sonraki filmi seçmek için keşfet sayfasına göz at.'
            : 'İzleme listendeki her filmi tamamladın. Yeni bir film ekleyerek koleksiyonunu büyütebilirsin.' ?>
    </p>
</section>

<?php require 'partials/footer.php'; ?>
