<?php
// p2 -  サブジェクト -  ツールバー表示
// for subject.php

//===========================================================
// HTML表示用変数
//===========================================================
/* 主なHTML表示用変数は sb_header.inc.php にて設定 */

// {{{ 新着まとめ読み

if (isset($new_matome_i)) {
    $new_matome_i++;
} else {
    $new_matome_i = 0;
}

$shinchaku_matome_ht = '';

// 倉庫でなければ
if ($aThreadList->spmode != "soko") { 
    if (!empty($shinchaku_attayo)) {
        $shinchaku_num_ht = " (<span id=\"smynum{$new_matome_i}\" class=\"matome_num\">{$shinchaku_num}</span>)";
    } else {
        $shinchaku_num_ht = '';
    }
    $shinchaku_matome_ht = <<<EOP
<a id="smy{$new_matome_i}" class="matome" href="{$_conf['read_new_php']}?host={$aThreadList->host}&bbs={$aThreadList->bbs}&spmode={$aThreadList->spmode}{$norefresh_q}&amp;nt={$newtime}" onClick="chNewAllColor();">新着まとめ読み{$shinchaku_num_ht}</a>
EOP;
}

// }}}

if (isset($sb_tool_i)) {
    $sb_tool_i++;
} else {
    $sb_tool_i = 1;
}

$sb_tool_anchor = '';

if ($sb_tool_i == 1) {
    $sb_tool_anchor = <<<EOP
<a class="toolanchor" href="#sbtoolbar2" target="_self" title="ページ下部へ移動">▼</a>
EOP;

} elseif ($sb_tool_i == 2) {
    $sb_tool_anchor = <<<EOP
<a class="toolanchor" href="#header" target="_self" title="ページ上部へ移動">▲</a>
EOP;
}

//===========================================================
// HTMLプリント
//===========================================================
echo <<<EOP
    <table id="sbtoolbar{$sb_tool_i}" class="toolbar" cellspacing="0">
        <tr>
            <td align="left" valign="middle" nowrap>
                $ptitle_ht
            </td>
            <td align="left" valign="middle" nowrap>
                <form class="toolbar" method="GET" action="subject.php" accept-charset="{$_conf['accept_charset']}" target="_self">
                    $sb_form_hidden_ht
                    <input type="submit" name="submit_refresh" value="更新">
                    $sb_disp_num_ht
                </form>
            </td>
            <td align="left" valign="middle" nowrap>
                $filter_form_ht
            </td>
            <td align="left" valign="middle" nowrap>
                $edit_ht
            </td>
            <td align="right" valign="middle" nowrap>
                $shinchaku_matome_ht
                <span class="time">$reloaded_time</span>
                $sb_tool_anchor
            </td>
        </tr>
    </table>\n
EOP;

