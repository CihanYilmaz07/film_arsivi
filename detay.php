<?php
require_once 'functions.php';
girisGerekli();

$id   = (int) ($_GET['id'] ?? 0);
$film = filmBul($_SESSION['filmler'], $id);

if ($film === null) {
    flashAyarla('hata', 'Görüntülemek istediğin kayıt bulunamadı. Silinmiş olabilir.');
    yonlendir('index.php');
}

$benzer = benzerFilmler($_SESSION['filmler'], $id, 3);
$oneriler = filmOnerileri($_SESSION['filmler'], 4, $id);

$filmBaslik   = (string) $film['baslik'];
$filmKategori = (string) $film['kategori'];
$filmDurum    = (string) $film['durum'];
$filmPoster   = (string) ($film['poster'] ?? '');
$filmPuan     = (float) ($film['puan'] ?? 0);
$filmImdb     = filmImdbPuan($film);
$filmYil      = (int) ($film['yil'] ?? 0);
$filmYonetmen = (string) ($film['yonetmen'] ?? '—');
$filmAciklama = (string) ($film['aciklama'] ?? '');
$filmKonu     = trim((string) ($film['konu'] ?? ''));
$filmEklenme  = (string) ($film['eklenme'] ?? '');
$filmSure     = (int) ($film['sure'] ?? 0);
$filmUlke     = (string) ($film['ulke'] ?? '');
$filmEtiket   = etiketleriAyir($film['etiketler'] ?? '');
$filmFavori   = favoriMi($id);
$geriDonus    = 'detay.php?id=' . $id;

if ($filmKonu === '') {
    $filmKonu = $filmAciklama;
}

$yorumlar       = filmYorumlari($id);
$yorumSayisi    = count($yorumlar);
$toplulukPuani  = yorumOrtalamasi($yorumlar);
$kullaniciPuani = max(1, min(10, (int) round($filmPuan)));

$sayfaBaslik = $filmBaslik . ' — CHNStream';
$aktifSayfa  = 'arsiv';

require 'partials/header.php';
?>

