<?php
/*
	p2 -  板メニュー 携帯用
*/

require_once("./conf.php");  //基本設定ファイル読込
require_once("./brdctl_class.inc");
require_once("./showbrdmenuk_class.inc");

authorize(); //ユーザ認証

//==============================================================
// 変数設定
//==============================================================
$ktai=1;
$_info_msg_ht="";
$brd_menus = array();

// 板検索 ====================================
if (isset($_REQUEST['word'])) {

	$word = $_REQUEST['word'];
	if (get_magic_quotes_gpc()) {
		$word = stripslashes($word);
	}
	if (preg_match('/^\.+$/', $word)) {
		$word = '';
	}
	
	//正規表現検索
	include_once './strctl.class.php';
	$GLOBALS['word_fm'] = StrCtl::wordForMatch($word);
}


//============================================================
//特殊な前置処理
//============================================================
//お気に板の追加・削除
if( isset($_GET['setfavita']) ){
	include("./setfavita.inc");
}

//================================================================
// メイン
//================================================================
$aShowBrdMenuK = new ShowBrdMenuK;

//============================================================
// ヘッダ
//============================================================
if($_GET['view']=="favita"){
	$ptitle="お気に板";
}elseif($_GET['view']=="cate"){
	$ptitle="板ﾘｽﾄ";
}elseif(isset($_GET['cateid'])){
	$ptitle="板ﾘｽﾄ";
}else{
	$ptitle="ﾕﾋﾞｷﾀｽp2";
}

header_content_type();
if($doctype){ echo $doctype;}
echo <<<EOP
<html>
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<title>{$ptitle}</title>
EOP;

echo <<<EOP
</head>
<body>
EOP;

echo $_info_msg_ht;
$_info_msg_ht="";

//==============================================================
// お気に板をプリントする
//==============================================================
if($_GET['view']=="favita"){
	$aShowBrdMenuK->print_favIta();

// それ以外ならbrd読み込み
}else{
	$brd_menus =  BrdCtl::read_brds();		
}
//===========================================================
// 板検索
//===========================================================
if ($_GET['view'] != "favita" && $_GET['view'] != "rss" && !$_GET['cateid']) {
	$kensaku_form_ht = <<<EOFORM
<form method="GET" action="{$_SERVER['PHP_SELF']}">
	{$_conf['k_input_ht']}
	<input type="hidden" name="nr" value="1">
	<input type="text" id="word" name="word" value="{$word}" size="12">
	<input type="submit" name="submit" value="板検索">
</form>\n
EOFORM;

	echo $kensaku_form_ht;
	echo "<br>";
}

//===========================================================
// 検索結果をプリント
//===========================================================
if ((isset($_REQUEST['word']) && $_REQUEST['word'] == "") {

	$word_ht = htmlspecialchars($word);

	if ($GLOBALS['mikke']) {
		$hit_ht = "<br>\"{$word_ht}\" {$GLOBALS['mikke']}hit!";
	}
	echo "板ﾘｽﾄ検索結果{$hit_ht}<hr>";
	if ($word) {

		// 板名を検索してプリントする ==========================
		if ($brd_menus) {
			foreach ($brd_menus as $a_brd_menu) {
				$aShowBrdMenuK->printItaSearch($a_brd_menu->categories);
			}
		}
		
	}
	if (!$GLOBALS['mikke']) {
		$_info_msg_ht .=  "<p>\"{$word_ht}\"を含む板は見つかりませんでした。</p>\n";
		unset($word);
	}
	$modori_url_ht = <<<EOP
<div><a href="menu_k.php?view=cate&amp;nr=1{$_conf['k_at_a']}">板ﾘｽﾄ</a></div>
EOP;
}

//==============================================================
// カテゴリを表示
//==============================================================
if ((isset($_REQUEST['word']) && $_REQUEST['word'] == "") or $_GET['view'] == "cate") {
	echo "板ﾘｽﾄ<hr>";
	if($brd_menus){
		foreach($brd_menus as $a_brd_menu){
			$aShowBrdMenuK->printCate($a_brd_menu->categories);
		}
	}

}

//==============================================================
// カテゴリの板を表示
//==============================================================
if(isset($_GET['cateid'])){
	if($brd_menus){
		foreach($brd_menus as $a_brd_menu){
			$aShowBrdMenuK->printIta($a_brd_menu->categories);
		}
	}
	$modori_url_ht=<<<EOP
<a href="menu_k.php?view=cate&amp;nr=1{$k_at_a}">板ﾘｽﾄ</a><br>
EOP;
}

	
echo $_info_msg_ht;
$_info_msg_ht="";

//==============================================================
// フッタを表示
//==============================================================

echo <<<EOFOOTER
<hr>
$list_navi_ht
$modori_url_ht
$k_to_index_ht
</body>
</html>
EOFOOTER;


?>