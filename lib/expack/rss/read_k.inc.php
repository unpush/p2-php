<?php
/**
 * rep2expack - 簡易RSSリーダ（内容・携帯用）
 */

// {{{ ヘッダ

$ch_title = P2Util::re_htmlspecialchars($channel['title']);

echo <<<EOH
<html lang="ja">
<head>
{$_conf['meta_charset_ht']}
{$_conf['extra_headers_ht']}
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
<title>{$title}</title>
</head>
<body{$_conf['k_colors']}>
{$_info_msg_ht}
<h1>{$ch_title}</h1>
<hr>
EOH;

// RSSがパースできなかったとき
if (!$rss_parse_success) {
    echo '</body></html>';
    exit;
}

// }}}
// {{{ 概要

if (isset($num) && is_numeric($num)) {
    rss_print_content_k($items[$num], $num, count($items));
}

// }}}
// {{{ フッタ

echo '</body></html>';

// }}}
// {{{ 表示用関数

function rss_print_content_k($item, $num, $count)
{
    global $_conf, $xml_en, $channel, $ch_title;
    $item = array_map('trim', $item);

    // 変数の初期化
    $date_ht = '';
    $subject_ht = '';
    $creator_ht = '';
    $description_ht = '';
    $prev_item_ht = '';
    $next_item_ht = '';

    // タイトル
    $item_title = P2Util::re_htmlspecialchars($item['title']);

    // リンク
    $link_orig = P2Util::throughIme($item['link']);

    // jigブラウザWEB http://bwXXXX.jig.jp/fweb/?_jig_=
    $view_jig = '';
    /*
    $link_jig = 'http://bwXXXX.jig.jp/fweb/?_jig_=' . rawurlencode($item['link']);
    $view_jig = ' <a href="' . P2Util::throughIme($link_jig) . '">jW</a>';
    */

    // トピック
    if (isset($item['dc:subject'])) {
        $subject_ht = $item['dc:subject'];
    }

    // 文責
    if (isset($item['dc:creator']) && $item['dc:creator'] !== '') {
        $creator_ht = $item['dc:creator'];
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

    // 前後の概要へのリンク
    $prev_item_num = $num - 1;
    $next_item_num = $num + 1;
    if ($prev_item_num >= 0) {
        $prev_item_ht = "<a {$_conf['accesskey']}=\"4\" href=\"read_rss.php?xml={$xml_en}&amp;num={$prev_item_num}\">4.前</a>";
    }
    if ($next_item_num <= $count) {
        $next_item_ht = "<a {$_conf['accesskey']}=\"6\" href=\"read_rss.php?xml={$xml_en}&amp;num={$next_item_num}\">6.次</a>";
    }

    // 表示
    if ($_conf['iphone']) {
        echo <<<EOP
<h3>{$item_title}</h3>
<div>{$creator_ht}{$date_ht} <a href="{$link_orig}">直</a>{$view_jig}</div>
<hr>
<div>{$description_ht}</div>
<hr>
<div class="read-footer">{$prev_item_ht} {$next_item_ht}<br>
<a href="subject_rss.php?xml={$xml_en}">{$ch_title}</a><br>
{$_conf['k_to_index_ht']}
</div>\n
EOP;
    } else {
        echo <<<EOP
<h3>{$item_title}</h3>
<div>{$creator_ht}{$date_ht} <a href="{$link_orig}">直</a>{$view_jig}</div>
<hr>
<div>{$description_ht}</div>
<hr>
<div>{$prev_item_ht} {$next_item_ht}<br>
<a {$_conf['accesskey']}="5" href="subject_rss.php?xml={$xml_en}">5.{$ch_title}</a><br>
<a {$_conf['accesskey']}="9" href="menu_k.php?view=rss">9.RSS</a>
{$_conf['k_to_index_ht']}
</div>\n
EOP;
    }
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
