<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 -  板メニュー
    フレーム分割画面、左側部分 PC用

    menu.php, menu_side.php より読み込まれる
*/

require_once 'conf/conf.php';   //基本設定ファイル読込
require_once (P2_LIBRARY_DIR . '/p2util.class.php');    // p2用のユーティリティクラス
require_once (P2_LIBRARY_DIR . '/brdctl.class.php');
require_once (P2_LIBRARY_DIR . '/showbrdmenupc.class.php');

authorize(); // ユーザ認証

$debug = false;

//==============================================================
// 変数設定
//==============================================================
$me_url = 'http'.(empty($_SERVER['HTTPS'])?'':'s').'://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
$me_dir_url = dirname($me_url);
// menu_side.php の URL。（ローカルパス指定はできないようだ）
$menu_side_url = $me_dir_url."/menu_side.php";

$brd_menus = array();

if (isset($_GET['word'])) {
    $word = $_GET['word'];
} elseif (isset($_POST['word'])) {
    $word = $_POST['word'];
}

// ■板検索 ====================================
if (isset($word) && strlen($word) > 0) {
    if (!preg_match('/[^. ]/', $word)) {
        $word = null;
    }
    $word_ht = htmlspecialchars($word);

    // 正規表現検索
    include_once (P2_LIBRARY_DIR . '/strctl.class.php');
    $word_fm = StrCtl::wordForMatch($word);
} else {
    $word_ht = '';
}


//============================================================
// 特殊な前置処理
//============================================================
//お気に板の追加・削除
if (isset($_GET['setfavita'])) {
    include (P2_LIBRARY_DIR . '/setfavita.inc.php');
}

//================================================================
// ■メイン
//================================================================
$aShowBrdMenuPc = &new ShowBrdMenuPc;

//============================================================
// ■ヘッダ
//============================================================
$reloaded_time = date('n/j G:i:s'); //更新時刻
$ptitle = 'p2 - menu';

P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">\n
EOP;

// 自動更新
if ($_conf['menu_refresh_time']) {
    $refresh_time_s = $_conf['menu_refresh_time'] * 60;
    echo <<<EOP
    <meta http-equiv="refresh" content="{$refresh_time_s};URL={$me_url}?new=1">\n
EOP;
}

echo <<<EOP
    <title>{$ptitle}</title>
    <base target="subject">
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=menu&amp;skin={$skin_en}" type="text/css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">\n
EOP;

