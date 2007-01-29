<?php
/*
    cmd 引き数でコマンド分け
    返り値は、テキストで返す
*/

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

// {{{ HTTPヘッダとXML宣言

P2Util::header_nocache();
if (P2Util::isBrowserSafariGroup()) {
	header('Content-Type: application/xml; charset=UTF-8');
	$xmldec = '<' . '?xml version="1.0" encoding="UTF-8" ?' . '>' . "\n";
} else {
	header('Content-Type: text/html; charset=Shift_JIS');
	// 半角で「？＞」が入ってる文字列をコメントにするとパースエラー
	//$xmldec = '<' . '?xml version="1.0" encoding="Shift_JIS" ?' . '>' . "\n";
	$xmldec = '';
}

// }}}

$r_msg = "";

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
            $r_msg = "1"; // 完了
        } elseif ($r == 2) {
            $r_msg = "2"; // なし
        } else {
            $r_msg = "0"; // 失敗
        }
    }
    
// }}}
// {{{ お気にスレ

} elseif ($cmd == 'setfav') {
    if (isset($_REQUEST['host']) && isset($_REQUEST['bbs']) && isset($_REQUEST['key']) && isset($_REQUEST['setfav'])) {
        require_once P2_LIB_DIR . '/setfav.inc.php';
        $r = setFav($_REQUEST['host'], $_REQUEST['bbs'], $_REQUEST['key'], $_REQUEST['setfav']);
        if (empty($r)) {
            $r_msg = "0"; // 失敗
        } elseif ($r == 1) {
            $r_msg = "1"; // 完了
        }
    }

// }}}
// {{{ 書き込みフォームのオートセーブ（※これは使っていない。通信負荷を避けて、クッキーにまかせた）

} elseif ($cmd == 'auto_save_post_form') {
    // 未実装のテスト
    ob_start();
    var_dump($_POST);
    $r_msg = ob_get_clean();

}
// }}}

// 結果出力
if (P2Util::isBrowserSafariGroup()) {
	$r_msg = mb_convert_encoding($r_msg, 'UTF-8', 'SJIS-win');
}
echo $xmldec;
echo $r_msg;

