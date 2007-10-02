<?php
/*
    p2 -  設定管理ページ
*/

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/filectl.class.php';

$_login->authorize(); // ユーザ認証

// 書き出し用変数 ========================================
$ptitle = 'ログイン管理';
$pStrs = array();

if ($_conf['ktai']) {
    $status_st      = "ｽﾃｰﾀｽ";
    $autho_user_st  = "認証ﾕｰｻﾞ";
    $client_host_st = "端末ﾎｽﾄ";
    $client_ip_st   = "端末IPｱﾄﾞﾚｽ";
    $browser_ua_st  = "ﾌﾞﾗｳｻﾞUA";
    $p2error_st     = "rep2 ｴﾗｰ";
    $pStrs['logout'] = 'ﾛｸﾞｱｳﾄ';
} else {
    $status_st      = "ステータス";
    $autho_user_st  = "認証ユーザ";
    $client_host_st = "端末ホスト";
    $client_ip_st   = "端末IPアドレス";
    $browser_ua_st  = "ブラウザUA";
    $p2error_st     = "rep2 エラー";
    $pStrs['logout'] = 'ログアウト';
}

$autho_user_ht = "{$autho_user_st}: {$_login->user_u}<br>";


$body_onload = "";
if (!$_conf['ktai']) {
	$body_onload = " onLoad=\"setWinTitle();\"";
}

$hc['remoto_host'] = P2Util::getRemoteHost();

$hc['ua'] = $_SERVER['HTTP_USER_AGENT'];

$hs = array_map('htmlspecialchars', $hc);

$hr = P2Util::getHrHtmlK();
$body_at = P2Util::getBodyAttrK();

//=========================================================
// HTMLプリント
//=========================================================
P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOP
<html>
<head>
	{$_conf['meta_charset_ht']}
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<title>{$ptitle}</title>
EOP;

if (!$_conf['ktai']) {
    include_once './style/style_css.inc';
    include_once './style/setting_css.inc';
    echo <<<EOP
	<script type="text/javascript" src="js/basic.js?v=20061206"></script>\n
EOP;
}

echo <<<EOP
</head>
<body{$body_onload}{$body_at}>
EOP;

// 携帯用表示
if (!$_conf['ktai']) {
	echo <<<EOP
<p id="pan_menu">ログイン管理</p>
EOP;
}

P2Util::printInfoHtml();

?><ul id="setting_menu">
	<li>
		<a href="login.php<?php eh($_conf['k_at_q']); ?>">rep2ログイン管理</a>
	</li>
<?php
echo <<<EOP
	<li><a href="login2ch.php{$_conf['k_at_q']}">2chログイン管理</a>（いわゆる●）</li>
EOP;

echo '</ul>' . "\n";

?>
[<a href="./index.php?logout=1" target="_parent">rep2から<?php eh($pStrs['logout']); ?>する</a>]
<?php
if ($_conf['ktai']) {
	echo $hr;
}

echo '<p id="client_status">';
echo <<<EOP
{$autho_user_ht}
{$client_host_st}: {$hs['remoto_host']}<br>
{$client_ip_st}: {$_SERVER['REMOTE_ADDR']}<br>
{$browser_ua_st}: {$hs['ua']}<br>
EOP;
echo "</p>\n";


// フッタHTML表示
if ($_conf['ktai']) {
	echo $hr . $_conf['k_to_index_ht'] . "\n";
}

echo '</body></html>';


/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
