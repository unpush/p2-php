<?php
/*
    p2 -  お気に入り編集
*/

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/filectl.class.php';

require_once P2_LIB_DIR . '/UA.php';

$_login->authorize(); // ユーザ認証


// {{{ 特殊な前処理

// お気に板の追加・削除、並び替え
if (isset($_GET['setfavita']) or isset($_POST['setfavita']) or isset($_POST['submit_listfavita'])) {
    require_once P2_LIB_DIR . '/setfavita.inc.php';
    setFavIta();
}
// お気に板のホストを同期
if (isset($_GET['syncfavita']) or isset($_POST['syncfavita'])) {
    require_once P2_LIB_DIR . '/BbsMap.class.php';
    BbsMap::syncBrd($_conf['favita_path']);
}

// }}}

// 並び替えにJavaScript使うかい？
if ($_conf['ktai'] or UA::isNetFront() or !empty($_POST['sortNoJs']) || !empty($_GET['sortNoJs']) or isset($_GET['setfavita'])) {
    $sortNoJs = true;
} else {
    $sortNoJs = false;
}

//================================================================
// ヘッダHTML表示
//================================================================
P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>p2 - お気に板の並び替え</title>
<script type="text/javascript" src="js/yui/YAHOO.js" ></script>
<script type="text/javascript" src="js/yui/log.js" ></script>
<script type="text/javascript" src="js/yui/event.js" ></script>
<script type="text/javascript" src="js/yui/dom.js"></script>

<script type="text/javascript" src="js/yui/dragdrop.js" ></script>
		<script type="text/javascript" src="js/yui/ygDDOnTop.js" ></script>
		<script type="text/javascript" src="js/yui/ygDDSwap.js" ></script>
		<script type="text/javascript" src="js/yui/ygDDMy.js" ></script>
		<script type="text/javascript" src="js/yui/ygDDMy2.js" ></script>
		<script type="text/javascript" src="js/yui/ygDDList.js" ></script>
		<script type="text/javascript" src="js/yui/ygDDPlayer.js" ></script>
EOP;

include_once './style/style_css.inc';
include_once './style/editfavita_css.inc';

echo '</head><body>' . "\n";

P2Util::printInfoHtml();

//=====================================================================
// メイン部分HTML表示
//=====================================================================

// お気に板情報を取得
FileCtl::make_datafile($_conf['favita_path'], $_conf['favita_perm']);
$lines = file($_conf['favita_path']);
$okini_itas = getOkiniItasFromLines($lines);


