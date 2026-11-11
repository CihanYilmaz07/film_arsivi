<?php
require_once 'functions.php';
girisGerekli();

$kullanici = aktifKullanici() ?? [];
$hatalar = [];
$sifreHatalar = [];
$adBasari = '';
$sifreBasari = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $islem = (string) ($_POST['islem'] ?? '');

    if (!csrfDogrula(is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : null)) {
        $hatalar['genel'] = 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.';
    } elseif ($islem === 'ad') {
        $ad = trim((string) ($_POST['ad'] ?? ''));

        if (profilAdiGuncelle($ad)) {
            flashAyarla('basari', 'Görünen adın güncellendi.');
            yonlendir('profil.php');
        }

        $hatalar['ad'] = 'Görünen ad 2 ile 40 karakter arasında olmalıdır.';
    } elseif ($islem === 'sifre') {
        $sonuc = sifreDegistir(
            (string) ($_POST['mevcut_sifre'] ?? ''),
            (string) ($_POST['yeni_sifre'] ?? ''),
            (string) ($_POST['tekrar'] ?? '')
        );

        if ($sonuc['ok']) {
            flashAyarla('basari', 'Şifren bu oturum için güncellendi. Demo sürümde çıkış yapınca demo şifresi yeniden geçerli olur.');
            yonlendir('profil.php');
        }

        $sifreHatalar = $sonuc['hatalar'];
    }
}

$kullanici = aktifKullanici() ?? [];
$filmler = $_SESSION['filmler'];
$sayilar = filmSayilari($filmler);
$favoriler = favoriFilmler($filmler);
$girisZamani = (string) ($kullanici['giris_zamani'] ?? '');
$girisZamaniGoster = $girisZamani !== '' ? date('d.m.Y H:i', strtotime($girisZamani)) : '—';
$yorumToplam = (int) $sayilar['yorum'];
$sonFilm = sonEklenenler($filmler, 3);

$sayfaBaslik = 'Profil — CHNStream';
$aktifSayfa  = 'profil';
$sayfaAciklama = 'Hesap bilgilerini görüntüle, görünen adını ve şifreni güncelle.';

require 'partials/header.php';
?>

<section class="page-hero page-hero-compact">
    <div>
        <span class="eyebrow"><i class="fa-solid fa-user-gear"></i> Hesap</span>
        <h1>Profil <span class="text-gradient">& Ayarlar</span></h1>
        <p>Arşiv yöneticisi hesabın. Görünen adını değiştirebilir, şifreni güvenli biçimde güncelleyebilirsin.</p>
    </div>
    <div class="page-hero-actions">
        <a href="disa-aktar.php" class="btn btn-ghost"><i class="fa-solid fa-file-export"></i> Verileri Yedekle</a>
    </div>
</section>

