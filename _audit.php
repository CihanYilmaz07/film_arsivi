<?php
/**
 * GEÇİCİ düzen denetimi — teslimden önce silinir.
 */
$hedef = (string) ($_GET['hedef'] ?? 'index.php');
$hedef = ltrim($hedef, '/');
if ($hedef === '' || !preg_match('#^[a-z0-9._-]+\.php(\?[^\s]*)?$#i', $hedef)) {
    $hedef = 'index.php';
}
$url = '_shot.php?token=chn-shot-2026&hedef=' . rawurlencode($hedef);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>audit</title>
<style>html,body{margin:0}iframe{width:100%;height:4200px;border:0}</style>
</head>
<body>
<iframe id="f" src="<?= htmlspecialchars($url, ENT_QUOTES) ?>"></iframe>
<pre id="out">pending</pre>
<script>
const frame = document.getElementById('f');
frame.addEventListener('load', () => {
    setTimeout(() => {
        try {
            const doc = frame.contentDocument;
            const win = frame.contentWindow;
            const vw = doc.documentElement.clientWidth;
            const q = (sel) => {
                const el = doc.querySelector(sel);
                if (!el) return null;
                const r = el.getBoundingClientRect();
                const cs = win.getComputedStyle(el);
                return { sel, x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.width), h: Math.round(r.height), display: cs.display, color: cs.color, bg: cs.backgroundColor, fs: cs.fontSize, overflowX: cs.overflowX };
            };
            const over = Array.from(doc.querySelectorAll('body *')).filter(el => {
                const r = el.getBoundingClientRect();
                const cs = win.getComputedStyle(el);
                return r.right > vw + 2 && cs.position !== 'fixed' && cs.display !== 'none';
            }).slice(0, 12).map(el => ({ tag: el.tagName, cls: String(el.className || '').slice(0, 70), right: Math.round(el.getBoundingClientRect().right) }));
            const cards = Array.from(doc.querySelectorAll('.movie-card')).slice(0, 3).map(el => {
                const r = el.getBoundingClientRect();
                const poster = el.querySelector('.movie-poster');
                const body = el.querySelector('.movie-body');
                return { w: Math.round(r.width), h: Math.round(r.height), posterH: poster ? Math.round(poster.getBoundingClientRect().height) : null, bodyH: body ? Math.round(body.getBoundingClientRect().height) : null };
            });
            const data = {
                url: frame.contentWindow.location.href,
                title: doc.title,
                theme: doc.documentElement.getAttribute('data-theme'),
                vw,
                docW: doc.documentElement.scrollWidth,
                docH: doc.documentElement.scrollHeight,
                over,
                cards,
                els: ['.site-header','.header-inner','.brand','.main-nav','.header-actions','.hero','.hero-copy','.hero-feature','.stats-grid','.filter-panel','.filter-form','.quick-filters','.catalog','.movie-grid','.page-hero','.page-hero-actions','.footer-inner','.command-trigger','.advanced-grid','.pagination-wrap','.kpi-grid','.analytics-grid','.watchlist-summary','.profile-grid','.export-grid','.about-grid'].map(q).filter(Boolean),
                cardCount: doc.querySelectorAll('.movie-card').length,
                sheetCount: doc.styleSheets.length,
                details: Array.from(doc.querySelectorAll('details')).map(d => ({ cls: d.className, open: d.open, h: Math.round(d.getBoundingClientRect().height), grid: d.querySelector('.advanced-grid') ? { h: Math.round(d.querySelector('.advanced-grid').getBoundingClientRect().height), display: win.getComputedStyle(d.querySelector('.advanced-grid')).display, offsetParent: !!d.querySelector('.advanced-grid').offsetParent } : null })),
                navLinks: Array.from(doc.querySelectorAll('.main-nav .nav-link')).map(a => ({ text: a.textContent.trim().slice(0, 24), w: Math.round(a.getBoundingClientRect().width) }))
            };
            document.getElementById('out').textContent = 'AUDIT_JSON:' + JSON.stringify(data);
        } catch (e) {
            document.getElementById('out').textContent = 'AUDIT_ERR:' + e.message;
        }
    }, 1200);
});
</script>
</body>
</html>