if (!$sortNoJs and !empty($lines)) {
?>
<script type="text/javascript">
	// var gLogger = new ygLogger("test_noimpl.php");
	var dd = []
	var gVarObj = new Object(); // お気に板のデータリスト
    
	function dragDropInit() {
		var i = 0;
		var id = '';
        for (j = 0; j < <?php echo count($lines); ?>; ++j) {
            id = "li" + j;
			dd[i++] = new ygDDList(id);
		}
        <?php
        foreach ($okini_itas as $k => $v) {
            echo "gVarObj['{$k}'] = '{$v['encValue']}';\n";
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
	var elem = document.getElementById('italist');
    var childs = elem.childNodes;
    for (var i = 0; i < childs.length; i++) {
        if (childs[i].tagName == 'LI' && childs[i].style.visibility != 'hidden' && childs[i].style.display != 'none') {
            values[i] = gVarObj[childs[i].id];
            //alert(values[i]);
        }
    }
    
    var val = "";
    for (var j = 0; j < values.length; j++) {
        if (val > "") {
            val += ",";
        }
        if (values[j] > "") {
			val += values[j];
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
if (!$_conf['ktai']) {
    $menu_href = $_SERVER['SCRIPT_NAME'];
    $sortNoJs and $menu_href .= '?sortNoJs=1';
    $onclick = " onClick='if (parent.menu) { parent.menu.location.href=\"{$_conf['menu_php']}?nr=1\"; }'";
    
// 携帯用
} else {
    $menu_href = 'menu_k.php?view=favita&amp;nr=1' . $_conf['k_at_a'] . '&amp;nt=' . time();
    $onclick = '';
}

echo <<<EOP
<div>お気に板の編集 [<a href="{$menu_href}"{$onclick}>板メニューを更新</a>]</div>
<hr>
EOP;


// {{{ お気に板追加フォーム HTML表示

$sortNoJsInputHtml = $sortNoJs ? '<input type="hidden" id="sortNoJs" name="sortNoJs" value="1">' : '';

echo  <<<EOFORM
<div><b>お気に板の新規追加</b></div>
<form method="POST" action="{$_SERVER['SCRIPT_NAME']}" accept-charset="{$_conf['accept_charset']}" target="_self">
    <input type="hidden" name="detect_hint" value="◎◇">
    {$_conf['k_input_ht']}
    板URL: <input type="text" id="url" name="url" value="http://" size="48">
    板名: <input type="text" id="itaj" name="itaj" value="" size="16">
    <input type="hidden" id="setfavita" name="setfavita" value="1">
    {$sortNoJsInputHtml}
    <input type="submit" name="submit" value="新規追加">
    <div>（入力例 → 板URL: http://news19.2ch.net/newsplus/ 板名: ニュース速報+）</div>
</form>

EOFORM;

// }}}

echo '<hr>';

// JavaScriptソート用
if (!$sortNoJs) {

    if ($lines) {
        $script_enable_html = <<<EOP
<b>お気に板の並び替え</b> （<a href="{$_SERVER['SCRIPT_NAME']}?sortNoJs=1">非JavaScript版はこちら</a>）<br>
（ドラッグアンドドロップ操作で並び替えができます。アイテムを枠外にD&Dすると削除できます。「変更を適用する」ボタンで決定します）
<div class="itas">
<form id="form" name="form" method="post" action="{$_SERVER['SCRIPT_NAME']}" accept-charset="{$_conf['accept_charset']}" target="_self">
        
<table border="0">
<tr>
<td class="italist" id="ddrange">

<ul id="italist">
<li id="hidden6" class="sortList" style="visibility:hidden;">Hidden</li>
EOP;
        if (is_array($okini_itas)) {
            foreach ($okini_itas as $k => $v) {
                $script_enable_html .= '<li id="' . $k . '" class="sortList"><b style="width:120pt;">' . hs($v['itaj']) . '</b> ' . hs($v['host']) . '/' . hs($v['bbs']) . '</li>';
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
<input type="submit" name="submit_listfavita" value="変更を適用する" onClick="submitApply();">

</div>
</form>
EOP;

    $regex = array('/"/', '/\n/');
    $replace = array('\"', null);
    $out = preg_replace($regex, $replace, $script_enable_html);

    echo <<<EOP
<script language="Javascript"> <!-- 
document.write("{$out}"); 
//--></script>
EOP;

}


// {{{ 非JavaScriptのソート HTMLを表示

if ($lines) {

    // JavaScriptソートなら <noscript>
    if (!$sortNoJs) {
        echo '<noscript>';
    }
    
    // PC（NetFront以外）なら
    if (!$_conf['ktai'] && !UA::isNetFront()) {
        $linkDD = '（<a href="' . $_SERVER['SCRIPT_NAME'] . '">JavaScript版はこちら</a>）';
    } else {
        $linkDD = '';
    }
    
    echo"<strong>お気に板の並び替え</strong> {$linkDD}";
    echo '<table>';
    foreach ($lines as $l) {
        if (preg_match('/^\t?(.+?)\t(.+?)\t(.+?)$/', rtrim($l), $matches)) {
            $itaj       = rtrim($matches[3]);
            $itaj_en    = rawurlencode(base64_encode($itaj));
            $host       = $matches[1];
            $bbs        = $matches[2];
            $itaj_hs    = htmlspecialchars($itaj, ENT_QUOTES);
            $itaj_q     = '&amp;itaj_en=' . $itaj_en;
            echo <<<EOP
            <tr>
            <td><a href="{$_conf['subject_php']}?host={$host}&amp;bbs={$bbs}{$_conf['k_at_a']}" title="{$host}/{$bbs}">{$itaj_hs}</a></td>
            <td>[ <a class="te" href="{$_SERVER['SCRIPT_NAME']}?host={$host}&amp;bbs={$bbs}{$itaj_q}&amp;setfavita=top{$_conf['k_at_a']}" title="一番上に移動">▲</a></td>
            <td><a class="te" href="{$_SERVER['SCRIPT_NAME']}?host={$host}&amp;bbs={$bbs}{$itaj_q}&amp;setfavita=up{$_conf['k_at_a']}" title="一つ上に移動">↑</a></td>
            <td><a class="te" href="{$_SERVER['SCRIPT_NAME']}?host={$host}&amp;bbs={$bbs}{$itaj_q}&amp;setfavita=down{$_conf['k_at_a']}" title="一つ下に移動">↓</a></td>
            <td><a class="te" href="{$_SERVER['SCRIPT_NAME']}?host={$host}&amp;bbs={$bbs}{$itaj_q}&amp;setfavita=bottom{$_conf['k_at_a']}" title="一番下に移動">▼</a> ]</td>
            <td>[<a href="{$_SERVER['SCRIPT_NAME']}?host={$host}&amp;bbs={$bbs}&amp;setfavita=0{$_conf['k_at_a']}">削除</a>]</td>
            </tr>
EOP;
        }
    }
    echo "</table>";
    
    // JavaScriptソートなら <noscript>
    if (!$sortNoJs) {
        echo '</noscript>';
    }
}

// }}}

/*
// PC用 お気に板同期フォーム HTML表示
if (!$_conf['ktai']) {
    echo '<hr>';

    echo <<<EOFORM
<form method="POST" action="{$_SERVER['SCRIPT_NAME']}" target="_self">
    <p>
        {$_conf['k_input_ht']}
        <input type="hidden" id="syncfavita" name="syncfavita" value="1">
        <input type="submit" name="submit" value="板リストとホストを同期する">（板のホスト移転に対応します。通常は自動で同期されるので、この操作は特に必要ありません）
    </p>
</form>\n
EOFORM;
}
*/

// フッタHTMLを表示する

if ($_conf['ktai']) {
    echo '<hr>' . $_conf['k_to_index_ht'];
}

echo '</body></html>';


exit;


//====================================================================
// 関数（このファイル内でのみ利用）
//====================================================================
/**
 * お気にリストから、お気に板データを取得する
 *
 * @param   array  $lines
 * @return  array  assoc
 */
function getOkiniItasFromLines($lines)
{
    $okini_itas = array();
    $i = 0;
    if (is_array($lines)) {
        foreach ($lines as $l) {
            if (preg_match("/^\t?(.+?)\t(.+?)\t(.+?)$/", rtrim($l), $matches)) {
                $id = "li{$i}";
                $okini_itas[$id]['itaj']       = $itaj = rtrim($matches[3]);
                $okini_itas[$id]['itaj_en']    = $itaj_en = base64_encode($itaj);
                $okini_itas[$id]['host']       = $host = $matches[1];
                $okini_itas[$id]['bbs']        = $bbs = $matches[2];
                // rawurlencode しているのは、デリミタ(,@)をエスケープするため
                $okini_itas[$id]['encValue']      = rawurlencode($host) . "@" . rawurlencode($bbs) . "@" . rawurlencode($itaj_en);

                $i++;
            }
        }
    }
    return $okini_itas;
}
