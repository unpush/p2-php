<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

require_once 'conf/conf.php';

authorize();

//初期化
$url = $_GET['url'];
$x = '';
$y = '';

// ImageCache2
if ($_exconf['imgCache']['*']) {
    require_once (P2EX_LIBRARY_DIR . '/ic2/db_images.class.php');
    require_once (P2EX_LIBRARY_DIR . '/ic2/thumbnail.class.php');
    $icdb = &new IC2DB_Images;
    $thumbnailer = &new ThumbNailer;
    $pat3 = '/^' . preg_quote($thumbnailer->sourcedir, '/') . '/';
    $pat4 = '{(?:[\w.]*/)?ic2\.php\?(?:.*&)?ur[il]=([^&]+)(?:&|$)}';
    if (preg_match($pat3, $url) && file_exists($url)) {
        $info = getimagesize($url);
        $x = $info[0];
        $y = $info[1];
    } elseif (preg_match($pat4, $url, $m)) {
        $url = rawurldecode($m[1]);
        if ($icdb->get($url)) {
            $url = $thumbnailer->srcPath($icdb->size, $icdb->md5, $icdb->mime);
            $x = $icdb->width;
            $y = $icdb->height;
        }
    }
}

$alt = htmlspecialchars(basename($url));

$afi_js = '';
if ($x && $y) {
    switch ($_exconf['fitImage']['auto']) {
        case 1:
            $afi_js = "autofitimage('height',{$x},{$y})";
            break;
        case 2:
            $afi_js = "autofitimage('width',{$x},{$y})";
            break;
        case 3:
            $afi_js = "autofitimage('auto',{$x},{$y})";
            break;
    }
}

// 出力
P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
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
    <script type="text/javascript" src="js/basic.js"></script>
    <script type="text/javascript" src="js/fitimage.js"></script>
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
?>
