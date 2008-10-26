<?php
// p2 - サブジェクト - フッタHTMLを表示する 携帯
// for subject.php

$mae_ht     = "";
$tugi_ht    = "";
$bbs_q      = "&amp;bbs=" . $aThreadList->bbs;

if (!empty($GLOBALS['wakati_words'])) {
    $word_at = "&amp;method=similar&amp;word=" . rawurlencode($GLOBALS['wakati_word']);
} elseif ($word) {
    $word_at = "&amp;word=$word";
} else {
    $word_at = "";
}

if ($aThreadList->spmode == "fav" && $sb_view == "shinchaku") {
	$allfav_ht = <<<EOP
	<p><a href="{$_conf['subject_php']}?spmode=fav{$norefresh_q}{$_conf['k_at_a']}">全てのお気にｽﾚを表示</a></p>
EOP;
} else {
    $allfav_ht = '';
}

// {{{ ページタイトル部分HTML設定

if ($aThreadList->spmode == "taborn") {
	$ptitle_ht = <<<EOP
	<a href="{$ptitle_url}" {$_conf['accesskey']}="{$_conf['k_accesskey']['up']}">{$_conf['k_accesskey']['up']}.<b>{$aThreadList->itaj}</b></a>（ｱﾎﾞﾝ中）
EOP;
} elseif ($aThreadList->spmode == "soko") {
	$ptitle_ht = <<<EOP
	<a  href="{$ptitle_url}" {$_conf['accesskey']}="{$_conf['k_accesskey']['up']}">{$_conf['k_accesskey']['up']}.<b>{$aThreadList->itaj}</b></a>（dat倉庫）
EOP;
} elseif (!empty($ptitle_url)) {
	$ptitle_ht = <<<EOP
	<a  href="{$ptitle_url}"><b>{$ptitle_hs}</b></a>
EOP;
} else {
	$ptitle_ht = <<<EOP
	<b>{$ptitle_hs}</b>
EOP;
}

// }}}
// {{{ ナビ HTML設定

$sb_view_at = "";
if (!empty($_REQUEST['sb_view'])) {
    $sb_view_at = "&amp;sb_view=" . htmlspecialchars($_REQUEST['sb_view']);
}

if ($disp_navi['from'] > 1) {
	$mae_ht = <<<EOP
<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;key={$key}&amp;spmode={$aThreadList->spmode}{$norefresh_q}&amp;from={$disp_navi['mae_from']}{$sb_view_at}{$word_at}{$_conf['k_at_a']}">前</a>
EOP;
}

if ($disp_navi['tugi_from'] <= $sb_disp_all_num) {
	$tugi_ht = <<<EOP
<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;key={$key}&amp;spmode={$aThreadList->spmode}{$norefresh_q}&amp;from={$disp_navi['tugi_from']}{$sb_view_at}{$word_at}{$_conf['k_at_a']}">次</a>
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
{$sb_range_st}{$mae_ht} {$tugi_ht}
EOP;
} else {
    $k_sb_navi_ht = '';
}

// }}}

// dat倉庫
// スペシャルモードでなければ、またはあぼーんリストなら
if (!$aThreadList->spmode or $aThreadList->spmode == "taborn") {
	$dat_soko_ht = <<<EOP
<a href="{$_conf['subject_php']}?host={$aThreadList->host}{$bbs_q}{$norefresh_q}&amp;spmode=soko{$_conf['k_at_a']}">dat倉庫</a>
EOP;
} else {
    $dat_soko_ht = '';
}

// あぼーん中のスレッド
if (!empty($ta_num)) {
	$taborn_link_ht = <<<EOP
	<a href="{$_conf['subject_php']}?host={$aThreadList->host}{$bbs_q}{$norefresh_q}&amp;spmode=taborn{$_conf['k_at_a']}">ｱﾎﾞﾝ中({$ta_num})</a> 
EOP;
} else {
    $taborn_link_ht = '';
}

// 新規スレッド作成
if (!$aThreadList->spmode and !P2Util::isHostKossoriEnq($aThreadList->host)) {
	$buildnewthread_ht = <<<EOP
	<a href="post_form_i.php?host={$aThreadList->host}{$bbs_q}&amp;newthread=1{$_conf['k_at_a']}">ｽﾚ立て</a>
EOP;
} else {
    $buildnewthread_ht = '';
}


