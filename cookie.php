<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 -  クッキー認証処理

    内部文字エンコーディング: Shift_JIS
*/

require_once 'conf/conf.php'; // 基本設定

authorize(); // ユーザ認証


// 書き出し用変数

$return_path = 'login.php';

$next_url = $return_path . '?check_regist_cookie=1&amp;regist_cookie=' . $_REQUEST['regist_cookie'];

$next_url = str_replace('&amp;', '&', $next_url);

header('Location: '.$next_url);
exit;

?>
