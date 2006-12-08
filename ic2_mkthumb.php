<?php
/* ImageCache2 - サムネイルの再構築 */

// {{{ p2基本設定読み込み&認証

require_once 'conf/conf.inc.php';

$_login->authorize();

if (!$_conf['expack.ic2.enabled']) {
    exit('<html><body><p>ImageCache2は無効です。<br>conf/conf_admin_ex.inc.php の設定を変えてください。</p></body></html>');
}

// }}}

if ($GLOBALS['debug']) {
    require_once 'Var_Dump.php';
    Var_Dump::display($_GET);
    exit;
}

require_once 'PEAR.php';
require_once 'DB/DataObject.php';
require_once P2EX_LIBRARY_DIR . '/ic2/loadconfig.inc.php';
require_once P2EX_LIBRARY_DIR . '/ic2/database.class.php';
require_once P2EX_LIBRARY_DIR . '/ic2/thumbnail.class.php';

$uri   = $_GET['u'];
$type  = $_GET['v'];
$thumb = isset($_GET['t']) ? intval($_GET['t']) : 0;
$options = array();
$options['quality'] = isset($_GET['q']) ? intval($_GET['q']) : NULL;
$options['rotate']  = isset($_GET['r']) ? intval($_GET['r']) : 0;
$options['trim']    = !empty($_GET['p']);

$search = &new IC2DB_Images;

switch ($type) {
    case 'id':
        $search->whereAddQuoted('id', '=', $uri);
        break;
    case 'file':
        preg_match('/^([1-9][0-9]*)_([0-9a-f]{32})(?:\.(jpg|png|gif))?$/', $uri, $fdata);
        $search->whereAddQuoted('size', '=', $fdata[0]);
        $search->whereAddQuoted('md5', '=', $fdata[1]);
        break;
    default:
        $search->whereAddQuoted('uri', '=', $uri);
}

if ($search->find(TRUE)) {
    // 縦横のサイズが大きい画像はまず中間イメージを作成する
    if ($search->width > 1280 || $search->height > 1280) {
        $thumbX = &new ThumbNailer(IC2_THUMB_SIZE_INTERMD);
        $resultX = &$thumbX->convert($search->size, $search->md5, $search->mime, $search->width, $search->height);
        if (PEAR::isError($result)) {
            error($resultX->getMessage());
            exit;
        }
        $options['intermd'] = $resultX;
    }
    $thumb = &new ThumbNailer($thumb, $options);
    $result = &$thumb->convert($search->size, $search->md5, $search->mime, $search->width, $search->height);
    if (PEAR::isError($result)) {
        error($result->getMessage());
    } else {
        $name = 'filename="' . basename($result) . '"';
        if ($thumb->type == '.png') {
            header('Content-Type: image/png; ' . $name);
        } else {
            header('Content-Type: image/jpeg; ' . $name);
        }
        header('Content-Disposition: inline; ' . $name);
        echo $thumb->buf;
    }
} else {
    error("&quot;{$uri}&quot;はキャッシュされていません。");
}

function error($msg)
{
    echo <<<EOF
<html>
<head><title>ImageCache::Error</title></head>
<body>
<p>{$msg}</p>
</body>
</html>
EOF;
}

/*
 * Local variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
