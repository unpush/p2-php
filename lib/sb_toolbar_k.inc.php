<?php
// p2 -  サブジェクト -  ツールバー表示（携帯）
// for subject.php

$matome_accesskey_at = "";
$matome_accesskey_navi = "";

// 新着まとめ読み =========================================
if ($upper_toolbar_done && !$_conf['iphone']) {
    $matome_accesskey_at = " {$_conf['accesskey']}=\"{$_conf['k_accesskey']['matome']}\"";
    $matome_accesskey_navi = "{$_conf['k_accesskey']['matome']}.";
}

// 倉庫でなければ
if ($aThreadList->spmode != "soko") {
    if ($shinchaku_attayo) {
        $shinchaku_matome_ht = <<<EOP
<a href="{$_conf['read_new_k_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}{$norefresh_q}&amp;nt={$newtime}{$_conf['k_at_a']}"{$matome_accesskey_at}>{$matome_accesskey_navi}新まとめ({$shinchaku_num})</a>
EOP;
        $shinchaku_norefresh_ht = '<input type="hidden" name="norefresh" value="1">';
    } else {
        $shinchaku_matome_ht = <<<EOP
<a href="{$_conf['read_new_k_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}&amp;nt={$newtime}{$_conf['k_at_a']}"{$matome_accesskey_at}>{$matome_accesskey_navi}新まとめ</a>
EOP;
        $shinchaku_norefresh_ht = '';
    }
    $shinchaku_matome_ht .= <<<EOP
\n<form class="ib" method="get" action="{$_conf['read_new_k_php']}">
<input type="hidden" name="host" value="{$aThreadList->host}">
<input type="hidden" name="bbs" value="{$aThreadList->bbs}">
<input type="hidden" name="spmode" value="{$aThreadList->spmode}">
<input type="hidden" name="nt" value="1">{$shinchaku_norefresh_ht}
未読数が<input type="text" name="unum_limit" value="100" size="4" maxlength="4" istyle="4" format="4N" mode="numeric">未満の
<input type="submit" value="新まとめ">
</form>\n
EOP;
} else {
    $shinchaku_matome_ht = '';
}

// プリント==============================================
echo "<div>{$ptitle_ht} {$shinchaku_matome_ht}</div>\n";

// 後変数==============================================
$upper_toolbar_done = true;
