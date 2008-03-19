<?php
/*
	+live - 表示設定の重複エラー処理 ../read_header.inc.php より読み込まれる
*/

// エラー処理
$live_error_top = "<html><body><h3>+live error:";
$live_error_end = "</h3></body></html>";
$live_conf_link = "<br><br>　　　　　　　<span><a href=\"./edit_conf_user.php\">ユーザ設定編集で修正する</a></span>";

// 表示設定の重複エラー表示
$view_bbs_error = "{$live_error_top} 板 <span style=\"color: #900;\">{$aThread->bbs}</span> で通常表示と実況用表示の設定値が重複しています。{$live_conf_link}{$live_error_end}";
$view_host_error = "{$live_error_top} 鯖 <span style=\"color: #900;\">{$aThread->host}</span> で通常表示と実況用表示の設定値が重複しています。<br>除外設定出来るのは <span style=\"color: #900;\">板単位</span> のみです。{$live_conf_link}{$live_error_end}";
$view_all_error = "{$live_error_top} <span style=\"color: #900;\">全ての鯖と板</span> で通常表示と実況用表示の設定値が重複しています。<br><br>　　　　　　　除外設定出来るのは <span style=\"color: #900;\">板単位</span> のみです。{$live_conf_link}{$live_error_end}";

// オートリロード+スクロール設定の重複エラー表示
$rel_bbs_error = "{$live_error_top} 板 <span style=\"color: #900;\">{$aThread->bbs}</span> でオートリロード/スクロール有無の設定値が重複しています。{$live_conf_link}{$live_error_end}";
$rel_host_error = "{$live_error_top} 鯖 <span style=\"color: #900;\">{$aThread->host}</span> でオートリロード/スクロール有無の設定値が重複しています。<br>除外設定出来るのは <span style=\"color: #900;\">板単位</span> のみです。{$live_conf_link}{$live_error_end}";
$rel_all_error = "{$live_error_top} <span style=\"color: #900;\">全ての鯖と板</span> でオートリロード/スクロール有無の設定値が重複しています。<br>除外設定出来るのは <span style=\"color: #900;\">板単位</span> のみです。{$live_conf_link}{$live_error_end}";

// 時間設定のエラー表示
$rel_time_error = "{$live_error_top} オートリロード時間が <span style=\"color: #900;\">{$_conf['live.reload_time']}</span> 秒に設定されています、設定値は最短で <span style=\"color: #900;\">5</span> 秒になります。{$live_conf_link}{$live_error_end}";

// 時間設定
if ($_GET['lastres'] == $aThread->rescount) {
	$reload_time = $_GET['reltime'] + 5000;
} else {
	$reload_time = $_conf['live.reload_time'] * 1000;
}

// スレ立てからの日数による処理
$thr_birth = date("U", $aThread->key);

if ($_conf['live.time_lag'] != 0) {
	$thr_time_lag = $_conf['live.time_lag'] * 86400;
} else {
	$thr_time_lag = 365 * 86400;
}

// 表示設定の重複エラー処理
// bbs
if (preg_match("({$aThread->bbs})", $_conf['live.default_view']) && preg_match("({$aThread->bbs})", $_conf['live.view'])) {
	die($view_bbs_error);
// host
} elseif (preg_match("({$aThread->host})", $_conf['live.default_view']) && preg_match("({$aThread->host})", $_conf['live.view'])) {
	die($view_host_error);
// all
} elseif ($_conf['live.default_view'] == all && $_conf['live.view'] == all) {
	die($view_all_error);
// オートリロード+スクロール設定の重複エラー処理
// bbs
} elseif (preg_match("({$aThread->bbs})", $_conf['live.default_reload']) && preg_match("({$aThread->bbs})", $_conf['live.reload'])) {
	die($rel_bbs_error);
// host
} elseif (preg_match("({$aThread->host})", $_conf['live.default_reload']) && preg_match("({$aThread->host})", $_conf['live.reload'])) {
	die($rel_host_error);
// all
} elseif ($_conf['live.default_reload'] == all && $_conf['live.reload'] == all) {
	die($rel_all_error);
// 
} elseif (!preg_match("({$aThread->bbs})", $_conf['live.default_reload'])
&& (preg_match("({$aThread->bbs}|{$aThread->host})", $_conf['live.reload']) || $_conf['live.reload'] == all)) {
	// 時間設定のエラー処理
	if ($reload_time < 5000 && $reload_time > 0) {
		die($rel_time_error);
	// スレ立てからの日数による処理
	} elseif (date("U") > $thr_birth + $thr_time_lag) {
		echo "";
	// 検索結果等
	} elseif ($_GET['word'] || !$_GET['live']) {
		echo "";
	} else {
		include_once (P2_LIBRARY_DIR . '/live/live_js.inc.php');
	}
} else {
	echo "";
}

?>