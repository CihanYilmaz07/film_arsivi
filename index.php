<?php
require_once 'functions.php';
girisGerekli();

/* ---- Filtre parametrelerini güvenli biçimde oku ---- */
$arama    = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$kategori = is_string($_GET['kategori'] ?? null) ? trim($_GET['kategori']) : '';
$durum    = is_string($_GET['durum'] ?? null) ? trim($_GET['durum']) : '';
$sirala   = is_string($_GET['sirala'] ?? null) ? trim($_GET['sirala']) : 'yeni';

$gecerliSiralama = ['yeni', 'eski', 'baslik', 'puan', 'imdb', 'yil', 'sure', 'yorum'];

if (!in_array($sirala, $gecerliSiralama, true)) {
    $sirala = 'yeni';
}

if ($durum !== '' && !in_array($durum, DURUMLAR, true)) {
    $durum = '';
}

$yilMin = max(0, (int) ($_GET['yil_min'] ?? 0));
$yilMax = max(0, (int) ($_GET['yil_max'] ?? 0));

if ($yilMin > 0 && $yilMax > 0 && $yilMax < $yilMin) {
    [$yilMin, $yilMax] = [$yilMax, $yilMin];
}

$minPuan = min(10.0, max(0.0, (float) str_replace(',', '.', (string) ($_GET['min_puan'] ?? '0'))));
$minImdb = min(10.0, max(0.0, (float) str_replace(',', '.', (string) ($_GET['min_imdb'] ?? '0'))));
$sadeceFavoriler = (string) ($_GET['favori'] ?? '') === '1';
$sadeceYorumlu   = (string) ($_GET['yorumlu'] ?? '') === '1';

$filmler     = $_SESSION['filmler'];
$kategoriler = kategorileriGetir($filmler);

if ($kategori !== '' && !in_array($kategori, $kategoriler, true)) {
    $kategori = '';
}

$filtreli = filmleriGetir($filmler, $arama, $kategori, $durum, $sirala, [
    'yil_min'         => $yilMin,
    'yil_max'         => $yilMax,
    'min_puan'        => $minPuan,
    'min_imdb'        => $minImdb,
    'sadece_favoriler' => $sadeceFavoriler,
    'sadece_yorumlu'   => $sadeceYorumlu,
    'favoriler'        => $_SESSION['favoriler'] ?? [],
]);

$sayilar     = filmSayilari($filmler);
$sayfalama   = sayfalamaBilgisi(count($filtreli), sayfaNumarasi());
$sayfadakiler = array_slice($filtreli, $sayfalama['baslangic'], $sayfalama['limit']);

$filtreAktif = $arama !== '' || $kategori !== '' || $durum !== '' || $sirala !== 'yeni'
    || $yilMin > 0 || $yilMax > 0 || $minPuan > 0 || $minImdb > 0
    || $sadeceFavoriler || $sadeceYorumlu;

$ileriFiltreAktif = $yilMin > 0 || $yilMax > 0 || $minPuan > 0 || $minImdb > 0 || $sadeceFavoriler || $sadeceYorumlu;

/* Aynı sayfada filtre bağlantısı üretmek için küçük yardımcı. */
$filtreUrl = static function (array $degisiklikler = []) use (
    $arama,
    $kategori,
    $durum,
    $sirala,
    $yilMin,
    $yilMax,
    $minPuan,
    $minImdb,
    $sadeceFavoriler,
    $sadeceYorumlu
): string {
    $parametreler = array_merge([
        'q'        => $arama,
        'kategori' => $kategori,
        'durum'    => $durum,
        'sirala'   => $sirala,
        'yil_min'  => $yilMin > 0 ? $yilMin : '',
        'yil_max'  => $yilMax > 0 ? $yilMax : '',
        'min_puan' => $minPuan > 0 ? $minPuan : '',
        'min_imdb' => $minImdb > 0 ? $minImdb : '',
        'favori'   => $sadeceFavoriler ? '1' : '',
        'yorumlu'  => $sadeceYorumlu ? '1' : '',
    ], $degisiklikler);

    $parametreler = array_filter(
        $parametreler,
        static fn ($deger): bool => $deger !== '' && $deger !== null
    );

    return 'index.php' . ($parametreler !== [] ? '?' . http_build_query($parametreler) : '');
};

