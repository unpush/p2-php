<?php
/**
 * rep2expack - 簡易RSSリーダ（記事一覧・携帯用）
 */

// {{{ ヘッダ

$info_msg_ht = P2Util::getInfoHtml();

echo <<<EOH
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
{$_conf['extra_headers_ht']}
<title>{$title}</title>
</head>
<body{$_conf['k_colors']}>
{$info_msg_ht}
<p><b>{$title}</b></p>
<hr>\n
EOH;

// RSSがパースできなかったとき
if (!$rss_parse_success) {
    echo '</body></html>';
    exit;
}

// }}}
// {{{ 表示用変数

if ($atom) {
    $atom_q = '&amp;atom=1';
    $atom_ht = '<input type="hidden" name="atom" value="1">';
    $atom_chk = ' chedked';
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
// {{{ 見出し

reset($items);
$i = 0;
echo "<ol>\n";
foreach ($items as $item) {
    $item = array_map('trim', $item);
    $item_title = htmlspecialchars($item['title'], ENT_QUOTES, 'Shift_JIS', false);
    $link_orig = P2Util::throughIme($item['link']);
    // jigブラウザWEB http://bwXXXX.jig.jp/fweb/?_jig_=
    $view_jig = '';
    /*
    $link_jig = 'http://bwXXXX.jig.jp/fweb/?_jig_=' . rawurlencode($item['link']);
    $view_jig = ' <a href="' . P2Util::throughIme($link_jig) . '">jW</a>';
    */
    if ((isset($item['content:encoded']) && $item['content:encoded'] !== '') ||
        (isset($item['description']) && $item['description'] !== '')
    ) {
        echo "<li><a href=\"read_rss.php?xml={$xml_en}&amp;title_en={$title_en}&amp;num={$i}{$atom_q}{$mtime_q}\">{$item_title}</a></li>\n";
    } else {
        echo "<li>{$item_title} <a href=\"{$link_orig}\">直</a>{$view_jig}</li>\n";
    }
    $i++;
}
echo "</ol>\n";

// }}}
// {{{ フッタ

echo <<<EOF
<hr>
<div class="center">
<a href="menu_k.php?view=rss"{$_conf['k_accesskey_at'][9]}>{$_conf['k_accesskey_st'][9]}RSS</a>
{$_conf['k_to_index_ht']}
</div>
<hr>
<form id="urlform" method="post" action="{$_SERVER['SCRIPT_NAME']}" target="_self">
RSS/Atomを直接指定<br>
<input type="hidden" name ="k" value="1">
<input type="text" name="xml" value="{$xml_ht}"><br>
<input type="submit" name="btnG" value="表示">
(<input type="checkbox" name="atom" value="1"{$atom_chk}>Atom)
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
