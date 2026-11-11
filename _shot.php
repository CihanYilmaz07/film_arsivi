<?php
/**
 * GEÇİCİ ekran görüntüsü yardımcısı — teslimden önce silinir.
 * Edge headless oturum çerezi alabilsin diye demo hesapla giriş yapar.
 */
require_once 'functions.php';

$token = (string) ($_GET['token'] ?? '');
$hedef = (string) ($_GET['hedef'] ?? 'index.php');

if ($token !== 'chn-shot-2026') {
    http_response_code(403);
    exit('Forbidden');
}

$hedef = ltrim($hedef, '/');

if ($hedef === '' || !preg_match('#^[a-z0-9._-]+\.php(\?[^\s]*)?$#i', $hedef)) {
    $hedef = 'index.php';
}

if (!girisYapildi()) {
    girisDene(DEMO_KULLANICI, 'sinema123');
}

yonlendir($hedef);