<section class="detail-hero">
    <div class="detail-poster-wrap">
        <div class="detail-poster <?= e(posterSinifi($filmKategori)) ?>">
            <div class="poster-fallback" aria-hidden="true">
                <span><?= e(filmBasHarfleri($filmBaslik)) ?></span>
                <i class="fa-solid fa-film"></i>
            </div>

            <?php if ($filmPoster !== ''): ?>
                <img src="<?= e($filmPoster) ?>" alt="<?= e($filmBaslik) ?> afişi" data-poster-img>
            <?php endif; ?>

            <span class="detail-poster-rating">
                <i class="fa-solid fa-star"></i> <?= e(puanFormat($filmPuan)) ?>
            </span>
        </div>
    </div>

    <div class="detail-content">
        <a href="index.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Arşive dön
        </a>

        <div class="detail-badges">
            <span class="status-badge status-<?= e(durumSinifi($filmDurum)) ?>">
                <i class="fa-solid <?= e(durumIkonu($filmDurum)) ?>"></i>
                <?= e($filmDurum) ?>
            </span>
            <span class="movie-category"><?= e($filmKategori) ?></span>
            <span class="imdb-pill" title="IMDb puanı">
                <span>IMDb</span>
                <strong><?= e(puanFormat($filmImdb)) ?></strong>
            </span>
            <span class="detail-id">#<?= $id ?></span>
        </div>

        <h1><?= e($filmBaslik) ?></h1>

        <p class="detail-meta">
            <span><i class="fa-solid fa-user-tie"></i> <?= e($filmYonetmen) ?></span>
            <span><i class="fa-solid fa-calendar"></i> <?= $filmYil ?></span>
            <?php if ($filmSure > 0): ?>
                <span><i class="fa-regular fa-clock"></i> <?= e(sureFormat($filmSure)) ?></span>
            <?php endif; ?>
            <?php if ($filmUlke !== ''): ?>
                <span><i class="fa-solid fa-earth-europe"></i> <?= e($filmUlke) ?></span>
            <?php endif; ?>
            <span><i class="fa-solid fa-star"></i> <?= e(puanFormat($filmPuan)) ?> / 10</span>
            <span><i class="fa-brands fa-imdb"></i> <?= e(puanFormat($filmImdb)) ?> / 10</span>
        </p>

        <p class="detail-desc"><?= nl2br(e($filmAciklama)) ?></p>

        <?php if ($filmEtiket !== []): ?>
            <div class="movie-tags detail-tags" aria-label="Etiketler">
                <?php foreach ($filmEtiket as $etiket): ?>
                    <span class="movie-tag">#<?= e($etiket) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="detail-actions">
            <form method="POST" action="favori.php" class="inline-form">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="yonlendir" value="<?= e($geriDonus) ?>">
                <button type="submit" class="btn <?= $filmFavori ? 'btn-favori-active' : 'btn-ghost' ?>">
                    <i class="<?= $filmFavori ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                    <?= $filmFavori ? 'Favorilerden Çıkar' : 'Favorilere Ekle' ?>
                </button>
            </form>

            <form method="POST" action="durum.php" class="inline-form">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="yonlendir" value="<?= e($geriDonus) ?>">
                <button type="submit" class="btn btn-status">
                    <i class="fa-solid fa-arrows-rotate"></i> Durumu Değiştir
                </button>
            </form>

            <a href="duzenle.php?id=<?= $id ?>" class="btn btn-primary">
                <i class="fa-solid fa-pen"></i> Düzenle
            </a>

            <form method="POST" action="sil.php" class="inline-form js-delete-form" data-film-title="<?= e($filmBaslik) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="yonlendir" value="index.php">
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-trash-can"></i> Sil
                </button>
            </form>
        </div>

        <dl class="detail-info-grid">
            <div>
                <dt>Kategori</dt>
                <dd><?= e($filmKategori) ?></dd>
            </div>
            <div>
                <dt>Durum</dt>
                <dd><?= e($filmDurum) ?></dd>
            </div>
            <div>
                <dt>Yayın Yılı</dt>
                <dd><?= $filmYil ?></dd>
            </div>
            <?php if ($filmSure > 0): ?>
                <div>
                    <dt>Süre</dt>
                    <dd><?= e(sureFormat($filmSure)) ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($filmUlke !== ''): ?>
                <div>
                    <dt>Ülke</dt>
                    <dd><?= e($filmUlke) ?></dd>
                </div>
            <?php endif; ?>
            <div>
                <dt>Kendi Puanın</dt>
                <dd><?= e(puanFormat($filmPuan)) ?> / 10</dd>
            </div>
            <div>
                <dt>IMDb Puanı</dt>
                <dd><?= e(puanFormat($filmImdb)) ?> / 10</dd>
            </div>
            <div>
                <dt>Topluluk Puanı</dt>
                <dd><?= $toplulukPuani > 0 ? e(puanFormat($toplulukPuani)) . ' / 10' : 'Henüz yok' ?></dd>
            </div>
            <div>
                <dt>Yorumlar</dt>
                <dd><?= $yorumSayisi ?> yorum</dd>
            </div>
            <div>
                <dt>Eklenme Tarihi</dt>
                <dd><?= e(tarihFormat($filmEklenme)) ?></dd>
            </div>
        </dl>
    </div>
</section>

