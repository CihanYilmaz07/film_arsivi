<?php
require_once 'functions.php';
girisGerekli();

/*
 * Detay sayfasındaki yıldızlı puanlama formu.
 * Puan yalnızca POST + CSRF doğrulamasıyla kaydedilir.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flashAyarla('hata', 'Puanlama işlemi yalnızca POST yöntemiyle yapılabilir.');
    yonlendir('index.php');
}

$token = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : null;

if (!csrfDogrula($token)) {
    flashAyarla('hata', 'Güvenlik doğrulaması başarısız oldu. Puan kaydedilmedi.');
    yonlendir('index.php');
}

$id   = (int) ($_POST['id'] ?? 0);
$film = filmBul($_SESSION['filmler'], $id);

if ($film === null) {
    flashAyarla('hata', 'Puanlanacak film bulunamadı.');
    yonlendir('index.php');
}

$puan = str_replace(',', '.', trim((string) ($_POST['puan'] ?? '')));

if (!is_numeric($puan) || (float) $puan < 1 || (float) $puan > 10) {
    flashAyarla('hata', 'Puan 1 ile 10 arasında bir değer olmalıdır.');
    yonlendir('detay.php?id=' . $id . '#puanla');
}

if (filmPuanla($id, (float) $puan)) {
    flashAyarla(
        'basari',
        '“' . (string) ($film['baslik'] ?? 'Film') . '” için puanın ' . puanFormat((float) $puan) . ' olarak kaydedildi.'
    );
} else {
    flashAyarla('hata', 'Puan kaydedilirken bir sorun oluştu.');
}

yonlendir('detay.php?id=' . $id . '#puanla');
