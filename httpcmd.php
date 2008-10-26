<?php
/*
    ajax用に
    cmd 引き数でコマンド分け
    返り値は、テキストで返す
*/

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

// {{{ HTTPヘッダとXML宣言

P2Util::headerNoCache();
if (UA::isSafariGroup()) {
    header('Content-Type: application/xml; charset=UTF-8');
    $xmldecTag = '<' . '?xml version="1.0" encoding="UTF-8" ?' . '>' . "\n";
} else {
    header('Content-Type: text/html; charset=Shift_JIS');
    // 半角で「？＞」が入ってる文字列をコメントにするとパースエラー
    //$xmldecTag = '<' . '?xml version="1.0" encoding="Shift_JIS" ?' . '>' . "\n";
    $xmldecTag = '';
}

// }}}

$r_msg_ht = '';

// cmdが指定されていなければ、何も返さずに終了
if (!isset($_GET['cmd']) && !isset($_POST['cmd'])) {
    die;
}

// コマンド取得
if (isset($_GET['cmd'])) {
    $cmd = $_GET['cmd'];
} elseif (isset($_POST['cmd'])) {
    $cmd = $_POST['cmd'];
}


// {{{ ログ削除

if ($cmd == 'delelog') { 
    if (isset($_REQUEST['host']) && isset($_REQUEST['bbs']) && isset($_REQUEST['key'])) {
        require_once P2_LIB_DIR . '/dele.inc.php';
        $r = deleteLogs($_REQUEST['host'], $_REQUEST['bbs'], array($_REQUEST['key']));
        if ($r == 1) {
            $r_msg_ht = '1'; // 完了
        } elseif ($r == 2) {
            $r_msg_ht = '2'; // なし
        } else {
            $r_msg_ht = '0'; // 失敗
        }
    }
    
// }}}
// {{{ お気にスレ

} elseif ($cmd == 'setfav') {
    if (isset($_REQUEST['host']) && isset($_REQUEST['bbs']) && isset($_REQUEST['key']) && isset($_REQUEST['setfav'])) {
        require_once P2_LIB_DIR . '/setfav.inc.php';
        $r = setFav($_REQUEST['host'], $_REQUEST['bbs'], $_REQUEST['key'], $_REQUEST['setfav']);
        if (empty($r)) {
            $r_msg_ht = '0'; // 失敗
        } elseif ($r == 1) {
            $r_msg_ht = '1'; // 完了
        }
    }

// }}}
// {{{ 書き込みフォームのオートセーブ（※これは使っていない。通信負荷を避けて、クッキーにまかせた）

} elseif ($cmd == 'auto_save_post_form') {
    // 未実装のテスト
    ob_start();
    var_dump($_POST);
    $r_msg = ob_get_clean();
    $r_msg_ht = hs($r_msg);

}

// }}}

if (UA::isSafariGroup()) {
    $r_msg_ht = mb_convert_encoding($r_msg_ht, 'UTF-8', 'SJIS-win');
}

// 結果出力
echo $xmldecTag;
echo $r_msg_ht;


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