<section class="detail-section rating-panel" id="puanla" aria-labelledby="puanla-baslik">
    <div class="detail-section-head">
        <div>
            <span class="section-kicker"><i class="fa-solid fa-star"></i> Senin puanın</span>
            <h2 id="puanla-baslik">Filmi Puanla</h2>
            <p>Yıldızlara tıklayarak 1 ile 10 arasında puan ver. Puanın arşivde ve öneri motorunda anında kullanılır.</p>
        </div>
        <div class="rating-summary">
            <span class="imdb-pill" title="IMDb puanı"><span>IMDb</span><strong><?= e(puanFormat($filmImdb)) ?></strong></span>
            <?php if ($toplulukPuani > 0): ?>
                <span class="community-pill"><i class="fa-solid fa-users"></i> Topluluk <?= e(puanFormat($toplulukPuani)) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <form method="POST" action="puanla.php" class="rating-form" data-rating-form>
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="star-rating" data-star-rating role="group" aria-label="Film puanı">
            <?php for ($yildiz = 1; $yildiz <= 10; $yildiz++): ?>
                <label class="star-rating-item <?= $yildiz <= $kullaniciPuani ? 'is-filled' : '' ?>"
                       data-value="<?= $yildiz ?>" title="<?= $yildiz ?> puan">
                    <input type="radio" name="puan" value="<?= $yildiz ?>" <?= $yildiz === $kullaniciPuani ? 'checked' : '' ?>>
                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                    <span class="sr-only"><?= $yildiz ?> puan</span>
                </label>
            <?php endfor; ?>
        </div>

        <div class="rating-form-meta">
            <span class="rating-current">
                Seçtiğin puan: <strong data-rating-label><?= e(puanFormat((float) $kullaniciPuani)) ?></strong> / 10
            </span>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Puanı Kaydet
            </button>
        </div>
    </form>
</section>

<section class="detail-section plot-panel" aria-labelledby="konu-baslik">
    <div class="detail-section-head">
        <div>
            <span class="section-kicker"><i class="fa-solid fa-book-open"></i> Hikâye</span>
            <h2 id="konu-baslik">Filmin Konusu</h2>
            <p>Filmin olay örgüsü, karakterleri ve merkezindeki çatışma.</p>
        </div>
        <span class="detail-id"><?= e($filmKategori) ?></span>
    </div>

    <p class="plot-text"><?= nl2br(e($filmKonu)) ?></p>
</section>

