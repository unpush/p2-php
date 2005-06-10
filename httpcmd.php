<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

/*
	cmd 引き数でコマンド分け
	返り値は、テキストで返す
*/

include_once 'conf/conf.php';  // 基本設定ファイル

authorize(); // ユーザ認証

$r_msg = '';

// cmdが指定されていなければ、何も返さずに終了
if (!isset($_GET['cmd']) && !isset($_POST['cmd'])) {
	die('');
}

// コマンド取得
if (isset($_GET['cmd'])) {
	$cmd = $_GET['cmd'];
} elseif (isset($_POST['cmd'])) {
	$cmd = $_POST['cmd'];
}

// ■ログ削除
if ($cmd == 'delelog') { 
	if (isset($_REQUEST['host']) && isset($_REQUEST['bbs']) && isset($_REQUEST['key'])) {
		include_once (P2_LIBRARY_DIR . '/dele.inc.php');
		$r = deleteLogs($_REQUEST['host'], $_REQUEST['bbs'], array($_REQUEST['key']));
		if (empty($r)) {
			$r_msg = '0'; // 失敗
		} elseif ($r == 1) {
			$r_msg = '1'; // 完了
		} elseif ($r == 2) {
			$r_msg = '2'; // なし
		}
	}

// ■お気にスレ
} elseif ($cmd == 'setfav') {
	if (isset($_REQUEST['host']) && isset($_REQUEST['bbs']) && isset($_REQUEST['key']) && isset($_REQUEST['setfav'])) {
		include_once (P2_LIBRARY_DIR . '/setfav.inc.php');
		$r = setFav($_REQUEST['host'], $_REQUEST['bbs'], $_REQUEST['key'], $_REQUEST['setfav']);
		if (empty($r)) {
			$r_msg = '0'; // 失敗
		} elseif ($r == 1) {
			$r_msg = '1'; // 完了
		}
	}
}


// 結果プリント

//$r_msg = mb_convert_encoding($r_msg, 'UTF-8', 'SJIS-win');

echo $r_msg;

?>
