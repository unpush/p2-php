<?php
/*
    p2 -  板メニュー
    フレーム分割画面、左側部分 PC用
    
    menu.php, menu_side.php より読み込まれる
*/

require_once (P2_LIBRARY_DIR . '/brdctl.class.php');
require_once (P2_LIBRARY_DIR . '/showbrdmenupc.class.php');

authorize(); //ユーザ認証

//==============================================================
// 変数設定
//==============================================================
$s = $_SERVER['HTTPS'] ? 's' : '';
$me_url = "http{$s}://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
$me_dir_url = dirname($me_url);
// menu_side.php の URL。（ローカルパス指定はできないようだ）
$menu_side_url = $me_dir_url.'/menu_side.php';

$brd_menus = array();

if (isset($_GET['word'])) {
    $word = $_GET['word'];
} elseif (isset($_POST['word'])) {
    $word = $_POST['word'];
}

// ■板検索 ====================================
if (isset($word) && strlen($word) > 0) {

    if (preg_match('/^\.+$/', $word)) {
        $word = '';
    }
    
    // and検索
    include_once (P2_LIBRARY_DIR . '/strctl.class.php');
    $word_fm = StrCtl::wordForMatch($word, 'and');
    if (P2_MBREGEX_AVAILABLE == 1) {
        $GLOBALS['words_fm'] = @mb_split('\s+', $word_fm);
        $GLOBALS['word_fm'] = @mb_ereg_replace('\s+', '|', $word_fm);
    } else {
        $GLOBALS['words_fm'] = @preg_split('/\s+/', $word_fm);
        $GLOBALS['word_fm'] = @preg_replace('/\s+/', '|', $word_fm);
    }
}


//============================================================
// 特殊な前置処理
//============================================================
// お気に板の追加・削除
if (isset($_GET['setfavita'])) {
    include_once (P2_LIBRARY_DIR . '/setfavita.inc.php');
    setFavIta();
}

//================================================================
// ■メイン
//================================================================
$aShowBrdMenuPc =& new ShowBrdMenuPc();

//============================================================
// ■ヘッダ
//============================================================
$reloaded_time = date('n/j G:i:s'); // 更新時刻
$ptitle = 'p2 - menu';

P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">\n
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
EOP;

@include("./style/style_css.inc");
@include("./style/menu_css.inc");

echo <<<EOSCRIPT
    <script type="text/javascript" src="js/showhide.js"></script>
    <script language="JavaScript">
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
$_info_msg_ht = '';

if (!empty($sidebar)) {
    echo <<<EOP
<p><a href="index.php?sidebar=true" target="_content">p2 - 2ペイン表示</a></p>\n
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

//==============================================================
// ■特別
//==============================================================
$norefresh_q = '&amp;norefresh=true';

echo <<<EOP
<div class="menu_cate"><b><a class="menu_cate" href="javascript:void(0);" onClick="showHide('c_spacial');" target="_self">特別</a></b><br>
    <div class="itas" id="c_spacial">
EOP;

// ■新着数を表示する場合
if ($_conf['enable_menu_new'] == 1 and $_GET['new']) {

    initMenuNewSp("fav");    // 新着数を初期化
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=fav{$norefresh_q}" onClick="chMenuColor({$matome_i});" accesskey="f">お気にスレ</a> (<a href="{$_conf['read_new_php']}?spmode=fav" target="read" id="un{$matome_i}" onClick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;
    flush();

    initMenuNewSp("recent");    // 新着数を初期化
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=recent{$norefresh_q}" onClick="chMenuColor({$matome_i});" accesskey="h">最近読んだスレ</a> (<a href="{$_conf['read_new_php']}?spmode=recent" target="read" id="un{$matome_i}" onClick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;
    flush();

    initMenuNewSp("res_hist");    // 新着数を初期化
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=res_hist{$norefresh_q}" onClick="chMenuColor({$matome_i});">書込履歴</a> <a href="read_res_hist.php" target="read">ログ</a> (<a href="{$_conf['read_new_php']}?spmode=res_hist" target="read" id="un{$matome_i}" onClick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;
    flush();

// 新着数を表示しない場合
} else {
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=fav{$norefresh_q}" accesskey="f">お気にスレ</a><br>
    　<a href="{$_conf['subject_php']}?spmode=recent{$norefresh_q}" accesskey="h">最近読んだスレ</a><br>
    　<a href="{$_conf['subject_php']}?spmode=res_hist{$norefresh_q}">書込履歴</a> (<a href="./read_res_hist.php" target="read">ログ</a>)<br>
EOP;
}

echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=palace{$norefresh_q}">スレの殿堂</a><br>
    　<a href="setting.php">ログイン管理</a><br>
    　<a href="editpref.php">設定管理</a><br>
    　<a href="http://find.2ch.net/" target="_blank" title="2ch公式検索">find.2ch.net</a>
    </div>
</div>\n
EOP;

//==============================================================
// ■カテゴリと板を表示
//==============================================================
// brd読み込み
$brd_menus = BrdCtl::read_brds();

//===========================================================
// ■プリント
//===========================================================

// {{{ 検索ワードがあれば

if (isset($word) && strlen($word) > 0) {

    $word_ht = htmlspecialchars($word);
    
    $msg_ht .=  '<p>';
    if (!$GLOBALS['ita_mikke']['num']) {
        if (empty($GLOBALS['threti_match_ita_num'])) {
            $msg_ht .=  "\"{$word_ht}\"を含む板は見つかりませんでした。\n";
        }
    } else {
        $msg_ht .=  "\"{$word_ht}\"を含む板 {$GLOBALS['ita_mikke']['num']}hit!\n";
        
        // 検索結果が一つなら、自動で板一覧を開く
        if ($GLOBALS['ita_mikke']['num'] == 1) {
        $msg_ht .= '（自動オープン）';
            echo <<<EOP
<script type="text/javascript">
<!--
    parent.subject.location.href="{$_conf['subject_php']}?host={$GLOBALS['ita_mikke']['host']}&bbs={$GLOBALS['ita_mikke']['bbs']}&itaj_en={$GLOBALS['ita_mikke']['itaj_en']}";
// -->
</script>
EOP;
        }
    }
    $msg_ht .= '</p>';
    
    $_info_msg_ht .= $msg_ht;
}

// }}}

echo $_info_msg_ht;
$_info_msg_ht = "";

// 板検索フォームを表示
echo <<<EOFORM
<form method="GET" action="{$_SERVER['PHP_SELF']}" accept-charset="{$_conf['accept_charset']}" target="_self">
    <input type="hidden" name="detect_hint" value="◎◇">
    <p>
        <input type="text" id="word" name="word" value="{$word_ht}" size="14">
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

// ■for Mozilla Sidebar
if (empty($sidebar)) {
    echo <<<EOP
<script type="text/JavaScript">
<!--
if ((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function")) {
    document.writeln("<p><a href=\"javascript:void(0);\" onClick=\"addSidebar('p2 Menu', '{$menu_side_url}');\">p2 Menuを Sidebar に追加</a></p>");
}
-->
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
    $host = "";
    $bbs = "";
    $spmode = $spmode_in;
    include("./subject_new.php");    // $shinchaku_num, $_newthre_num をセット
    if ($shinchaku_num > 0) {
        $class_newres_num = ' class="newres_num"';
    } else {
        $class_newres_num = ' class="newres_num_zero"';
    }
}

?>
