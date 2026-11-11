<?php
/**
 * Film ekleme / düzenleme formu.
 *
 * Beklenen değişkenler:
 * $formVeri, $hatalar, $kategoriler, $formAksiyon, $butonMetni, $iptalUrl, $filmId (opsiyonel)
 */

$formVeri    = $formVeri ?? [];
$hatalar     = $hatalar ?? [];
$kategoriler = $kategoriler ?? [];
$formAksiyon = $formAksiyon ?? 'ekle.php';
$butonMetni  = $butonMetni ?? 'Filmi Arşive Kaydet';
$iptalUrl    = $iptalUrl ?? 'index.php';
$filmId      = $filmId ?? null;

$baslikDegeri   = (string) ($formVeri['baslik'] ?? '');
$kategoriDegeri = (string) ($formVeri['kategori'] ?? '');
$yonetmenDegeri = (string) ($formVeri['yonetmen'] ?? '');
$yilDegeri      = (string) ($formVeri['yil'] ?? '');
$puanDegeri     = isset($formVeri['puan']) && $formVeri['puan'] !== '' ? (float) $formVeri['puan'] : VARSAYILAN_PUAN;
$imdbDegeri     = isset($formVeri['imdb']) && $formVeri['imdb'] !== '' ? (float) $formVeri['imdb'] : VARSAYILAN_IMDB;
$aciklamaDegeri = (string) ($formVeri['aciklama'] ?? '');
$konuDegeri     = (string) ($formVeri['konu'] ?? '');
$durumDegeri    = (string) ($formVeri['durum'] ?? 'İzlenecek');
$posterDegeri   = (string) ($formVeri['poster'] ?? '');
$sureDegeri     = (string) ($formVeri['sure'] ?? '');
$ulkeDegeri     = (string) ($formVeri['ulke'] ?? '');
$etiketDegeri   = (string) ($formVeri['etiketler'] ?? '');
$posterYerel    = str_starts_with($posterDegeri, POSTER_URL_ONEKI);
?>
<form action="<?= e($formAksiyon) ?>" method="POST" enctype="multipart/form-data" class="film-form" data-film-form>
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <?php if ($filmId !== null): ?>
        <input type="hidden" name="id" value="<?= (int) $filmId ?>">
    <?php endif; ?>

    <?php if ($hatalar !== []): ?>
        <div class="alert alert-error" role="alert">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Formda düzeltilmesi gereken alanlar var.</strong>
                <span>Aşağıdaki uyarıları kontrol edip tekrar deneyin.</span>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-grid">
        <div class="field <?= isset($hatalar['baslik']) ? 'has-error' : '' ?>">
            <label for="baslik">Film / Dizi Adı <span class="required">*</span></label>
            <div class="input-wrap">
                <i class="fa-solid fa-heading" aria-hidden="true"></i>
                <input type="text" id="baslik" name="baslik" value="<?= e($baslikDegeri) ?>"
                       placeholder="Örn. Interstellar" maxlength="120" autocomplete="off" required>
            </div>
            <?php if (isset($hatalar['baslik'])): ?>
                <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['baslik']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($hatalar['kategori']) ? 'has-error' : '' ?>">
            <label for="kategori">Kategori / Tür <span class="required">*</span></label>
            <div class="input-wrap">
                <i class="fa-solid fa-tags" aria-hidden="true"></i>
                <input type="text" id="kategori" name="kategori" value="<?= e($kategoriDegeri) ?>"
                       placeholder="Örn. Bilim Kurgu" maxlength="60" list="kategori-listesi" autocomplete="off" required>
            </div>
            <datalist id="kategori-listesi">
                <?php foreach ($kategoriler as $kategori): ?>
                    <option value="<?= e($kategori) ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <small class="field-hint">Mevcut bir tür seçebilir veya yeni bir tür yazabilirsin.</small>
            <?php if (isset($hatalar['kategori'])): ?>
                <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['kategori']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($hatalar['yonetmen']) ? 'has-error' : '' ?>">
            <label for="yonetmen">Yönetmen <span class="required">*</span></label>
            <div class="input-wrap">
                <i class="fa-solid fa-user-tie" aria-hidden="true"></i>
                <input type="text" id="yonetmen" name="yonetmen" value="<?= e($yonetmenDegeri) ?>"
                       placeholder="Örn. Christopher Nolan" maxlength="100" autocomplete="off" required>
            </div>
            <?php if (isset($hatalar['yonetmen'])): ?>
                <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['yonetmen']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($hatalar['yil']) ? 'has-error' : '' ?>">
            <label for="yil">Yayın Yılı <span class="required">*</span></label>
            <div class="input-wrap">
                <i class="fa-solid fa-calendar" aria-hidden="true"></i>
                <input type="number" id="yil" name="yil" value="<?= e($yilDegeri) ?>"
                       placeholder="Örn. 2014" min="1888" max="<?= (int) date('Y') + 2 ?>" inputmode="numeric" required>
            </div>
            <?php if (isset($hatalar['yil'])): ?>
                <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['yil']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($hatalar['sure']) ? 'has-error' : '' ?>">
            <label for="sure">Süre <small>(dakika, isteğe bağlı)</small></label>
            <div class="input-wrap">
                <i class="fa-regular fa-clock" aria-hidden="true"></i>
                <input type="number" id="sure" name="sure" value="<?= e($sureDegeri) ?>"
                       placeholder="Örn. 169" min="1" max="900" inputmode="numeric">
            </div>
            <small class="field-hint">Boş bırakırsan süre gösterilmez. Örn. 169 → 2 sa 49 dk.</small>
            <?php if (isset($hatalar['sure'])): ?>
                <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['sure']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($hatalar['ulke']) ? 'has-error' : '' ?>">
            <label for="ulke">Ülke <small>(isteğe bağlı)</small></label>
            <div class="input-wrap">
                <i class="fa-solid fa-earth-europe" aria-hidden="true"></i>
                <input type="text" id="ulke" name="ulke" value="<?= e($ulkeDegeri) ?>"
                       placeholder="Örn. ABD" maxlength="60" autocomplete="off">
            </div>
            <?php if (isset($hatalar['ulke'])): ?>
                <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['ulke']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field field-rating <?= isset($hatalar['puan']) ? 'has-error' : '' ?>">
            <label for="puan">
                Puanın
                <span class="rating-value" data-rating-output><?= e(puanFormat($puanDegeri)) ?></span>
                <small>/ 10</small>
            </label>
            <div class="range-wrap">
                <span class="range-min">1</span>
                <input type="range" id="puan" name="puan" min="1" max="10" step="0.1"
                       value="<?= e((string) $puanDegeri) ?>" data-rating-input>
                <span class="range-max">10</span>
            </div>
            <?php if (isset($hatalar['puan'])): ?>
                <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['puan']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field <?= isset($hatalar['imdb']) ? 'has-error' : '' ?>">
            <label for="imdb">
                IMDb Puanı
                <span class="imdb-inline"><i class="fa-brands fa-imdb"></i></span>
            </label>
            <div class="input-wrap">
                <i class="fa-brands fa-imdb" aria-hidden="true"></i>
                <input type="number" id="imdb" name="imdb" value="<?= e((string) $imdbDegeri) ?>"
                       placeholder="Örn. 8.5" min="0" max="10" step="0.1" inputmode="decimal" required>
            </div>
            <small class="field-hint">IMDb puanı 0 ile 10 arasında olmalıdır. Öneri motoru bu puanı da kullanır.</small>
            <?php if (isset($hatalar['imdb'])): ?>
                <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['imdb']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field field-full <?= isset($hatalar['durum']) ? 'has-error' : '' ?>">
            <label>İzlenme Durumu <span class="required">*</span></label>
            <div class="status-options">
                <?php foreach (DURUMLAR as $durum): ?>
                    <label class="status-option status-option-<?= e(durumSinifi($durum)) ?>">
                        <input type="radio" name="durum" value="<?= e($durum) ?>" <?= $durumDegeri === $durum ? 'checked' : '' ?>>
                        <span class="status-option-inner">
                            <i class="fa-solid <?= e(durumIkonu($durum)) ?>"></i>
                            <span><?= e($durum) ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if (isset($hatalar['durum'])): ?>
                <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['durum']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field field-full <?= isset($hatalar['aciklama']) ? 'has-error' : '' ?>">
            <label for="aciklama">Açıklama <span class="required">*</span></label>
            <textarea id="aciklama" name="aciklama" rows="5" maxlength="1000"
                      placeholder="Filmin konusunu, senin için ne ifade ettiğini veya kısa bir notu yaz..."
                      data-desc-input required><?= e($aciklamaDegeri) ?></textarea>
            <div class="field-hint field-hint-between">
                <span>En az 10 karakter</span>
                <span data-desc-count><?= metinUzunlugu($aciklamaDegeri) ?> / 1000</span>
            </div>
            <?php if (isset($hatalar['aciklama'])): ?>
                <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['aciklama']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field field-full <?= isset($hatalar['konu']) ? 'has-error' : '' ?>">
            <label for="konu">Filmin Konusu <small>(isteğe bağlı)</small></label>
            <textarea id="konu" name="konu" rows="4" maxlength="2000"
                      placeholder="Filmin olay örgüsünü, karakterlerini ve merkezindeki çatışmayı anlat..."
                      data-plot-input><?= e($konuDegeri) ?></textarea>
            <div class="field-hint field-hint-between">
                <span>Boş bırakırsan açıklama alanı konu olarak kullanılır.</span>
                <span data-plot-count><?= metinUzunlugu($konuDegeri) ?> / 2000</span>
            </div>
            <?php if (isset($hatalar['konu'])): ?>
                <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['konu']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field field-full <?= isset($hatalar['etiketler']) ? 'has-error' : '' ?>">
            <label for="etiketler">Etiketler <small>(isteğe bağlı)</small></label>
            <div class="input-wrap">
                <i class="fa-solid fa-hashtag" aria-hidden="true"></i>
                <input type="text" id="etiketler" name="etiketler" value="<?= e($etiketDegeri) ?>"
                       placeholder="Örn. kült, uzay, zaman yolculuğu" maxlength="120" autocomplete="off">
            </div>
            <small class="field-hint">Virgülle ayır. En fazla 6 etiket. Etiketler arama ve keşfet sayfasında kullanılır.</small>
            <?php if (isset($hatalar['etiketler'])): ?>
                <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['etiketler']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field field-full <?= isset($hatalar['poster']) ? 'has-error' : '' ?>">
            <label for="poster">Afiş <small>(isteğe bağlı)</small></label>

            <div class="poster-input-grid">
                <div>
                    <span class="field-sublabel"><i class="fa-solid fa-link"></i> Afiş adresi</span>
                    <div class="input-wrap">
                        <i class="fa-solid fa-image" aria-hidden="true"></i>
                        <input type="url" id="poster" name="poster" value="<?= e($posterDegeri) ?>"
                               placeholder="https://ornek.com/afis.jpg" maxlength="500" autocomplete="off"
                               <?= $posterYerel ? 'readonly' : '' ?>>
                    </div>
                    <?php if ($posterYerel): ?>
                        <small class="field-hint"><i class="fa-solid fa-circle-info"></i> Yüklenen yerel afiş kullanılıyor. Yeni dosya seçersen değişir.</small>
                    <?php endif; ?>
                </div>

                <div>
                    <span class="field-sublabel"><i class="fa-solid fa-upload"></i> Bilgisayardan yükle</span>
                    <label class="file-drop" for="poster_dosya" data-file-drop>
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span data-file-label>JPG, PNG, WEBP veya GIF · en fazla 2 MB</span>
                        <input type="file" id="poster_dosya" name="poster_dosya" accept="image/jpeg,image/png,image/webp,image/gif" data-file-input>
                    </label>
                </div>
            </div>

            <small class="field-hint">Boş bırakırsan kategoriye göre otomatik, şık bir kapak oluşturulur.</small>
            <?php if (isset($hatalar['poster'])): ?>
                <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['poster']) ?></small>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-floppy-disk"></i> <?= e($butonMetni) ?>
        </button>
        <a href="<?= e($iptalUrl) ?>" class="btn btn-ghost">Vazgeç</a>
    </div>
</form>
