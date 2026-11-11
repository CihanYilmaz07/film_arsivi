<?php
require_once 'functions.php';
girisGerekli();

$filmler = $_SESSION['filmler'];
$format  = is_string($_GET['format'] ?? null) ? strtolower(trim($_GET['format'])) : '';
$dosyaTarih = date('Y-m-d');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="chnstream-arsiv-' . $dosyaTarih . '.csv"');
    header('Cache-Control: no-store');

    $cikti = fopen('php://output', 'wb');

    if ($cikti === false) {
        exit;
    }

    /* Excel'in Türkçe karakterleri doğru okuması için BOM. */
    fwrite($cikti, "\xEF\xBB\xBF");

    fputcsv($cikti, [
        'ID', 'Başlık', 'Kategori', 'Yönetmen', 'Yıl', 'Süre (dk)', 'Ülke',
        'Puan', 'IMDb', 'Durum', 'Etiketler', 'Açıklama', 'Konu', 'Afiş', 'Eklenme',
    ], ';');

    foreach ($filmler as $film) {
        fputcsv($cikti, [
            (int) ($film['id'] ?? 0),
            (string) ($film['baslik'] ?? ''),
            (string) ($film['kategori'] ?? ''),
            (string) ($film['yonetmen'] ?? ''),
            (int) ($film['yil'] ?? 0),
            (int) ($film['sure'] ?? 0),
            (string) ($film['ulke'] ?? ''),
            puanFormat((float) ($film['puan'] ?? 0)),
            puanFormat(filmImdbPuan($film)),
            (string) ($film['durum'] ?? ''),
            (string) ($film['etiketler'] ?? ''),
            (string) ($film['aciklama'] ?? ''),
            (string) ($film['konu'] ?? ''),
            (string) ($film['poster'] ?? ''),
            (string) ($film['eklenme'] ?? ''),
        ], ';');
    }

    fclose($cikti);
    exit;
}

