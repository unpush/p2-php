<?php
// p2 -  携帯用インデックスプリント関数

require_once './p2util.class.php';	// p2用のユーティリティクラス

function index_print_k()
{
	global $_conf, $login;
	global $_info_msg_ht;
	global $doctype, $accesskey;
	global $k_at_a, $k_at_q;
	
	$newtime = date("gis");
	
	$body = "";
	$autho_user_ht = "";
	$ptitle = "ﾕﾋﾞｷﾀｽp2";

	// 認証ユーザ情報
	$autho_user_ht = "";
	if ($login['use']) {
		$autho_user_ht = "<p>ﾛｸﾞｲﾝﾕｰｻﾞ: {$login['user']} - ".date("Y/m/d (D) G:i:s")."</p>\n";
	}
	
	// 前回のログイン情報
	if ($_conf['login_log_rec'] && $_conf['last_login_log_show'] and ($alog = P2Util::getLastAccessLog($_conf['login_log_file']))) {
		$last_login_ht =<<<EOP
前回のログイン情報 - {$alog['date']}<br>
ユーザ: {$alog['user']}<br>
IP: {$alog['ip']}<br>
HOST: {$alog['host']}<br>
UA: {$alog['ua']}<br>
REFERER: {$alog['referer']}
EOP;
	} else {
		$last_login_ht = "";
	}

	//=========================================================
	// 携帯用 HTML プリント
	//=========================================================
	header_content_type();
	if($doctype){
		echo $doctype;
	}
	echo <<<EOP
<html>
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<title>{$ptitle}</title>
</head>
<body>
<h1>{$ptitle}</h1>
{$_info_msg_ht}
<ol>
	<li><a {$accesskey}="1" href="subject.php?spmode=fav&amp;sb_view=shinchaku{$k_at_a}">お気にｽﾚの新着</a></li>
	<li><a {$accesskey}="2" href="subject.php?spmode=fav{$k_at_a}">お気にｽﾚの全て</a></li>
	<li><a {$accesskey}="3" href="menu_k.php?view=favita{$k_at_a}">お気に板</a></li>
	<li><a {$accesskey}="4" href="menu_k.php?view=cate{$k_at_a}">板ﾘｽﾄ</a></li>	
	<li><a {$accesskey}="5" href="subject.php?spmode=recent&amp;sb_view=shinchaku{$k_at_a}">最近読んだｽﾚの新着</a></li>
	<li><a {$accesskey}="6" href="subject.php?spmode=recent{$k_at_a}">最近読んだｽﾚの全て</a></li>
	<li><a {$accesskey}="7" href="subject.php?spmode=res_hist{$k_at_a}">書き込み履歴</a> <a href="read_res_hist.php?nt={$newtime}{$k_at_a}#footer">*</a></li>
	<li><a {$accesskey}="8" href="subject.php?spmode=palace&amp;norefresh=true{$k_at_a}">ｽﾚの殿堂</a></li>
	<li><a {$accesskey}="9" href="setting.php{$k_at_q}">設定</a></li>	
</ol>
<br>
{$autho_user_ht}
{$last_login_ht}
</body>
</html>
EOP;

}
?>