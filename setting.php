<?php
/**
 * rep2 - 設定管理ページ
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

// 書き出し用変数 ========================================
$ptitle = 'ログイン管理';

if ($_conf['ktai']) {
    $status_st = "ｽﾃｰﾀｽ";
    $autho_user_st = "認証ﾕｰｻﾞ";
    $client_host_st = "端末ﾎｽﾄ";
    $client_ip_st = "端末IPｱﾄﾞﾚｽ";
    $browser_ua_st = "ﾌﾞﾗｳｻﾞUA";
    $p2error_st = "rep2 ｴﾗｰ";
} else {
    $status_st = "ステータス";
    $autho_user_st = "認証ユーザ";
    $client_host_st = "端末ホスト";
    $client_ip_st = "端末IPアドレス";
    $browser_ua_st = "ブラウザUA";
    $p2error_st = "rep2 エラー";
}

$autho_user_ht = "{$autho_user_st}: {$_login->user_u}<br>";

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
// HTMLプリント
//=========================================================
P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOP
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    {$_conf['extra_headers_ht']}
    <title>{$ptitle}</title>\n
EOP;

if (!$_conf['ktai']) {
    echo <<<EOP
    <link rel="stylesheet" type="text/css" href="css.php?css=style&amp;skin={$skin_en}">
    <link rel="stylesheet" type="text/css" href="css.php?css=setting&amp;skin={$skin_en}">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <script type="text/javascript" src="js/basic.js?{$_conf['p2_version_id']}"></script>\n
EOP;
}

$body_at = ($_conf['ktai']) ? $_conf['k_colors'] : ' onload="setWinTitle();"';
echo <<<EOP
</head>
<body{$body_at}>
EOP;

// 携帯用表示
if (!$_conf['ktai']) {
    echo <<<EOP
<p id="pan_menu">ログイン管理</p>
EOP;
}

// インフォメッセージ表示
echo $_info_msg_ht;
$_info_msg_ht = "";

echo "<ul id=\"setting_menu\">";

echo <<<EOP
    <li><a href="login.php{$_conf['k_at_q']}"{$access_login_at}>rep2ログイン管理</a></li>
EOP;

echo <<<EOP
    <li><a href="login2ch.php{$_conf['k_at_q']}"{$access_login2ch_at}>2chログイン管理</a></li>
EOP;

echo '</ul>'."\n";

if ($_conf['ktai']) {
    echo "<hr>";
}

echo "<p id=\"client_status\">";
echo <<<EOP
{$autho_user_ht}
{$client_host_st}: {$hd['remoto_host']}<br>
{$client_ip_st}: {$_SERVER['REMOTE_ADDR']}<br>
{$browser_ua_st}: {$hd['ua']}<br>
EOP;
echo "</p>\n";


// フッタプリント===================
if ($_conf['ktai']) {
    echo "<hr><div class=\"center\">{$_conf['k_to_index_ht']}</div>";
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
