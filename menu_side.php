<?php
/*
	p2 -  板メニュー（Mozillaサイドバー用）
	フレーム分割画面、左側部分
*/


require_once("./conf.php");  //設定読込
require_once './p2util.class.php';	// p2用のユーティリティクラス
require_once("./datactl.inc");
require_once("./brdctl_class.inc");
require_once("./showbrdmenupc_class.inc");

authorize(); //ユーザ認証

//================================================================
// 変数設定
//================================================================
if($_SERVER['HTTPS']){ 
	$me_url = "https://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']; 
}else{ 
	$me_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']; 
} 
$me_dir_url = dirname($me_url);
$menu_side_url = $me_dir_url."/menu_side.php"; //menu_side.php の URL。（ローカルパス指定はできないようだ）

$_info_msg_ht="";
$brd_menus = array();

// 板検索 ====================================
if( isset($_GET['word'])||isset($_POST['word']) ){

	if($_POST['word']){ $word = $_POST['word']; }
	if($_GET['word']){ $word = $_GET['word']; }
	if(get_magic_quotes_gpc()) {
		$word = stripslashes($word);
	}
	if (preg_match('/^\.+$/', $word)) {
		$word = '';
	}
	
	// 正規表現検索
	include_once './strctl.class.php';
	$word_fm = StrCtl::wordForMatch($word);
}


//================================================================
//特殊な前置処理
//================================================================
//お気に板の追加・削除
if( isset($_GET['setfavita']) ){
	include("./setfavita.inc");
}

//================================================================
// メイン
//================================================================
$aShowBrdMenuPc = new ShowBrdMenuPc;

//==============================================================
// ヘッダ
//==============================================================
$reloaded_time = date("n/j G:i:s"); //更新時刻
$ptitle="p2 - menu";

header_content_type();
if($doctype){ echo $doctype;}
echo <<<EOP
<html>
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">\n
EOP;


if($c_menu_refresh_time){	// 自動更新
	$refresh_time_s = $c_menu_refresh_time * 60;
	echo <<<EOP
	<meta http-equiv="refresh" content="{$refresh_time_s};URL={$me_url}?new=1">
EOP;
}

echo <<<EOP
	<title>{$ptitle}</title>
	<base target="subject">
EOP;

@include("./style/style_css.inc");
@include("./style/menu_css.inc");

echo <<<EOSCRIPT
	<script type="text/javascript" src="js/showhide.js"></script>
	<script language="JavaScript">
	<!--
	function chUnColor(idnum){
		unid='un'+idnum;
		document.getElementById(unid).style.color="{$STYLE['menu_color']}";
	}
	
	function chMenuColor(idnum){
		newthreid='newthre'+idnum;
		if(document.getElementById(newthreid)){document.getElementById(newthreid).style.color="{$STYLE['menu_color']}";}
		unid='un'+idnum;
		document.getElementById(unid).style.color="{$STYLE['menu_color']}";
	}
	// -->
	</script>\n
EOSCRIPT;
echo <<<EOP
</head>
<body>
EOP;

echo $_info_msg_ht;
$_info_msg_ht="";

echo <<<EOP
<p><a href="index.php?sidebar=true" target="_content">p2 - 2ペイン表示</a></p>\n
EOP;


if($c_enable_menu_new){
	echo <<<EOP
$reloaded_time [<a href="{$_SERVER['PHP_SELF']}?new=1" target="_self">更新</a>]
EOP;
}

//==============================================================
// お気に板をプリントする
//==============================================================
$aShowBrdMenuPc->print_favIta();

//==============================================================
// 特別
//==============================================================
$bbs_table_url = "http://www6.ocn.ne.jp/~mirv/bbstable.html";
$bbs_table_url_r = P2Util::throughIme($bbs_table_url);
$norefresh_q = "&amp;norefresh=true";

echo <<<EOP
<div class="menu_cate"><b><a class="menu_cate" href="javascript:void(0);" onClick="showHide('c_spacial');">特別</a></b><br>
	<div class="itas" id="c_spacial">
