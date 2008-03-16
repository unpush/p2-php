<?php
/* ImageCache2 - 画像のダウンロード・サムネイル作成 */

// {{{ p2基本設定読み込み&認証

require_once 'conf/conf.inc.php';
require_once P2EX_LIBRARY_DIR . '/ic2/db_images.class.php';
require_once P2EX_LIBRARY_DIR . '/ic2/thumbnail.class.php';

$_login->authorize();

if (!$_conf['expack.ic2.enabled']) {
    exit('<html><body><p>ImageCache2は無効です。<br>conf/conf_admin_ex.inc.php の設定を変えてください。</p></body></html>');
}

// }}}

$url = $_GET['url'];
$x = '';
$y = '';

$icdb = &new IC2DB_Images;
$thumbnailer = &new ThumbNailer;
if (preg_match('/^' . preg_quote($thumbnailer->sourcedir, '/') . '/', $url) && file_exists($url)) {
    $info = getimagesize($url);
    $x = $info[0];
    $y = $info[1];
} elseif (preg_match('{(?:[\w.]*/)?ic2\.php\?(?:.*&)?ur[il]=([^&]+)(?:&|$)}', $url, $m)) {
    $url = rawurldecode($m[1]);
    if ($icdb->get($url)) {
        $url = $thumbnailer->srcPath($icdb->size, $icdb->md5, $icdb->mime);
        $x = $icdb->width;
        $y = $icdb->height;
    }
}

$alt = htmlspecialchars(basename($url));

$afi_js = '';
if ($x && $y) {
    if ($_conf['expack.ic2.fitimage'] == 1) {
        $afi_js = "autofitimage('auto',{$x},{$y})";
    } elseif ($_conf['expack.ic2.fitimage'] == 2) {
        $afi_js = "autofitimage('height',{$x},{$y})";
    } elseif ($_conf['expack.ic2.fitimage'] == 3) {
        $afi_js = "autofitimage('width',{$x},{$y})";
    }
}

P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOF
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>fitImage - {$alt}</title>
    <link rel="stylesheet" href="css.php?css=fitimage&amp;skin={$skin_en}" type="text/css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <script type="text/javascript" src="js/basic.js?{$_conf['p2expack']}"></script>
    <script type="text/javascript" src="js/fitimage.js?{$_conf['p2expack']}"></script>
</head>
<body onload="focus()">
<div id="pct">
    <img id="picture" src="{$url}" width="{$x}" height="{$y}" onclick="fiShowHide()" onload="{$afi_js}" alt="{$alt}">
</div>
<div id="btn">
    <img src="img/fi_height.gif" width="15" height="15" onclick="fitimage('height')" alt="Y"><br>
    <img src="img/fi_width.gif" width="15" height="15" onclick="fitimage('width')" alt="X"><br>
    <img src="img/fi_full.gif" width="15" height="15" onclick="fitimage('full')" alt="XY">
</div>
</body>
</html>
EOF;

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
