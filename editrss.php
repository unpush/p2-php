<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 - RSS編集

include_once './conf/conf.inc.php';   // 基本設定ファイル読込
include_once P2_LIBRARY_DIR . '/filectl.class.php';
include_once P2_LIBRARY_DIR . '/strctl.class.php';

$_login->authorize(); // ユーザ認証

// 変数 =============
$_info_msg_ht = '';

//================================================================
//特殊な前置処理
//================================================================

//RSSの追加・削除、並び替え
if (isset($_GET['setrss']) || isset($_POST['setrss']) || isset($_POST['submit_setrss'])) {
    include P2EX_LIBRARY_DIR . '/rss/setrss.inc.php';
}

// プリント用変数 ======================================================

// RSS追加フォーム
$add_rss_form_ht = <<<EOFORM
<hr>
<form method="POST" action="{$_SERVER['SCRIPT_NAME']}" accept-charset="{$_conf['accept_charset']}" target="_self">
    <input type="hidden" name="detect_hint" value="◎◇">
    <input type="hidden" id="setrss" name="setrss" value="1">
    <table border="0" cellspacing="1" cellpadding="0">
        <tr>
            <td align="right">URL:</td>
            <td>
                <input type="text" id="xml" name="xml" value="http://" size="48">
                (<label><input type="checkbox" id="atom" name="atom" value="1">Atom</label>)
            </td>
        </tr>
        <tr>
            <td align="right">サイト名:</td>
            <td>
                <input type="text" id="site" name="site" value="" size="32">
                <input type="submit" name="submit" value="新規追加">
            </td>
        </tr>
    </table>
</form>\n
EOFORM;

// RSS切替フォーム
if ($_conf['expack.misc.multi_favs']) {
    $switch_rss_form_ht = FavSetManager::makeFavSetSwitchForm('m_rss_set', 'RSS', NULL, NULL, !$_conf['ktai']);
} else {
    $switch_rss_form_ht = '';
}

//================================================================
// ヘッダ
//================================================================
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>p2 - RSSの並び替え</title>
    <script type="text/javascript" src="js/yui/YAHOO.js?{$_conf['p2expack']}" ></script>
    <script type="text/javascript" src="js/yui/log.js?{$_conf['p2expack']}" ></script>
    <script type="text/javascript" src="js/yui/event.js?{$_conf['p2expack']}" ></script>
    <script type="text/javascript" src="js/yui/dom.js?{$_conf['p2expack']}"></script>
    <script type="text/javascript" src="js/yui/dragdrop.js?{$_conf['p2expack']}" ></script>
        <script type="text/javascript" src="js/yui/ygDDOnTop.js?{$_conf['p2expack']}" ></script>
        <script type="text/javascript" src="js/yui/ygDDSwap.js?{$_conf['p2expack']}" ></script>
        <script type="text/javascript" src="js/yui/ygDDMy.js?{$_conf['p2expack']}" ></script>
        <script type="text/javascript" src="js/yui/ygDDMy2.js?{$_conf['p2expack']}" ></script>
        <script type="text/javascript" src="js/yui/ygDDList.js?{$_conf['p2expack']}" ></script>
        <script type="text/javascript" src="js/yui/ygDDPlayer.js?{$_conf['p2expack']}" ></script>
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=editfavita&amp;skin={$skin_en}" type="text/css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body>\n
EOP;

echo $_info_msg_ht;
$_info_msg_ht = '';

//================================================================
// メイン部分HTML表示
//================================================================
// ページタイトル
if ($_conf['expack.misc.multi_favs']) {
    $i = (isset($_SESSION['m_rss_set'])) ? (int)$_SESSION['m_rss_set'] : 0;
    $rss_titles = FavSetManager::getFavSetTitles('m_rss_set');
    if (!$rss_titles || !isset($rss_titles[$i]) || strlen($rss_titles[$i]) == 0) {
        if ($i == 0) {
            $ptitle_hd = 'RSS' . $i;
        } else {
            $ptitle_hd = 'RSS';
        }
    } else {
        $ptitle_hd = $rss_titles[$i];
    }
} else {
    $ptitle_hd = 'RSS';
}

