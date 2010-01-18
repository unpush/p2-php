<?php
/**
 * rep2expack - 簡易RSSリーダ（記事一覧・PC用）
 */

// {{{ 表示用変数

if ($atom) {
    $atom_q = '&amp;atom=1';
    $atom_ht = '<input type="hidden" name="atom" value="1">';
    $atom_chk = ' checked';
} else {
    $atom_q = '';
    $atom_ht = '';
    $atom_chk = '';
}
if ($mtime) {
    $mtime_q = '&amp;mt=' . $mtime;
} else {
    $mtime_q = '';
}

// }}}
// {{{ ツールバー

if ($rss_parse_success) {

    // タイトル画像をポップアップ表示
    $onmouse_popup = '';
    $popup_header = '';

    // ツールバー共通部品
    $matomeyomi = '';
    if (rss_item_exists($items, 'content:encoded') || rss_item_exists($items, 'description')) {
        $all_en = UrlSafeBase64::encode(UrlSafeBase64::decode($site_en) . ' の 概要まとめ読み');
        $matomeyomi = <<<EOP
<a class="toolanchor" href="read_rss.php?xml={$xml_en}&amp;title_en={$all_en}&amp;num=all{$atom_q}" target="read">概要まとめ読み</a>\n
EOP;
    }
    $ch_link = P2Util::throughIme($channel['link']);
    $ch_dscr_all = str_replace(array('&amp;', '&gt;', '&lt;', '&quot;'), array('&', '>', '<', '"'), strip_tags($channel['description']));
    $ch_dscr = (mb_strwidth($ch_dscr_all) > 36) ? mb_strcut($ch_dscr_all, 0, 36) . '...' : $ch_dscr_all;
    $ch_dscr_all = htmlspecialchars($ch_dscr_all, ENT_QUOTES);
    $ch_dscr = htmlspecialchars($ch_dscr, ENT_QUOTES);
    $rss_toolbar_ht = <<<EOP
<span class="itatitle"><a class="aitatitle" href="{$ch_link}" title="{$ch_dscr_all}"{$onmouse_popup}><b>{$title}</b></a></span> <span class="time">{$ch_dscr}</span></td>
<td class="toolbar-update" width="100%">
    <form class="toolbar" method="get" action="subject_rss.php" target="_self">
        <input type="hidden" name="xml" value="{$xml}">
        <input type="hidden" name="site_en" value="{$site_en}">
        <input type="hidden" name="refresh" value="1">{$atom_ht}
        <input type="submit" name="submit" value="更新">
    </form>
</td>
<td class="toolbar-anchor">{$matomeyomi}<span class="time">{$reloaded_time}</span>
EOP;

}

// }}}
// {{{ ヘッダ

echo <<<EOH
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    {$_conf['extra_headers_ht']}
    <title>{$title}</title>
    <base target="{$_conf['expack.rss.target_frame']}">
    <link rel="stylesheet" type="text/css" href="css.php?css=style&amp;skin={$skin_en}">
    <link rel="stylesheet" type="text/css" href="css.php?css=subject&amp;skin={$skin_en}">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    {$popup_header}
    <script type="text/javascript" src="js/basic.js?{$_conf['p2_version_id']}"></script>
    <script type="text/javascript">
    //<![CDATA[
    function setWinTitle(){
        if (top != self) {top.document.title=self.document.title;}
    }
    //]]>
    </script>
</head>
<body onload="setWinTitle();">
{$_info_msg_ht}

EOH;

// RSSがパースできなかったとき
if (!$rss_parse_success) {
    echo "<h1>{$title}</h1></body></html>";
    exit;
}

echo <<<EOTB
<table id="sbtoolbar1" class="toolbar" cellspacing="0"><tbody><tr>
<td class="toolbar-title">{$rss_toolbar_ht}<a class="toolanchor" href="#sbtoolbar2" target="_self">▼</a></td>
</tr></tbody></table>
<table class="threadlist" cellspacing="0">

EOTB;

// }}}
// {{{ 見出し

