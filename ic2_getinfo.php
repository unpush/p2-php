<?php
/**
 * ImageCache2 - 画像のID or URLから情報を取得する
 */

// {{{ p2基本設定読み込み&認証

require_once './conf/conf.inc.php';

$_login->authorize();

if (!$_conf['expack.ic2.enabled']) {
    p2die('ImageCache2は無効です。', 'conf/conf_admin_ex.inc.php の設定を変えてください。');
}

// }}}
// {{{ HTTPヘッダ

P2Util::header_nocache();
header('Content-Type: application/json; charset=UTF-8');

// }}}
// {{{ 初期化

// パラメータを検証
if (!isset($_GET['id']) && !isset($_GET['url']) && !isset($_GET['md5'])) {
    echo 'null';
    exit;
}

// ライブラリ読み込み
require_once P2EX_LIB_DIR . '/ic2/bootstrap.php';

// }}}
// {{{ execute

$icdb = new IC2_DataObject_Images;
if (isset($_GET['id'])) {
    $icdb->whereAdd(sprintf('id=%d', (int)$_GET['id']));
} elseif (isset($_GET['url'])) {
    $icdb->whereAddQuoted('uri', '=', (string)$_GET['url']);
} else {
    $icdb->whereAddQuoted('md5', '=', (string)$_GET['md5']);
}

if (!$icdb->find(1)) {
    echo 'null';
    exit;
}

$thumb_type = isset($_GET['t']) ? $_GET['t'] : IC2_Thumbnailer::SIZE_DEFAULT;
switch ($thumb_type) {
    case IC2_Thumbnailer::SIZE_PC:
    case IC2_Thumbnailer::SIZE_MOBILE:
    case IC2_Thumbnailer::SIZE_INTERMD:
        $thumbnailer = new IC2_Thumbnailer($thumb_type);
        break;
    default:
        $thumbnailer = new IC2_Thumbnailer();
}

$src = $thumbnailer->srcPath($icdb->size, $icdb->md5, $icdb->mime);
$thumb = $thumbnailer->thumbPath($icdb->size, $icdb->md5, $icdb->mime);

echo json_encode(array(
    'id'     => (int)$icdb->id,
    'uri'    => $icdb->uri,
    'host'   => $icdb->host,
    'name'   => $icdb->name,
    'size'   => (int)$icdb->size,
    'md5'    => $icdb->md5,
    'width'  => (int)$icdb->width,
    'height' => (int)$icdb->height,
    'mime'   => $icdb->mime,
    'rank'   => (int)$icdb->rank,
    'time'   => (int)$icdb->time,
    'memo'   => $icdb->memo,
    'url'    => $icdb->uri,
    'src'    => ($src && file_exists($src)) ? $src : null,
    'thumb'  => ($thumb && file_exists($thumb)) ? $thumb : null,
));
exit;

// }}}

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
