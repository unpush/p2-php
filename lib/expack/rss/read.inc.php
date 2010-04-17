<?php
/**
 * rep2expack - 簡易RSSリーダ（内容・PC用）
 */

// {{{ ヘッダ

$ch_title = htmlspecialchars($channel['title'], ENT_QUOTES, 'Shift_JIS', false);

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
    <link rel="stylesheet" type="text/css" href="css.php?css=read&amp;skin={$skin_en}">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <script type="text/javascript" src="js/basic.js?{$_conf['p2_version_id']}"></script>
    <script type="text/javascript">
    //<![CDATA[
    function setWinTitle(){
        if (top != self) {top.document.title=self.document.title;}
    }
    //]]>
    </script>
</head>
<body onload="setWinTitle()">
EOH;

P2Util::printInfoHtml();

// RSSがパースできなかったとき
if (!$rss_parse_success) {
    echo '</body></html>';
    exit;
}

// }}}
// {{{ 概要

reset($items);
if (isset($num)) {
    if (is_string($num) && $num == 'all') {
        $i = 1;
        $j = count($items);
        foreach ($items as $item) {
            rss_print_content($item, $i, $j);
            $i++;
        }
    } else {
        rss_print_content($items[$num], $num, 0);
    }
}

// }}}
// {{{ フッタ

echo '</body></html>';

// }}}
// {{{ 表示用関数

function rss_print_content($item, $num, $count)
{
    $item = array_map('trim', $item);

    // 変数の初期化
    $date_ht = '';
    $subject_ht = '';
    $creator_ht = '';
    $description_ht = '';
    $prev_item_ht = '';
    $next_item_ht = '';

    // リンク
    $item_title = htmlspecialchars($item['title'], ENT_QUOTES, 'Shift_JIS', false);

    // タイトル
    $link_orig = P2Util::throughIme($item['link']);

    // トピック
    if (isset($item['dc:subject'])) {
        $subject_ht = $item['dc:subject'];
    }

    // 文責
    if (isset($item['dc:creator']) && $item['dc:creator'] !== '') {
        $creator_ht = "<b class=\"name\">" . trim($item['dc:creator']) . "</b>：";
    }

    // 日時
    if (!empty($item['dc:date'])) {
        $date_ht = rss_format_date($item['dc:date']);
    } elseif (!empty($item['dc:pubdate'])) {
        $date_ht = rss_format_date($item['dc:pubdate']);
    }

    // 概要
    if (isset($item['content:encoded']) && $item['content:encoded'] !== '') {
        $description_ht = rss_desc_converter($item['content:encoded']);
    } elseif (isset($item['description']) && $item['description'] !== '') {
        $description_ht = rss_desc_converter($item['description']);
    }

    // 前後の概要へジャンプ
    if ($count != 0) {
        $prev_item_num = $num - 1;
        $next_item_num = $num + 1;
        if ($prev_item_num != 0) {
            $prev_item_ht = "<a href=\"#it{$prev_item_num}\">▲</a>";
        }
        if ($next_item_num <= $count) {
            $next_item_ht = "<a href=\"#it{$next_item_num}\">▼</a>";
        }
    }

    // 表示
    echo <<<EOP
<table id="it{$num}" width="100%">
    <tr>
        <td align="left"><h3 class="thread_title">{$item_title}</h3></td>
        <td align="right" nowrap>{$prev_item_ht} {$next_item_ht}</td>
    </tr>
</table>
<div style="margin:0.5em">{$creator_ht}{$date_ht} <a href="{$link_orig}">[LINK]</a></div>
<div style="margin:1em 1em 1em 2em">
{$description_ht}
</div>
<div style="text-align:right"><a href="#it{$num}">▲</a></div>\n
EOP;
    if ($count != 0 && $num != $count) { echo "\n<hr style=\"margin:20px 0px\">\n\n"; }

}

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