echo <<<EOSCRIPT
    <script type="text/javascript" src="js/basic.js"></script>
    <script type="text/javascript" src="js/showhide.js"></script>
    <script type="text/javascript" src="js/menu.js"></script>
    <script type="text/javascript">
    <!--
    function addSidebar(title, url) {
        if ((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function")) {
            window.sidebar.addPanel(title, url, '');
        } else {
            goNetscape();
        }
    }
    function goNetscape()
    {
    //  var rv = window.confirm ("This page is enhanced for use with Netscape 7.  " + "Would you like to upgrade now?");
        var rv = window.confirm ("このページは Netscape 7 用に拡張されています.  " + "今すぐアップデートしますか?");
        if (rv)
            document.location.href = "http://home.netscape.com/ja/download/download_n6.html";
    }

    function chUnColor(idnum){
        unid='un'+idnum;
        document.getElementById(unid).style.color="{$STYLE['menu_color']}";
    }

    function chMenuColor(idnum){
        newthreid='newthre'+idnum;
        if (document.getElementById(newthreid)) {
            document.getElementById(newthreid).style.color="{$STYLE['menu_color']}";
        }
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
$_info_msg_ht = '';

if (!empty($sidebar)) {
    echo <<<EOP
<p><a href="index.php?sidebar=true" target="_content">2ペイン表示</a></p>\n
EOP;
}
 
if ($_conf['enable_menu_new']) {
    echo <<<EOP
$reloaded_time [<a href="{$_SERVER['PHP_SELF']}?new=1" target="_self">更新</a>]
EOP;
}

//==============================================================
// ■お気に板をプリントする
//==============================================================
$aShowBrdMenuPc->print_favIta();

echo $_info_msg_ht;
$_info_msg_ht = '';

flush();

//==============================================================
// ■RSSをプリントする
//==============================================================
if ($_exconf['rss']['*']) {
    include_once (P2EX_LIBRARY_DIR . '/rss/menu.inc.php');
    flush();
}

//==============================================================
// ■特別
//==============================================================
$norefresh_q = '&amp;norefresh=true';

echo <<<EOP
<div class="menu_cate"><b class="menu_cate" onclick="showHide('c_spacial');">特別</b>\n
EOP;
if ($_exconf['etc']['multi_favs']) {
    $favlist_onchange = "openFavList('{$_conf['subject_php']}', this.options[this.selectedIndex].value, window.top.subject);";
    echo "<br>\n";
    echo FavSetManager::makeFavSetSwitchElem('m_favlist_set', 'お気にスレ', FALSE, $favlist_onchange);
}
echo "\t<div class=\"itas\" id=\"c_spacial\">\n";

// ■新着数を表示する場合
if ($_conf['enable_menu_new'] == 1 && !empty($_GET['new'])) {

    initMenuNewSp('fav');   // 新着数を初期化
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=fav{$norefresh_q}" onclick="chMenuColor({$matome_i});" accesskey="f">お気にスレ</a> (<a href="{$_conf['read_new_php']}?spmode=fav" target="read" id="un{$matome_i}" onclick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>\n
EOP;
    flush();

    initMenuNewSp('recent');    // 新着数を初期化
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=recent{$norefresh_q}" onclick="chMenuColor({$matome_i});" accesskey="h">最近読んだスレ</a> (<a href="{$_conf['read_new_php']}?spmode=recent" target="read" id="un{$matome_i}" onclick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>\n
EOP;
    flush();

    initMenuNewSp('res_hist');  // 新着数を初期化
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=res_hist{$norefresh_q}" onclick="chMenuColor({$matome_i});">書込履歴</a> <a href="read_res_hist.php#footer" target="read">ログ</a> (<a href="{$_conf['read_new_php']}?spmode=res_hist" target="read" id="un{$matome_i}" onclick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>\n
EOP;
    flush();

// 新着数を表示しない場合
} else {
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=fav{$norefresh_q}" accesskey="f">お気にスレ</a><br>
    　<a href="{$_conf['subject_php']}?spmode=recent{$norefresh_q}" accesskey="h">最近読んだスレ</a><br>
    　<a href="{$_conf['subject_php']}?spmode=res_hist{$norefresh_q}">書込履歴</a>
        (<a href="./read_res_hist.php#footer" target="read">ログ</a>)<br>\n
EOP;
}

echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=palace{$norefresh_q}">スレの殿堂</a><br>
    　<a href="{$_conf['subject_php']}?spmode=news">ニュースチェック</a><br>
    　<a href="setting.php">ログイン管理</a><br>
    　<a href="editpref.php">設定管理</a><br>
    　<a href="http://find.2ch.net/" target="_blank" title="2ch公式検索">find.2ch.net</a>
EOP;

if ($_conf['tgrep_url']) {
    echo <<<EOP
<br>
    　<a href="tgrepc.php" title="&copy;rsk">スレタイ検索</a>
EOP;
}

// Google検索
$google_search_ht = '';
if ($_exconf['soap']['*']) {
    if ($_exconf['soap']['google_key'] && file_exists($_exconf['soap']['google_wsdl'])) {
        $google_search_ht = <<<EOP
<br>
    　<a href="gsearch.php">Google検索</a>
        (<a href="#" onclick="return OpenSubWin('gsearch.php', 480, 320, 0, 1);">p</a>)
EOP;
    }
}

echo $google_search_ht;
echo "\n\t</div>\n</div>\n";

echo $_info_msg_ht;
$_info_msg_ht = '';

flush();

//==============================================================
// ■ImageCache2
//==============================================================
if ($_exconf['imgCache']['*']) {
    echo <<<EOP
<div class="menu_cate"><b class="menu_cate" onclick="showHide('c_ic2');">ImageCache2</b><br>
    <div class="itas" id="c_ic2">
    　<a href="iv2.php" target="_blank">画像キャッシュ一覧</a><br>
    　<a href="ic2_getter.php">ダウンローダ</a>
        (<a href="#" onclick="return OpenSubWin('ic2_getter.php?popup=1', 480, 320, 1, 1);">p</a>)<br>
    　<a href="ic2_manager.php">データベース管理</a>
    </div>
</div>
EOP;
}

//==============================================================
// ■カテゴリと板を表示
//==============================================================
// brd読み込み
$brd_menus = BrdCtl::read_brds();

//===========================================================
// ■プリント
//===========================================================
if (isset($word) && strlen($word) > 0) {
    if (!$GLOBALS['ita_mikke']['num']) {
        $_info_msg_ht .=  "<p>&quot;{$word_ht}&quot;を含む板は見つかりませんでした。</p>\n";
    } else {
        $_info_msg_ht .=  "<p>&quot;{$word_ht}&quot;を含む板 {$GLOBALS['ita_mikke']['num']}hit!</p>\n";
    }
}

echo $_info_msg_ht;
$_info_msg_ht = '';

// 板検索フォームを表示
echo <<<EOFORM
<form method="GET" action="{$_SERVER['PHP_SELF']}" accept-charset="{$_conf['accept_charset']}" target="_self">
    <input type="hidden" name="detect_hint" value="◎◇">
    <p>
        <input type="text" id="word" name="word" value="{$word_ht}" size="14">
        <input type="submit" name="submit" value="板検索">\n
EOFORM;
if ($google_search_ht) {
    echo <<<EOFORM
        <input type="button" id="google" name="google" value="Google" onclick="doGoogleSearch(document.getElementById('word').value, window.top.subject);">\n
EOFORM;
}
echo <<<EOFORM
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

// ■for Mozilla Sidebar
if (empty($sidebar)) {
    echo <<<EOP
<script type="text/javascript">
<!--
if ((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function")) {
    document.writeln("<p><a href=\"javascript:void(0);\" onclick=\"addSidebar('P2 Menu', '{$menu_side_url}');\">p2 Menuを Sidebar に追加</a></p>");
}
// -->
</script>\n
EOP;
}

echo '</body></html>';


//==============================================================
// 関数
//==============================================================
/**
 * spmode用のmenuの新着数を初期化する
 */
function initMenuNewSp($spmode_in)
{
    global $shinchaku_num, $matome_i, $host, $bbs, $spmode, $STYLE, $class_newres_num;
    $matome_i++;
    $host = '';
    $bbs = '';
    $spmode = $spmode_in;
    include (P2_LIBRARY_DIR . '/subject_new.inc.php');  // $shinchaku_num, $_newthre_num をセット
    if ($shinchaku_num > 0) {
        $class_newres_num = ' class="newres_num"';
    } else {
        $class_newres_num = ' class="newres_num_zero"';
    }
}

?>