EOP;

if($c_enable_menu_new==1 and  $_GET['new']){	// 新着数を表示する場合

	initMenuNew("fav");	// 新着数を初期化
	echo <<<EOP
	　<a href="{$subject_php}?spmode=fav{$norefresh_q}" onClick="chMenuColor({$matome_i});" accesskey="f">お気にスレ</a> (<a href="{$_conf['read_new_php']}?spmode=fav" target="read" id="un{$matome_i}" onClick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;

	initMenuNew("recent");	// 新着数を初期化
	echo <<<EOP
	　<a href="{$subject_php}?spmode=recent{$norefresh_q}" onClick="chMenuColor({$matome_i});" accesskey="h">最近読んだスレ</a> (<a href="{$_conf['read_new_php']}?spmode=recent" target="read" id="un{$matome_i}" onClick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;

	initMenuNew("res_hist");	// 新着数を初期化
	echo <<<EOP
	　<a href="{$subject_php}?spmode=res_hist{$norefresh_q}" onClick="chMenuColor({$matome_i});">書き込み履歴</a> <a href="read_res_hist.php#footer" target="read">※</a> (<a href="{$_conf['read_new_php']}?spmode=res_hist" target="read" id="un{$matome_i}" onClick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;

}else{	// 新着数を表示しない場合
	echo <<<EOP
	　<a href="{$subject_php}?spmode=fav{$norefresh_q}" accesskey="f">お気にスレ</a><br>
	　<a href="{$subject_php}?spmode=recent{$norefresh_q}" accesskey="h">最近読んだスレ</a><br>
	　<a href="{$subject_php}?spmode=res_hist{$norefresh_q}">書き込み履歴</a> (<a href="./read_res_hist.php#footer" target="read">※</a>)<br>
EOP;
}

echo <<<EOP
	　<a href="{$subject_php}?spmode=palace{$norefresh_q}">スレの殿堂</a><br>
	<!--　<a href="{$subject_php}?spmode=news">ニュースチェック</a><br>-->
	　<a href="setting.php">設定</a><!--<br>
	　<a href="{$bbs_table_url_t}">BBS TABLE</a>-->
	</div>
</div>\n
EOP;

//==============================================================
// カテゴリと板を表示
//==============================================================
//brd読み込み
$brd_menus =  BrdCtl::read_brds();

//===========================================================
// プリント
//===========================================================
if($word!=""){
	if(!$mikke){
		$_info_msg_ht .=  "<p>\"{$word}\"を含む板は見つかりませんでした。</p>\n";
		unset($word);
	}else{
		$_info_msg_ht .=  "<p>\"{$word}\"を含む板 {$mikke}hit!</p>\n";
	}
}
		
echo $_info_msg_ht;
$_info_msg_ht = "";

// 板検索
echo <<<EOFORM
<form method="GET" action="{$_SERVER['PHP_SELF']}" target="_self">
	<p>
		<input type="text" id="word" name="word" value="{$word}" size="14">
		<input type="submit" name="submit" value="板検索">
	</p>
</form>\n
EOFORM;

// 板カテゴリメニューを表示
if ($brd_menus) {
	foreach ($brd_menus as $a_brd_menu) {
		$aShowBrdMenuPc->printBrdMenu($a_brd_menu->categories);
	}
}

//==============================================================
// フッタを表示
//==============================================================
echo <<<EOFOOTER
</body>
</html>
EOFOOTER;

//==============================================================
// 関数
//==============================================================
// menuの新着数を初期化する関数
function initMenuNew($spmode_in)
{
	global $shinchaku_num, $matome_i, $host, $bbs, $spmode, $STYLE, $class_newres_num;
	$matome_i++;
	$host = "";
	$bbs = "";
	$spmode = $spmode_in;
	include("./subject_new.php");
	if($shinchaku_num>0){
		$class_newres_num = " class=\"newres_num\"";
	}else{
		$class_newres_num = " class=\"newres_num_zero\"";
	}
}

?>