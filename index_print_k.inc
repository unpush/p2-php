<?php
// p2 -  携帯用インデックスプリント関数

require_once './p2util.class.php';	// p2用のユーティリティクラス

function index_print_k()
{
	global $_conf, $login;
	global $_info_msg_ht;
	global $k_at_a, $k_at_q;
	
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
	header_content_type();
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
	<li><a {$_conf['accesskey']}="1" href="subject.php?spmode=fav&amp;sb_view=shinchaku{$k_at_a}">お気にｽﾚの新着</a></li>
	<li><a {$_conf['accesskey']}="2" href="subject.php?spmode=fav{$k_at_a}">お気にｽﾚの全て</a></li>
	<li><a {$_conf['accesskey']}="3" href="menu_k.php?view=favita{$k_at_a}">お気に板</a></li>
	<li><a {$_conf['accesskey']}="4" href="menu_k.php?view=cate{$k_at_a}">板ﾘｽﾄ</a></li>	
	<li><a {$_conf['accesskey']}="5" href="subject.php?spmode=recent&amp;sb_view=shinchaku{$k_at_a}">最近読んだｽﾚの新着</a></li>
	<li><a {$_conf['accesskey']}="6" href="subject.php?spmode=recent{$k_at_a}">最近読んだｽﾚの全て</a></li>
	<li><a {$_conf['accesskey']}="7" href="subject.php?spmode=res_hist{$k_at_a}">書き込み履歴</a> <a href="read_res_hist.php?nt={$newtime}{$k_at_a}#footer">ﾛｸﾞ</a></li>
	<li><a {$_conf['accesskey']}="8" href="subject.php?spmode=palace&amp;norefresh=true{$k_at_a}">ｽﾚの殿堂</a></li>
	<li><a {$_conf['accesskey']}="9" href="setting.php{$k_at_q}">設定</a></li>	
</ol>
<hr>
{$autho_user_ht}
{$p_htm['last_login']}
</body>
</html>
EOP;

}
?>