/* Haftanın seçkisi: izlenecekler arasından en yüksek puanlı kayıt. */
$seckiler = array_values(array_filter(
    $filmler,
    static fn (array $film): bool => (string) ($film['durum'] ?? '') === 'İzlenecek'
));

if ($seckiler === []) {
    $seckiler = $filmler;
}

usort($seckiler, static fn (array $a, array $b): int => ((float) ($b['puan'] ?? 0)) <=> ((float) ($a['puan'] ?? 0)));
$oneCikan = $seckiler[0] ?? null;

$izlendiYuzde   = yuzdeHesapla($sayilar['izlendi'], $sayilar['toplam']);
$izlenecekYuzde = yuzdeHesapla($sayilar['izlenecek'], $sayilar['toplam']);

/* İzleme geçmişine göre otomatik öneriler ve en çok sevilen türler. */
$oneriler      = filmOnerileri($filmler, 4);
$ilgiAlanlari  = ilgiAlanlari($filmler, 4);

$sayfaBaslik = 'CHNStream — Kişisel Film ve Dizi Arşivi';
$aktifSayfa  = 'arsiv';

require 'partials/header.php';
?>

<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow">
            <span class="eyebrow-dot" aria-hidden="true"></span>
            Kişisel Sinema Arşivi
        </span>

        <h1>
            İzlediğin her sahne,<br>
            <span class="text-gradient">arşivinde iz bırakır.</span>
        </h1>

        <p class="hero-desc">
            Film ve dizilerini tek bir yerde kaydet, puanla, filtrele ve izleme yolculuğunu takip et.
            Tüm kayıtların oturum boyunca güvenle saklanır.
        </p>

        <div class="hero-actions">
            <a href="ekle.php" class="btn btn-primary">
                <i class="fa-solid fa-circle-plus"></i>
                Filmi Arşive Ekle
            </a>
            <a href="kesfet.php" class="btn btn-ghost">
                <i class="fa-solid fa-compass"></i>
                Keşfet
            </a>
            <a href="rastgele.php" class="btn btn-ghost">
                <i class="fa-solid fa-shuffle"></i>
                Rastgele Film
            </a>
            <a href="#film-arsivi" class="btn btn-ghost">
                <i class="fa-solid fa-arrow-down"></i>
                Arşivi Görüntüle
            </a>
        </div>

        <div class="hero-meta" aria-label="Hızlı istatistikler">
            <div>
                <strong><?= (int) $sayilar['toplam'] ?></strong>
                <span>kayıt</span>
            </div>
            <span class="meta-divider" aria-hidden="true"></span>
            <div>
                <strong><?= (int) $sayilar['kategori'] ?></strong>
                <span>kategori</span>
            </div>
            <span class="meta-divider" aria-hidden="true"></span>
            <div>
                <strong><?= e(puanFormat($sayilar['ortalama'])) ?></strong>
                <span>ortalama puan</span>
            </div>
            <span class="meta-divider" aria-hidden="true"></span>
            <div>
                <strong><?= e(puanFormat($sayilar['imdb_ortalama'])) ?></strong>
                <span>IMDb ortalaması</span>
            </div>
            <span class="meta-divider" aria-hidden="true"></span>
            <div>
                <strong><?= favoriSayisi() ?></strong>
                <span>favori</span>
            </div>
        </div>
    </div>

    <?php if ($oneCikan !== null): ?>
        <aside class="hero-feature" aria-label="Haftanın seçkisi">
            <div class="feature-label">
                <i class="fa-solid fa-crown"></i>
                Haftanın Seçkisi
            </div>

            <a href="detay.php?id=<?= (int) $oneCikan['id'] ?>" class="feature-card">
                <div class="feature-poster <?= e(posterSinifi((string) $oneCikan['kategori'])) ?>">
                    <div class="poster-fallback" aria-hidden="true">
                        <span><?= e(filmBasHarfleri((string) $oneCikan['baslik'])) ?></span>
                        <i class="fa-solid fa-film"></i>
                    </div>

                    <?php if ((string) ($oneCikan['poster'] ?? '') !== ''): ?>
                        <img src="<?= e((string) $oneCikan['poster']) ?>" alt="<?= e((string) $oneCikan['baslik']) ?> afişi" loading="lazy" data-poster-img>
                    <?php endif; ?>

                    <span class="feature-rating">
                        <i class="fa-solid fa-star"></i>
                        <?= e(puanFormat((float) $oneCikan['puan'])) ?>
                    </span>

                    <span class="imdb-pill feature-imdb">
                        <span>IMDb</span>
                        <strong><?= e(puanFormat(filmImdbPuan($oneCikan))) ?></strong>
                    </span>
                </div>

                <div class="feature-info">
                    <span class="movie-category"><?= e((string) $oneCikan['kategori']) ?></span>
                    <h2><?= e((string) $oneCikan['baslik']) ?></h2>
                    <p><?= e((string) $oneCikan['yonetmen']) ?> · <?= (int) $oneCikan['yil'] ?></p>
                    <span class="feature-link">
                        Detayları gör <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>
            </a>
        </aside>
    <?php endif; ?>
