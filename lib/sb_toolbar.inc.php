<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 -  サブジェクト -  ツールバー表示
// for subject.php

//===========================================================
// HTML表示用変数
//===========================================================
/* 主なHTML表示用変数は sb_header.inc.php にて設定 */

// 新着まとめ読み =========================================
if (!isset($new_matome_i)) {
	$new_matome_i = 1;
} else {
	$new_matome_i++;
}

// 倉庫でなければ
$shinchaku_num_ht = '';
$shinchaku_fav_ht = '';
if ($aThreadList->spmode != 'soko') {
	if ($shinchaku_attayo) {
		$shinchaku_num_ht = " (<span id=\"smynum{$new_matome_i}\" class=\"matome_num\">{$shinchaku_num}</span>)";
		if ($shinokini_attayo) {
			$shinchaku_fav_ht = <<<EOP
 <a id="smyfav{$new_matome_i}" class="matome matome_num" href="{$_conf['read_new_php']}?host={$aThreadList->host}&bbs={$aThreadList->bbs}&spmode={$aThreadList->spmode}{$norefresh_q}&amp;nt={$newtime}&amp;onlyfav=1">★{$shinokini_num}</a>
EOP;
		}
	}
	$shinchaku_matome_ht =<<<EOP
		<a id="smy{$new_matome_i}" class="matome" href="{$_conf['read_new_php']}?host={$aThreadList->host}&bbs={$aThreadList->bbs}&spmode={$aThreadList->spmode}{$norefresh_q}&amp;nt={$newtime}" onclick="chNewAllColor();">新着まとめ読み{$shinchaku_num_ht}</a>{$shinchaku_fav_ht}
EOP;
}

if (!isset($sb_tool_i)) {
	$sb_tool_i = 1;
} else {
	$sb_tool_i++;
}
if ($sb_tool_i == 1) {
	$sb_tool_anchor = '<a class="toolanchor" href="#sbtoolbar2" target="_self">▼</a>';
} elseif ($sb_tool_i == 2) {
	$sb_tool_anchor = '<a class="toolanchor" href="#sbtoolbar1" target="_self">▲</a>';
}

//===========================================================
// HTMLプリント
//===========================================================
echo <<<EOP
	<table id="sbtoolbar{$sb_tool_i}" class="toolbar" cellspacing="0">
		<tr>
			<td align="left" valign="middle" nowrap>
				{$ptitle_ht}
			</td>
			<td align="left" valign="middle" nowrap>
				<form class="toolbar" method="GET" action="subject.php" accept-charset="{$_conf['accept_charset']}" target="_self">
					{$sb_form_hidden_ht}
					<input type="submit" name="submit" value="更新">
					{$sb_disp_num_ht}
				</form>
			</td>
			<td align="left" valign="middle" nowrap>
				{$filter_form_ht}
			</td>
			<td align="left" valign="middle" nowrap>
				{$edit_ht}
			</td>
			<td align="right" valign="middle" nowrap>
				{$shinchaku_matome_ht}
				<span class="time">{$reloaded_time}</span>
				{$sb_tool_anchor}
			</td>
		</tr>
	</table>\n
EOP;

?>
