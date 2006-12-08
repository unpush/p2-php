<?php
// p2 - スタイルシートを外部スタイルシートとして出力する

// 初期設定読み込み & ユーザ認証
require_once 'conf/conf.inc.php';
$_login->authorize();

// 妥当なファイルか検証
if (isset($_GET['css']) && preg_match('/^\w+$/', $_GET['css'])) {
    $css = P2_STYLE_DIR . '/' . $_GET['css'] . '_css.inc';
}
if (!isset($css) || !file_exists($css)) {
    exit;
}

// ヘッダ
header('Content-Type: text/css; charset=Shift_JIS');

// スタイルシート読込
ob_start();
include_once $css;
$stylesheet = ob_get_contents();
ob_end_clean();

// 表示
echo "@charset \"Shift_JIS\";\n\n";
echo $stylesheet;


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * mode: php
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