if ($format === 'json') {
    $kayitlar = [];

    foreach ($filmler as $film) {
        $id = (int) ($film['id'] ?? 0);
        $kayitlar[] = [
            'id'        => $id,
            'baslik'    => (string) ($film['baslik'] ?? ''),
            'kategori'  => (string) ($film['kategori'] ?? ''),
            'yonetmen'  => (string) ($film['yonetmen'] ?? ''),
            'yil'       => (int) ($film['yil'] ?? 0),
            'sure'      => (int) ($film['sure'] ?? 0),
            'ulke'      => (string) ($film['ulke'] ?? ''),
            'puan'      => (float) ($film['puan'] ?? 0),
            'imdb'      => filmImdbPuan($film),
            'durum'     => (string) ($film['durum'] ?? ''),
            'etiketler' => etiketleriAyir($film['etiketler'] ?? ''),
            'aciklama'  => (string) ($film['aciklama'] ?? ''),
            'konu'      => (string) ($film['konu'] ?? ''),
            'poster'    => (string) ($film['poster'] ?? ''),
            'eklenme'   => (string) ($film['eklenme'] ?? ''),
            'favori'    => favoriMi($id),
            'yorumlar'  => filmYorumlari($id),
        ];
    }

    $veri = [
        'uygulama'   => UYGULAMA_ADI,
        'surum'      => UYGULAMA_SURUMU,
        'olusturma'  => date('c'),
        'kullanici'  => (string) ((aktifKullanici()['kullanici_adi'] ?? '')),
        'toplam'     => count($kayitlar),
        'filmler'    => $kayitlar,
    ];

    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="chnstream-arsiv-' . $dosyaTarih . '.json"');
    header('Cache-Control: no-store');

    echo json_encode($veri, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$sayilar = filmSayilari($filmler);
$favoriAdet = favoriSayisi();
$ilkFilmler = array_slice($filmler, 0, 5);

$sayfaBaslik = 'Verileri Dışa Aktar — CHNStream';
$aktifSayfa  = 'disa-aktar';
$sayfaAciklama = 'Arşivini CSV veya JSON olarak indir, yedekle ve başka araçlarda kullan.';

require 'partials/header.php';
?>

<section class="page-hero page-hero-compact">
    <div>
        <span class="eyebrow"><i class="fa-solid fa-file-export"></i> Yedekleme</span>
        <h1>Verileri <span class="text-gradient">Dışa Aktar</span></h1>
        <p>Arşivinin tamamını CSV (Excel uyumlu) veya JSON biçiminde indir. Verilerin her zaman senin kontrolünde.</p>
    </div>
    <div class="page-hero-actions">
        <a href="istatistikler.php" class="btn btn-ghost"><i class="fa-solid fa-chart-simple"></i> İstatistikler</a>
    </div>
</section>

<section class="export-grid">
    <article class="panel export-card">
        <span class="export-icon export-csv"><i class="fa-solid fa-file-csv"></i></span>
        <h2>CSV olarak indir</h2>
        <p>Excel, Google Sheets veya Numbers ile açabilirsin. Türkçe karakterler ve noktalı virgül ayırıcı hazır.</p>
        <ul class="export-list">
            <li><i class="fa-solid fa-check"></i> <?= count($filmler) ?> film kaydı</li>
            <li><i class="fa-solid fa-check"></i> Tüm alanlar ve etiketler</li>
            <li><i class="fa-solid fa-check"></i> UTF-8 BOM ile Excel uyumu</li>
        </ul>
        <a href="disa-aktar.php?format=csv" class="btn btn-primary">
            <i class="fa-solid fa-download"></i> CSV İndir
        </a>
    </article>

    <article class="panel export-card">
        <span class="export-icon export-json"><i class="fa-solid fa-file-code"></i></span>
        <h2>JSON olarak indir</h2>
        <p>Geliştiriciler ve yedekleme araçları için tam yapılandırılmış veri. Favoriler ve yorumlar da dahil.</p>
        <ul class="export-list">
            <li><i class="fa-solid fa-check"></i> Film, yorum ve favori verileri</li>
            <li><i class="fa-solid fa-check"></i> Okunabilir, girintili JSON</li>
            <li><i class="fa-solid fa-check"></i> Sürüm ve tarih bilgisi</li>
        </ul>
        <a href="disa-aktar.php?format=json" class="btn btn-primary">
            <i class="fa-solid fa-download"></i> JSON İndir
        </a>
    </article>
</section>

<section class="panel export-preview" aria-labelledby="onizleme-baslik">
    <div class="panel-head">
        <div>
            <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-table"></i> Önizleme</span>
            <h2 id="onizleme-baslik">Dışa Aktarılacak Veri</h2>
        </div>
        <span class="panel-badge"><?= count($filmler) ?> kayıt · <?= $favoriAdet ?> favori · <?= (int) $sayilar['yorum'] ?> yorum</span>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Başlık</th>
                    <th>Kategori</th>
                    <th>Yıl</th>
                    <th>Puan</th>
                    <th>IMDb</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ilkFilmler as $film): ?>
                    <tr>
                        <td><?= (int) $film['id'] ?></td>
                        <td><?= e((string) $film['baslik']) ?></td>
                        <td><?= e((string) $film['kategori']) ?></td>
                        <td><?= (int) $film['yil'] ?></td>
                        <td><?= e(puanFormat((float) $film['puan'])) ?></td>
                        <td><?= e(puanFormat(filmImdbPuan($film))) ?></td>
                        <td><span class="status-badge status-<?= e(durumSinifi((string) $film['durum'])) ?>"><?= e((string) $film['durum']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (count($filmler) > count($ilkFilmler)): ?>
        <p class="table-note">İlk <?= count($ilkFilmler) ?> kayıt gösteriliyor. İndirdiğin dosya <?= count($filmler) ?> kaydın tamamını içerir.</p>
    <?php endif; ?>
</section>

<section class="panel info-panel">
    <h2><i class="fa-solid fa-circle-info"></i> Veriler nerede saklanıyor?</h2>
    <p>
        Bu proje veritabanı kullanmaz; tüm kayıtlar PHP oturumu (<code>$_SESSION</code>) içinde tutulur.
        Bu nedenle tarayıcı oturumu kapandığında veya sunucu yeniden başladığında demo verileri sıfırlanabilir.
        Önemli bir koleksiyon oluşturduysan CSV veya JSON yedeği alman önerilir.
    </p>
</section>

<?php require 'partials/footer.php'; ?>
