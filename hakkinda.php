<?php
require_once 'functions.php';
girisGerekli();

$sayilar = filmSayilari($_SESSION['filmler']);

$sayfaBaslik = 'Hakkında — CHNStream';
$aktifSayfa  = 'hakkinda';
$sayfaAciklama = 'CHNStream projesinin kapsamı, özellikleri ve teknik yapısı.';

require 'partials/header.php';
?>

<section class="page-hero page-hero-compact">
    <div>
        <span class="eyebrow"><i class="fa-solid fa-circle-info"></i> Proje Bilgisi</span>
        <h1>CHN<span class="text-gradient">STREAM</span></h1>
        <p>Kişisel film ve dizi arşivi. PHP ile geliştirilmiş, çerçeve kullanmayan, oturum tabanlı bir kayıt ve takip sistemi.</p>
    </div>
    <div class="page-hero-actions">
        <a href="index.php" class="btn btn-primary"><i class="fa-solid fa-layer-group"></i> Arşivi Aç</a>
    </div>
</section>

<div class="about-grid">
    <section class="panel about-main">
        <span class="eyebrow eyebrow-sm">Amaç</span>
        <h2>İzleme yolculuğunu tek yerde tut</h2>
        <p>
            CHNStream; izlediğin, izlemek istediğin ve yarıda bıraktığın filmleri kaydetmen için tasarlandı.
            Her kayıt; tür, yönetmen, yıl, süre, ülke, kişisel puan, IMDb puanı, etiketler ve serbest açıklama alanlarıyla
            zenginleştirilir. Favoriler, yorumlar ve otomatik öneriler arşivi yaşayan bir koleksiyona dönüştürür.
        </p>

        <div class="about-stats">
            <div><strong><?= (int) $sayilar['toplam'] ?></strong><span>film</span></div>
            <div><strong><?= (int) $sayilar['kategori'] ?></strong><span>tür</span></div>
            <div><strong><?= (int) $sayilar['yorum'] ?></strong><span>yorum</span></div>
            <div><strong><?= favoriSayisi() ?></strong><span>favori</span></div>
        </div>

        <h3>Öne çıkan özellikler</h3>
        <ul class="feature-list">
            <li><i class="fa-solid fa-check"></i> Film ekleme, düzenleme, silme ve durum değiştirme (tam CRUD)</li>
            <li><i class="fa-solid fa-check"></i> Gelişmiş filtreleme: arama, tür, durum, yıl aralığı, minimum puan ve IMDb</li>
            <li><i class="fa-solid fa-check"></i> Sayfalama, ızgara/liste görünümü ve sıralama seçenekleri</li>
            <li><i class="fa-solid fa-check"></i> Favoriler, izleme listesi ve kişisel istatistik paneli</li>
            <li><i class="fa-solid fa-check"></i> Yıldızlı puanlama ve yorum sistemi</li>
            <li><i class="fa-solid fa-check"></i> Afiş yükleme (JPG, PNG, WEBP, GIF) veya URL ile afiş ekleme</li>
            <li><i class="fa-solid fa-check"></i> CSV ve JSON dışa aktarma</li>
            <li><i class="fa-solid fa-check"></i> Açık/koyu tema, komut paleti ve klavye kısayolları</li>
            <li><i class="fa-solid fa-check"></i> CSRF korumalı formlar, giriş sınırlama ve güvenlik başlıkları</li>
        </ul>
    </section>

    <aside class="about-side">
        <section class="panel">
            <div class="panel-head panel-head-compact">
                <div>
                    <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-list-check"></i> Proje Koşulları</span>
                    <h2>Karşılanan Gereksinimler</h2>
                </div>
            </div>
            <ul class="check-list">
                <li><i class="fa-solid fa-circle-check"></i> PHP ile listeleme ve dizi işlemleri</li>
                <li><i class="fa-solid fa-circle-check"></i> En az 4 alanlı kayıt yapısı</li>
                <li><i class="fa-solid fa-circle-check"></i> <code>foreach</code> ile listeleme</li>
                <li><i class="fa-solid fa-circle-check"></i> Form + POST ile kayıt</li>
                <li><i class="fa-solid fa-circle-check"></i> Sunucu tarafı doğrulama</li>
                <li><i class="fa-solid fa-circle-check"></i> POST ile silme</li>
                <li><i class="fa-solid fa-circle-check"></i> GET ile filtreleme</li>
                <li><i class="fa-solid fa-circle-check"></i> Sayaçlar ve istatistikler</li>
                <li><i class="fa-solid fa-circle-check"></i> <code>htmlspecialchars()</code> ile çıktı kaçışı</li>
                <li><i class="fa-solid fa-circle-check"></i> En az 2 kullanıcı tanımlı fonksiyon</li>
                <li><i class="fa-solid fa-circle-check"></i> <code>$_SESSION</code> ile veri saklama</li>
                <li><i class="fa-solid fa-circle-check"></i> README ve ekran görüntüleri</li>
            </ul>
        </section>

        <section class="panel">
            <div class="panel-head panel-head-compact">
                <div>
                    <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-code"></i> Teknoloji</span>
                    <h2>Teknik Yapı</h2>
                </div>
            </div>
            <ul class="tech-list">
                <li><span><i class="fa-brands fa-php"></i> PHP 8.2</span><small>Sunucu tarafı</small></li>
                <li><span><i class="fa-brands fa-html5"></i> HTML5</span><small>Anlamsal yapı</small></li>
                <li><span><i class="fa-brands fa-css3-alt"></i> CSS3</span><small>Tema ve yerleşim</small></li>
                <li><span><i class="fa-brands fa-js"></i> Vanilla JS</span><small>Etkileşim</small></li>
                <li><span><i class="fa-solid fa-database"></i> $_SESSION</span><small>Veri katmanı</small></li>
            </ul>
        </section>

        <section class="panel">
            <div class="panel-head panel-head-compact">
                <div>
                    <span class="eyebrow eyebrow-sm"><i class="fa-solid fa-keyboard"></i> Kısayollar</span>
                    <h2>Hızlı Erişim</h2>
                </div>
            </div>
            <ul class="shortcut-list">
                <li><span>Komut paleti</span><kbd>Ctrl</kbd><kbd>K</kbd></li>
                <li><span>Aramaya odaklan</span><kbd>/</kbd></li>
                <li><span>Pencereyi kapat</span><kbd>Esc</kbd></li>
            </ul>
        </section>
    </aside>
</div>

<section class="panel info-panel">
    <h2><i class="fa-solid fa-shield-halved"></i> Güvenlik ve veri notu</h2>
    <p>
        Bu proje eğitim amaçlı bir demo uygulamasıdır. Şifreler <code>password_hash()</code> ile saklanır,
        formlar CSRF anahtarıyla korunur ve tüm kullanıcı çıktıları <code>htmlspecialchars()</code> ile kaçırılır.
        Veriler <code>$_SESSION</code> içinde tutulduğu için kalıcı bir veritabanı yoktur; önemli kayıtlar için
        <a href="disa-aktar.php">CSV/JSON yedeği</a> alabilirsin.
    </p>
</section>

<?php require 'partials/footer.php'; ?>
