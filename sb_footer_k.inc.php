<?php
// p2 -  サブジェクト - 携帯フッタ表示
// for subject.php

//=================================================
//フッタプリント
//=================================================
$mae_ht = "";

$bbs_q = "&amp;bbs=".$aThreadList->bbs;

if ($word) {
	$word_at = "&amp;word=$word";
} else {
	$word_at = "";
}

if ($aThreadList->spmode=="fav" && $sb_view=="shinchaku") {
	$allfav_ht = <<<EOP
	<p><a href="subject.php?spmode=fav{$norefresh_q}{$_conf['k_at_a']}">全てのお気にｽﾚを表示</a></p>
EOP;
}

// ページタイトル部分HTML設定 ====================================
if ($aThreadList->spmode == "taborn") {
	$ptitle_ht = <<<EOP
	<a href="{$ptitle_url}" {$_conf['accesskey']}="{$_conf['k_accesskey']['up']}">{$_conf['k_accesskey']['up']}.<b>{$aThreadList->itaj}</b></a>（ｱﾎﾞﾝ中）
EOP;
} elseif ($aThreadList->spmode == "soko") {
	$ptitle_ht = <<<EOP
	<a  href="{$ptitle_url}" {$_conf['accesskey']}="{$_conf['k_accesskey']['up']}">{$_conf['k_accesskey']['up']}.<b>{$aThreadList->itaj}</b></a>（dat倉庫）
EOP;
} elseif ($ptitle_url) {
	$ptitle_ht = <<<EOP
	<a  href="{$ptitle_url}"><b>{$ptitle_hd}</b></a>
EOP;
} else {
	$ptitle_ht = <<<EOP
	<b>{$ptitle_hd}</b>
EOP;
}

// ナビ ===============================
if ($disp_navi['from'] > 1) {
	$mae_ht = <<<EOP
<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}{$norefresh_q}&amp;from={$disp_navi['mae_from']}{$word_at}{$_conf['k_at_a']}" {$_conf['accesskey']}="{$_conf['k_accesskey']['prev']}">{$_conf['k_accesskey']['prev']}.前</a>
EOP;
}

if ($disp_navi['tugi_from'] < $sb_disp_all_num) {
	$tugi_ht = <<<EOP
<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}{$norefresh_q}&amp;from={$disp_navi['tugi_from']}{$word_at}{$_conf['k_at_a']}" {$_conf['accesskey']}="{$_conf['k_accesskey']['next']}">{$_conf['k_accesskey']['next']}.次</a>
EOP;
}

if ($disp_navi['from'] == $disp_navi['end']) {
	$sb_range_on = $disp_navi['from'];
} else {
	$sb_range_on = "{$disp_navi['from']}-{$disp_navi['end']}";
}
$sb_range_st = "{$sb_range_on}/{$sb_disp_all_num} ";

if (!$disp_navi['all_once']) {
	$k_sb_navi_ht = <<<EOP
<p>{$sb_range_st}{$mae_ht} {$tugi_ht}</p>
EOP;
}
	
// {{{ dat倉庫
// スペシャルモードでなければ、またはあぼーんリストなら
if (!$aThreadList->spmode or $aThreadList->spmode == "taborn") {
	$dat_soko_ht = <<<EOP
	<a href="{$_conf['subject_php']}?host={$aThreadList->host}{$bbs_q}{$norefresh_q}&amp;spmode=soko{$_conf['k_at_a']}">dat倉庫</a> 
EOP;
}
// }}}

// {{{ あぼーん中のスレッド
if ($ta_num) {
	$taborn_link_ht = <<<EOP
	<a href="{$_conf['subject_php']}?host={$aThreadList->host}{$bbs_q}{$norefresh_q}&amp;spmode=taborn{$_conf['k_at_a']}">ｱﾎﾞﾝ中({$ta_num})</a> 
EOP;
}
// }}}

// {{{ 新規スレッド作成
if (!$aThreadList->spmode) {
	$buildnewthread_ht = <<<EOP
	<a href="post_form.php?host={$aThreadList->host}{$bbs_q}&amp;newthread=1{$_conf['k_at_a']}">ｽﾚ立て</a>
EOP;
}
// }}}

// {{{ ソート変更 （新着 レス No. タイトル 板 すばやさ 勢い Birthday ☆）
$sortq_spmode = '';
$sortq_host = '';
$sortq_ita = '';
// spmode時
if ($aThreadList->spmode) { 
	$sortq_spmode = "&amp;spmode={$aThreadList->spmode}";
}
// spmodeでない、または、spmodeがあぼーん or dat倉庫なら
if (!$aThreadList->spmode || $aThreadList->spmode == "taborn" || $aThreadList->spmode == "soko") { 
	$sortq_host = "&amp;host={$aThreadList->host}";
	$sortq_ita = "&amp;bbs={$aThreadList->bbs}";
}

$sorts = array('midoku' => '新着', 'res' => 'ﾚｽ', 'no' => 'No.', 'title' => 'ﾀｲﾄﾙ');
if ($aThreadList->spmode and $aThreadList->spmode != 'taborn' and $aThreadList->spmode != 'soko') { $sorts['ita'] = '板'; }
if ($_conf['sb_show_spd']) { $sorts['spd'] = 'すばやさ'; }
if ($_conf['sb_show_ikioi']) { $sorts['ikioi'] = '勢い'; }
$sorts['bd'] = 'Birthday';
if ($_conf['sb_show_fav'] and $aThreadList->spmode != 'taborn') { $sorts['fav'] = '☆'; }

foreach ($sorts as $k => $v) {
	if ($GLOBALS['now_sort'] == $k) {
		//$sorts_ht[$k] = "<font color=\"{$STYLE['sb_now_sort_color']}\">{$v}</font>";
		$sorts_ht[$k] = $v;
	} else {
		$sorts_ht[$k] = "<a href=\"{$_conf['subject_php']}?sort={$k}{$sortq_spmode}{$sortq_host}{$sortq_ita}{$norefresh_q}{$_conf['k_at_a']}\">{$v}</a>";
	}
}
$htm['change_sort'] = 'ｿｰﾄ変更→' . implode(' ', $sorts_ht);
// }}}

// HTMLプリント==============================================
echo "<hr>";
echo $k_sb_navi_ht;
include './sb_toolbar_k.inc.php';
echo $allfav_ht;
echo "<p>";
echo $dat_soko_ht;
echo $taborn_link_ht;
echo $buildnewthread_ht;
echo "</p>";
echo '<p>'. $htm['change_sort'] . '</p>';
echo "<hr>";
echo "<p><a {$_conf['accesskey']}=\"0\" href=\"index.php{$_conf['k_at_q']}\">0.TOP</a></p>";

echo '</body></html>';

?>