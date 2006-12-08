<?php
/*
    p2 -  板メニュー 携帯用
*/

include_once './conf/conf.inc.php';
require_once P2_LIBRARY_DIR . '/brdctl.class.php';
require_once P2_LIBRARY_DIR . '/showbrdmenuk.class.php';

$_login->authorize(); // ユーザ認証

//==============================================================
// 変数設定
//==============================================================
$_conf['ktai'] = 1;
$brd_menus = array();
$GLOBALS['menu_show_ita_num'] = 0;

BrdCtl::parseWord(); // set $GLOBALS['word']

//============================================================
// 特殊な前置処理
//============================================================
// お気に板の追加・削除
if (isset($_GET['setfavita'])) {
    include_once P2_LIBRARY_DIR . '/setfavita.inc.php';
    setFavIta();
}

//================================================================
// メイン
//================================================================
$aShowBrdMenuK =& new ShowBrdMenuK();

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

echo <<<EOP
{$_conf['doctype']}
<html>
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>{$ptitle}</title>\n
EOP;

echo <<<EOP
</head>
<body{$_conf['k_colors']}>
EOP;

P2Util::printInfoMsgHtml();

// お気に板をプリントする
if($_GET['view'] == 'favita'){
    $aShowBrdMenuK->printFavItaHtml();

//RSSリスト読み込み
} elseif ($_GET['view'] == 'rss' && $_conf['expack.rss.enabled']) {
    //$mobile = &Net_UserAgent_Mobile::singleton();
    if ($mobile->isNonMobile()) {
        output_add_rewrite_var('b', 'k');
    }
    include P2EX_LIBRARY_DIR . '/rss/menu.inc.php';


// それ以外ならbrd読み込み
} else {
    $brd_menus =  BrdCtl::read_brds();
}

// {{{ 板検索フォームをHTML表示

if ($_GET['view'] != 'favita' && $_GET['view'] != 'rss' && empty($_GET['cateid'])) {

    echo BrdCtl::getMenuKSearchFormHtml();

    echo '<br>';
}

// }}}

//===========================================================
// 検索結果をHTML表示
//===========================================================
// {{{ 検索ワードがあれば

if (strlen($_REQUEST['word']) > 0) {

    $hd['word'] = htmlspecialchars($word, ENT_QUOTES);

    if ($GLOBALS['ita_mikke']['num']) {
        $hit_ht = "<br>\"{$hd['word']}\" {$GLOBALS['ita_mikke']['num']}hit!";
    }
    echo "板ﾘｽﾄ検索結果{$hit_ht}<hr>";

    // 板名を検索して表示する
    if ($brd_menus) {
        foreach ($brd_menus as $a_brd_menu) {
            $aShowBrdMenuK->printItaSearch($a_brd_menu->categories);
        }
    }

    if (!$GLOBALS['ita_mikke']['num']) {
        P2Util::pushInfoMsgHtml("<p>\"{$hd['word']}\"を含む板は見つかりませんでした。</p>");
    }
    $modori_url_ht = <<<EOP
<div><a href="menu_k.php?view=cate&amp;nr=1{$_conf['k_at_a']}">板ﾘｽﾄ</a></div>
EOP;
}

// }}}
// {{{ カテゴリをHTML表示

if ($_GET['view'] == 'cate' or isset($_REQUEST['word']) && strlen($GLOBALS['word']) == 0) {
    echo "板ﾘｽﾄ<hr>";
    if ($brd_menus) {
        foreach ($brd_menus as $a_brd_menu) {
            $aShowBrdMenuK->printCate($a_brd_menu->categories);
        }
    }

}

// }}}

//==============================================================
// カテゴリの板をHTML表示
//==============================================================
if (isset($_GET['cateid'])) {
    if ($brd_menus) {
        foreach ($brd_menus as $a_brd_menu) {
            $aShowBrdMenuK->printIta($a_brd_menu->categories);
        }
    }
    $modori_url_ht = <<<EOP
<a href="menu_k.php?view=cate&amp;nr=1{$_conf['k_at_a']}">板ﾘｽﾄ</a><br>
EOP;
}

P2Util::printInfoMsgHtml();

//==============================================================
// セット切り替えフォームを表示
//==============================================================

if (($_GET['view'] == 'favita' && $_conf['favita_set_num'] > 0) ||
    ($_GET['view'] == 'rss' && $_conf['expack.rss.set_num'] > 0))
{
    echo '<hr>';
    if ($_GET['view'] == 'favita') {
        $set_name = 'm_favita_set';
        $set_title = 'お気に板';
    } elseif ($_GET['view'] == 'rss') {
        $set_name = 'm_rss_set';
        $set_title = 'RSS';
    }
    echo FavSetManager::makeFavSetSwitchForm($set_name, $set_title,
        null, null, false, array('view' => $_GET['view']));
}

// フッタをHTML表示
echo '<hr>';
echo $list_navi_ht;
echo $modori_url_ht;
echo $_conf['k_to_index_ht'];
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
