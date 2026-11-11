<?php
/**
 * Tek bir film kartı. index.php, keşfet, liste ve favori sayfalarında döngüyle kullanılır.
 *
 * Beklenen değişken: $film (dizi)
 */
if (empty($film) || !is_array($film)) {
    return;
}

$filmId       = (int) ($film['id'] ?? 0);
$filmBaslik   = (string) ($film['baslik'] ?? 'İsimsiz Kayıt');
$filmKategori = (string) ($film['kategori'] ?? 'Genel');
$filmDurum    = (string) ($film['durum'] ?? 'İzlenecek');
$filmPoster   = (string) ($film['poster'] ?? '');
$filmPuan     = (float) ($film['puan'] ?? 0);
$filmYil      = (int) ($film['yil'] ?? 0);
$filmYonetmen = (string) ($film['yonetmen'] ?? '—');
$filmAciklama = (string) ($film['aciklama'] ?? '');
$filmEklenme  = (string) ($film['eklenme'] ?? '');
$filmSure     = (int) ($film['sure'] ?? 0);
$filmUlke     = (string) ($film['ulke'] ?? '');
$filmEtiket   = etiketleriAyir($film['etiketler'] ?? '');
$filmImdb     = filmImdbPuan($film);
$filmYorum    = yorumSayisi($filmId);
$filmFavori   = favoriMi($filmId);
$geriDonus    = (string) ($_SERVER['REQUEST_URI'] ?? 'index.php');
?>
<article class="movie-card <?= $filmFavori ? 'is-favorite' : '' ?>" data-film-card>
    <div class="movie-poster <?= e(posterSinifi($filmKategori)) ?>">
        <div class="poster-fallback" aria-hidden="true">
            <span><?= e(filmBasHarfleri($filmBaslik)) ?></span>
            <i class="fa-solid fa-film"></i>
        </div>

        <?php if ($filmPoster !== ''): ?>
            <img src="<?= e($filmPoster) ?>" alt="<?= e($filmBaslik) ?> afişi" loading="lazy" data-poster-img>
        <?php endif; ?>

        <div class="poster-shade" aria-hidden="true"></div>

        <div class="poster-top">
            <span class="status-badge status-<?= e(durumSinifi($filmDurum)) ?>">
                <i class="fa-solid <?= e(durumIkonu($filmDurum)) ?>"></i>
                <?= e($filmDurum) ?>
            </span>
            <span class="rating-badge" title="Puan: <?= e(puanFormat($filmPuan)) ?> / 10">
                <i class="fa-solid fa-star"></i> <?= e(puanFormat($filmPuan)) ?>
            </span>
        </div>

        <form method="POST" action="favori.php" class="favorite-form">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= $filmId ?>">
            <input type="hidden" name="yonlendir" value="<?= e($geriDonus) ?>">
            <button type="submit" class="favorite-btn <?= $filmFavori ? 'is-active' : '' ?>"
                    aria-pressed="<?= $filmFavori ? 'true' : 'false' ?>"
                    title="<?= $filmFavori ? 'Favorilerden çıkar' : 'Favorilere ekle' ?>"
                    aria-label="<?= e($filmBaslik) ?> filmini favorilere <?= $filmFavori ? 'çıkar' : 'ekle' ?>">
                <i class="<?= $filmFavori ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
            </button>
        </form>

        <div class="poster-actions">
            <a href="detay.php?id=<?= $filmId ?>" class="poster-btn" title="Detayları görüntüle" aria-label="<?= e($filmBaslik) ?> detayları">
                <i class="fa-solid fa-expand"></i>
            </a>
            <a href="duzenle.php?id=<?= $filmId ?>" class="poster-btn" title="Kaydı düzenle" aria-label="<?= e($filmBaslik) ?> kaydını düzenle">
                <i class="fa-solid fa-pen"></i>
            </a>

            <form method="POST" action="durum.php" class="inline-form">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= $filmId ?>">
                <input type="hidden" name="yonlendir" value="<?= e($geriDonus) ?>">
                <button type="submit" class="poster-btn" title="Durumu değiştir" aria-label="<?= e($filmBaslik) ?> durumunu değiştir">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </form>

            <form method="POST" action="sil.php" class="inline-form js-delete-form" data-film-title="<?= e($filmBaslik) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= $filmId ?>">
                <input type="hidden" name="yonlendir" value="<?= e($geriDonus) ?>">
                <button type="submit" class="poster-btn poster-btn-danger" title="Kaydı sil" aria-label="<?= e($filmBaslik) ?> kaydını sil">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </form>
        </div>

        <span class="poster-number" aria-hidden="true">#<?= $filmId ?></span>
    </div>

    <div class="movie-body">
        <div class="movie-category-row">
            <span class="movie-category"><?= e($filmKategori) ?></span>
            <span class="imdb-pill" title="IMDb puanı">
                <span>IMDb</span>
                <strong><?= e(puanFormat($filmImdb)) ?></strong>
            </span>
        </div>

        <h3 class="movie-title">
            <a href="detay.php?id=<?= $filmId ?>"><?= e($filmBaslik) ?></a>
        </h3>

        <p class="movie-meta">
            <span><i class="fa-solid fa-user-tie"></i> <?= e($filmYonetmen) ?></span>
            <span class="dot" aria-hidden="true"></span>
            <span><i class="fa-solid fa-calendar"></i> <?= $filmYil ?></span>
            <?php if ($filmSure > 0): ?>
                <span class="dot" aria-hidden="true"></span>
                <span><i class="fa-regular fa-clock"></i> <?= e(sureFormat($filmSure)) ?></span>
            <?php endif; ?>
            <?php if ($filmUlke !== ''): ?>
                <span class="dot" aria-hidden="true"></span>
                <span><i class="fa-solid fa-earth-europe"></i> <?= e($filmUlke) ?></span>
            <?php endif; ?>
        </p>

        <p class="movie-desc"><?= e($filmAciklama) ?></p>

        <?php if ($filmEtiket !== []): ?>
            <div class="movie-tags" aria-label="Etiketler">
                <?php foreach ($filmEtiket as $etiket): ?>
                    <span class="movie-tag">#<?= e($etiket) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="movie-footer">
            <span class="movie-date">
                <i class="fa-regular fa-clock"></i> <?= e(tarihFormat($filmEklenme)) ?>
                <span class="movie-comments" title="<?= $filmYorum ?> yorum">
                    <i class="fa-regular fa-comment"></i> <?= $filmYorum ?>
                </span>
            </span>
            <a href="detay.php?id=<?= $filmId ?>" class="text-link">
                İncele <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</article>
