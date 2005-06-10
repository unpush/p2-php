<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
	p2 -  設定管理ページ
*/

require_once 'conf/conf.php'; 	// 基本設定
require_once (P2_LIBRARY_DIR . '/filectl.class.php');

authorize(); // ユーザ認証

// 書き出し用変数 ========================================
$ptitle = 'ログイン管理';

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

$autho_user_ht = '';
if ($login['use']) {
	$autho_user_ht = "{$autho_user_st}: {$login['user']}<br>";
}


$body_onload = '';
if (!$_conf['ktai']) {
	$body_onload = ' onload="setWinTitle();"';
}

// HOSTを取得
if (!$hc[remoto_host] = $_SERVER['REMOTE_HOST']) {
	$hc[remoto_host] = gethostbyaddr($_SERVER['REMOTE_ADDR']);
}
if ($hc[remoto_host] == $_SERVER['REMOTE_ADDR']) {
	$hc[remoto_host] = "";
}

$hc['ua'] = $_SERVER['HTTP_USER_AGENT'];

$hd = array_map('htmlspecialchars', $hc);

//=========================================================
// ■ HTMLプリント
//=========================================================
P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html lang="ja">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<title>{$ptitle}</title>
EOP;
if (!$_conf['ktai']) {
	echo <<<EOP
	<link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
	<link rel="stylesheet" href="css.php?css=setting&amp;skin={$skin_en}" type="text/css">
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	<script type="text/javascript" src="js/basic.js"></script>\n
EOP;
}
echo <<<EOP
</head>
<body{$k_color_settings}{$body_onload}>
EOP;

if (!$_conf['ktai']) {
	echo <<<EOP
<p id="pan_menu">ログイン管理</p>
EOP;
}

echo $_info_msg_ht;
$_info_msg_ht = '';

echo '<ul id="setting_menu">';

if ($login['use']) {
	echo <<<EOP
	<li><a href="login.php"{$access_login_at}>p2ログイン管理</a></li>
EOP;
}

echo <<<EOP
	<li><a href="login2ch.php"{$access_login2ch_at}>2chログイン管理</a></li>
EOP;

echo '</ul>';

if ($_conf['ktai']) {
	echo '<hr>';
}

echo <<<EOP
<p id="client_status">
{$autho_user_ht}
{$client_host_st}: {$hd['remoto_host']}<br>
{$client_ip_st}: {$_SERVER['REMOTE_ADDR']}<br>
{$browser_ua_st}: {$hd['ua']}
</p>
EOP;

if (!$mobile->isNonMobile()) {
	$m_disp = $mobile->makeDisplay();
	echo '<p>';
	echo '機種: ' . $mobile->getModel() . '<br>';
	echo '画面ｻｲｽﾞ: ' . $m_disp->getWidth() . 'x' . $m_disp->getHeight() . '<br>';
	echo '色数: ' . $m_disp->getDepth() . '<br>';
	echo '</p>';
}

// フッタプリント ===================
if ($_conf['ktai']) {
	echo '<hr>',$_conf['k_to_index_ht'];
}

echo '</body></html>';

?>
