<?php
require_once 'functions.php';
girisGerekli();

/*
 * Kayıt silme işlemi yalnızca POST ile yapılır.
 * GET ile silme bağlantısı kullanılmaz; böylece yanlışlıkla/zararlı bağlantıyla
 * kayıt silinmesi engellenir.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flashAyarla('hata', 'Silme işlemi yalnızca POST yöntemiyle yapılabilir.');
    yonlendir('index.php');
}

$token = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : null;

if (!csrfDogrula($token)) {
    flashAyarla('hata', 'Güvenlik doğrulaması başarısız oldu. Kayıt silinmedi.');
    yonlendir('index.php');
}

$id   = (int) ($_POST['id'] ?? 0);
$film = filmBul($_SESSION['filmler'], $id);

if ($film === null) {
    flashAyarla('hata', 'Silmek istediğin kayıt bulunamadı. Daha önce silinmiş olabilir.');
    yonlendir('index.php');
}

$baslik = (string) ($film['baslik'] ?? 'Kayıt');

if (filmSil($id)) {
    flashAyarla('basari', '“' . $baslik . '” arşivden silindi.');
} else {
    flashAyarla('hata', 'Kayıt silinirken bir sorun oluştu.');
}

$donus = guvenliDonus($_POST['yonlendir'] ?? null);

if (str_contains($donus, 'detay.php')) {
    $donus = 'index.php';
}

yonlendir($donus);
