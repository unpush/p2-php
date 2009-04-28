<?php
/*
    p2 -  板メニュー
    フレーム分割画面、左側部分 PC用
    
    menu.php, menu_side.php より読み込まれる
*/

require_once P2_LIB_DIR . '/BrdCtl.php';
require_once P2_LIB_DIR . '/ShowBrdMenuPc.php';

$_login->authorize(); // ユーザ認証


// {{{ 変数設定

// menu_side.php の URL。（相対パス指定はできないようだ）
$menu_side_url = dirname(P2Util::getMyUrl()) . '/menu_side.php';

BrdCtl::parseWord(); // set $GLOBALS['word']

// }}}
// {{{ 前処理

// お気に板の追加・削除
if (isset($_GET['setfavita'])) {
    require_once P2_LIB_DIR . '/setfavita.inc.php';
    setFavIta();
}

// }}}

//================================================================
// メイン
//================================================================
$aShowBrdMenuPc = new ShowBrdMenuPc;

//============================================================
// ヘッダHTML表示
//============================================================
$reloaded_time = date('n/j G:i:s'); // 更新時刻
$ptitle = 'p2 - menu';

P2Util::headerNoCache();
P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();

// 自動更新 meta refreshタグ
_printMetaRereshHtml()
?>
	<title><?php eh($ptitle); ?></title>
	<base target="subject">
<?php
P2View::printIncludeCssHtml('style');
P2View::printIncludeCssHtml('menu');
?>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<script type="text/javascript" src="js/showhide.js"></script>
<?php
_printHeaderJs();
?>
</head><body>
<?php
P2Util::printInfoHtml();

if (!empty($sidebar)) {
    ?>
<p><a href="index.php?sidebar=1" target="_content">p2 - 2ペイン表示</a></p>
<?php
}

if ($_conf['enable_menu_new']) {
    $shownew_atag = P2View::tagA(
        P2Util::buildQueryUri($_SERVER['SCRIPT_NAME'], array('shownew' => '1')),
        '更新',
        array('target' => '_self')
    );
    echo <<<EOP
$reloaded_time <span style="white-space:nowrap;">[$shownew_atag]</span>
EOP;
}

// お気に板をHTML表示する
$aShowBrdMenuPc->printFavItaHtml();

//==============================================================
// 特別をHTML表示
//==============================================================

?>
<div class="menu_cate"><b><a class="menu_cate" href="javascript:void(0);" onClick="showHide('c_spacial', 'itas_hide');" target="_self">特別</a></b><br>
    <div class="itas" id="c_spacial">
<?php

// 新着数を表示する場合
if ($_conf['enable_menu_new'] == 1 and !empty($_GET['shownew'])) {
    
    ?>　<?php echo _getRecentNewLinkHtml();?><br><?php
    
    ob_flush(); flush();
    
    list($matome_i, $shinchaku_num) = _initMenuNewSp('fav');    // 新着数を初期化取得
    $id = 'sp' . $matome_i;
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=fav&amp;norefresh=1" onClick="chMenuColor('{$id}');" accesskey="{$_conf['pc_accesskey']['setfav']}">お気にスレ</a> (<a href="{$_conf['read_new_php']}?spmode=fav" target="read" id="un{$id}" onClick="chUnColor('{$id}');"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;
    ob_flush(); flush();

    list($matome_i, $shinchaku_num) = _initMenuNewSp('res_hist');    // 新着数を初期化取得
    $id = 'sp' . $matome_i;
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=res_hist&amp;norefresh=1" onClick="chMenuColor('{$id}');">書込履歴</a> <a href="read_res_hist.php" target="read">ログ</a> (<a href="{$_conf['read_new_php']}?spmode=res_hist" target="read" id="un{$id}" onClick="chUnColor('{$id}');"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;
    ob_flush(); flush();

// 新着数を表示しない場合
} else {
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=recent&amp;norefresh=1" accesskey="{$_conf['pc_accesskey']['recent']}">最近読んだスレ</a><br>
    　<a href="{$_conf['subject_php']}?spmode=fav&amp;norefresh=1" accesskey="{$_conf['pc_accesskey']['setfav']}">お気にスレ</a><br>
    　<a href="{$_conf['subject_php']}?spmode=res_hist&amp;norefresh=1">書込履歴</a> (<a href="./read_res_hist.php" target="read">ログ</a>)<br>
EOP;
}

?>
    　<a href="<?php eh($_conf['subject_php']); ?>?spmode=palace&amp;norefresh=1" title="DAT落ちしたスレ用のお気に入り">スレの殿堂</a><br>
    　<a href="setting.php">ログイン管理</a><br>
    　<a href="<?php eh($_conf['editpref_php']) ?>">設定管理</a><br>
    　<a href="http://find.2ch.net/" target="_blank" title="find.2ch.net">2ch検索</a>
    </div>
</div>
<?php

//==============================================================
// カテゴリと板をHTML表示
//==============================================================
$brd_menus = BrdCtl::readBrdMenus();

// {{{ 検索ワードがあれば

