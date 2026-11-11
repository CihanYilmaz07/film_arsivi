<?php
require_once 'functions.php';
girisGerekli();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$film = filmBul($_SESSION['filmler'], $id);

if ($film === null) {
    flashAyarla('hata', 'Düzenlemek istediğin kayıt bulunamadı. Silinmiş olabilir.');
    yonlendir('index.php');
}

$hatalar = [];
$formVeri = $film;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formVeri = [
        'baslik'    => trim((string) ($_POST['baslik'] ?? '')),
        'kategori'  => trim((string) ($_POST['kategori'] ?? '')),
        'yonetmen'  => trim((string) ($_POST['yonetmen'] ?? '')),
        'yil'       => trim((string) ($_POST['yil'] ?? '')),
        'puan'      => trim((string) ($_POST['puan'] ?? '')),
        'imdb'      => trim((string) ($_POST['imdb'] ?? '')),
        'aciklama'  => trim((string) ($_POST['aciklama'] ?? '')),
        'konu'      => trim((string) ($_POST['konu'] ?? '')),
        'durum'     => trim((string) ($_POST['durum'] ?? '')),
        'poster'    => trim((string) ($_POST['poster'] ?? '')),
        'sure'      => trim((string) ($_POST['sure'] ?? '')),
        'ulke'      => trim((string) ($_POST['ulke'] ?? '')),
        'etiketler' => trim((string) ($_POST['etiketler'] ?? '')),
    ];

    if (!csrfDogrula(is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : null)) {
        $hatalar['genel'] = 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.';
    } else {
        $sonuc  = filmVerisiniDogrula($_POST);
        $hatalar = $sonuc['hatalar'];

        if (!isset($hatalar['puan']) && $sonuc['veri']['puan'] !== '') {
            $formVeri['puan'] = (string) $sonuc['veri']['puan'];
        }

        if (!isset($hatalar['imdb']) && $sonuc['veri']['imdb'] !== '') {
            $formVeri['imdb'] = (string) $sonuc['veri']['imdb'];
        }

        if (!isset($hatalar['yil']) && $sonuc['veri']['yil'] !== '') {
            $formVeri['yil'] = (string) $sonuc['veri']['yil'];
        }

        if (!isset($hatalar['durum']) && $sonuc['veri']['durum'] !== '') {
            $formVeri['durum'] = (string) $sonuc['veri']['durum'];
        }

        if (!isset($hatalar['sure'])) {
            $formVeri['sure'] = (string) ($sonuc['veri']['sure'] ?? '');
        }

        if ($hatalar === []) {
            $yukleme = posterYukle($_FILES['poster_dosya'] ?? []);

            if ($yukleme['hata'] !== '') {
                $hatalar['poster'] = $yukleme['hata'];
            } else {
                if ($yukleme['yol'] !== '') {
                    posterDosyasiniSil((string) ($film['poster'] ?? ''));
                    $sonuc['veri']['poster'] = $yukleme['yol'];
                }

                filmGuncelle($id, $sonuc['veri']);

                flashAyarla('basari', '“' . (string) $sonuc['veri']['baslik'] . '” kaydı güncellendi.');
                yonlendir('detay.php?id=' . $id);
            }
        }
    }
}

$kategoriler = kategorileriGetir($_SESSION['filmler']);

$sayfaBaslik = 'Filmi Düzenle — CHNStream';
$aktifSayfa  = 'arsiv';

$formAksiyon = 'duzenle.php';
$butonMetni  = 'Değişiklikleri Kaydet';
$iptalUrl    = 'detay.php?id=' . $id;
$filmId      = $id;

require 'partials/header.php';
?>

<section class="page-hero page-hero-compact">
    <div>
        <span class="eyebrow"><i class="fa-solid fa-pen"></i> Kayıt #<?= $id ?> düzenleniyor</span>
        <h1><span class="text-gradient"><?= e((string) $film['baslik']) ?></span></h1>
        <p>Film bilgilerini güncelle. Değişiklikler kaydedildiğinde detay sayfasına yönlendirileceksin.</p>
    </div>
    <a href="detay.php?id=<?= $id ?>" class="btn btn-ghost">
        <i class="fa-solid fa-arrow-left"></i> Detaya Dön
    </a>
</section>

<div class="form-layout">
    <section class="panel form-panel" aria-labelledby="form-baslik">
        <div class="panel-head">
            <div>
                <span class="eyebrow eyebrow-sm">Kaydı Güncelle</span>
                <h2 id="form-baslik">Film Bilgileri</h2>
            </div>
            <span class="panel-badge"><i class="fa-solid fa-pen-to-square"></i> Düzenleme Modu</span>
        </div>

        <?php require 'partials/film_form.php'; ?>
    </section>

    <?php require 'partials/film_preview.php'; ?>
</div>

<?php require 'partials/footer.php'; ?>