// {{{ ソート変更 （新着 レス No. タイトル 板 すばやさ 勢い Birthday ☆）

$sorts = array('midoku' => '新着', 'res' => 'ﾚｽ', 'no' => 'No.', 'title' => 'ﾀｲﾄﾙ');
if ($aThreadList->spmode and $aThreadList->spmode != 'taborn' and $aThreadList->spmode != 'soko') {
    $sorts['ita'] = '板';
}
if ($_conf['sb_show_spd']) {
    $sorts['spd'] = 'すばやさ';
}
if ($_conf['sb_show_ikioi']) {
    $sorts['ikioi'] = '勢い';
}
$sorts['bd'] = 'Birthday';
if ($_conf['sb_show_fav'] and $aThreadList->spmode != 'taborn') {
    $sorts['fav'] = '☆';
}

$htm['change_sort'] = "<form method=\"get\" action=\"{$_conf['subject_php']}\">";
$htm['change_sort'] .= $_conf['k_input_ht'];
$htm['change_sort'] .= '<input type="hidden" name="norefresh" value="1">';
// spmode時
if ($aThreadList->spmode) {
    $htm['change_sort'] .= "<input type=\"hidden\" name=\"spmode\" value=\"{$aThreadList->spmode}\">";
}
// spmodeでない、または、spmodeがあぼーん or dat倉庫なら
if (!$aThreadList->spmode || $aThreadList->spmode == "taborn" || $aThreadList->spmode == "soko") {
    $htm['change_sort'] .= "<input type=\"hidden\" name=\"host\" value=\"{$aThreadList->host}\">";
    $htm['change_sort'] .= "<input type=\"hidden\" name=\"bbs\" value=\"{$aThreadList->bbs}\">";
}

if (!empty($_REQUEST['sb_view'])) {
    $htm['change_sort'] .= "<input type=\"hidden\" name=\"sb_view\" value=\"" . htmlspecialchars($_REQUEST['sb_view']) . "\">";
}

$htm['change_sort'] .= 'ソート:<select name="sort">';
foreach ($sorts as $k => $v) {
    if ($GLOBALS['now_sort'] == $k) {
        $selected = ' selected';
    } else {
        $selected = '';
    }
    $htm['change_sort'] .= "<option value=\"{$k}\"{$selected}>{$v}</option>";
}
$htm['change_sort'] .= '</select>';
$htm['change_sort'] .= '<input type="submit" value="変更"></form>';

// }}}

// {{{ HTMLプリント

/*
echo "<hr>";
echo $k_sb_navi_ht;
include P2_LIB_DIR . '/sb_toolbar_k.inc.php';
echo $allfav_ht;
echo "<p>";
echo $dat_soko_ht;
echo $taborn_link_ht;
echo $buildnewthread_ht;
echo "</p>";
echo '<p>'. $htm['change_sort'] . '</p>';
//echo "<hr>";
echo "<p><a {$_conf['accesskey']}=\"0\" href=\"index.php{$_conf['k_at_q']}\">TOP</a></p>";
echo '</body></html>';
*/
/*
$k_sb_navi_ht は下の３と同じ
{$sb_range_st}
*/
$foot_sure = ""; 
if($dat_soko_ht)$foot_sure .= "<span class=\"soko\">{$dat_soko_ht}</span>";
if($buildnewthread_ht) $foot_sure .= "<span class=\"build\">{$buildnewthread_ht}</span>";
if($allfav_ht) $foot_sure .= "<span class=\"all\">{$allfav_ht}</span>";
if($taborn_link_ht)$foot_sure .= "<span class=\"abon\">{$taborn_link_ht}</span>";
if($mae_ht)$foot_sure .= "<span class=\"mae\">{$mae_ht}</span>";
if($tugi_ht)$foot_sure .= "<span class=\"tugi\">{$tugi_ht}</span>";

echo <<<IUI
{$htm['change_sort']} 
<div id="foot">
  <div class="foot_sure">
    {$foot_sure}
  </div>
</div>
<p><a id="backButton"class="button" href="iphone.php">TOP</a></p>
</body></html>
IUI;

// }}}
