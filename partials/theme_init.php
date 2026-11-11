<?php
/**
 * Sayfa boyanmadan önce tema tercihini uygular.
 * Koyu/açık tema geçişinde ekranda titreme (flash) oluşmasını engeller.
 *
 * Varsayılan tema sıcak açık temadır. Kullanıcı temayı değiştirdiyse
 * tercih localStorage'da saklanır; ?tema=light|dark bağlantısı da
 * tercihi kalıcı olarak günceller.
 */
?>
<script>
    (function () {
        var meta = document.querySelector('meta[name="theme-color"]');

        var temaUygula = function (tema) {
            document.documentElement.setAttribute('data-theme', tema);

            if (meta) {
                meta.setAttribute('content', tema === 'light' ? '#f3ece1' : '#08090d');
            }
        };

        try {
            var kayitli = window.localStorage.getItem('chnstream-theme');
            var adresTema = null;

            try {
                var parametre = new URLSearchParams(window.location.search).get('tema');

                if (parametre === 'light' || parametre === 'dark') {
                    adresTema = parametre;
                }
            } catch (parametreHatasi) {
                adresTema = null;
            }

            if (adresTema !== null) {
                try {
                    window.localStorage.setItem('chnstream-theme', adresTema);
                } catch (depolamaHatasi) {
                    /* Gizli sekmede localStorage kapalı olabilir. */
                }
            }

            /* İlk açılışta sıcak açık tema kullanılır. */
            var tema = adresTema || (kayitli === 'light' || kayitli === 'dark' ? kayitli : 'light');
            temaUygula(tema);
        } catch (hata) {
            temaUygula('light');
        }
    })();
</script>
