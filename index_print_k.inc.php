<?php
/*
	p2 -  携帯用インデックスプリント関数
*/

require_once './p2util.class.php';	// p2用のユーティリティクラス

/**
* 携帯用インデックスプリント
*/
function index_print_k()
{
	global $_conf, $login;
	global $_info_msg_ht;
	
	$p_htm = array();
	
	$newtime = date('gis');
	
	$body = "";
	$autho_user_ht = "";
	$ptitle = "ﾕﾋﾞｷﾀｽp2";
	
	// 認証ユーザ情報
	$autho_user_ht = "";
	if ($login['use']) {
		$autho_user_ht = "<p>ﾛｸﾞｲﾝﾕｰｻﾞ: {$login['user']} - ".date("Y/m/d (D) G:i:s")."</p>\n";
	}
	
	// 前回のログイン情報
	if ($_conf['login_log_rec'] && $_conf['last_login_log_show']) {
		if (($log = P2Util::getLastAccessLog($_conf['login_log_file'])) !== false) {
			$p_htm['log'] = array_map('htmlspecialchars', $log);
			$p_htm['last_login'] =<<<EOP
前回のﾛｸﾞｲﾝ情報 - {$p_htm['log']['date']}<br>
ﾕｰｻﾞ: {$p_htm['log']['user']}<br>
IP: {$p_htm['log']['ip']}<br>
HOST: {$p_htm['log']['host']}<br>
UA: {$p_htm['log']['ua']}<br>
REFERER: {$p_htm['log']['referer']}
EOP;
		}
	}
	
	//=========================================================
	// 携帯用 HTML プリント
	//=========================================================
	P2Util::header_content_type();
	if ($_conf['doctype']) {
		echo $_conf['doctype'];
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
	<li><a {$_conf['accesskey']}="1" href="subject.php?spmode=fav&amp;sb_view=shinchaku{$_conf['k_at_a']}">お気にｽﾚの新着</a></li>
	<li><a {$_conf['accesskey']}="2" href="subject.php?spmode=fav{$_conf['k_at_a']}">お気にｽﾚの全て</a></li>
	<li><a {$_conf['accesskey']}="3" href="menu_k.php?view=favita{$_conf['k_at_a']}">お気に板</a></li>
	<li><a {$_conf['accesskey']}="4" href="menu_k.php?view=cate{$_conf['k_at_a']}">板ﾘｽﾄ</a></li>	
	<li><a {$_conf['accesskey']}="5" href="subject.php?spmode=recent&amp;sb_view=shinchaku{$_conf['k_at_a']}">最近読んだｽﾚの新着</a></li>
	<li><a {$_conf['accesskey']}="6" href="subject.php?spmode=recent{$_conf['k_at_a']}">最近読んだｽﾚの全て</a></li>
	<li><a {$_conf['accesskey']}="7" href="subject.php?spmode=res_hist{$_conf['k_at_a']}">書込履歴</a> <a href="read_res_hist.php?nt={$newtime}{$_conf['k_at_a']}">ﾛｸﾞ</a></li>
	<li><a {$_conf['accesskey']}="8" href="subject.php?spmode=palace&amp;norefresh=true{$_conf['k_at_a']}">ｽﾚの殿堂</a></li>
	<li><a {$_conf['accesskey']}="9" href="setting.php{$_conf['k_at_q']}">設定</a></li>	
</ol>
<hr>
{$autho_user_ht}
{$p_htm['last_login']}
</body>
</html>
EOP;

}
?>