<?php
/*
    p2 -  板メニュー
    フレーム分割画面、左側部分 PC用
    
    menu.php, menu_side.php より読み込まれる
*/

require_once P2_LIB_DIR . '/brdctl.class.php';
require_once P2_LIB_DIR . '/showbrdmenupc.class.php';

$_login->authorize(); // ユーザ認証


// {{{ 変数設定

$me_url = P2Util::getMyUrl();
$me_dir_url = dirname($me_url);
// menu_side.php の URL。（相対パス指定はできないようだ）
$menu_side_url = $me_dir_url . '/menu_side.php';

BrdCtl::parseWord(); // set $GLOBALS['word']

// }}}
// {{{ 特殊な前処理

// お気に板の追加・削除
if (isset($_GET['setfavita'])) {
    require_once P2_LIB_DIR . '/setfavita.inc.php';
    setFavIta();
}

// }}}

//================================================================
// メイン
//================================================================
$aShowBrdMenuPc =& new ShowBrdMenuPc();

//============================================================
// ヘッダHTML表示
//============================================================
$reloaded_time = date('n/j G:i:s'); // 更新時刻
$ptitle = 'p2 - menu';

P2Util::header_nocache();
echo $_conf['doctype'];
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

include_once "./style/style_css.inc";
include_once "./style/menu_css.inc";

echo '<script type="text/javascript" src="js/showhide.js"></script>' . "\n";
_printHeaderJs();

echo <<<EOP
</head>
<body>
EOP;

P2Util::printInfoHtml();

if (!empty($sidebar)) {
    echo <<<EOP
<p><a href="index.php?sidebar=true" target="_content">p2 - 2ペイン表示</a></p>\n
EOP;
}

if ($_conf['enable_menu_new']) {
    echo <<<EOP
$reloaded_time <span style="white-space:nowrap;">[<a href="{$_SERVER['SCRIPT_NAME']}?new=1" target="_self">更新</a>]</span>
EOP;
}


// お気に板をHTML表示する
$aShowBrdMenuPc->printFavItaHtml();

//==============================================================
// 特別をHTML表示
//==============================================================
$norefresh_q = '&amp;norefresh=true';

echo <<<EOP
<div class="menu_cate"><b><a class="menu_cate" href="javascript:void(0);" onClick="showHide('c_spacial', 'itas_hide');" target="_self">特別</a></b><br>
    <div class="itas" id="c_spacial">
EOP;

// 新着数を表示する場合
if ($_conf['enable_menu_new'] == 1 and !empty($_GET['new'])) {

    _initMenuNewSp("recent");    // 新着数を初期化
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=recent{$norefresh_q}" onClick="chMenuColor({$matome_i});" accesskey="h">最近読んだスレ</a> (<a href="{$_conf['read_new_php']}?spmode=recent" target="read" id="un{$matome_i}" onClick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;
    flush();
    
    _initMenuNewSp("fav");    // 新着数を初期化
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=fav{$norefresh_q}" onClick="chMenuColor({$matome_i});" accesskey="f">お気にスレ</a> (<a href="{$_conf['read_new_php']}?spmode=fav" target="read" id="un{$matome_i}" onClick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;
    flush();

    _initMenuNewSp("res_hist");    // 新着数を初期化
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=res_hist{$norefresh_q}" onClick="chMenuColor({$matome_i});">書込履歴</a> <a href="read_res_hist.php" target="read">ログ</a> (<a href="{$_conf['read_new_php']}?spmode=res_hist" target="read" id="un{$matome_i}" onClick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;
    flush();

// 新着数を表示しない場合
} else {
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=recent{$norefresh_q}" accesskey="h">最近読んだスレ</a><br>
    　<a href="{$_conf['subject_php']}?spmode=fav{$norefresh_q}" accesskey="f">お気にスレ</a><br>
    　<a href="{$_conf['subject_php']}?spmode=res_hist{$norefresh_q}">書込履歴</a> (<a href="./read_res_hist.php" target="read">ログ</a>)<br>
EOP;
}

echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=palace{$norefresh_q}" title="DAT落ちしたスレ用のお気に入り">スレの殿堂</a><br>
    　<a href="setting.php">ログイン管理</a><br>
    　<a href="editpref.php">設定管理</a><br>
    　<a href="http://find.2ch.net/" target="_blank" title="find.2ch.net">2ch検索</a>
    </div>
</div>\n
EOP;

