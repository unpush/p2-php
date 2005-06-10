<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 - スタイルシートを外部スタイルシートとして出力する

// 初期設定読み込み & ユーザ認証
require_once 'conf/conf.php';
authorize();

// 妥当なファイルか検証
if (isset($_GET['css']) && preg_match('/^\w+$/', $_GET['css'])) {
    $css = P2_STYLE_DIR . '/' . $_GET['css'] . '_css.php';
}
if (!isset($css) || !file_exists($css)) {
    exit;
}

// ヘッダ
header('Content-Type: text/css; charset=Shift_JIS');

// スタイルシート読込
$stylesheet = '';
include_once $css;

// 表示
echo "@charset \"Shift_JIS\";\n\n";
echo $stylesheet;

?>