//================================================================
// RSS
//================================================================

// rssファイルがなければ生成
FileCtl::make_datafile($_conf['expack.rss.setting_path'], $_conf['expack.rss.setting_perm']);
// rss読み込み
$lines = file($_conf['expack.rss.setting_path']);
$myrss = array();

$i = 0;
if (is_array($lines)) {
    foreach ($lines as $l) {
        $l = rtrim($l);
        $p = explode("\t", $l);
        if (count($p) > 1) {
            $id = "li{$i}";
            $myrss[$id]['site']       = $site = rtrim($p[0]);
            $myrss[$id]['site_en']    = $site_en = base64_encode($site);
            $myrss[$id]['site_view']  = htmlspecialchars($site);
            $myrss[$id]['site_ht']    = "&amp;site_en=" . $site_en;
            $myrss[$id]['xml']        = $xml = $p[1];
            $myrss[$id]['xml_en']     = rawurlencode($xml);
            $myrss[$id]['atom']       = $atom = ((isset($p[2]) && $p[2] == 1) ? '1' : '0');
            $myrss[$id]['value']      = StrCtl::toJavaScript("{$site}\t{$xml}\t{$atom}");

            $i++;
        }
    }
}

// PC用
if (empty($_conf['ktai']) and !empty($lines)) {
?>
<script type="text/javascript">
    // var gLogger = new ygLogger("test_noimpl.php");
    var dd = []
    var gVarObj = new Object();

    function dragDropInit() {
        var i = 0;
        var id = '';
        for (j = 0; j < <?php echo count($lines); ?>; ++j) {
            id = "li" + j;
            dd[i++] = new ygDDList(id);
            //gVarObj[id] = '<?php echo $host . "@" . $bbs . "@" . $itaj_en; ?>';
        }
        <?php
        foreach ($myrss as $k => $v) {
            echo "gVarObj['{$k}'] = '{$v['value']}';";
        }
        ?>

        dd[i++] = new ygDDListBoundary("hidden1");

        YAHOO.util.DDM.mode = 0; // 0:Point, :Intersect
    }

    YAHOO.util.Event.addListener(window, "load", dragDropInit);
    // YAHOO.util.DDM.useCache = false;


function makeOptionList()
{
    var values = [];
    var elem = document.getElementById('feedlist');
    var childs = elem.childNodes;
    for (var i = 0; i < childs.length; i++) {
        if (childs[i].tagName == 'LI' && childs[i].style.visibility != 'hidden' && childs[i].style.display != 'none') {
            values[i] = gVarObj[childs[i].id];
            //alert(values[i]);
        }
    }

    var val = "";
    for (var j = 0; j < values.length; j++) {
        if (values[j] > "") {
            val += values[j] + "\n";
        }
    }
    //alert(val);

    return val;
}

function submitApply()
{
    document.form['list'].value = makeOptionList();
    //alert(document.form['list'].value);
    //document.form.submit();
}
</script>
<?php
}


// PC用
if (empty($_conf['ktai'])) {
    $onclick = " onclick='if (parent.menu) { parent.menu.location.href=\"{$_conf['menu_php']}?nr=1\"; }'";
    $m_php = $_SERVER['SCRIPT_NAME'];

// 携帯用
} else {
    $onclick = '';
    $m_php = 'menu_k.php?view=rss&amp;nr=1' . $_conf['k_at_a'] . '&amp;nt=' . time();
}

echo <<<EOP
<div><b>{$ptitle_hd}の編集</b> [<a href="{$m_php}"{$onclick}>メニューを更新</a>] {$switch_rss_form_ht}</div>\n
EOP;

echo $add_rss_form_ht;
echo "<hr>\n";

