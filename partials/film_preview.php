<?php
/**
 * Formun sağ tarafındaki canlı önizleme ve ipuçları.
 *
 * Beklenen değişken: $formVeri
 */

$formVeri = $formVeri ?? [];

$baslikDegeri   = trim((string) ($formVeri['baslik'] ?? ''));
$kategoriDegeri = trim((string) ($formVeri['kategori'] ?? ''));
$yonetmenDegeri = trim((string) ($formVeri['yonetmen'] ?? ''));
$yilDegeri      = trim((string) ($formVeri['yil'] ?? ''));
$puanDegeri     = isset($formVeri['puan']) && $formVeri['puan'] !== '' ? (float) $formVeri['puan'] : VARSAYILAN_PUAN;
$imdbDegeri     = isset($formVeri['imdb']) && $formVeri['imdb'] !== '' ? (float) $formVeri['imdb'] : VARSAYILAN_IMDB;
$durumDegeri    = (string) ($formVeri['durum'] ?? 'İzlenecek');
$posterDegeri   = trim((string) ($formVeri['poster'] ?? ''));

$onizlemeBaslik   = $baslikDegeri !== '' ? $baslikDegeri : 'Film adı burada görünecek';
$onizlemeKategori = $kategoriDegeri !== '' ? $kategoriDegeri : 'Kategori';
$onizlemeMeta     = ($yonetmenDegeri !== '' ? $yonetmenDegeri : 'Yönetmen') . ' · ' . ($yilDegeri !== '' ? $yilDegeri : 'Yıl');
?>
<aside class="form-side">
    <div class="panel preview-panel">
        <div class="panel-head panel-head-compact">
            <div>
                <span class="eyebrow eyebrow-sm">Canlı Önizleme</span>
                <h2>Kartın nasıl görünecek?</h2>
            </div>
            <span class="live-dot" title="Canlı güncelleniyor" aria-hidden="true"></span>
        </div>

        <div class="preview-card">
            <div class="preview-poster <?= e(posterSinifi($onizlemeKategori)) ?>" data-preview-poster>
                <?php if ($posterDegeri !== ''): ?>
                    <img src="<?= e($posterDegeri) ?>" alt="Afiş önizlemesi" data-preview-img>
                <?php else: ?>
                    <img alt="" data-preview-img hidden>
                <?php endif; ?>

                <span class="preview-initials" data-preview-initials><?= e(filmBasHarfleri($onizlemeBaslik)) ?></span>

                <span class="status-badge status-<?= e(durumSinifi($durumDegeri)) ?>" data-preview-status>
                    <i class="fa-solid <?= e(durumIkonu($durumDegeri)) ?>" data-preview-status-icon></i>
                    <span data-preview-status-text><?= e($durumDegeri) ?></span>
                </span>

                <span class="preview-rating">
                    <i class="fa-solid fa-star"></i>
                    <span data-preview-rating><?= e(puanFormat($puanDegeri)) ?></span>
                </span>

                <span class="imdb-pill preview-imdb" title="IMDb puanı">
                    <span>IMDb</span>
                    <strong data-preview-imdb><?= e(puanFormat($imdbDegeri)) ?></strong>
                </span>
            </div>

            <div class="preview-body">
                <div class="movie-category-row">
                    <span class="movie-category" data-preview-category><?= e($onizlemeKategori) ?></span>
                    <span class="imdb-pill" title="IMDb puanı">
                        <span>IMDb</span>
                        <strong data-preview-imdb><?= e(puanFormat($imdbDegeri)) ?></strong>
                    </span>
                </div>
                <h3 data-preview-title><?= e($onizlemeBaslik) ?></h3>
                <p data-preview-meta><?= e($onizlemeMeta) ?></p>
            </div>
        </div>

        <p class="preview-note">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
            Formu doldurdukça bu kart canlı olarak güncellenir.
        </p>
    </div>

    <div class="panel tips-panel">
        <h2><i class="fa-solid fa-lightbulb"></i> İyi bir arşiv kaydı için</h2>
        <ul class="tips-list">
            <li><i class="fa-solid fa-check"></i> Film adını orijinal yazımıyla gir.</li>
            <li><i class="fa-solid fa-check"></i> Kategoriyi tutarlı kullan; filtreleme buna göre çalışır.</li>
            <li><i class="fa-solid fa-check"></i> Açıklamaya kısa bir kişisel not ekle.</li>
            <li><i class="fa-solid fa-check"></i> Afiş adresi boş kalırsa otomatik kapak oluşur.</li>
            <li><i class="fa-solid fa-check"></i> Durumu daha sonra kart üzerinden tek tıkla değiştirebilirsin.</li>
        </ul>
    </div>
</aside>
