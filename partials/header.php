<?php
/**
 * Ortak sayfa başlığı ve üst menü.
 * Kullanılmadan önce functions.php dahil edilmiş olmalıdır.
 */

$sayfaBaslik = $sayfaBaslik ?? 'CHNStream — Kişisel Sinema Arşivi';
$aktifSayfa  = $aktifSayfa ?? '';
$sayfaAciklama = $sayfaAciklama ?? 'CHNStream — Film ve dizi arşivinizi kaydedin, puanlayın, filtreleyin ve izleme yolculuğunuzu takip edin.';
$flash       = flashGetir();
$kullanici   = aktifKullanici();
$favoriAdet   = favoriSayisi();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($sayfaAciklama) ?>">
    <meta name="theme-color" content="#f3ece1">
    <title><?= e($sayfaBaslik) ?></title>

    <?php require __DIR__ . '/theme_init.php'; ?>

    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css?v=<?= e(varlikSurumu('assets/style.css')) ?>">
    <link rel="stylesheet" href="assets/v2.css?v=<?= e(varlikSurumu('assets/v2.css')) ?>">
</head>
<body>
    <a class="skip-link" href="#ana-icerik">İçeriğe geç</a>

    <div class="ambient ambient-1" aria-hidden="true"></div>
    <div class="ambient ambient-2" aria-hidden="true"></div>
    <div class="ambient ambient-3" aria-hidden="true"></div>
    <div class="grain" aria-hidden="true"></div>

    <div class="scroll-progress" aria-hidden="true"><span data-scroll-progress></span></div>

    <header class="site-header" data-header>
        <div class="container header-inner">
            <a href="index.php" class="brand" aria-label="CHNStream ana sayfa">
                <span class="brand-mark"><i class="fa-solid fa-film"></i></span>
                <span class="brand-text">
                    CHN<span>STREAM</span>
                    <small>Sinema Arşivi</small>
                </span>
            </a>

            <nav class="main-nav" id="main-nav" data-nav aria-label="Ana menü">
                <a href="index.php" class="nav-link <?= $aktifSayfa === 'arsiv' ? 'is-active' : '' ?>" <?= $aktifSayfa === 'arsiv' ? 'aria-current="page"' : '' ?>>
                    <i class="fa-solid fa-layer-group"></i> Arşiv
                </a>
                <a href="kesfet.php" class="nav-link <?= $aktifSayfa === 'kesfet' ? 'is-active' : '' ?>" <?= $aktifSayfa === 'kesfet' ? 'aria-current="page"' : '' ?>>
                    <i class="fa-solid fa-compass"></i> Keşfet
                </a>
                <a href="istatistikler.php" class="nav-link <?= $aktifSayfa === 'istatistik' ? 'is-active' : '' ?>" <?= $aktifSayfa === 'istatistik' ? 'aria-current="page"' : '' ?>>
                    <i class="fa-solid fa-chart-simple"></i> İstatistikler
                </a>
                <a href="izleme-listesi.php" class="nav-link <?= $aktifSayfa === 'liste' ? 'is-active' : '' ?>" <?= $aktifSayfa === 'liste' ? 'aria-current="page"' : '' ?>>
                    <i class="fa-solid fa-bookmark"></i> İzleme Listesi
                </a>
                <a href="favori.php" class="nav-link <?= $aktifSayfa === 'favori' ? 'is-active' : '' ?>" <?= $aktifSayfa === 'favori' ? 'aria-current="page"' : '' ?>>
                    <i class="fa-solid fa-heart"></i> Favoriler
                    <?php if ($favoriAdet > 0): ?>
                        <span class="nav-count"><?= (int) $favoriAdet ?></span>
                    <?php endif; ?>
                </a>

                <?php if ($kullanici !== null): ?>
                    <div class="mobile-auth">
                        <div class="mobile-user">
                            <span class="user-avatar"><?= e(isimBasHarfleri((string) ($kullanici['ad'] ?? 'K'))) ?></span>
                            <span>
                                <strong><?= e((string) ($kullanici['ad'] ?? '')) ?></strong>
                                <small><?= e((string) ($kullanici['rol'] ?? '')) ?></small>
                            </span>
                        </div>
                        <div class="mobile-auth-links">
                            <a href="profil.php" class="btn btn-ghost btn-sm">
                                <i class="fa-solid fa-user-gear"></i> Profil
                            </a>
                            <form action="cikis.php" method="POST">
                                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                                <button type="submit" class="btn btn-ghost btn-sm">
                                    <i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>

            <div class="header-actions">
                <button type="button" class="command-trigger" data-command-open aria-label="Komut paletini aç" title="Komut paleti (Ctrl + K)">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span class="command-trigger-text">Ara veya komut çalıştır</span>
                    <kbd>Ctrl K</kbd>
                </button>

                <button type="button" class="theme-toggle" data-theme-toggle aria-label="Koyu temaya geç" title="Tema değiştir">
                    <i class="fa-solid fa-sun theme-icon-sun" aria-hidden="true"></i>
                    <i class="fa-solid fa-moon theme-icon-moon" aria-hidden="true"></i>
                </button>

                <?php if ($kullanici !== null): ?>
                    <a href="profil.php" class="user-chip" title="Profil ve hesap ayarları">
                        <span class="user-avatar"><?= e(isimBasHarfleri((string) ($kullanici['ad'] ?? 'K'))) ?></span>
                        <span class="user-chip-meta">
                            <strong><?= e((string) ($kullanici['ad'] ?? '')) ?></strong>
                            <small><?= e((string) ($kullanici['rol'] ?? '')) ?></small>
                        </span>
                    </a>

                    <form action="cikis.php" method="POST" class="logout-form">
                        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                        <button type="submit" class="logout-btn" aria-label="Çıkış yap" title="Çıkış yap">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </button>
                    </form>
                <?php endif; ?>

                <a href="ekle.php" class="btn btn-primary btn-sm header-add">
                    <i class="fa-solid fa-plus"></i>
                    <span>Yeni Film</span>
                </a>
                <button type="button" class="nav-toggle" data-nav-toggle aria-expanded="false" aria-controls="main-nav" aria-label="Menüyü aç">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <?php if ($flash !== null): ?>
        <?php
        $flashTipi  = (string) ($flash['tip'] ?? 'bilgi');
        $flashIkonu = match ($flashTipi) {
            'basari' => 'fa-circle-check',
            'hata'   => 'fa-circle-exclamation',
            default  => 'fa-circle-info',
        };
        ?>
        <div class="toast toast-<?= e($flashTipi) ?>" data-toast role="status" aria-live="polite">
            <span class="toast-icon"><i class="fa-solid <?= e($flashIkonu) ?>"></i></span>
            <span class="toast-text"><?= e((string) ($flash['mesaj'] ?? '')) ?></span>
            <button type="button" class="toast-close" data-toast-close aria-label="Bildirimi kapat">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <main class="container page-main" id="ana-icerik">
