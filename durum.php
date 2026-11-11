<?php
require_once 'functions.php';
girisGerekli();

/*
 * İzlenme durumunu tek tıkla bir sonraki aşamaya taşır.
 * İzlenecek -> İzleniyor -> İzlendi -> İzlenecek
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flashAyarla('hata', 'Durum değiştirme işlemi yalnızca POST yöntemiyle yapılabilir.');
    yonlendir('index.php');
}

$token = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : null;

if (!csrfDogrula($token)) {
    flashAyarla('hata', 'Güvenlik doğrulaması başarısız oldu. Durum değiştirilmedi.');
    yonlendir('index.php');
}

$id   = (int) ($_POST['id'] ?? 0);
$film = filmBul($_SESSION['filmler'], $id);

if ($film === null) {
    flashAyarla('hata', 'Durumu değiştirilecek kayıt bulunamadı.');
    yonlendir('index.php');
}

$yeniDurum = filmDurumDegistir($id);

if ($yeniDurum === null) {
    flashAyarla('hata', 'Durum güncellenirken bir sorun oluştu.');
} else {
    flashAyarla(
        'basari',
        '“' . (string) ($film['baslik'] ?? 'Kayıt') . '” durumu “' . $yeniDurum . '” olarak güncellendi.'
    );
}

/* Detay sayfasından gelindiyse oraya, aksi halde arşive dön. */
$donus = guvenliDonus($_POST['yonlendir'] ?? $_POST['donus'] ?? null);

yonlendir($donus);
