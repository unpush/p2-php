<?php
/*
    p2 -  お気に入り編集
*/

include_once './conf/conf.inc.php';  // 基本設定
require_once (P2_LIBRARY_DIR . '/filectl.class.php');

authorize(); // ユーザ認証

//================================================================
// ■特殊な前置処理
//================================================================

// お気に板の追加・削除、並び替え
if (isset($_GET['setfavita']) or isset($_POST['setfavita'])) {
    include_once (P2_LIBRARY_DIR . '/setfavita.inc.php');
    setFavIta();
}
// お気に板のホストを同期
if (isset($_GET['syncfavita']) or isset($_POST['syncfavita'])) {
    include_once (P2_LIBRARY_DIR . '/syncfavita.inc.php');
}

// プリント用変数 ======================================================

// お気に板追加フォーム
$add_favita_form_ht = <<<EOFORM
<form method="POST" action="{$_SERVER['PHP_SELF']}" accept-charset="{$_conf['accept_charset']}" target="_self">
    <input type="hidden" name="detect_hint" value="◎◇">
    <p>
        {$_conf['k_input_ht']}
        URL: <input type="text" id="url" name="url" value="http://" size="48">
        板名: <input type="text" id="itaj" name="itaj" value="" size="16">
        <input type="hidden" id="setfavita" name="setfavita" value="1">
        <input type="submit" name="submit" value="新規追加">
    </p>
</form>\n
EOFORM;

// お気に板同期フォーム
$sync_favita_form_ht = <<<EOFORM
<form method="POST" action="{$_SERVER['PHP_SELF']}" target="_self">
    <p>
        {$_conf['k_input_ht']}
        <input type="hidden" id="syncfavita" name="syncfavita" value="1">
        <input type="submit" name="submit" value="板リストと同期">
    </p>
</form>\n
EOFORM;

//================================================================
// ヘッダ
//================================================================
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>p2 - お気に板の並び替え</title>
EOP;

@include("./style/style_css.inc");
@include("./style/editfavita_css.inc");

echo '</head><body>'."\n";

echo $_info_msg_ht;
$_info_msg_ht = '';

//================================================================
// メイン部分HTML表示
//================================================================

//================================================================
// お気に板
//================================================================

// favitaファイルがなければ生成
FileCtl::make_datafile($_conf['favita_path'], $_conf['favita_perm']);
// favita読み込み
$lines = file($_conf['favita_path']);

// PC用
if (!$_conf['ktai']) {
    $onclick = " onClick='parent.menu.location.href=\"{$_conf['menu_php']}?nr=1\"'";
    $m_php = $_SERVER['PHP_SELF'];
    
// 携帯用
} else {
    $onclick = '';
    $m_php = 'menu_k.php?view=favita&amp;nr=1'.$_conf['k_at_a'].'&amp;nt='.time();
}

echo <<<EOP
<div><b>お気に板の編集</b> [<a href="{$m_php}"{$onclick}>メニューを更新</a>]</div>
EOP;

echo $add_favita_form_ht;

if ($lines) {
    echo "<table>";
    foreach ($lines as $l) {
        $l = rtrim($l);
        if (preg_match('/^\t?(.+)\t(.+)\t(.+)$/', $l, $matches)) {
            $itaj = rtrim($matches[3]);
            $itaj_en = rawurlencode(base64_encode($itaj));
            $host = $matches[1];
            $bbs = $matches[2];
            $itaj_view = htmlspecialchars($itaj);
            $itaj_q = '&amp;itaj_en='.$itaj_en;
            echo <<<EOP
            <tr>
            <td><a href="{$_conf['subject_php']}?host={$host}&amp;bbs={$bbs}{$_conf['k_at_a']}">{$itaj_view}</a></td>
            <td>[ <a class="te" href="{$_SERVER['PHP_SELF']}?host={$host}&amp;bbs={$bbs}{$itaj_q}&amp;setfavita=top{$_conf['k_at_a']}" title="一番上に移動">▲</a></td>
            <td><a class="te" href="{$_SERVER['PHP_SELF']}?host={$host}&amp;bbs={$bbs}{$itaj_q}&amp;setfavita=up{$_conf['k_at_a']}" title="一つ上に移動">↑</a></td>
            <td><a class="te" href="{$_SERVER['PHP_SELF']}?host={$host}&amp;bbs={$bbs}{$itaj_q}&amp;setfavita=down{$_conf['k_at_a']}" title="一つ下に移動">↓</a></td>
            <td><a class="te" href="{$_SERVER['PHP_SELF']}?host={$host}&amp;bbs={$bbs}{$itaj_q}&amp;setfavita=bottom{$_conf['k_at_a']}" title="一番下に移動">▼</a> ]</td>
            <td>[<a href="{$_SERVER['PHP_SELF']}?host={$host}&amp;bbs={$bbs}&amp;setfavita=0{$_conf['k_at_a']}">削除</a>]</td>
            </tr>
EOP;
        }
    }
    echo "</table>";
}

if (!$_conf['ktai']) {
    echo $sync_favita_form_ht;
}

//================================================================
// フッタHTML表示
//================================================================
if ($_conf['ktai']) {
    echo '<hr>'.$_conf['k_to_index_ht'];
}

echo '</body></html>';

?>
