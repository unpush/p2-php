<?php
// p2 -  設定管理ページ

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/FileCtl.php';

$_login->authorize(); // ユーザ認証


// 書き出し用変数

$ptitle = 'ログイン管理';

if (UA::isK()) {
    $status_st      = 'ｽﾃｰﾀｽ';
    $autho_user_st  = '認証ﾕｰｻﾞ';
    $client_host_st = '端末ﾎｽﾄ';
    $client_ip_st   = '端末IPｱﾄﾞﾚｽ';
    $browser_ua_st  = 'ﾌﾞﾗｳｻﾞUA';
    $p2error_st     = 'rep2 ｴﾗｰ';
    $logout_st      = 'ﾛｸﾞｱｳﾄ';
} else {
    $status_st      = 'ステータス';
    $autho_user_st  = '認証ユーザ';
    $client_host_st = '端末ホスト';
    $client_ip_st   = '端末IPアドレス';
    $browser_ua_st  = 'ブラウザUA';
    $p2error_st     = 'rep2 エラー';
    $logout_st      = 'ログアウト';
}

$body_onload = '';
if (UA::isPC()) {
    $body_onload = ' onLoad="setWinTitle();"';
}

$hr = P2View::getHrHtmlK();
$body_at = P2View::getBodyAttrK();

//=========================================================
// HTMLプリント
//=========================================================
P2Util::headerNoCache();
P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
?>
	<title><?php eh($ptitle); ?></title>
<?php
if (UA::isPC()) {
    P2View::printIncludeCssHtml('style');
    P2View::printIncludeCssHtml('setting');
    ?>
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	<script type="text/javascript" src="js/basic.js?v=20061206"></script>
<?php
}

echo <<<EOP
</head>
<body{$body_onload}{$body_at}>
EOP;

if (UA::isPC()) {
    ?><p id="pan_menu">ログイン管理</p><?php
}

P2Util::printInfoHtml();

?><ul id="setting_menu">
	<li>
		<a href="login.php<?php eh($_conf['k_at_q']); ?>">rep2ログイン管理</a>
	</li>
	<li><a href="login2ch.php<?php eh($_conf['k_at_q']); ?>">2chログイン管理</a>（いわゆる●）</li>
</ul>

[<a href="./index.php?logout=1" target="_parent">rep2から<?php eh($logout_st); ?>する</a>]
<?php
if (UA::isK()) {
    echo $hr;
}
?>
<p id="client_status">
<?php eh($autho_user_st) ?>: <?php eh($_login->user_u) ?><br>
<?php eh($client_host_st) ?>: <?php eh(P2Util::getRemoteHost()) ?><br>
<?php eh($client_ip_st) ?>: <?php eh($_SERVER['REMOTE_ADDR']) ?><br>
<?php eh($browser_ua_st) ?>: <?php ehi($_SERVER['HTTP_USER_AGENT']) ?><br>
</p>
<?php

// フッタHTML表示
if (UA::isK()) {
	echo $hr . P2View::getBackToIndexKATag() . "\n";
}

?>
</body></html>
<?php

exit;

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