</section>

<section class="stats-grid" id="istatistikler" aria-label="Arşiv özeti">
    <article class="stat-card stat-card-total">
        <div class="stat-icon stat-icon-red"><i class="fa-solid fa-clapperboard"></i></div>
        <div class="stat-content">
            <span class="stat-value" data-counter="<?= (int) $sayilar['toplam'] ?>"><?= (int) $sayilar['toplam'] ?></span>
            <span class="stat-label">Toplam Kayıt</span>
        </div>
        <span class="stat-trend"><?= (int) $sayilar['kategori'] ?> kategori</span>
    </article>

    <article class="stat-card stat-card-watched">
        <div class="stat-icon stat-icon-green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-content">
            <span class="stat-value" data-counter="<?= (int) $sayilar['izlendi'] ?>"><?= (int) $sayilar['izlendi'] ?></span>
            <span class="stat-label">İzlenen</span>
        </div>
        <div class="stat-progress" title="<?= e((string) $izlendiYuzde) ?>% tamamlandı">
            <span style="--progress: <?= e((string) $izlendiYuzde) ?>%"></span>
        </div>
        <span class="stat-trend">%<?= e((string) $izlendiYuzde) ?></span>
    </article>

    <article class="stat-card stat-card-watchlist">
        <div class="stat-icon stat-icon-orange"><i class="fa-solid fa-bookmark"></i></div>
        <div class="stat-content">
            <span class="stat-value" data-counter="<?= (int) $sayilar['izlenecek'] ?>"><?= (int) $sayilar['izlenecek'] ?></span>
            <span class="stat-label">İzlenecek</span>
        </div>
        <div class="stat-progress" title="<?= e((string) $izlenecekYuzde) ?>% listende">
            <span style="--progress: <?= e((string) $izlenecekYuzde) ?>%"></span>
        </div>
        <span class="stat-trend">%<?= e((string) $izlenecekYuzde) ?></span>
    </article>

    <article class="stat-card stat-card-rating">
        <div class="stat-icon stat-icon-gold"><i class="fa-solid fa-star"></i></div>
        <div class="stat-content">
            <span class="stat-value" data-counter="<?= e(puanFormat($sayilar['ortalama'])) ?>" data-decimals="1"><?= e(puanFormat($sayilar['ortalama'])) ?></span>
            <span class="stat-label">Ortalama Puan</span>
        </div>
        <span class="stat-trend">IMDb <?= e(puanFormat($sayilar['imdb_ortalama'])) ?> · <?= (int) $sayilar['yorum'] ?> yorum</span>
    </article>
</section>

