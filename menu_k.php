<?php
/**
 * rep2 - 板メニュー 携帯用
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

//==============================================================
// 変数設定
//==============================================================
$_conf['ktai'] = 1;
$brd_menus = array();
$menu_show_ita_num = 0;
$list_navi_ht = '';
$modori_url_ht = '';

// {{{ 板検索のための設定

if (isset($_GET['word'])) {
    $word = $_GET['word'];
} elseif (isset($_POST['word'])) {
    $word = $_POST['word'];
}

if (isset($word) && strlen($word) > 0) {
    if (substr_count($word, '.') == strlen($word)) {
        $word = null;
    } else {
        p2_set_filtering_word($word, 'and');
    }
} else {
    $word = null;
}

// }}}

//============================================================
// 特殊な前置処理
//============================================================
// お気に板の追加・削除
if (isset($_GET['setfavita'])) {
    require_once P2_LIB_DIR . '/setfavita.inc.php';
    setFavIta();
}

//================================================================
// メイン
//================================================================
$aShowBrdMenuK = new ShowBrdMenuK();

//============================================================
// ヘッダ
//============================================================
if ($_GET['view'] == "favita") {
    $ptitle = "お気に板";
} elseif ($_GET['view'] == "rss") {
    $ptitle = "RSS";
} elseif ($_GET['view'] == "cate"){
    $ptitle = "板ﾘｽﾄ";
} elseif (isset($_GET['cateid'])){
    $ptitle = "板ﾘｽﾄ";
} else {
    $ptitle = "ﾕﾋﾞｷﾀｽp2";
}

echo $_conf['doctype'];
echo <<<EOP
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
{$_conf['extra_headers_ht']}
<title>{$ptitle}</title>
EOP;

echo <<<EOP
</head>
<body{$_conf['k_colors']}>
EOP;

P2Util::printInfoHtml();

//==============================================================
// お気に板をプリントする
//==============================================================
if($_GET['view']=="favita"){
    $aShowBrdMenuK->printFavIta();

//RSSリスト読み込み
} elseif ($_GET['view'] == "rss" && $_conf['expack.rss.enabled']) {
    if ($_conf['view_forced_by_query']) {
        output_add_rewrite_var('b', $_conf['b']);
    }
    require_once P2EX_LIB_DIR . '/rss/menu.inc.php';


// それ以外ならbrd読み込み
}else{
    $brd_menus =  BrdCtl::read_brds();
}
//===========================================================
// 板検索
//===========================================================
if ($_GET['view'] != "favita" && $_GET['view'] != "rss" && !$_GET['cateid']) {
    $kensaku_form_ht = <<<EOFORM
<form method="GET" action="{$_SERVER['SCRIPT_NAME']}" accept-charset="{$_conf['accept_charset']}">
    <input type="hidden" name="nr" value="1">
    <input type="text" id="word" name="word" value="{$word}" size="12">
    <input type="submit" name="submit" value="板検索">
    {$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
</form>\n
EOFORM;

    echo $kensaku_form_ht;
    echo "<br>";
}

//===========================================================
// 検索結果をプリント
//===========================================================
// {{{ 検索ワードがあれば

if (isset($_REQUEST['word']) && strlen($_REQUEST['word']) > 0) {

    $hd['word'] = htmlspecialchars($word, ENT_QUOTES);

    if ($GLOBALS['ita_mikke']['num']) {
        $hit_ht = "<br>\"{$hd['word']}\" {$GLOBALS['ita_mikke']['num']}hit!";
    }
    echo "板ﾘｽﾄ検索結果{$hit_ht}<hr>";
    if ($word) {

        // 板名を検索してプリントする
        if ($brd_menus) {
            foreach ($brd_menus as $a_brd_menu) {
                $aShowBrdMenuK->printItaSearch($a_brd_menu->categories);
            }
        }

    }
    if (!$GLOBALS['ita_mikke']['num']) {
        P2Util::pushInfoHtml("<p>\"{$hd['word']}\"を含む板は見つかりませんでした。</p>\n");
        unset($word);
    }
    $modori_url_ht = <<<EOP
<a href="menu_k.php?view=cate&amp;nr=1{$_conf['k_at_a']}">板ﾘｽﾄ</a>
EOP;
}

// }}}
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
if (isset($_GET['cateid'])) {
    if ($brd_menus) {
        foreach ($brd_menus as $a_brd_menu) {
            $aShowBrdMenuK->printIta($a_brd_menu->categories);
        }
    }
    $modori_url_ht = <<<EOP
<a href="menu_k.php?view=cate&amp;nr=1{$_conf['k_at_a']}">板ﾘｽﾄ</a>
EOP;
}

P2Util::printInfoHtml();

//==============================================================
// セット切り替えフォームを表示
//==============================================================

if ($_conf['expack.misc.multi_favs'] && ($_GET['view'] == 'favita' || $_GET['view'] == 'rss')) {
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
echo '<div class="center">';
echo $modori_url_ht;
echo $_conf['k_to_index_ht'];
echo '</div></body></html>';

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