$description_column_ht = '';
$subject_column_ht = '';
$creator_column_ht = '';
$date_column_ht = '';
if ($matomeyomi) {
    $description_column_ht = '<th class="tu">概要</th>';
}
if (rss_item_exists($items, 'dc:subject')) {
    $subject_column_ht = '<th class="t">トピック</th>';
}
if (rss_item_exists($items, 'dc:creator')) {
    $creator_column_ht = '<th class="t">文責</th>';
}
if (rss_item_exists($items, 'dc:date') || rss_item_exists($items, 'dc:pubdate')) {
    $date_column_ht = '<th class="t">日時</th>';
}
echo <<<EOP
<thead><tr class="tableheader">
{$description_column_ht}<th class="tl">タイトル</th>{$subject_column_ht}{$creator_column_ht}{$date_column_ht}
</tr></thead>
<tbody>\n
EOP;

// 一覧
reset($items);
$i = 0;
foreach ($items as $item) {
    // 変数の初期化
    $date = '';
    $date_ht = '';
    $subject_ht = '';
    $creator_ht = '';
    $description_ht = '';
    $target_ht = '';
    $preview_one = '';
    // 偶数列か奇数列か
    $r = ($i % 2) ? 'r2' : 'r1';
    // 概要
    if ($description_column_ht) {
        if (isset($item['content:encoded']) || isset($item['description'])) {
            $title_en = UrlSafeBase64::encode($item['title']);
            $description_ht = "<td class=\"tu\"><a class=\"thre_title\" href=\"read_rss.php?xml={$xml_en}&amp;title_en={$title_en}&amp;num={$i}{$atom_q}{$mtime_q}\" target=\"{$_conf['expack.rss.desc_target_frame']}\">●</a></td>";
        } else {
            $description_ht = "<td class=\"tu\"></td>";
        }
    }
    // トピック
    if ($subject_column_ht) {
        $subject_ht = "<td class=\"t\">" . htmlspecialchars($item['dc:subject'], ENT_QUOTES, 'Shift_JIS', false) . "</td>";
    }
    // 文責
    if ($creator_column_ht) {
        $creator_ht = "<td class=\"t\">" . htmlspecialchars($item['dc:creator'], ENT_QUOTES, 'Shift_JIS', false) . "</td>";
    }
    // 日時
    if ($date_column_ht) {
        if (!empty($item['dc:date'])) {
            $date = rss_format_date($item['dc:date']);
        } elseif (!empty($item['dc:pubdate'])) {
            $date = rss_format_date($item['dc:pubdate']);
        }
        $date_ht = "<td class=\"t\">{$date}</td>";
    }
    // 2ch,bbspinkのスレッドをp2で表示
    if (preg_match('/http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))\/test\/read\.cgi\/([^\/]+)\/([0-9]+)(\/)?([^\/]+)?/', $item['link'])) {
        $link_orig = preg_replace_callback('/http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))\/test\/read\.cgi\/([^\/]+)\/([0-9]+)(\/)?([^\/]+)?/', 'rss_link2ch_callback', $item['link']);
        $preview_one = "<a href=\"{$link_orig}&amp;one=true\">&gt;&gt;1</a> ";
    } else {
        $link_orig = P2Util::throughIme($item['link'], TRUE);
    }
    // 一列表示
    $item_title = $item['title'];
    echo <<<EOP
<tr class="{$r}">{$description_ht}<td class="tl">{$preview_one}<a id="tt{$i}" class="thre_title" href="{$link_orig}">{$item_title}</a></td>{$subject_ht}{$creator_ht}{$date_ht}</tr>\n
EOP;
    $i++;
}

// }}}
// {{{ フッタ

echo <<<EOF
</tbody>
</table>
<table id="sbtoolbar2" class="toolbar" cellspacing="0"><tbody><tr>
<td class="toolbar-title">{$rss_toolbar_ht}<a class="toolanchor" href="#sbtoolbar1" target="_self">▲</a></td>
</tr></tbody></table>
<form id="urlform" method="get" action="{$_SERVER['SCRIPT_NAME']}" target="_self">
    RSS/Atomを直接指定
    <input id="url_text" type="text" value="{$xml_ht}" name="xml" size="54">
    (<label><input type="checkbox" name="atom" value="1"{$atom_chk}>Atom</label>)
    <input type="submit" name="btnG" value="表示">
</form>
</body>
</html>
EOF;

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