//==============================================================
// カテゴリと板をHTML表示
//==============================================================
$brd_menus = BrdCtl::readBrdMenus();

$word_hs = '';

// {{{ 検索ワードがあれば

if (strlen($GLOBALS['word']) > 0) {

    $word_hs = htmlspecialchars($word, ENT_QUOTES);
    
    $msg_ht =  '<p>';
    if (empty($GLOBALS['ita_mikke']['num'])) {
        if (empty($GLOBALS['threti_match_ita_num'])) {
            $msg_ht .=  "\"{$word_hs}\"を含む板は見つかりませんでした。\n";
        }
    } else {
        $msg_ht .=  "\"{$word_hs}\"を含む板 {$GLOBALS['ita_mikke']['num']}hit!\n";
        
        // 検索結果が一つなら、自動で板一覧を開く
        if ($GLOBALS['ita_mikke']['num'] == 1) {
        $msg_ht .= '（自動オープンするよ）';
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
    
    P2Util::pushInfoHtml($msg_ht);
}

// }}}

P2Util::printInfoHtml();

// 板検索フォームをHTML表示
echo <<<EOFORM
<form method="GET" action="{$_SERVER['SCRIPT_NAME']}" accept-charset="{$_conf['accept_charset']}" target="_self">
    <input type="hidden" name="detect_hint" value="◎◇">
    <p>
        <input type="text" id="word" name="word" value="{$word_hs}" size="14">
        <input type="submit" name="submit" value="板検索">
    </p>
</form>\n
EOFORM;

// 板カテゴリメニューをHTML表示
if ($brd_menus) {
    foreach ($brd_menus as $a_brd_menu) {
        $aShowBrdMenuPc->printBrdMenu($a_brd_menu->categories);
    }
}


// {{{ フッタHTMLを表示

// for Mozilla Sidebar
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

// }}}


//==============================================================
// 関数 （このファイル内でのみ利用）
//==============================================================
/**
 * spmode用のmenuの新着数を初期化する
 *
 * @return  void
 */
function _initMenuNewSp($spmode_in)
{
    global $shinchaku_num, $matome_i, $host, $bbs, $spmode, $STYLE, $class_newres_num, $_conf;
    
    $matome_i++;
    $host = "";
    $bbs = "";
    $spmode = $spmode_in;
    include "./subject_new.php";    // $shinchaku_num, $_newthre_num をセット
    if ($shinchaku_num > 0) {
        $class_newres_num = ' class="newres_num"';
    } else {
        $class_newres_num = ' class="newres_num_zero"';
    }
}

/**
 * ヘッダ内のJavaScriptをHTMLプリントする
 *
 * @return  void
 */
function _printHeaderJs()
{
    global $STYLE;
    
    echo <<<EOSCRIPT
    <script language="JavaScript">
    <!--
    function addSidebar(title, url)
    {
       if ((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function")) {
          window.sidebar.addPanel(title, url, '');
       } else {
          goNetscape();
       }
    }
    
    function goNetscape()
    {
        // var rv = window.confirm ("This page is enhanced for use with Netscape 7.  " + "Would you like to upgrade now?");
        var rv = window.confirm ("このページは Netscape 7 用に拡張されています.  " + "今すぐアップデートしますか?");
        if (rv) {
           document.location.href = "http://home.netscape.com/ja/download/download_n6.html";
        }
    }
    
    function chUnColor(idnum)
    {
        var unid = 'un'+idnum;
        document.getElementById(unid).style.color="{$STYLE['menu_color']}";
    }
    
    function chMenuColor(idnum)
    {
        var newthreid = 'newthre'+idnum;
        if (document.getElementById(newthreid)) {
            document.getElementById(newthreid).style.color = "{$STYLE['menu_color']}";
        }
        unid = 'un'+idnum;
        document.getElementById(unid).style.color = "{$STYLE['menu_color']}";
    }
    
    function confirmSetFavIta(itaj)
    {
        return window.confirm('「' + itaj + '」をお気に板から外しますか？');
    }
    
    
    // @see  showhide.js
    // あらかじめ隠しておくのはJavaScript有効時のみ
    if (document.getElementById) {
    	document.writeln('<style type="text/css" media="all">');
    	document.writeln('<!--');
    	document.writeln('.itas_hide{ display:none; }');
    	document.writeln('-->');
    	document.writeln('</style>');
    }

    // -->
    </script>\n
EOSCRIPT;
}
