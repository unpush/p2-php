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
if ($_GET['view'] == "favita") {
    $ptitle = "お気に板";
} elseif ($_GET['view'] == "cate"){
    $ptitle = "板ﾘｽﾄ";
} elseif (isset($_GET['cateid'])) {
    $ptitle = "板ﾘｽﾄ";
} else {
    $ptitle = "ﾕﾋﾞｷﾀｽp2";
}

P2Util::header_content_type();
echo <<<EOP
{$_conf['doctype']}
<html>
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>{$ptitle}</title>\n
EOP;

echo "</head><body>\n";

P2Util::printInfoMsgHtml();

// お気に板をプリントする
if ($_GET['view'] == 'favita') {
    $aShowBrdMenuK->printFavItaHtml();

// それ以外ならbrd読み込み
} else {
    $brd_menus = BrdCtl::read_brds();
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

if (strlen($GLOBALS['word']) > 0) {

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


// フッタをHTML表示
echo '<hr>';
echo $list_navi_ht;
echo $modori_url_ht;
echo $_conf['k_to_index_ht'];
echo '</body></html>';

?>