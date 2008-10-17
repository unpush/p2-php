<?php
/**
 * p2 -  クッキー認証処理
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証


header('Location: ' . _getCookieLocationUri());

exit;


//===========================================================================
// 関数（このファイル内でのみ利用）
//===========================================================================
/**
 * @return  string
 */
function _getCookieLocationUri()
{
    $qs = array(
        'check_regist_cookie' => '1',
        'regist_cookie'     => intval(geti($_REQUEST['regist_cookie'])),
        UA::getQueryKey()   => UA::getQueryValue()
    );
    if ($session_id = session_id()) {
        $qs[session_name()] = $session_id;
    }
    return $next_uri = P2Util::buildQueryUri('login.php', $qs);
}


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
