<?php
// p2 -  インデックスページ

include_once './conf.inc.php';  // 基本設定ファイル読込
require_once './p2util.class.php';	// p2用のユーティリティクラス

authorize(); //ユーザ認証

$_info_msg_ht = "";

// アクセスログを記録
if ($_conf['login_log_rec']) {
	if (isset($_conf['login_log_rec_num'])) {
		P2Util::recAccessLog($_conf['login_log_file'], $_conf['login_log_rec_num']);
	} else {
		P2Util::recAccessLog($_conf['login_log_file']);
	}
}

if ($_conf['ktai']) {

	//=========================================================
	// 携帯用 インデックス
	//=========================================================
	include("./index_print_k.inc");
	index_print_k();
	
} else {
	//=========================================
	// 変数
	//=========================================
	$title_page = "title.php";
	if (!$_conf['first_page']) { $_conf['first_page'] = "first_cont.php"; }
	$sidebar = $_GET['sidebar'];
	
	$ptitle = " p2";
	//======================================================
	// PC用 HTMLプリント
	//======================================================
	header_nocache();
	header_content_type();
	if ($_conf['doctype']) { echo $_conf['doctype']; }
	echo <<<EOHEADER
<html lang="ja">
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<title>{$ptitle}</title>
</head>
EOHEADER;

	if(!$sidebar){
		echo <<<EOMENUFRAME
<frameset cols="156,*" frameborder="1" border="1">
	<frame src="menu.php" name="menu" scrolling="auto">
EOMENUFRAME;
	}
	
	echo <<<EOMAINFRAME
	<frameset rows="40%,60%" frameborder="1" border="2">
		<frame src="{$title_page}" name="subject" scrolling="auto">
		<frame src="{$_conf['first_page']}" name="read" scrolling="auto">
	</frameset>
EOMAINFRAME;

	if(!$sidebar){
		echo <<<EOMENUFRAME
</frameset>
EOMENUFRAME;
	}
	
	echo <<<EOFOOTER
</html>
EOFOOTER;

}

?>