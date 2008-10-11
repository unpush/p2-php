<?php
/**
 * rep2 - クッキー認証処理
 *
 * 内部文字エンコーディング: Shift_JIS
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証


// 書き出し用変数

$return_path = 'login.php';

$next_url = <<<EOP
{$return_path}?check_regist_cookie=1&amp;regist_cookie={$_REQUEST['regist_cookie']}{$_conf['k_at_a']}
EOP;


$next_url = str_replace('&amp;', '&', $next_url);

$sid_q = defined('SID') ? '&'.strip_tags(SID) : '';
header('Location: '.$next_url.$sid_q);
exit;

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
