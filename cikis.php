<?php
require_once 'functions.php';

/*
 * Oturumu kapatma işlemi yalnızca POST + CSRF ile yapılır.
 * Böylece bir bağlantıya tıklanması kullanıcıyı istemeden çıkış yaptıramaz.
 */

$donus = girisYapildi() ? 'index.php' : 'giris.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flashAyarla('hata', 'Çıkış işlemi yalnızca POST yöntemiyle yapılabilir.');
    yonlendir($donus);
}

if (!csrfDogrula((string) ($_POST['csrf'] ?? ''))) {
    flashAyarla('hata', 'Oturum doğrulaması başarısız oldu. Tekrar deneyin.');
    yonlendir($donus);
}

cikisYap();
flashAyarla('basari', 'Güvenli biçimde çıkış yaptın. Tekrar görüşmek üzere!');
yonlendir('giris.php');
