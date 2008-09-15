<?php
/**
 * rep2expack - スタイルシートを外部スタイルシートとして出力する
 */

// {{{ 初期設定読み込み & ユーザ認証

require_once './conf/conf.inc.php';
$_login->authorize();

// }}}
// {{{ 妥当なファイルか検証

if (isset($_GET['css']) && preg_match('/^\w+$/', $_GET['css'])) {
    $css = P2_STYLE_DIR . '/' . $_GET['css'] . '_css.inc';
    if (!file_exists($css)) {
        exit;
    }
} else {
    exit;
}

// }}}
// {{{ 出力

// クエリにユニークキーを埋め込んでいるいるので、キャッシュさせてよい
$now = time();
header('Expires: ' . http_date($now + 3600));
header('Last-Modified: ' . http_date($now));
header('Pragma: cache');
header('Content-Type: text/css; charset=Shift_JIS');
echo "@charset \"Shift_JIS\";\n\n";
ob_start();
include $css;
// 空スタイルを除去
echo preg_replace('/[a-z\\-]+[ \\t]*:[ \\t]*;/', '', ob_get_clean());

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
