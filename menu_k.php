<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
	p2 -  板メニュー 携帯用
*/

require_once 'conf/conf.php';	//基本設定ファイル読込
require_once (P2_LIBRARY_DIR . '/brdctl.class.php');
require_once (P2_LIBRARY_DIR . '/showbrdmenuk.class.php');

authorize(); // ユーザ認証

$debug = false;

//==============================================================
// 変数設定
//==============================================================
$_conf['ktai'] = true;
$brd_menus = array();

if (isset($_GET['word'])) {
	$word = $_GET['word'];
} elseif (isset($_POST['word'])) {
	$word = $_POST['word'];
}

// ■板検索 ====================================
if (isset($word) && strlen($word) > 0) {

	if (!preg_match('/[^. ]/', $word)) {
		$word = '';
	}
	$word_ht = htmlspecialchars($word);

	// 正規表現検索
	include_once (P2_LIBRARY_DIR . '/strctl.class.php');
	$word_fm = StrCtl::wordForMatch($word);
}


//============================================================
// 特殊な前置処理
//============================================================
// お気に板の追加・削除
if (isset($_GET['setfavita'])) {
	include (P2_LIBRARY_DIR . '/setfavita.inc.php');
}

//================================================================
// メイン
//================================================================
$aShowBrdMenuK = &new ShowBrdMenuK;

//============================================================
// ヘッダ
//============================================================
if ($_GET['view'] == 'favita') {
	$ptitle = 'お気に板';
} elseif ($_GET['view'] == 'rss') {
	$ptitle = 'RSS';
} elseif ($_GET['view'] == 'cate') {
	$ptitle = '板ﾘｽﾄ';
} elseif (isset($_GET['cateid'])) {
	$ptitle = '板ﾘｽﾄ';
} else {
	$ptitle = 'ﾕﾋﾞｷﾀｽp2';
}

P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html>
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<title>{$ptitle}</title>
EOP;

echo <<<EOP
</head>
<body{$k_color_settings}>
EOP;

echo $_info_msg_ht;
$_info_msg_ht = '';

//==============================================================
// お気に板をプリントする
//==============================================================
if ($_GET['view'] == 'favita') {
	$aShowBrdMenuK->print_favIta();

} elseif ($_GET['view'] == 'rss' && $_exconf['rss']['*']) { //RSSリスト読み込み
	@include_once (P2EX_LIBRARY_DIR . '/rss/menu.inc.php');

// それ以外ならbrd読み込み
} else {
	$brd_menus =  BrdCtl::read_brds();
}

//===========================================================
// 板検索
//===========================================================
if ($_GET['view'] != 'favita' && $_GET['view'] != 'rss' && !$_GET['cateid']) {
	$kensaku_form_ht = <<<EOFORM
<form method="GET" action="{$_SERVER['PHP_SELF']}" accept-charset="{$_conf['accept_charset']}">
	<input type="hidden" name="detect_hint" value="◎◇">
	<input type="hidden" name="nr" value="1">
	<input type="text" id="word" name="word" value="{$word_ht}" size="12">
	<input type="submit" name="submit" value="板検索">
</form>\n
EOFORM;

	echo $kensaku_form_ht;
	echo "<br>\n";
}

//===========================================================
// 検索結果をプリント
//===========================================================
if (isset($word) && strlen($word) > 0) {

	if ($GLOBALS['ita_mikke']['num']) {
		$hit_ht = "<br>&quot;{$word_ht}&quot; {$GLOBALS['ita_mikke']['num']}hit!";
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
	if (!$GLOBALS['ita_mikke']['num']) {
		$_info_msg_ht .=  "<p>&quot;{$word_ht}&quot;を含む板は見つかりませんでした。</p>\n";
		unset($word);
	}
	$modori_url_ht = <<<EOP
<div><a href="menu_k.php?view=cate&amp;nr=1">板ﾘｽﾄ</a></div>
EOP;
} else {
	$menu_update_q = preg_replace('/(^|&)(k=1|nt=\d+)/', '', $_SERVER['QUERY_STRING']);
	$menu_update_q = htmlspecialchars($menu_update_q) . '&amp;nt=' . time();
	$modori_url_ht = <<<EOP
<a href="menu_k.php?{$menu_update_q}">ﾒﾆｭｰを更新</a>\n
EOP;
}

//==============================================================
// カテゴリを表示
//==============================================================
if ($_GET['view'] == 'cate') {
	echo '板ﾘｽﾄ<hr>';
	if ($brd_menus) {
		foreach ($brd_menus as $a_brd_menu) {
			$aShowBrdMenuK->printCate($a_brd_menu->categories);
		}
	}

}

//==============================================================
// カテゴリの板を表示
//==============================================================
if (isset($_GET['cateid'])) {
	if ($brd_menus) {
		foreach ($brd_menus as $a_brd_menu) {
			$aShowBrdMenuK->printIta($a_brd_menu->categories);
		}
	}
	$modori_url_ht = '<a href="menu_k.php?view=cate&amp;nr=1">板ﾘｽﾄ</a><br>';
}

echo $_info_msg_ht;
$_info_msg_ht = '';

//==============================================================
// セット切り替えフォームを表示
//==============================================================

if ($_exconf['etc']['multi_favs'] && ($_GET['view'] == 'favita' || $_GET['view'] == 'rss')) {
	echo '<hr>';
	if ($_GET['view'] == 'favita') {
		$set_name = 'm_favita_set';
		$set_title = 'お気に板';
	} elseif ($_GET['view'] == 'rss') {
		$set_name = 'm_rss_set';
		$set_title = 'RSS';
	}
	echo FavSetManager::makeFavSetSwitchForm($set_name, $set_title, NULL, NULL, FALSE, array('view' => $_GET['view']));
}

//==============================================================
// フッタを表示
//==============================================================

echo '<hr>';
echo $list_navi_ht;
echo $kensaku_form_ht;
echo $modori_url_ht;
echo $_conf['k_to_index_ht'];
echo '</body></html>';

?>
