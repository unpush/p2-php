<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 - 書き込み履歴 レス内容表示
// フレーム分割画面、右下部分

require_once 'conf/conf.php';	//基本設定読込
require_once (P2_LIBRARY_DIR . '/dataphp.class.php');
require_once (P2_LIBRARY_DIR . '/res_hist.class.php');
require_once (P2_LIBRARY_DIR . '/read_res_hist.inc.php');

/*
$debug = false;
$debug && require_once 'Benchmark/Profiler.php';
$debug && $prof = &new Benchmark_Profiler;
$debug && $prof->start();
*/

authorize(); // ユーザ認証

//======================================================================
// 変数
//======================================================================
$newtime = date('gis');
$p2_res_hist_dat_php = $_conf['pref_dir'].'/p2_res_hist.dat.php';

$deletemsg_st = '削除';
$deleteallmsg_st = '全件削除';
$ptitle = '書き込んだレスの記録';

//================================================================
// 特殊な前置処理
//================================================================
// 削除
if (isset($_POST['delete_all']) && $_POST['delete_all'] == $deleteallmsg_st) {
	@unlink($p2_res_hist_dat_php) or die("p2 Error: {$p2_res_hist_dat_php} を削除できませんでした");
	FileCtl::make_datafile($p2_res_hist_dat_php, $_conf['res_write_perm']);
} elseif (isset($_POST['submit']) && $_POST['submit'] == $deletemsg_st) {
	deleMsg($_POST['checked_hists']);
}

// 旧形式の書き込み履歴を新形式に変換する
P2Util::transResHistLog();

//======================================================================
// メイン
//======================================================================

//==================================================================
// 特殊DAT読み
//==================================================================

// 読み込んで
if (!$datlines = DataPhp::fileDataPhp($p2_res_hist_dat_php)) {
	die("p2 - 書き込み履歴内容は空っぽのようです");
}

$datlines = array_map('rtrim', $datlines);

// ファイルの下に記録されているものが新しい
$datlines = array_reverse($datlines);

$aResHist = &new ResHist;

$n = 1;
if ($datlines) {
	foreach ($datlines as $aline) {

		$aResArticle = &new ResArticle;

		$resar = explode("\t", $aline);
		$aResArticle->name = $resar[0];
		$aResArticle->mail = $resar[1];
		$aResArticle->daytime = $resar[2];
		$aResArticle->msg = $resar[3];
		$aResArticle->ttitle = $resar[4];
		$aResArticle->host = $resar[5];
		$aResArticle->bbs = $resar[6];
		$aResArticle->itaj = P2Util::getItaName($aResArticle->host, $aResArticle->bbs);
		if (!$aResArticle->itaj) {
			$aResArticle->itaj = $aResArticle->bbs;
		}
		$aResArticle->key = trim($resar[7]);
		$aResArticle->order = $n;

		$aResHist->addRes($aResArticle);

		$n++;
	}
}

// HTMLプリント用変数
$htm['toolbar'] = <<<EOP
			チェックした項目を<input type="submit" name="submit" value="{$deletemsg_st}">
			　全てのチェックボックスを 
            <input type="button" onclick="hist_checkAll(true)" value="選択"> 
            <input type="button" onclick="hist_checkAll(false)" value="解除">
			|
			<input type="submit" name="delete_all" value="{$deleteallmsg_st}">
EOP;

//==================================================================
// ヘッダ 表示
//==================================================================
P2Util::header_content_type();
if ($_conf['doctype']) {
	echo $_conf['doctype'];
}
echo <<<EOP
<html lang="ja">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<title>{$ptitle}</title>
EOP;

// PC用表示
if (!$_conf['ktai']) {
	echo <<<EOP
	<link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
	<link rel="stylesheet" href="css.php?css=read&amp;skin={$skin_en}" type="text/css">
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	<script type="text/javascript" src="js/basic.js"></script>
	<script type="text/javascript" src="js/respopup.js"></script>
	<script type="text/javascript">
	function hist_checkAll(mode) {
		if (!document.getElementsByName) {
			return;
		}
		var checkboxes = document.getElementsByName('checked_hists[]');
		var cbnum = checkboxes.length;
		for (var i = 0; i < cbnum; i++) {
			checkboxes[i].checked = mode;
		}
	}
	var gIsPageLoaded = false;
	</script>
EOP;
	$body_onload = ' onload="gIsPageLoaded = true;"';
	$form_onsubmit = ' onsubmit="if(gIsPageLoaded){return true;}else{alert(\'まだページを読み込み中なんです。もうちょっと待って。\');return false;}"';
} else {
	$body_onload = '';
	$form_onsubmit = '';
}

echo <<<EOP
</head>
<body{$k_color_settings}{$body_onload}>\n
EOP;

echo $_info_msg_ht;
$_info_msg_ht = '';

// 携帯用表示
if ($_conf['ktai']) {
	echo "{$ptitle}<br>";
	echo "<div id=\"header\" name=\"header\">";
	$aResHist->showNaviK("header");
	echo " <a {$_conf['accesskey']}=\"8\" href=\"#footer\">8.▼</a><br>";
	echo '</div>';
	echo '<hr>';

// PC用表示
} else {
	echo <<<EOP
<form method="post" action="./read_res_hist.php" target="_self"{$form_onsubmit}>
<input type="hidden" name="detect_hint" value="◎◇">

<table id="header" width="100%" style="padding:0px 10px 0px 0px;">
	<tr>
		<td align="left">
			<h3 class="thread_title">{$ptitle}</h3>
		</td>
		<td align="right">{$htm['toolbar']}</td>
		<td align="right" style="padding-left:12px;"><a href="#footer">▼</a></td>
	</tr>
</table>\n
EOP;
}


//==================================================================
// レス記事 表示
//==================================================================
if ($_conf['ktai']) {
	$aResHist->showArticlesK();
} else {
	$aResHist->showArticles();
}

//==================================================================
// フッタ 表示
//==================================================================
// 携帯用表示
if ($_conf['ktai']) {
	echo "<div id=\"footer\" name=\"footer\">";
	$aResHist->showNaviK("footer");
	echo " <a {$_conf['accesskey']}=\"2\" href=\"#header\">2.▲</a><br>";
	echo '</div>';
	echo "<p>{$_conf['k_to_index_ht']}</p>";

// PC用表示
} else {
	echo '<hr>';
	echo <<<EOP
<table id="footer" width="100%" style="padding:0px 10px 0px 0px;">
	<tr>
		<td align="right">{$htm['toolbar']}</td>
		<td align="right" style="padding-left:12px;"><a href="#header">▲</a></td>
	</tr>
</table>\n
EOP;
}

if (!$_conf['ktai']) {
	echo '</form>'."\n";
}

//$debug && $prof->stop();
//$debug && $prof->display();

echo '</body></html>';

?>