// PC（NetFrontを除外）
if (empty($_conf['ktai']) && $_conf['favita_order_dnd'] && !P2Util::isNetFront()) {

    if ($lines) {
        $script_enable_html .= <<<EOP
RSSの並び替え（ドラッグアンドドロップ）
<div class="itas">
<form id="form" name="form" method="post" action="{$_SERVER['PHP_SELF']}" accept-charset="{$_conf['accept_charset']}" target="_self">

<table border="0">
<tr>
<td class="feedlist" id="ddrange">

<ul id="feedlist"><li id="hidden6" class="sortList" style="visibility:hidden;">Hidden</li>
EOP;
        if (is_array($myrss)) {
            foreach ($myrss as $k => $v) {
                $script_enable_html .= '<li id="' . $k . '" class="sortList">' . $v['site_view'] . '</li>';
            }
        }
    }

    $script_enable_html .= <<<EOP
<li id="hidden1" style="visibility:hidden;">Hidden</li></ul>

</td>
</tr>
</table>

<input type="hidden" name="list">

<input type="submit" value="元に戻す">
<input type="submit" name="submit_setrss" value="変更を適用する" onClick="submitApply();">

</div>
</form>
EOP;
    $regex = array('/"/', '/\n/');
    $replace = array('\"', null);
    $out = preg_replace($regex, $replace, $script_enable_html);

    echo <<<EOP
<script type="text/javascript">
<!--
document.write("{$out}");
//-->
</script>
EOP;

}

//================================================================
// NOSCRIPT時のHTML表示
//================================================================
if ($lines) {
    // PC（NetFrontを除外）
    if (empty($_conf['ktai']) && $_conf['favita_order_dnd'] && !P2Util::isNetFront()) {
        echo '<noscript>';
    }
    echo 'RSSの並び替え';
    echo "<table>\n";
    foreach ($lines as $l) {
        $l = rtrim($l);
        $p = explode("\t", $l);
        if (count($p) > 1) {
            $site = $p[0];
            $xml = $p[1];
            if (isset($p[2]) && $p[2] == 1) {
                $atom = 1;
                $atom_ht = '&amp;atom=1';
                $type_ht = 'Atom';
                $cngtype_ht = '&amp;setrss=rss';
            } else {
                $atom = 0;
                $atom_ht = '';
                $type_ht = 'RSS';
                $cngtype_ht = '&amp;setrss=atom';
            }
            $site_en = rawurlencode(base64_encode($site));
            $site_ht = "&amp;site_en=".$site_en;
            $xml_en = rawurlencode($xml);
            echo <<<EOP
    <tr>
        <td><a href="{$_SERVER['SCRIPT_NAME']}?xml={$xml_en}&amp;setrss=0" class="fav">★</a></td>
        <td><a href="subject_rss.php?xml={$xml_en}{$site_ht}{$atom_ht}">{$site}</a></td>
        <td>(<a class="te" href="{$_SERVER['SCRIPT_NAME']}?xml={$xml_en}{$site_ht}{$cngtype_ht}">{$type_ht}</a>)</td>
        <td>[ <a class="te" href="{$_SERVER['SCRIPT_NAME']}?xml={$xml_en}{$site_ht}{$atom_ht}&amp;setrss=top">▲</a></td>
        <td><a class="te" href="{$_SERVER['SCRIPT_NAME']}?xml={$xml_en}{$site_ht}{$atom_ht}&amp;setrss=up">↑</a></td>
        <td><a class="te" href="{$_SERVER['SCRIPT_NAME']}?xml={$xml_en}{$site_ht}{$atom_ht}&amp;setrss=down">↓</a></td>
        <td><a class="te" href="{$_SERVER['SCRIPT_NAME']}?xml={$xml_en}{$site_ht}{$atom_ht}&amp;setrss=bottom">▼</a> ]</td>
    </tr>\n
EOP;
        }
    }
    echo "</table>\n";
    // PC（NetFrontを除外）
    if (empty($_conf['ktai']) && $_conf['favita_order_dnd'] && !P2Util::isNetFront()) {
        echo '</noscript>';
    }
}

//================================================================
// フッタHTML表示
//================================================================

echo '</body></html>';

?>
