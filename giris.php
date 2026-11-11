<?php
require_once 'functions.php';

/* Giriş yapmış kullanıcı tekrar giriş ekranını görmesin. */
if (girisYapildi()) {
    yonlendir('index.php');
}

$hatalar       = [];
$kullaniciAdi  = '';
$genelHata     = '';
$flash         = flashGetir();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kullaniciAdi = trim((string) ($_POST['kullanici_adi'] ?? ''));
    $sifre        = (string) ($_POST['sifre'] ?? '');
    $csrf         = (string) ($_POST['csrf'] ?? '');
    $bekleme      = girisBeklemeSuresi();

    if ($bekleme > 0) {
        $genelHata = 'Çok fazla başarısız deneme yaptın. Lütfen ' . $bekleme . ' saniye sonra tekrar dene.';
    } elseif (!csrfDogrula($csrf)) {
        $genelHata = 'Oturum doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.';
    } elseif ($kullaniciAdi === '') {
        $hatalar['kullanici_adi'] = 'Kullanıcı adı zorunludur.';
    } elseif ($sifre === '') {
        $hatalar['sifre'] = 'Şifre zorunludur.';
    } elseif (!girisDene($kullaniciAdi, $sifre)) {
        $yeniBekleme = girisBeklemeSuresi();
        $genelHata = $yeniBekleme > 0
            ? 'Çok fazla başarısız deneme yaptın. Lütfen ' . $yeniBekleme . ' saniye sonra tekrar dene.'
            : 'Kullanıcı adı veya şifre hatalı. Demo bilgilerini kullanabilirsin.';
    } else {
        $hedef = (string) ($_SESSION['giris_hedef'] ?? 'index.php');
        unset($_SESSION['giris_hedef']);

        if (!str_starts_with($hedef, '/') || str_starts_with($hedef, '//')) {
            $hedef = 'index.php';
        }

        flashAyarla('basari', 'Tekrar hoş geldin! Arşivin seni bekliyor.');
        yonlendir($hedef);
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CHNStream kişisel film arşivine giriş yapın.">
    <meta name="theme-color" content="#f3ece1">
    <title>Giriş Yap · CHNStream</title>

    <?php require __DIR__ . '/partials/theme_init.php'; ?>

    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css?v=<?= e(varlikSurumu('assets/style.css')) ?>">
    <link rel="stylesheet" href="assets/v2.css?v=<?= e(varlikSurumu('assets/v2.css')) ?>">
</head>
<body class="auth-body">
    <a class="skip-link" href="#giris-icerik">İçeriğe geç</a>

    <div class="ambient ambient-1" aria-hidden="true"></div>
    <div class="ambient ambient-2" aria-hidden="true"></div>
    <div class="ambient ambient-3" aria-hidden="true"></div>
    <div class="grain" aria-hidden="true"></div>

    <main class="auth-shell" id="giris-icerik">
        <section class="auth-showcase" aria-label="CHNStream tanıtımı">
            <a href="giris.php" class="brand auth-brand" aria-label="CHNStream giriş">
                <span class="brand-mark"><i class="fa-solid fa-film"></i></span>
                <span class="brand-text">
                    CHN<span>STREAM</span>
                    <small>Sinema Arşivi</small>
                </span>
            </a>

            <div class="auth-showcase-copy">
                <span class="eyebrow">
                    <span class="eyebrow-dot" aria-hidden="true"></span>
                    Kişisel Sinema Arşivi
                </span>
                <h1>Her film bir iz,<br><span class="text-gradient">her iz bir hikâye.</span></h1>
                <p>
                    İzlediğin filmleri kaydet, puanla, yorumla ve zevkine göre yeni öneriler keşfet.
                    Arşivin oturum boyunca seninle birlikte.
                </p>
            </div>

            <ul class="auth-benefits">
                <li>
                    <span class="auth-benefit-icon"><i class="fa-solid fa-layer-group"></i></span>
                    <span>
                        <strong>50 filmlik hazır arşiv</strong>
                        <small>Kendi kayıtlarınla büyüt, filtrele ve sırala.</small>
                    </span>
                </li>
                <li>
                    <span class="auth-benefit-icon"><i class="fa-solid fa-star-half-stroke"></i></span>
                    <span>
                        <strong>Puanla ve yorumla</strong>
                        <small>IMDb puanları ve topluluk yorumlarıyla karşılaştır.</small>
                    </span>
                </li>
                <li>
                    <span class="auth-benefit-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                    <span>
                        <strong>Zevkine göre öneriler</strong>
                        <small>İzleme alışkanlıklarından ilham alan akıllı öneriler.</small>
                    </span>
                </li>
            </ul>

            <p class="auth-showcase-foot">
                <i class="fa-solid fa-shield-halved"></i>
                Oturum tabanlı giriş · CSRF korumalı formlar
            </p>
        </section>

        <section class="auth-panel">
            <div class="auth-panel-top">
                <span class="auth-panel-label"><i class="fa-solid fa-lock"></i> Güvenli Giriş</span>
                <button type="button" class="theme-toggle" data-theme-toggle aria-label="Açık temaya geç" title="Tema değiştir">
                    <i class="fa-solid fa-sun theme-icon-sun" aria-hidden="true"></i>
                    <i class="fa-solid fa-moon theme-icon-moon" aria-hidden="true"></i>
                </button>
            </div>

            <div class="auth-card">
                <span class="eyebrow eyebrow-sm">Hoş geldin</span>
                <h2>Arşivine giriş yap</h2>
                <p class="auth-lead">Devam etmek için demo hesap bilgilerini kullanabilirsin.</p>

                <?php if ($flash !== null): ?>
                    <div class="auth-note auth-note-<?= e((string) ($flash['tip'] ?? 'bilgi')) ?>" role="status">
                        <i class="fa-solid fa-circle-info"></i>
                        <span><?= e((string) ($flash['mesaj'] ?? '')) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($genelHata !== ''): ?>
                    <div class="alert alert-error" role="alert">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div>
                            <strong>Giriş yapılamadı.</strong>
                            <span><?= e($genelHata) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="giris.php" method="POST" class="auth-form" autocomplete="on">
                    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

                    <div class="field <?= isset($hatalar['kullanici_adi']) ? 'has-error' : '' ?>">
                        <label for="kullanici_adi">Kullanıcı Adı</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                            <input type="text" id="kullanici_adi" name="kullanici_adi"
                                   value="<?= e($kullaniciAdi) ?>" placeholder="admin"
                                   autocomplete="username" autocapitalize="none" spellcheck="false" required>
                        </div>
                        <?php if (isset($hatalar['kullanici_adi'])): ?>
                            <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['kullanici_adi']) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="field <?= isset($hatalar['sifre']) ? 'has-error' : '' ?>">
                        <label for="sifre">Şifre</label>
                        <div class="input-wrap input-wrap-password">
                            <i class="fa-solid fa-key" aria-hidden="true"></i>
                            <input type="password" id="sifre" name="sifre" placeholder="••••••••"
                                   autocomplete="current-password" required data-password-input>
                            <button type="button" class="password-reveal" data-password-toggle
                                    aria-label="Şifreyi göster" title="Şifreyi göster" aria-pressed="false">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($hatalar['sifre'])): ?>
                            <small class="field-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($hatalar['sifre']) ?></small>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg auth-submit">
                        <i class="fa-solid fa-right-to-bracket"></i> Giriş Yap
                    </button>
                </form>

                <div class="auth-demo">
                    <div class="auth-demo-head">
                        <span><i class="fa-solid fa-circle-info"></i> Demo hesap</span>
                        <button type="button" class="auth-demo-fill" data-demo-fill>
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Bilgileri doldur
                        </button>
                    </div>
                    <div class="auth-demo-grid">
                        <span><small>Kullanıcı adı</small><code>admin</code></span>
                        <span><small>Şifre</small><code>sinema123</code></span>
                    </div>
                </div>

                <p class="auth-foot">
                    <i class="fa-solid fa-database"></i>
                    Kayıtlar <code>$_SESSION</code> içinde saklanır; çıkış yaptığında silinmez.
                </p>
            </div>
        </section>
    </main>

    <script src="assets/app.js?v=<?= e(varlikSurumu('assets/app.js')) ?>"></script>
</body>
</html>
