<?php
require_once 'functions.php';
girisGerekli();

/*
 * Arşivden rastgele bir film seçip detay sayfasına yönlendirir.
 * "Rastgele Film" kısayolu için küçük bir keşif özelliğidir.
 */

$film = rastgeleFilm($_SESSION['filmler']);

if ($film === null) {
    flashAyarla('bilgi', 'Arşivde henüz film yok. Önce bir film ekleyerek başla.');
    yonlendir('ekle.php');
}

yonlendir('detay.php?id=' . (int) $film['id']);
