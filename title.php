<?php
// p2 -  タイトルページ

include("./conf.php");   //基本設定ファイル読込
require_once './p2util.class.php';	// p2用のユーティリティクラス
require_once './filectl.class.php';
require_once("./datactl.inc");

authorize(); //ユーザ認証

//=========================================================
// 変数
//=========================================================
$_info_msg_ht = "";

$p2web_url_r = P2Util::throughIme($p2web_url);

// パーミッション注意喚起 ================
if ($prefdir == $datdir) {
	datadir_writable_check($prefdir);
} else {
	datadir_writable_check($prefdir);
	datadir_writable_check($datdir);
}

//=========================================================
// ●ID 2ch オートログイン
//=========================================================
if (file_exists($_conf['idpw2ch_php'])) {
	include($_conf['idpw2ch_php']);
	$login2chPW = base64_decode($login2chPW);
	include_once("./crypt_xor.inc");
	$login2chPW = decrypt_xor($login2chPW, $_conf['crypt_xor_key']);
	if($autoLogin2ch){
		include_once("./login2ch.inc");
		login2ch();
	}
}

$p_htm = array();

// 最新版チェック
if ($_conf['updatan_haahaa']) {
	$newversion_found = checkUpdatan();
}

// 認証ユーザ情報
$autho_user_ht = "";
if ($login['use']) {
	$autho_user_ht = "<p>ログインユーザ: {$login['user']} - ".date("Y/m/d (D) G:i")."</p>\n";
}

// 前回のログイン情報
if ($_conf['login_log_rec'] && $_conf['last_login_log_show']) {
	if (($log = P2Util::getLastAccessLog($_conf['login_log_file'])) !== false) {
		$p_htm['log'] = array_map('htmlspecialchars', $log);
		$p_htm['last_login'] =<<<EOP
前回のログイン情報 - {$p_htm['log']['date']}<br>
ユーザ: {$p_htm['log']['user']}<br>
IP: {$p_htm['log']['ip']}<br>
HOST: {$p_htm['log']['host']}<br>
UA: {$p_htm['log']['ua']}<br>
REFERER: {$p_htm['log']['referer']}
EOP;
	}
/*
	$p_htm['log'] =<<<EOP
<table cellspacing="0" cellpadding="2";>
	<tr>
		<td colspan="2">前回のログイン情報</td>
	</tr>
	<tr>
		<td align="right">時刻: </td><td>{$alog['date']}</td>
	</tr>
	<tr>
		<td align="right">ユーザ: </td><td>{$alog['user']}</td>
	</tr>
	<tr>
		<td align="right">IP: </td><td>{$alog['ip']}</td>
	</tr>
	<tr>
		<td align="right">HOST: </td><td>{$alog['host']}</td>
	</tr>
	<tr>
		<td align="right">UA: </td><td>{$alog['ua']}</td>
	</tr>
	<tr>
		<td align="right">REFERER: </td><td>{$alog['referer']}</td>
</table>
EOP;
*/
}

//=========================================================
// HTMLプリント
//=========================================================
$ptitle = "p2 - title";

header_content_type();
if ($doctype) { echo $doctype;}
echo <<<EOP
<html lang="ja">
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<title>{$ptitle}</title>
	<base target="read">
EOP;

@include("./style/style_css.inc");

echo <<<EOP
</head>
<body>
EOP;

echo $_info_msg_ht;
$_info_msg_ht = "";

echo <<<EOP
<br>
<div class="container">
	{$newversion_found}
	<p>p2 version {$_conf['p2version']} 　<a href="{$p2web_url_r}" target="_blank">{$p2web_url}</a></p>
	<ul>
		<li><a href="viewtxt.php?file=doc/README.txt">README.txt</a></li>
		<li><a href="img/how_to_use.png">ごく簡単な操作法</a></li>
		<li><a href="viewtxt.php?file=doc/ChangeLog.txt">ChangeLog（更新記録）</a></li>
	</ul>
	<!-- <p><a href="{$p2web_url_r}" target="_blank">p2 web &lt;{$p2web_url}&gt;</a></p> -->
	{$autho_user_ht}
	{$p_htm['last_login']}
</div>
</body>
</html>
EOP;

//==================================================
// ■関数
//==================================================
/**
* オンライン上のp2最新版をチェックする
*/
function checkUpdatan()
{
	global $_conf, $p2web_url, $p2web_url_r, $prefdir;

	$ver_txt_url = $p2web_url . 'p2status.txt';
	$cachefile = $prefdir . '/p2_cache/p2status.txt';
	FileCtl::mkdir_for($cachefile);
	
	if (file_exists($cachefile)) {
		// キャッシュの更新が指定時間以内なら
		if (@filemtime($cachefile) > time() - $_conf['p2status_dl_interval'] * 60) {
			$no_p2status_dl_flag = true;
		}
	}
	
	if (!$no_p2status_dl_flag) {
		P2Util::fileDownload($ver_txt_url, $cachefile);
	}
	
	$ver_txt = file($cachefile);
	$update_ver = $ver_txt[0];
	$kita = 'ｷﾀ━━━━（ﾟ∀ﾟ）━━━━!!!!!!';
	//$kita = 'ｷﾀ*･ﾟﾟ･*:.｡..｡.:*･ﾟ(ﾟ∀ﾟ)ﾟ･*:.｡. .｡.:*･ﾟﾟ･*!!!!!';
	
	if (compVars($_conf['p2version'], $update_ver)) {
		$newversion_found = <<<EOP
<div class="kakomi">
	{$kita}<br>
	オンライン上に p2 の最新バージョンを見つけますた。<br>
	p2<!-- version {$update_ver}--> → <a href="{$p2web_url_r}cgi/dl/dl.php?dl=p2">ダウンロード</a> / <a href="{$p2web_url_r}p2/doc/ChangeLog.txt"{$_conf['ext_win_target']}>更新記録</a>
</div>
<hr class="invisible">
EOP;
	}
	return $newversion_found;
}

/**
 * .で区切ったバージョン番号の大きさを比較する
 *
 * @return boolean チェック対象バージョンの方が大きければtrue
 */
function compVars($myvar, $checkvar)
{
	$myvar_sp = explode('.', $myvar);
	$checkvar_sp = explode('.', $checkvar);
	
	$i = 0;
	while (isset($checkvar_sp[$i])) {
		if (!isset($myvar_sp[$i])) {
			return true;
		}
		if ($checkvar_sp[$i] > $myvar_sp[$i]) {
			return true;
		}
		$i++;
	}
	return false;
}

?>