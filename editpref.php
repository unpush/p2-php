<?php
/*
	p2 -  設定編集
*/

include_once './conf.inc.php';  //基本設定
require_once './filectl.class.php';
require_once './p2util.class.php';

authorize(); //ユーザ認証

$_info_msg_ht = "";

// ホストの同期用設定
if (!isset($rh_idx))     { $rh_idx     = $_conf['pref_dir'] . '/p2_res_hist.idx'; }
if (!isset($palace_idx)) { $palace_idx = $_conf['pref_dir'] . '/p2_palace.idx'; }

$synctitle = array(
	$_conf['favita_path'] => 'お気に板',
	$_conf['favlist_file'] => 'お気にスレ',
	$_conf['rct_file']     => '最近読んだスレ',
	$rh_idx      => '書き込み履歴',
	$palace_idx  => 'スレの殿堂',
);

if (isset($_POST['sync'])) {
	$syncfile = $_POST['sync'];
	if ($syncfile == $_conf['favita_path']) {
		include_once './syncfavita.inc';
	} elseif (in_array($syncfile, array($_conf['favlist_file'], $_conf['rct_file'], $rh_idx, $palace_idx))) {
		include_once './syncindex.inc';
	}
	if ($sync_ok) {
		$_info_msg_ht .= "<p>{$synctitle[$syncfile]}を同期しました。</p>";
	} else {
		$_info_msg_ht .= "<p>{$synctitle[$syncfile]}は変更されませんでした。</p>";
	}
}

// 書き出し用変数========================================
$ptitle = "設定ファイル編集";

if ($_conf['ktai']) {
	$status_st = 'ｽﾃｰﾀｽ';
	$autho_user_st = '認証ﾕｰｻﾞ';
	$client_host_st = '端末ﾎｽﾄ';
	$client_ip_st = '端末IPｱﾄﾞﾚｽ';
	$browser_ua_st = 'ﾌﾞﾗｳｻﾞUA';
	$p2error_st = 'p2 ｴﾗｰ';
} else {
	$status_st = 'ステータス';
	$autho_user_st = '認証ユーザ';
	$client_host_st = '端末ホスト';
	$client_ip_st = '端末IPアドレス';
	$browser_ua_st = 'ブラウザUA';
	$p2error_st = 'p2 エラー';
}

$autho_user_ht = "";

//=========================================================
// HTMLプリント
//=========================================================
P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html>
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<title>{$ptitle}</title>
EOP;
if(!$_conf['ktai']){
	@include("./style/style_css.inc");
	@include("./style/editpref_css.inc");
}
echo <<<EOP
</head>
<body>
EOP;

if (!$_conf['ktai']) {
	echo <<<EOP
<p id="pan_menu"><a href="setting.php">設定</a> &gt; {$ptitle}</p>
EOP;
}


echo $_info_msg_ht;
$_info_msg_ht = "";

//設定プリント=====================
$aborn_name_txt = $_conf['pref_dir']."/p2_aborn_name.txt";
$aborn_mail_txt = $_conf['pref_dir']."/p2_aborn_mail.txt";
$aborn_msg_txt = $_conf['pref_dir']."/p2_aborn_msg.txt";
$aborn_id_txt = $_conf['pref_dir']."/p2_aborn_id.txt";
$ng_name_txt = $_conf['pref_dir']."/p2_ng_name.txt";
$ng_mail_txt = $_conf['pref_dir']."/p2_ng_mail.txt";
$ng_msg_txt = $_conf['pref_dir']."/p2_ng_msg.txt";
$ng_id_txt = $_conf['pref_dir']."/p2_ng_id.txt";

