<?php
/*
    p2 -  板メニュー 携帯用
*/

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/brdctl.class.php';
require_once P2_LIB_DIR . '/showbrdmenuk.class.php';

$_login->authorize(); // ユーザ認証

//==============================================================
// 変数設定
//==============================================================
$_conf['ktai'] = 1;
$brd_menus = array();
$GLOBALS['menu_show_ita_num'] = 0;

BrdCtl::parseWord(); // set $GLOBALS['word']

//============================================================
// 特殊な前処理
//============================================================
// お気に板の追加・削除
if (isset($_GET['setfavita'])) {
    require_once P2_LIB_DIR . '/setfavita.inc.php';
    setFavIta();
}

//================================================================
// メイン
//================================================================
$aShowBrdMenuK =& new ShowBrdMenuK;

//============================================================
// ヘッダHTMLを表示
//============================================================

$get['view'] = isset($_GET['view']) ? $_GET['view'] : null;

if ($get['view'] == "favita") {
    $ptitle = "お気に板";
} elseif ($get['view'] == "cate"){
    $ptitle = "板ﾘｽﾄ";
} elseif (isset($_GET['cateid'])) {
    $ptitle = "板ﾘｽﾄ";
} else {
    $ptitle = "ﾕﾋﾞｷﾀｽp2";
}

echo $_conf['doctype'];
echo <<<EOP
<html>
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>{$ptitle}</title>\n
EOP;

echo "</head><body>\n";

P2Util::printInfoHtml();

// お気に板をHTML表示する
if ($get['view'] == 'favita') {
    $aShowBrdMenuK->printFavItaHtml();

// それ以外ならbrd読み込み
} else {
    $brd_menus = BrdCtl::readBrdMenus();
}

// 板検索フォームをHTML表示
if ($get['view'] != 'favita' && $get['view'] != 'rss' && empty($_GET['cateid'])) {
    
    echo BrdCtl::getMenuKSearchFormHtml();
    echo '<br>';
}

//===========================================================
// 検索結果をHTML表示
//===========================================================
// {{{ 検索ワードがあれば

if (strlen($GLOBALS['word']) > 0) {

    $word_hs = htmlspecialchars($word, ENT_QUOTES);

    if ($GLOBALS['ita_mikke']['num']) {
        $hit_ht = "<br>\"{$word_hs}\" {$GLOBALS['ita_mikke']['num']}hit!";
    }
    echo "板ﾘｽﾄ検索結果{$hit_ht}<hr>";

    // 板名を検索して表示する
    if ($brd_menus) {
        foreach ($brd_menus as $a_brd_menu) {
            $aShowBrdMenuK->printItaSearch($a_brd_menu->categories);
        }
    }

    if (!$GLOBALS['ita_mikke']['num']) {
        P2Util::pushInfoHtml("<p>\"{$word_hs}\"を含む板は見つかりませんでした。</p>");
    }
    $modori_url_ht = <<<EOP
<div><a href="menu_k.php?view=cate&amp;nr=1{$_conf['k_at_a']}">板ﾘｽﾄ</a></div>
EOP;
}

// }}}

// カテゴリをHTML表示
if ($get['view'] == 'cate' or isset($_REQUEST['word']) && strlen($GLOBALS['word']) == 0) {
    echo "板ﾘｽﾄ<hr>";
    if ($brd_menus) {
        foreach ($brd_menus as $a_brd_menu) {
            $aShowBrdMenuK->printCate($a_brd_menu->categories);
        }
    }
}


// カテゴリの板をHTML表示
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


P2Util::printInfoHtml();

!isset($GLOBALS['list_navi_ht']) and $GLOBALS['list_navi_ht'] = null;
!isset($modori_url_ht) and $modori_url_ht = null;

// フッタをHTML表示
echo '<hr>';
echo $list_navi_ht;
echo $modori_url_ht;
echo $_conf['k_to_index_ht'];
echo '</body></html>';

