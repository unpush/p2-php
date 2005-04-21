<?php
/*
	p2 -  設定管理
*/

include_once './conf/conf.inc.php';  //基本設定
require_once './filectl.class.php';
require_once './p2util.class.php';

authorize(); //ユーザ認証

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
		include_once './syncfavita.inc.php';
	} elseif (in_array($syncfile, array($_conf['favlist_file'], $_conf['rct_file'], $rh_idx, $palace_idx))) {
		include_once './syncindex.inc.php';
	}
	if ($sync_ok) {
		$_info_msg_ht .= "<p>{$synctitle[$syncfile]}を同期しました。</p>";
	} else {
		$_info_msg_ht .= "<p>{$synctitle[$syncfile]}は変更されませんでした。</p>";
	}
}

// 書き出し用変数========================================
$ptitle = "設定管理";

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

if (empty($_conf['ktai'])) {
//<p id="pan_menu"><a href="setting.php">設定</a> &gt; {$ptitle}</p>
	echo <<<EOP
<p id="pan_menu">{$ptitle}</p>
EOP;
}


echo $_info_msg_ht;
$_info_msg_ht = "";

// 設定プリント =====================
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

	if (is_writable("conf/conf_user.inc.php") || is_writable("conf/conf_user_style.inc.php") || is_writable("conf/conf.inc.php")) {
		echo <<<EOP
<fieldset>
<legend>その他</legend>
<table><tr>
<td>
EOP;
		if (is_writable("conf/conf_user.inc.php")) {
			printEditFileForm("conf/conf_user.inc.php", 'ユーザ設定');
		}
		echo "</td><td>";
		if (is_writable("conf/conf_user_style.inc.php")) {
			printEditFileForm("conf/conf_user_style.inc.php", 'デザイン設定');
		}
		echo "</td><td>";
		if (is_writable("conf/conf.inc.php")) {
			printEditFileForm("conf/conf.inc.php", '基本設定');
		}
		echo <<<EOP
</td>
</tr></table>
</fieldset>
EOP;
	}

	echo <<<EOP
</td></tr>
<tr><td colspan="2">\n
EOP;

	// ホストの同期 HTMLのセット
	$htm['sync'] = <<<EOP
<fieldset>
<legend>ホストの同期（2chの板移転に対応します）</legend>
<table><tr>
EOP;
	$exist_sync_flag = false;
	foreach ($synctitle as $syncpath => $syncname) {
		if (is_writable($syncpath)) {
			$exist_sync_flag = true;
			$htm['sync'] .= '<td>';
			$htm['sync'] .= getSyncFavoritesFormHt($syncpath, $syncname);
			$htm['sync'] .= '</td>';
		}
	}
	$htm['sync'] .= <<<EOP
</tr></table>
</fieldset>\n
EOP;

	if ($exist_sync_flag) {
		echo $htm['sync'];
	} else {
		echo "&nbsp;";
		// echo "<p>ホストの同期は必要ありません</p>";
	}

	echo <<<EOP
</td></tr></table>\n
EOP;
}

// 携帯用表示
if ($_conf['ktai']) {
	$htm['sync'] .= "<p>ﾎｽﾄの同期（2chの板移転に対応します）</p>\n";
	$exist_sync_flag = false;
	foreach ($synctitle as $syncpath => $syncname) {
		if (is_writable($syncpath)) {
			$exist_sync_flag = true;
			$htm['sync'] .= getSyncFavoritesFormHt($syncpath, $syncname);
		}
	}	
	if ($exist_sync_flag) {
		echo $htm['sync'];
	} else {
		// echo "<p>ﾎｽﾄの同期は必要ありません</p>";
	}
}

// {{{ 新着まとめ読みのキャッシュ表示
$max = $_conf['matome_cache_max'];
for ($i = 0; $i <= $max; $i++) {
	$dnum = ($i) ? '.'.$i : '';
	$ai = '&amp;cnum='.$i;
	$file = $_conf['matome_cache_path'].$dnum.$_conf['matome_cache_ext'];
	//echo '<!-- '.$file.' -->';
	if (file_exists($file)) {
		$date = date('Y/m/d G:i:s', filemtime($file));
		$b = filesize($file)/1024;
		$kb = round($b, 0);
		$url = 'read_new.php?cview=1'.$ai;
		if ($i == 0) {
			$links[] = '<a href="'.$url.'" target="read">'.$date.'</a> '.$kb.'KB';
		} else {
			$links[] = '<a href="'.$url.'" target="read">'.$date.'</a> '.$kb.'KB';
		}
	}
}
if (!empty($links)) {
	if ($_conf['ktai']) {
		echo '<hr>'."\n";
	}
	echo $htm['matome'] = '<p>新着まとめ読みの前回キャッシュを表示<br>' . implode('<br>', $links) . '</p>';
}
// }}}

// 携帯用フッタ
if ($_conf['ktai']) {
	echo "<hr>\n";
	echo $_conf['k_to_index_ht']."\n";
}

echo '</body></html>';

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
 * ホストの同期用フォームのHTMLを取得する
 */
function getSyncFavoritesFormHt($path_value, $submit_value)
{
	global $_conf;
	
	$ht = <<<EOFORM
<form action="editpref.php" method="POST" target="_self">
	{$_conf['k_input_ht']}
	<input type="hidden" name="sync" value="{$path_value}">
	<input type="submit" value="{$submit_value}">
</form>\n
EOFORM;

	return $ht;
}

?>