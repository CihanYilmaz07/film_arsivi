<?php
require_once 'functions.php';
girisGerekli();

$hatalar = [];
$formVeri = [
    'baslik'    => '',
    'kategori'  => '',
    'yonetmen'  => '',
    'yil'       => '',
    'puan'      => (string) VARSAYILAN_PUAN,
    'imdb'      => (string) VARSAYILAN_IMDB,
    'aciklama'  => '',
    'konu'      => '',
    'durum'     => 'İzlenecek',
    'poster'    => '',
    'sure'      => '',
    'ulke'      => '',
    'etiketler' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* Kullanıcının yazdığı değerleri hata olsa bile formda koru. */
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

        /* Doğrulanmış sayısal değerleri forma geri yaz. */
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
                    $sonuc['veri']['poster'] = $yukleme['yol'];
                }

                $yeniId = filmEkle($sonuc['veri']);
                $yeniFilm = filmBul($_SESSION['filmler'], $yeniId);

                flashAyarla(
                    'basari',
                    '“' . (string) ($yeniFilm['baslik'] ?? 'Yeni film') . '” arşive başarıyla eklendi.'
                );

                yonlendir('detay.php?id=' . $yeniId);
            }
        }
    }
}

$kategoriler = kategorileriGetir($_SESSION['filmler']);

$sayfaBaslik = 'Yeni Film Ekle — CHNStream';
$aktifSayfa  = 'ekle';

$formAksiyon = 'ekle.php';
$butonMetni  = 'Filmi Arşive Kaydet';
$iptalUrl    = 'index.php';
$filmId      = null;

require 'partials/header.php';
?>

<section class="page-hero page-hero-compact">
    <div>
        <span class="eyebrow"><i class="fa-solid fa-circle-plus"></i> Arşive yeni bir iz</span>
        <h1>Film <span class="text-gradient">Ekle</span></h1>
        <p>İzlediğin ya da izlemek istediğin bir filmi arşivine kaydet. Alanları doldurdukça kartın canlı önizlemesini görebilirsin.</p>
    </div>
    <a href="index.php" class="btn btn-ghost">
        <i class="fa-solid fa-arrow-left"></i> Arşive Dön
    </a>
</section>

<div class="form-layout">
    <section class="panel form-panel" aria-labelledby="form-baslik">
        <div class="panel-head">
            <div>
                <span class="eyebrow eyebrow-sm">Yeni Kayıt</span>
                <h2 id="form-baslik">Film Bilgileri</h2>
            </div>
            <span class="panel-badge"><i class="fa-solid fa-shield-halved"></i> Güvenli Form</span>
        </div>

        <?php require 'partials/film_form.php'; ?>
    </section>

    <?php require 'partials/film_preview.php'; ?>
</div>

<?php require 'partials/footer.php'; ?>
