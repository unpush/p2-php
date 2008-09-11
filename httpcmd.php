<?php
/**
 * rep2 - Ajax
 * cmd 引き数でコマンド分け
 * 返り値は、テキストで返す
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

// {{{ HTTPヘッダとXML宣言

P2Util::header_nocache();
header('Content-Type: text/html; charset=Shift_JIS');

// }}}

$r_msg = '';

// コマンド取得 (指定されていなければ、何も返さずに終了)
if (!isset($_REQUEST['cmd'])) {
    exit;
} else {
    $cmd = $_REQUEST['cmd'];
}

switch ($cmd) {
// {{{ ログ削除

case 'delelog':
    if (isset($_REQUEST['host']) && isset($_REQUEST['bbs']) && isset($_REQUEST['key'])) {
        require_once P2_LIB_DIR . '/dele.inc.php';
        $r = deleteLogs($_REQUEST['host'], $_REQUEST['bbs'], array($_REQUEST['key']));
        if (empty($r)) {
            $r_msg = "0"; // 失敗
        } elseif ($r == 1) {
            $r_msg = "1"; // 完了
        } elseif ($r == 2) {
            $r_msg = "2"; // なし
        }
    }
    break;

// }}}
// {{{ お気にスレ

case 'setfav':
    if (isset($_REQUEST['host']) && isset($_REQUEST['bbs']) && isset($_REQUEST['key']) && isset($_REQUEST['setfav'])) {
        require_once P2_LIB_DIR . '/setfav.inc.php';
        if (isset($_REQUEST['setnum'])) {
            $r = setFav($_REQUEST['host'], $_REQUEST['bbs'], $_REQUEST['key'], $_REQUEST['setfav'], $_REQUEST['setnum']);
        } else {
            $r = setFav($_REQUEST['host'], $_REQUEST['bbs'], $_REQUEST['key'], $_REQUEST['setfav']);
        }
        if (empty($r)) {
            $r_msg = "0"; // 失敗
        } elseif ($r == 1) {
            $r_msg = "1"; // 完了
        }
    }
    break;

// }}}
// {{{ スレッドあぼーん

case 'taborn':
    if (isset($_REQUEST['host']) && isset($_REQUEST['bbs']) && isset($_REQUEST['key']) && isset($_REQUEST['taborn'])) {
        require_once P2_LIB_DIR . '/settaborn.inc.php';
        $r = settaborn($_REQUEST['host'], $_REQUEST['bbs'], $_REQUEST['key'], $_REQUEST['taborn']);
        if (empty($r)) {
            $r_msg = "0"; // 失敗
        } elseif ($r == 1) {
            $r_msg = "1"; // 完了
        }
    }
    break;

// }}}
// {{{ ImageCaceh2 ON/OFF

case 'ic2':
    if (isset($_REQUEST['switch'])) {
        require_once P2EX_LIB_DIR . '/ic2/Switch.php';
        $switch = (bool)$_REQUEST['switch'];
        if (IC2_Switch::set($switch, !empty($_REQUEST['mobile']))) {
            if ($switch) {
                $r_msg = '1'; // ONにした
            } else {
                $r_msg = '2'; // OFFにした
            }
        } else {
            $r_msg = '0'; // 失敗
        }
    }
    break;

// }}}
}
// {{{ 結果出力

if (P2Util::isBrowserSafariGroup()) {
    $r_msg = P2Util::encodeResponseTextForSafari($r_msg);
}
echo $r_msg;

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
