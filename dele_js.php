<?php
include_once './conf.inc.php';  // 基本設定
require_once './dele.inc.php';	// 削除処理用の関数郡

authorize(); // ユーザ認証

$r_msg = "";

// ■ログ削除
if (isset($_GET['host']) && isset($_GET['bbs']) && isset($_GET['key'])) {
	$r = deleteLogs($_GET['host'], $_GET['bbs'], array($_GET['key']));
	if (empty($r)) {
		$r_msg = "0"; // 失敗
	} elseif ($r == 1) {
		$r_msg = "1"; // 完了
	} elseif ($r == 2) {
		$r_msg = "2"; // なし
	}
}

// 結果プリント

//$r_msg = mb_convert_encoding($r_msg, 'UTF-8', 'SJIS-win');

echo $r_msg;

?>