<div class="profile-grid">
    <aside class="panel profile-card">
        <div class="profile-avatar"><?= e(isimBasHarfleri((string) ($kullanici['ad'] ?? 'K'))) ?></div>
        <h2><?= e((string) ($kullanici['ad'] ?? 'Kullanıcı')) ?></h2>
        <span class="profile-role"><i class="fa-solid fa-shield-halved"></i> <?= e((string) ($kullanici['rol'] ?? 'Üye')) ?></span>
        <code class="profile-username">@<?= e((string) ($kullanici['kullanici_adi'] ?? '')) ?></code>

        <dl class="profile-facts">
            <div>
                <dt>Giriş zamanı</dt>
                <dd><?= e($girisZamaniGoster) ?></dd>
            </div>
            <div>
                <dt>Arşivdeki film</dt>
                <dd><?= (int) $sayilar['toplam'] ?></dd>
            </div>
            <div>
                <dt>Favori</dt>
                <dd><?= count($favoriler) ?></dd>
            </div>
            <div>
                <dt>Yorum</dt>
                <dd><?= $yorumToplam ?></dd>
            </div>
        </dl>

        <div class="profile-actions">
            <a href="index.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-layer-group"></i> Arşiv</a>
            <a href="favori.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-heart"></i> Favoriler</a>
        </div>

        <form action="cikis.php" method="POST" class="profile-logout">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Oturumu Kapat</button>
        </form>
    </aside>

    <div class="profile-main">
        <section class="panel" aria-labelledby="ad-baslik">
            <div class="panel-head">
                <div>
                    <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-id-card"></i> Hesap Bilgisi</span>
                    <h2 id="ad-baslik">Görünen Ad</h2>
                </div>
                <span class="panel-badge"><i class="fa-solid fa-user"></i> Profil</span>
            </div>

            <?php if (isset($hatalar['genel'])): ?>
                <div class="alert alert-error" role="alert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div><strong>İşlem tamamlanamadı.</strong><span><?= e($hatalar['genel']) ?></span></div>
                </div>
            <?php endif; ?>

            <form action="profil.php" method="POST" class="profile-form">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="islem" value="ad">

                <div class="field <?= isset($hatalar['ad']) ? 'has-error' : '' ?>">
                    <label for="ad">Görünen Ad</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                        <input type="text" id="ad" name="ad" value="<?= e((string) ($kullanici['ad'] ?? '')) ?>"
                               maxlength="40" minlength="2" autocomplete="name" required>
                    </div>
                    <?php if (isset($hatalar['ad'])): ?>
                        <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['ad']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="kullanici_adi_goster">Kullanıcı Adı</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-at" aria-hidden="true"></i>
                        <input type="text" id="kullanici_adi_goster" value="<?= e((string) ($kullanici['kullanici_adi'] ?? '')) ?>" readonly>
                    </div>
                    <small class="field-hint">Demo hesapta kullanıcı adı sabittir.</small>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Adı Kaydet</button>
            </form>
        </section>

        <section class="panel" aria-labelledby="sifre-baslik">
            <div class="panel-head">
                <div>
                    <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-lock"></i> Güvenlik</span>
                    <h2 id="sifre-baslik">Şifre Değiştir</h2>
                </div>
                <span class="panel-badge"><i class="fa-solid fa-key"></i> Güçlü Şifre</span>
            </div>

            <?php if ($sifreHatalar !== []): ?>
                <div class="alert alert-error" role="alert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Şifre değiştirilemedi.</strong>
                        <span><?= e(implode(' ', array_values($sifreHatalar))) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form action="profil.php" method="POST" class="profile-form">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="islem" value="sifre">

                <div class="field <?= isset($sifreHatalar['mevcut_sifre']) ? 'has-error' : '' ?>">
                    <label for="mevcut_sifre">Mevcut Şifre</label>
                    <div class="input-wrap input-wrap-password">
                        <i class="fa-solid fa-key" aria-hidden="true"></i>
                        <input type="password" id="mevcut_sifre" name="mevcut_sifre" autocomplete="current-password" required data-password-input>
                        <button type="button" class="password-reveal" data-password-toggle aria-label="Şifreyi göster" aria-pressed="false">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($sifreHatalar['mevcut_sifre'])): ?>
                        <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($sifreHatalar['mevcut_sifre']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field <?= isset($sifreHatalar['yeni_sifre']) ? 'has-error' : '' ?>">
                    <label for="yeni_sifre">Yeni Şifre</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <input type="password" id="yeni_sifre" name="yeni_sifre" minlength="8" autocomplete="new-password" required>
                    </div>
                    <small class="field-hint">En az 8 karakter, bir harf ve bir rakam içermelidir.</small>
                    <?php if (isset($sifreHatalar['yeni_sifre'])): ?>
                        <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($sifreHatalar['yeni_sifre']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field <?= isset($sifreHatalar['tekrar']) ? 'has-error' : '' ?>">
                    <label for="tekrar">Yeni Şifre (tekrar)</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <input type="password" id="tekrar" name="tekrar" minlength="8" autocomplete="new-password" required>
                    </div>
                    <?php if (isset($sifreHatalar['tekrar'])): ?>
                        <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($sifreHatalar['tekrar']) ?></small>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-shield-halved"></i> Şifreyi Güncelle</button>
                <small class="field-hint">Veriler <code>$_SESSION</code> içinde tutulduğu için şifre değişikliği yalnızca bu oturum boyunca geçerlidir. Çıkış yaptığında demo şifresi (<code>sinema123</code>) yeniden kullanılır.</small>
            </form>
        </section>

        <?php if ($sonFilm !== []): ?>
            <section class="panel" aria-labelledby="son-baslik">
                <div class="panel-head panel-head-compact">
                    <div>
                        <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-clock-rotate-left"></i> Son İşlemler</span>
                        <h2 id="son-baslik">Son Eklediğin Filmler</h2>
                    </div>
                    <a href="index.php?sirala=yeni" class="text-link">Tümü <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <ul class="side-list">
                    <?php foreach ($sonFilm as $film): ?>
                        <li>
                            <a href="detay.php?id=<?= (int) $film['id'] ?>">
                                <span class="side-list-title"><?= e((string) $film['baslik']) ?></span>
                                <span class="side-list-meta"><?= e((string) $film['kategori']) ?> · <?= e(tarihFormat((string) ($film['eklenme'] ?? ''))) ?></span>
                            </a>
                            <span class="rank-score"><?= e(puanFormat((float) $film['puan'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </div>
</div>

<?php require 'partials/footer.php'; ?>