<?php if ($oneriler !== []): ?>
    <section class="catalog recommend-section" id="oneriler" aria-labelledby="oneriler-baslik">
        <div class="section-head">
            <div>
                <span class="section-kicker"><i class="fa-solid fa-wand-magic-sparkles"></i> Zevkine göre</span>
                <h2 id="oneriler-baslik">
                    Senin İçin Öneriler
                    <span class="count-pill"><?= count($oneriler) ?> öneri</span>
                </h2>
                <p class="section-desc">
                    İzlediğin ve puanladığın filmlere göre seçildi. Yeni bir film eklediğinde
                    öneriler otomatik olarak yenilenir.
                </p>
                <?php if ($ilgiAlanlari !== []): ?>
                    <div class="taste-chips" aria-label="İlgi alanların">
                        <span class="taste-label"><i class="fa-solid fa-heart"></i> İlgi alanların:</span>
                        <?php foreach ($ilgiAlanlari as $tur): ?>
                            <span class="taste-chip"><?= e($tur) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <a href="<?= e($filtreUrl(['durum' => 'İzlenecek', 'sirala' => 'imdb'])) ?>" class="btn btn-ghost btn-sm">
                İzleme Listem <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="movie-grid">
            <?php foreach ($oneriler as $film): ?>
                <?php require 'partials/movie_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="filter-panel" aria-label="Arşiv filtreleri">
    <form method="GET" action="index.php" class="filter-form" data-filter-form>
        <div class="field field-search">
            <label for="q" class="sr-only">Film ara</label>
            <i class="fa-solid fa-magnifying-glass field-icon" aria-hidden="true"></i>
            <input type="search" id="q" name="q" value="<?= e($arama) ?>"
                   placeholder="Film, yönetmen, ülke veya etiket ara..."
                   autocomplete="off" data-search-input>
            <kbd class="search-kbd" aria-hidden="true">/</kbd>
        </div>

        <div class="field">
            <label for="kategori" class="sr-only">Kategori</label>
            <i class="fa-solid fa-tags field-icon" aria-hidden="true"></i>
            <select id="kategori" name="kategori" data-auto-submit>
                <option value="">Tüm kategoriler</option>
                <?php foreach ($kategoriler as $kategoriSecenek): ?>
                    <option value="<?= e($kategoriSecenek) ?>" <?= $kategori === $kategoriSecenek ? 'selected' : '' ?>>
                        <?= e($kategoriSecenek) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="durum" class="sr-only">Durum</label>
            <i class="fa-solid fa-circle-half-stroke field-icon" aria-hidden="true"></i>
            <select id="durum" name="durum" data-auto-submit>
                <option value="">Tüm durumlar</option>
                <?php foreach (DURUMLAR as $durumSecenek): ?>
                    <option value="<?= e($durumSecenek) ?>" <?= $durum === $durumSecenek ? 'selected' : '' ?>>
                        <?= e($durumSecenek) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="sirala" class="sr-only">Sıralama</label>
            <i class="fa-solid fa-arrow-down-wide-short field-icon" aria-hidden="true"></i>
            <select id="sirala" name="sirala" data-auto-submit>
                <option value="yeni" <?= $sirala === 'yeni' ? 'selected' : '' ?>>En yeni eklenen</option>
                <option value="eski" <?= $sirala === 'eski' ? 'selected' : '' ?>>En eski eklenen</option>
                <option value="baslik" <?= $sirala === 'baslik' ? 'selected' : '' ?>>Başlığa göre (A–Z)</option>
                <option value="puan" <?= $sirala === 'puan' ? 'selected' : '' ?>>Kendi puanıma göre</option>
                <option value="imdb" <?= $sirala === 'imdb' ? 'selected' : '' ?>>IMDb puanına göre</option>
                <option value="yil" <?= $sirala === 'yil' ? 'selected' : '' ?>>Yıla göre (yeni → eski)</option>
                <option value="sure" <?= $sirala === 'sure' ? 'selected' : '' ?>>Süreye göre (uzun → kısa)</option>
                <option value="yorum" <?= $sirala === 'yorum' ? 'selected' : '' ?>>Yorum sayısına göre</option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-filter"></i> Filtrele
            </button>
            <?php if ($filtreAktif): ?>
                <a href="index.php" class="btn btn-ghost btn-icon" title="Filtreleri temizle" aria-label="Filtreleri temizle">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            <?php endif; ?>
        </div>

        <details class="advanced-filters" <?= $ileriFiltreAktif ? 'open' : '' ?>>
            <summary>
                <i class="fa-solid fa-sliders"></i>
                Gelişmiş filtreler
                <?php if ($ileriFiltreAktif): ?><span class="filter-dot" aria-hidden="true"></span><?php endif; ?>
            </summary>

            <div class="advanced-grid">
                <div class="field">
                    <label for="yil_min">En erken yıl</label>
                    <div class="input-wrap input-wrap-sm">
                        <i class="fa-solid fa-calendar" aria-hidden="true"></i>
                        <input type="number" id="yil_min" name="yil_min" min="1888" max="<?= (int) date('Y') + 2 ?>"
                               value="<?= $yilMin > 0 ? (int) $yilMin : '' ?>" placeholder="Örn. 1990" inputmode="numeric">
                    </div>
                </div>

                <div class="field">
                    <label for="yil_max">En geç yıl</label>
                    <div class="input-wrap input-wrap-sm">
                        <i class="fa-solid fa-calendar" aria-hidden="true"></i>
                        <input type="number" id="yil_max" name="yil_max" min="1888" max="<?= (int) date('Y') + 2 ?>"
                               value="<?= $yilMax > 0 ? (int) $yilMax : '' ?>" placeholder="Örn. 2024" inputmode="numeric">
                    </div>
                </div>

                <div class="field">
                    <label for="min_puan">En az puanın</label>
                    <div class="input-wrap input-wrap-sm">
                        <i class="fa-solid fa-star" aria-hidden="true"></i>
                        <input type="number" id="min_puan" name="min_puan" min="0" max="10" step="0.5"
                               value="<?= $minPuan > 0 ? e((string) $minPuan) : '' ?>" placeholder="Örn. 7" inputmode="decimal">
                    </div>
                </div>

                <div class="field">
                    <label for="min_imdb">En az IMDb</label>
                    <div class="input-wrap input-wrap-sm">
                        <i class="fa-brands fa-imdb" aria-hidden="true"></i>
                        <input type="number" id="min_imdb" name="min_imdb" min="0" max="10" step="0.5"
                               value="<?= $minImdb > 0 ? e((string) $minImdb) : '' ?>" placeholder="Örn. 7.5" inputmode="decimal">
                    </div>
                </div>

                <label class="toggle-check">
                    <input type="checkbox" name="favori" value="1" <?= $sadeceFavoriler ? 'checked' : '' ?>>
                    <span><i class="fa-solid fa-heart"></i> Sadece favoriler</span>
                </label>

                <label class="toggle-check">
                    <input type="checkbox" name="yorumlu" value="1" <?= $sadeceYorumlu ? 'checked' : '' ?>>
                    <span><i class="fa-regular fa-comment"></i> Sadece yorumlu</span>
                </label>

                <div class="advanced-actions">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check"></i> Uygula</button>
                    <a href="index.php" class="btn btn-ghost btn-sm">Sıfırla</a>
                </div>
            </div>
        </details>
    </form>

    <div class="quick-filters">
        <span class="quick-label">Hızlı filtre:</span>

        <a href="<?= e($filtreUrl(['durum' => ''])) ?>" class="chip <?= $durum === '' ? 'is-active' : '' ?>">
            Tümü <span><?= (int) $sayilar['toplam'] ?></span>
        </a>
        <a href="<?= e($filtreUrl(['durum' => 'İzlendi'])) ?>" class="chip chip-green <?= $durum === 'İzlendi' ? 'is-active' : '' ?>">
            <i class="fa-solid fa-circle-check"></i> İzlendi <span><?= (int) $sayilar['izlendi'] ?></span>
        </a>
        <a href="<?= e($filtreUrl(['durum' => 'İzlenecek'])) ?>" class="chip chip-orange <?= $durum === 'İzlenecek' ? 'is-active' : '' ?>">
            <i class="fa-solid fa-bookmark"></i> İzlenecek <span><?= (int) $sayilar['izlenecek'] ?></span>
        </a>
        <a href="<?= e($filtreUrl(['durum' => 'İzleniyor'])) ?>" class="chip chip-blue <?= $durum === 'İzleniyor' ? 'is-active' : '' ?>">
            <i class="fa-solid fa-circle-play"></i> İzleniyor <span><?= (int) $sayilar['izleniyor'] ?></span>
        </a>
        <a href="<?= e($filtreUrl(['favori' => $sadeceFavoriler ? '' : '1', 'sayfa' => ''])) ?>" class="chip chip-heart <?= $sadeceFavoriler ? 'is-active' : '' ?>">
            <i class="fa-solid fa-heart"></i> Favoriler <span><?= favoriSayisi() ?></span>
        </a>
    </div>
