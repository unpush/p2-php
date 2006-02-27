<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 - RSS編集

include_once './conf/conf.inc.php';   // 基本設定ファイル読込
require_once P2_LIBRARY_DIR . '/filectl.class.php';

$_login->authorize(); // ユーザ認証

// 変数 =============
$_info_msg_ht = '';

//================================================================
//特殊な前置処理
//================================================================

//RSSの追加・削除、並び替え
if (isset($_GET['setrss']) || isset($_POST['setrss'])) {
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

echo <<<EOP
<div><b>{$ptitle_hd}の編集</b> [<a href="{$_SERVER['SCRIPT_NAME']}" onclick='parent.menu.location.href="{$_conf['menu_php']}?nr=1"'>メニューを更新</a>] {$switch_rss_form_ht}</div>\n
EOP;

echo $add_rss_form_ht;

if ($lines) {
    echo "<hr>\n";
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
}

//================================================================
// フッタHTML表示
//================================================================

echo '</body></html>';

?>
