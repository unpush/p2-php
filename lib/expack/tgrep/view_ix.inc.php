<?php

if ($htm['query'] === '') {
    echo "<div class=\"panel\" title=\"スレ検索\">無効なキーワードです。</div>";
} elseif (!is_array($threads) || count($threads) < 1) {
    echo "<div class=\"panel\" title=\"{$htm['query']} - スレ検索\">&quot;{$htm['query']}&quot;".
        " にマッチするスレッドはありませんでした。</div>";
} else {
    echo "<ul class=\"tgrep-result\" title=\"{$htm['query']} - スレ検索\">";

    $current_page = (isset($_GET['P'])) ? (int)$_GET['P'] : 1;
    $all_pages = ceil($subhits / $limit);

    printf('<li class="group">%d-%d/%d</li>',
           1 + $limit * ($current_page - 1),
           min($limit * $current_page, $subhits),
           $subhits
           );

    foreach ($threads as $o => $t) {
        $turl = sprintf('%s?host=%s&amp;bbs=%s&amp;key=%d',
                        $_conf['read_php'],
                        $t->host,
                        $t->bbs,
                        $t->tkey
                        );

        $burl = sprintf('%s?host=%s&amp;bbs=%s&amp;itaj_en=%s&amp;word=%s',
                        $_conf['subject_php'],
                        $t->host,
                        $t->bbs,
                        urlencode(base64_encode($t->ita)),
                        $htm['query_en']
                        );

        printf('<li><a href="%s" target="_self">%s<br /><span>%s %s</span></a></li>',
               $turl,
               strip_tags($t->title),
               date('y/m/d ', $t->tkey),
               $t->ita
               );
    }

    if ($subhits && $subhits > $limit && $all_pages > $currnt_page) {
        printf('<li><a href="tgrepc.php?hint=%s&amp;iq=%s&amp;P=%d&amp;M=%d"'.
                    ' style="text-align:right">next</a></li>',
               rawurlencode('◎◇'),
               rawurlencode($_GET['Q']),
               $current_page + 1,
               time()
               );
    }

    echo '</ul>';
}