</section>

<section class="catalog" id="film-arsivi">
    <div class="section-head">
        <div>
            <span class="section-kicker"><i class="fa-solid fa-clapperboard"></i> Koleksiyon</span>
            <h2>
                Film Arşivi
                <span class="count-pill"><?= count($filtreli) ?> kayıt</span>
            </h2>
            <p class="section-desc">
                <?= $filtreAktif
                    ? 'Filtrelerine uyan kayıtlar gösteriliyor.'
                    : 'Tüm film ve dizilerin tek bir yerde, düzenli bir koleksiyonda.' ?>
            </p>
        </div>

        <div class="section-head-actions">
            <a href="disa-aktar.php" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-file-export"></i> Dışa Aktar
            </a>
            <div class="view-toggle" role="group" aria-label="Görünüm seçimi">
                <button type="button" class="view-btn is-active" data-view="grid" aria-label="Izgara görünümü" title="Izgara görünümü">
                    <i class="fa-solid fa-grip"></i>
                </button>
                <button type="button" class="view-btn" data-view="list" aria-label="Liste görünümü" title="Liste görünümü">
                    <i class="fa-solid fa-list"></i>
                </button>
            </div>
        </div>
    </div>

    <?php if ($sayfadakiler === []): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-film"></i></div>
            <h3><?= $sayilar['toplam'] === 0 ? 'Arşivin henüz boş' : 'Bu filtreye uygun kayıt yok' ?></h3>
            <p>
                <?= $sayilar['toplam'] === 0
                    ? 'İlk filmini ekleyerek kişisel sinema arşivini oluşturmaya başla.'
                    : 'Arama veya filtre ölçütlerini değiştirerek tekrar dene.' ?>
            </p>
            <div class="empty-actions">
                <a href="ekle.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Film Ekle</a>
                <?php if ($filtreAktif): ?>
                    <a href="index.php" class="btn btn-ghost"><i class="fa-solid fa-rotate-left"></i> Filtreleri Temizle</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="movie-grid" data-movie-grid>
            <?php foreach ($sayfadakiler as $film): ?>
                <?php require 'partials/movie_card.php'; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($sayfalama['sayfa_sayisi'] > 1): ?>
            <div class="pagination-wrap">
                <span class="pagination-info">
                    <?= $sayfalama['baslangic'] + 1 ?>–<?= $sayfalama['bitis'] ?> / <?= $sayfalama['toplam'] ?> kayıt
                </span>

                <nav class="pagination" aria-label="Sayfalar">
                    <a class="page-btn <?= $sayfalama['sayfa'] <= 1 ? 'is-disabled' : '' ?>"
                       href="<?= e($filtreUrl(['sayfa' => $sayfalama['onceki']])) ?>"
                       aria-label="Önceki sayfa"
                       <?= $sayfalama['sayfa'] <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    </a>

                    <?php for ($i = 1; $i <= $sayfalama['sayfa_sayisi']; $i++): ?>
                        <?php if ($i === 1 || $i === $sayfalama['sayfa_sayisi'] || abs($i - $sayfalama['sayfa']) <= 1): ?>
                            <a class="page-btn <?= $i === $sayfalama['sayfa'] ? 'is-active' : '' ?>"
                               href="<?= e($filtreUrl(['sayfa' => $i])) ?>"
                               <?= $i === $sayfalama['sayfa'] ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                        <?php elseif (abs($i - $sayfalama['sayfa']) === 2): ?>
                            <span class="page-gap">…</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <a class="page-btn <?= $sayfalama['sayfa'] >= $sayfalama['sayfa_sayisi'] ? 'is-disabled' : '' ?>"
                       href="<?= e($filtreUrl(['sayfa' => $sayfalama['sonraki']])) ?>"
                       aria-label="Sonraki sayfa"
                       <?= $sayfalama['sayfa'] >= $sayfalama['sayfa_sayisi'] ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </a>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require 'partials/footer.php'; ?>
