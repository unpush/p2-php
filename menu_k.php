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

$hr = P2View::getHrHtmlK();

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

P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printHeadMetasHtml();
?>
<title><?php eh($ptitle); ?></title>
</head><body<?php echo P2View::getBodyAttrK(); ?>>
<?php
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

$modori_url_ht = '';

// {{{ 検索ワードがあれば

if (strlen($GLOBALS['word']) > 0) {

    ?>板ﾘｽﾄ検索結果
    <?php
    if ($GLOBALS['ita_mikke']['num']) {
        printf('<br>"%s" %dhit!', hs($GLOBALS['word']), $GLOBALS['ita_mikke']['num']);
        echo $hr;
    }
    
    // 板名を検索して表示する
    if ($brd_menus) {
        foreach ($brd_menus as $a_brd_menu) {
            $aShowBrdMenuK->printItaSearch($a_brd_menu->categories);
        }
    }

    if (!$GLOBALS['ita_mikke']['num']) {
        P2Util::pushInfoHtml(sprintf('<p>"%s"を含む板は見つかりませんでした。</p>', hs($GLOBALS['word'])));
    }
    $atag = P2View::tagA(
        P2Util::buildQueryUri('menu_k.php',
            array(
                'view' => 'cate',
                'nr'   => '1',
                UA::getQueryKey() => UA::getQueryValue()
            )
        ),
        hs('板ﾘｽﾄ')
    );
    $modori_url_ht = '<div>' . $atag . '</div>';
}

// }}}

// カテゴリをHTML表示
if ($get['view'] == 'cate' or isset($_REQUEST['word']) && strlen($GLOBALS['word']) == 0) {
    echo "板ﾘｽﾄ{$hr}";
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
    $modori_url_ht = P2View::tagA(
        P2Util::buildQueryUri('menu_k.php',
            array('view' => 'cate', 'nr' => '1', UA::getQueryKey() => UA::getQueryValue())
        ),
        '板ﾘｽﾄ'
    ) . '<br>';
}


P2Util::printInfoHtml();

// フッタをHTML表示
echo $hr;
echo geti($GLOBALS['list_navi_ht']);
echo $modori_url_ht;
echo P2View::getBackToIndexKATag();
?>
</body></html>
<?php

exit;