<section class="comments-section" id="yorumlar" aria-labelledby="yorumlar-baslik">
    <div class="section-head">
        <div>
            <span class="section-kicker"><i class="fa-solid fa-comments"></i> Topluluk</span>
            <h2 id="yorumlar-baslik">
                İzleyici Yorumları
                <span class="count-pill"><?= $yorumSayisi ?> yorum</span>
            </h2>
            <p class="section-desc">
                Bu film hakkında diğer izleyicilerin düşüncelerini oku, kendi yorumunu paylaş.
            </p>
        </div>
        <?php if ($toplulukPuani > 0): ?>
            <div class="community-score">
                <span class="community-score-value"><?= e(puanFormat($toplulukPuani)) ?></span>
                <span class="community-score-label">topluluk ortalaması</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="comments-layout">
        <div class="comments-list">
            <?php if ($yorumlar === []): ?>
                <div class="empty-state empty-comments">
                    <div class="empty-icon"><i class="fa-regular fa-comment-dots"></i></div>
                    <h3>İlk yorumu sen yaz</h3>
                    <p>Bu film için henüz yorum yapılmamış. Düşüncelerini paylaşarak topluluğu başlat.</p>
                </div>
            <?php else: ?>
                <?php foreach ($yorumlar as $yorum): ?>
                    <?php
                    $yorumAdi   = (string) ($yorum['ad'] ?? 'İzleyici');
                    $yorumMetni = (string) ($yorum['yorum'] ?? '');
                    $yorumPuani = isset($yorum['puan']) && is_numeric($yorum['puan']) ? (float) $yorum['puan'] : null;
                    $yorumTarih = (string) ($yorum['tarih'] ?? '');
                    $yorumDemo  = (bool) ($yorum['demo'] ?? false);
                    ?>
                    <article class="comment-card">
                        <span class="comment-avatar" aria-hidden="true"><?= e(isimBasHarfleri($yorumAdi)) ?></span>
                        <div class="comment-body">
                            <div class="comment-head">
                                <div class="comment-author-wrap">
                                    <strong class="comment-author"><?= e($yorumAdi) ?></strong>
                                    <span class="comment-tag <?= $yorumDemo ? 'comment-tag-demo' : 'comment-tag-user' ?>">
                                        <?= $yorumDemo ? 'Topluluk' : 'Yeni' ?>
                                    </span>
                                </div>
                                <time class="comment-date" datetime="<?= e($yorumTarih) ?>">
                                    <?= e(tarihFormat($yorumTarih)) ?>
                                </time>
                            </div>

                            <?php if ($yorumPuani !== null): ?>
                                <div class="comment-stars" aria-label="<?= e(puanFormat($yorumPuani)) ?> / 10 puan">
                                    <i class="fa-solid fa-star"></i>
                                    <strong><?= e(puanFormat($yorumPuani)) ?></strong>
                                    <span>/ 10</span>
                                </div>
                            <?php endif; ?>

                            <p class="comment-text"><?= nl2br(e($yorumMetni)) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <aside class="panel comment-form-panel">
            <div class="panel-head panel-head-compact">
                <div>
                    <span class="eyebrow eyebrow-sm">Sen de katıl</span>
                    <h2>Yorumunu Paylaş</h2>
                </div>
                <span class="panel-badge"><i class="fa-solid fa-comment"></i> Topluluk</span>
            </div>

            <form method="POST" action="yorum.php" class="comment-form">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="field">
                    <label for="yorum-ad">Görünen Ad <span class="required">*</span></label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                        <input type="text" id="yorum-ad" name="ad" maxlength="40"
                               placeholder="Örn. Elif K." autocomplete="name" required>
                    </div>
                </div>

                <div class="field">
                    <label for="yorum-puan">Puanın <small>(isteğe bağlı)</small></label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-star" aria-hidden="true"></i>
                        <select id="yorum-puan" name="puan">
                            <option value="">Puan vermek istemiyorum</option>
                            <?php for ($puanSecenek = 10; $puanSecenek >= 1; $puanSecenek--): ?>
                                <option value="<?= $puanSecenek ?>"><?= $puanSecenek ?> / 10</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label for="yorum-metin">Yorumun <span class="required">*</span></label>
                    <textarea id="yorum-metin" name="yorum" rows="5" maxlength="600"
                              placeholder="Film hakkındaki düşüncelerini paylaş..."
                              data-comment-input required></textarea>
                    <div class="field-hint field-hint-between">
                        <span>En az 5 karakter</span>
                        <span data-comment-count>0 / 600</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg comment-submit">
                    <i class="fa-solid fa-paper-plane"></i> Yorumu Gönder
                </button>
            </form>
        </aside>
    </div>
</section>

<?php if ($benzer !== []): ?>
    <section class="catalog similar-section" aria-labelledby="benzer-baslik">
        <div class="section-head">
            <div>
                <span class="section-kicker"><i class="fa-solid fa-clone"></i> Aynı türden</span>
                <h2 id="benzer-baslik">
                    Bu Filme Benzer
                    <span class="count-pill"><?= count($benzer) ?> öneri</span>
                </h2>
                <p class="section-desc">Aynı kategoriden ve yüksek puanlı diğer kayıtlar.</p>
            </div>

            <a href="index.php" class="btn btn-ghost btn-sm">
                Tüm Arşiv <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="movie-grid">
            <?php foreach ($benzer as $film): ?>
                <?php require 'partials/movie_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($oneriler !== []): ?>
    <section class="catalog similar-section" aria-labelledby="oneriler-detay-baslik">
        <div class="section-head">
            <div>
                <span class="section-kicker"><i class="fa-solid fa-wand-magic-sparkles"></i> Zevkine göre</span>
                <h2 id="oneriler-detay-baslik">
                    Sana Özel Öneriler
                    <span class="count-pill"><?= count($oneriler) ?> öneri</span>
                </h2>
                <p class="section-desc">İzleme geçmişin ve puanlarına göre otomatik seçildi.</p>
            </div>

            <a href="index.php#oneriler" class="btn btn-ghost btn-sm">
                Tüm Öneriler <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="movie-grid">
            <?php foreach ($oneriler as $film): ?>
                <?php require 'partials/movie_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php require 'partials/footer.php'; ?>