if (!$_conf['ktai']) {

	echo <<<EOP
<table><tr><td>

<fieldset>
<legend><a href="http://akid.s17.xrea.com:8080/p2puki/pukiwiki.php?%5B%5BNG%A5%EF%A1%BC%A5%C9%A4%CE%C0%DF%C4%EA%CA%FD%CB%A1%5D%5D" target="read">NGワード</a>編集：</legend>
<table><tr><td>
EOP;
	printEditFileForm($ng_name_txt, "名前");
	echo "</td><td>";
	printEditFileForm($ng_mail_txt, "メール");
	echo "</td><td>";
	printEditFileForm($ng_msg_txt, "メッセージ");
	echo "</td><td>";
	printEditFileForm($ng_id_txt, " I D ");
	echo <<<EOP
</td></tr></table>
</fieldset>

</td><td>

<fieldset>
<legend>あぼーんワード編集</legend>
<table><tr><td>
EOP;
	printEditFileForm($aborn_name_txt, "名前");
	echo "</td><td>";
	printEditFileForm($aborn_mail_txt, "メール");
	echo "</td><td>";
	printEditFileForm($aborn_msg_txt, "メッセージ");
	echo "</td><td>";
	printEditFileForm($aborn_id_txt, " I D ");
	echo <<<EOP
</td>
</tr></table>
</fieldset>

</td></tr><tr><td colspan="2">
EOP;

	if( is_writable("conf_user.inc.php") || is_writable("conf_user_style.inc.php") || is_writable("conf.inc.php")){
		echo <<<EOP
<fieldset>
<legend>その他</legend>
<table><tr>
<td>
EOP;
		if (is_writable("conf_user.inc.php")) {
			printEditFileForm("conf_user.inc.php", "conf_user.inc.php");
		}
		echo "</td><td>";
		if (is_writable("conf_user_style.inc.php")) {
			printEditFileForm("conf_user_style.inc.php", "conf_user_style.inc.php");
		}
		echo "</td><td>";
		if (is_writable("conf.inc.php")) {
			printEditFileForm("conf.inc.php", "conf.inc.php");
		}
		echo <<<EOP
</td>
</tr></table>
</fieldset>
EOP;
	}

	echo <<<EOP
</td></tr><tr><td colspan="2">

<fieldset>
<legend>ホストの同期（2chの板移転に対応します）</legend>
<table><tr>
EOP;
	foreach ($synctitle as $syncpath => $syncname) {
		if (is_writable($syncpath)) {
			echo '<td>';
			printSyncFavoritesForm($syncpath, $syncname);
			echo '</td>';
		}
	}
	echo <<<EOP
</tr></table>
</fieldset>

</td></tr></table>

EOP;
}

//フッタプリント===================
if ($_conf['ktai']) {
	echo "<p>ﾎｽﾄの同期（2chの板移転に対応します）</p>\n";
	foreach ($synctitle as $syncpath => $syncname) {
		if (is_writable($syncpath)) {
			printSyncFavoritesForm($syncpath, $syncname);
		}
	}
	echo "<hr>\n";
	echo $k_to_index_ht;
}

echo <<<EOP
</body>
</html>
EOP;

//=====================================================
// 関数
//=====================================================
function printEditFileForm($path_value, $submit_value)
{
	global $_conf;
	
	$rows = 36; //18
	$cols = 92; //90
	echo <<<EOFORM
<form action="editfile.php" method="POST" target="editfile">
	{$_conf['k_input_ht']}
	<input type="hidden" name="path" value="{$path_value}">
	<input type="hidden" name="encode" value="Shift_JIS">
	<input type="hidden" name="rows" value="{$rows}">
	<input type="hidden" name="cols" value="{$cols}">
	<input type="submit" value="{$submit_value}">
</form>\n
EOFORM;
}

/**
 * ホストの同期用フォームをプリントする
 */
function printSyncFavoritesForm($path_value, $submit_value){
	global $_conf;
	
	echo <<<EOFORM
<form action="editpref.php" method="POST" target="_self">
	{$_conf['k_input_ht']}
	<input type="hidden" name="sync" value="{$path_value}">
	<input type="submit" value="{$submit_value}">
</form>\n
EOFORM;
}


?>