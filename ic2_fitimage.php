<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */

/* ImageCache2 - 画像のダウンロード・サムネイル作成 */

// {{{ p2基本設定読み込み&認証

require_once 'conf/conf.inc.php';
require_once P2EX_LIB_DIR . '/ic2/db_images.class.php';
require_once P2EX_LIB_DIR . '/ic2/thumbnail.class.php';

$_login->authorize();

if (!$_conf['expack.ic2.enabled']) {
    exit('<html><body><p>ImageCache2は無効です。<br>conf/conf_admin_ex.inc.php の設定を変えてください。</p></body></html>');
}

// }}}

$url = $_GET['url'];
$x = '';
$y = '';

$info_key_type = 'url';
$info_key_value = $url;

$icdb = &new IC2DB_Images;
$thumbnailer = &new ThumbNailer;
if (preg_match('/^' . preg_quote($thumbnailer->sourcedir, '/') . '/', $url) && file_exists($url)) {
    $info = getimagesize($url);
    $x = $info[0];
    $y = $info[1];
    $info_key_type = 'md5';
    $info_key_value = preg_replace('/^\\d+_([0-9a-f]+)\\..*/', '\\1', basename($url));
} elseif (preg_match('{(?:[\w.]*/)?ic2\.php\?(?:.*&)?ur[il]=([^&]+)(?:&|$)}', $url, $m)) {
    $url = rawurldecode($m[1]);
    if ($icdb->get($url)) {
        $url = $thumbnailer->srcPath($icdb->size, $icdb->md5, $icdb->mime);
        $x = $icdb->width;
        $y = $icdb->height;
    }
    $info_key_type = 'id';
    $info_key_value = $icdb->id;
}

$info_key_value = htmlspecialchars($info_key_value, ENT_QUOTES);

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
    {$_conf['meta_charset_ht']}
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>fitImage - {$alt}</title>
    <link rel="stylesheet" href="css.php?css=fitimage&amp;skin={$skin_en}" type="text/css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <script type="text/javascript" src="js/basic.js?{$_conf['p2expack']}"></script>
    <script type="text/javascript" src="js/fitimage.js?{$_conf['p2expack']}"></script>
    <script type="text/javascript" src="js/iv2.js?{$_conf['p2expack']}"></script>
</head>
<body onload="focus();fiGetImageInfo('{$info_key_type}', '{$info_key_value}');">
<div id="btn" style="border:1px solid black;padding:2px;background-color:white">
    <input type="hidden" id="fi_id" value=""><input type="text" id="fi_memo" size="50" value=""><br>
    <img src="img/fi_full.gif" width="15" height="15" onclick="fitimage('full')" alt="XY">
    <img src="img/fi_height.gif" width="15" height="15" onclick="fitimage('height')" alt="Y">
    <img src="img/fi_width.gif" width="15" height="15" onclick="fitimage('width')" alt="X">
    <div id="fi_stars" style="position:absolute;bottom:2;right:2;cursor:pointer"><img src="img/sn0.png" width="16" height="16" alt="-1" onclick="fiUpdateRank(-1)"><img src="img/sz1.png" width="10" height="16" alt="0" onclick="fiUpdateRank(0)"><img src="img/s0.png" width="16" height="16" alt="1" onclick="fiUpdateRank(1)"><img src="img/s0.png" width="16" height="16" alt="2" onclick="fiUpdateRank(2)"><img src="img/s0.png" width="16" height="16" alt="3" onclick="fiUpdateRank(3)"><img src="img/s0.png" width="16" height="16" alt="4" onclick="fiUpdateRank(4)"><img src="img/s0.png" width="16" height="16" alt="5" onclick="fiUpdateRank(5)"></div>
</div>
<div id="pct">
    <img id="picture" src="{$url}" width="{$x}" height="{$y}" onclick="fiShowHide()" onload="{$afi_js}" alt="{$alt}">
</div>
</body>
</html>
EOF;
