<?php
// p2 -  設定

include_once './conf.inc.php';  // 基本設定
require_once './filectl.class.php';
require_once './p2util.class.php';

authorize(); // ユーザ認証

$_info_msg_ht = "";

//書き出し用変数========================================
$ptitle = "設定";

if ($_conf['ktai']) {
	$status_st = "ｽﾃｰﾀｽ";
	$autho_user_st = "認証ﾕｰｻﾞ";
	$client_host_st = "端末ﾎｽﾄ";
	$client_ip_st = "端末IPｱﾄﾞﾚｽ";
	$browser_ua_st = "ﾌﾞﾗｳｻﾞUA";
	$p2error_st = "p2 ｴﾗｰ";
} else {
	$status_st = "ステータス";
	$autho_user_st = "認証ユーザ";
	$client_host_st = "端末ホスト";
	$client_ip_st = "端末IPアドレス";
	$browser_ua_st = "ブラウザUA";
	$p2error_st = "p2 エラー";
}

$autho_user_ht = "";
if ($login['use']) {
	$autho_user_ht = "{$autho_user_st}: {$login['user']}<br>";
}


$body_onload = "";
if (!$_conf['ktai']) {
	$body_onload = " onLoad=\"setWinTitle();\"";
}

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
if (!$_conf['ktai']) {
	@include("./style/style_css.inc");
	@include("./style/setting_css.inc");
	echo <<<EOP
	<script type="text/javascript" src="js/basic.js"></script>
EOP;
}
echo <<<EOP
</head>
<body{$body_onload}>
EOP;

if (!$_conf['ktai']) {
	echo <<<EOP
<p id="pan_menu">設定</p>
EOP;
}

echo $_info_msg_ht;
$_info_msg_ht = "";

/*
if ($_conf['ktai']) {
	echo "<hr>";
}
*/

/*
if ($_conf['ktai']) {
	$access_login_at = " {$_conf['accesskey']}=\"1\"";
	$access_login2ch_at = " {$_conf['accesskey']}=\"2\"";
}
*/

echo "<ul id=\"setting_menu\">";

if ($login['use']) {
	echo <<<EOP
	<li><a href="login.php{$_conf['k_at_q']}"{$access_login_at}>p2認証ユーザ管理</a></li>
EOP;
}

echo <<<EOP
	<li><a href="login2ch.php{$_conf['k_at_q']}"{$access_login2ch_at}>2chログイン管理</a></li>
EOP;

if (!$_conf['ktai']) {
	echo <<<EOP
	<li><a href="editpref.php{$_conf['k_at_q']}">設定ファイル編集</a></li>
EOP;
} else {
	echo <<<EOP
	<li><a href="editpref.php{$_conf['k_at_q']}">ホストの同期</a>（2chの板移転に対応します）</li>
EOP;
}

echo <<<EOP
	</ul>
EOP;

if ($_conf['ktai']) {
	echo "<hr>";
}

echo "<p id=\"client_status\">";
echo <<<EOP
{$autho_user_ht}
{$client_host_st}: {$_SERVER['REMOTE_HOST']}<br>
{$client_ip_st}: {$_SERVER['REMOTE_ADDR']}<br>
{$browser_ua_st}: {$_SERVER['HTTP_USER_AGENT']}<br>
EOP;
echo "</p>";


// フッタプリント===================
if ($_conf['ktai']) {
	echo '<hr>'.$_conf['k_to_index_ht'];
}

echo '</body></html>';

?>