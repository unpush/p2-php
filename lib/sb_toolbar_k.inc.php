<?php
/**
 * rep2 - サブジェクト - ツールバー表示（携帯）
 * for subject.php
 */

$matome_accesskey_at = '';
$matome_accesskey_navi = '';

if (empty($upper_toolbar_done)) {
    if ($_conf['iphone']) {
        $toolbar_at = ' id="header" class="toolbar"';
        $updown_ht = "<a href=\"#footer\">▼</a>";
    } else {
        $toolbar_at = ' id="header" name="header"';
        $updown_ht = "<a href=\"#footer\"{$_conf['k_accesskey_at']['bottom']}>{$_conf['k_accesskey_st']['bottom']}▼</a>";
    }
} else {
    if ($_conf['iphone']) {
        $toolbar_at = ' id="footer" class="toolbar"';
        $updown_ht = "<a href=\"#header\">▲</a>";
    } else {
        $toolbar_at = ' id="footer" name="footer"';
        $updown_ht = "<a href=\"#header\"{$_conf['k_accesskey_at']['above']}>{$_conf['k_accesskey_st']['above']}▲</a>";
        $matome_accesskey_at = $_conf['k_accesskey_at']['matome'];
        $matome_accesskey_navi = $_conf['k_accesskey_st']['matome'];
    }
}

// 倉庫でなければ
if ($aThreadList->spmode != 'soko') {
    $shinchaku_matome_url = "{$_conf['read_new_k_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}&amp;nt={$newtime}{$_conf['k_at_a']}";

    if ($aThreadList->spmode == 'merge_favita') {
        $shinchaku_matome_url .= $_conf['m_favita_set_at_a'];
    }

    if ($shinchaku_attayo) {
        $shinchaku_matome_ht = <<<EOP
<a href="{$shinchaku_matome_url}{$norefresh_q}"{$matome_accesskey_at}>{$matome_accesskey_navi}新まとめ({$shinchaku_num})</a>
EOP;
        $shinchaku_norefresh_ht = '<input type="hidden" name="norefresh" value="1">';
    } else {
        $shinchaku_matome_ht = <<<EOP
<a href="{$shinchaku_matome_url}"{$matome_accesskey_at}>{$matome_accesskey_navi}新まとめ</a>
EOP;
        $shinchaku_norefresh_ht = '';
    }
} else {
    $shinchaku_matome_ht = '';
}

// プリント==============================================
echo "<div{$toolbar_at}>{$ptitle_ht} {$shinchaku_matome_ht} {$updown_ht}</div>\n";

// 後変数==============================================
$upper_toolbar_done = true;

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
