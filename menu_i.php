<?php
/*
    p2 -  板メニュー 携帯用
*/

//080825iphone用ライブラリ追加
require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/BrdCtl.php';
require_once P2_IPHONE_LIB_DIR . '/ShowBrdMenuK.php';

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
$aShowBrdMenuK = new ShowBrdMenuK;

//============================================================
// ヘッダHTMLを表示
//============================================================

$get['view'] = isset($_GET['view']) ? $_GET['view'] : null;

if ($get['view'] == "favita") {
    $ptitle = "お気に板";
} elseif ($get['view'] == "cate"){
    $ptitle = "板リスト";
} elseif (isset($_GET['cateid'])) {
    $ptitle = "板リスト";
} else {
    $ptitle = "ﾕﾋﾞｷﾀｽp2";
}

P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<style type="text/css" media="screen">@import "./iui/iui.css";</style>
<script type="text/javascript"> 
<!-- 
window.onload = function() { 
setTimeout(scrollTo, 100, 0, 1); 
} 
</script> 
<?php
P2View::printExtraHeadersHtml();
?>

<title><?php eh($ptitle); ?></title>
</head><body>
<div class="toolbar"><h1 id="pageTitle"><?php eh($ptitle); ?></h1></div>
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
    echo '<div id="usage" class="panel"><filedset>';
    echo BrdCtl::getMenuKSearchFormHtml();
    echo '</filedset></div>';
}

//===========================================================
// 検索結果をHTML表示
//===========================================================
// {{{ 検索ワードがあれば

$modori_url_ht = '';

// {{{ 検索ワードがあれば

if (strlen($GLOBALS['word']) > 0) {

    ?><div class="panel"><h2>
    <?php
    if ($GLOBALS['ita_mikke']['num']) {
        printf('"%s" %dhit!', hs($GLOBALS['word']), $GLOBALS['ita_mikke']['num']);
    }
    ?></h2></div><?
    
    // 板名を検索して表示する
    if ($brd_menus) {
        foreach ($brd_menus as $a_brd_menu) {
            $aShowBrdMenuK->printItaSearch($a_brd_menu->categories);
        }
    }

    if (!$GLOBALS['ita_mikke']['num']) {
        P2Util::pushInfoHtml(sprintf('"%s"を含む板は見つかりませんでした。', hs($GLOBALS['word'])));
    }
    $atag = P2View::tagA(
        P2Util::buildQueryUri('menu_i.php',
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
if ($get['view'] == 'cate' or isset($_REQUEST['word']) && !strlen($GLOBALS['word'])) {
    //echo "板ﾘｽﾄ{$hr}";
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
        P2Util::buildQueryUri($_conf['menu_k_php'],
            array('view' => 'cate', 'nr' => '1', UA::getQueryKey() => UA::getQueryValue())
        ),
        '板ﾘｽﾄ'
    ) . '<br>';
}


P2Util::printInfoHtml();


// フッタをHTML表示
echo geti($GLOBALS['list_navi_ht']);
echo '<p><a id="backButton"class="button" href="iphone.php">TOP</a></p>';
?>
</body></html>
<?php

exit;