if (strlen($GLOBALS['word'])) {

    $msg_ht =  '<p>';
    if (empty($GLOBALS['ita_mikke']['num'])) {
        if (empty($GLOBALS['threti_match_ita_num'])) {
            $msg_ht .=  sprintf('"%s"を含む板は見つかりませんでした。', hs($word));
        }
    } else {
        $msg_ht .= sprintf('"%s"を含む板 %shit!', hs($word), hs($GLOBALS['ita_mikke']['num']));
        
        // 検索結果が一つなら、自動で板一覧を開く
        if ($GLOBALS['ita_mikke']['num'] == 1) {
            $msg_ht .= '（自動オープンするよ）';
            
            $location_uri = P2Util::buildQueryUri(
                $_conf['subject_php'],
                array(
                    'host' => $GLOBALS['ita_mikke']['host'],
                    'bbs'  => $GLOBALS['ita_mikke']['bbs'],
                    'itaj_en' => $GLOBALS['ita_mikke']['itaj_en']
                )
            );
            $msg_ht .= <<<EOP
<script type="text/javascript">
<!--
    parent.subject.location.href="{$location_uri}";
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
?>
<form method="GET" action="<?php eh($_SERVER['SCRIPT_NAME']); ?>" accept-charset="<?php eh($_conf['accept_charset']); ?>" target="_self">
    <input type="hidden" name="detect_hint" value="◎◇">
    <p>
        <input type="text" id="word" name="word" value="<?php eh($word); ?>" size="14">
        <input type="submit" name="submit" value="板検索">
    </p>
</form>
<?php

// 板カテゴリメニューをHTML表示
if ($brd_menus) {
    foreach ($brd_menus as $a_brd_menu) {
        $aShowBrdMenuPc->printBrdMenu($a_brd_menu->categories);
    }
}


// {{{ フッタHTMLを表示

// for Mozilla Sidebar
if (empty($sidebar)) {
    ?>
<script type="text/JavaScript">
<!--
if ((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function")) {
    document.writeln("<p><a href=\"javascript:void(0);\" onClick=\"addSidebar('p2 Menu', '<?php eh($menu_side_url); ?>');\">p2 Menuを Sidebar に追加</a></p>");
}
-->
</script>
<?php
}

?>
</body></html>
<?php

// }}}



//==================================================================================
// 関数 （このファイル内でのみ利用）
//==================================================================================
/**
 * spmode用のmenuの新着数を初期化する
 *
 * @return  array
 */
function _initMenuNewSp($spmode_in)
{
    global $_conf, $STYLE;
    global $class_newres_num;
    static $matome_i_ = 1;
    
    $matome_i_++;
    
    $host = '';
    $bbs  = '';
    $spmode = $spmode_in;
    
    include './subject_new.php';    // $shinchaku_num, $_newthre_num がセットされる
    
    if ($shinchaku_num > 0) {
        $class_newres_num = ' class="newres_num"';
    } else {
        $class_newres_num = ' class="newres_num_zero"';
    }
    
    return array($matome_i_, $shinchaku_num);
}

/**
 * @return  string  HTML
 */
function _getRecentNewLinkHtml()
{
    global $_conf;
    
    list($matome_i, $shinchaku_num) = _initMenuNewSp('recent'); // 新着数を初期化取得
    
    $id = "sp{$matome_i}";
    
    $recent_atag = P2View::tagA(
        P2Util::buildQueryUri(
            $_conf['subject_php'],
            array(
                'spmode' => 'recent',
                'norefresh' => '1'
            )
        ),
        '最近読んだスレ',
        array(
            'onClick' => "chMenuColor('{$id}');",
            'accesskey' => $_conf['pc_accesskey']['recent']
        )
    );

    $recent_new_attrs = array(
        'id'      => "un$id",
        'onClick' => "chUnColor('$id');",
        'target'  => 'read'
    );
    
    if ($shinchaku_num > 0) {
        $recent_new_attrs['class'] = 'newres_num';
    } else {
        $recent_new_attrs['class'] = 'newres_num_zero';
    }
    
    $recent_new_atag = P2View::tagA(
        P2Util::buildQueryUri($_conf['read_new_php'], array('spmode' => 'recent')),
        hs($shinchaku_num),
        $recent_new_attrs
    );
    
    return "$recent_atag ($recent_new_atag)";
}

/**
 * 自動更新 meta refreshタグ
 *
 * @return  void
 */
function _printMetaRereshHtml()
{
    global $_conf;
    
    if ($_conf['menu_refresh_time']) {
        $refresh_time_s = $_conf['menu_refresh_time'] * 60;
        $qs = array(
            'shownew'   => 1,
            UA::getQueryKey() => UA::getQueryValue()
        );
        if (defined('SID') && strlen(SID)) {
            $qs[session_name()] = session_id();
        }
        $refresh_url = P2Util::buildQueryUri(P2Util::getMyUrl(), $qs);
        ?><meta http-equiv="refresh" content="<?php eh($refresh_time_s) ?>;URL=<?php eh($refresh_url); ?>">
        <?php
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
?>
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

	function chUnColor(id)
	{
		var unid = 'un'+id;
		document.getElementById(unid).style.color = "<?php echo $STYLE['menu_color']; ?>";
	}

	function chMenuColor(id)
	{
		var newthreid = 'newthre'+id;
		if (document.getElementById(newthreid)) {
			document.getElementById(newthreid).style.color = "<?php echo $STYLE['menu_color']; ?>";
		}
		var unid = 'un'+id;
		document.getElementById(unid).style.color = "<?php echo $STYLE['menu_color']; ?>";
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
	</script>
<?php
}
