<?php
require_once 'functions.php';
girisGerekli();

/*
 * Detay sayfasındaki yorum formu.
 * Yorum yalnızca POST + CSRF doğrulamasıyla kaydedilir.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flashAyarla('hata', 'Yorum gönderme işlemi yalnızca POST yöntemiyle yapılabilir.');
    yonlendir('index.php');
}

$token = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : null;

if (!csrfDogrula($token)) {
    flashAyarla('hata', 'Güvenlik doğrulaması başarısız oldu. Yorum kaydedilmedi.');
    yonlendir('index.php');
}

$id   = (int) ($_POST['id'] ?? 0);
$film = filmBul($_SESSION['filmler'], $id);

if ($film === null) {
    flashAyarla('hata', 'Yorum yapılacak film bulunamadı.');
    yonlendir('index.php');
}

$sonuc = yorumEkle($id, [
    'ad'    => $_POST['ad'] ?? '',
    'yorum' => $_POST['yorum'] ?? '',
    'puan'  => $_POST['puan'] ?? '',
]);

if ($sonuc['ok']) {
    flashAyarla('basari', 'Yorumun “' . (string) ($film['baslik'] ?? 'Film') . '” sayfasına eklendi. Teşekkürler!');
} else {
    $mesajlar = array_values($sonuc['hatalar']);
    flashAyarla('hata', implode(' ', $mesajlar));
}

yonlendir('detay.php?id=' . $id . '#yorumlar');
