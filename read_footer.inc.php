<?php
/*
	p2 -  スレッド表示 -  フッタ部分 -  for read.php
*/

require_once './p2util.class.php';	// p2用のユーティリティクラス
require_once './dataphp.class.php';

//=====================================================================
// ■フッタ
//=====================================================================

if ($_conf['bottom_res_form']) {
	
	$fake_time = -10; // time を10分前に偽装
	$time = time() - 9*60*60;
	$time = $time + $fake_time * 60;

	$submit_value = "書き込む";
	
	// ■ key.idxから名前とメールを読込み
	if (file_exists($aThread->keyidx)) {
		unset($lines);
		if ($lines = @file($aThread->keyidx)) {
			$line = explode('<>', rtrim($lines[0]));
			$line = array_map(create_function('$n', 'return htmlspecialchars($n, ENT_QUOTES);'), $line);
			$htm['FROM'] = $line[7];
			$htm['mail'] = $line[8];
		}
	}
	
	// 前回のPOST失敗があれば
	$failed_post_file = P2Util::getFailedPostFilePath($aThread->host, $aThread->bbs, $aThread->key);
	if ($cont_srd = DataPhp::getDataPhpCont($failed_post_file)) {
		$last_posted = unserialize($cont_srd);
		$last_posted = array_map('htmlspecialchars', $last_posted);

		$htm['FROM'] = $last_posted['FROM'];
		$htm['mail'] = $last_posted['mail'];
		$htm['MESSAGE'] = $last_posted['MESSAGE'];	

	}
	$onmouse_showform_ht = <<<EOP
 onMouseover="document.getElementById('kakiko').style.display = 'block';"
EOP;

	$ttitle_ht = <<<EOP
<p><b class="thre_title">{$aThread->ttitle}</b></p>
EOP;


	// 2chで●ログイン中なら
	if (P2Util::isHost2chs($aThread->host) and file_exists($_conf['sid2ch_php'])) {
		$isMaruChar = "●";
	} else {
		$isMaruChar = "";
	}

	// Be.2ch
	if (P2Util::isHost2chs($host) and $_conf['be_2ch_code'] && $_conf['be_2ch_mail']) {
		$htm['be2ch'] = '<input type="checkbox" id="post_be2ch" name="post_be2ch" value="1"><label for="post_be2ch">Be.2chのコードを送信</label><br>'."\n";
	}
		
	$res_form_ht = <<<EOP
<div id="kakiko">
{$ttitle_ht}
<form id="resform" method="POST" action="./post.php" accept-charset="{$_conf['accept_charset']}">
	<input type="hidden" name="detect_hint" value="◎◇">
	 {$isMaruChar}名前： <input name="FROM" type="text" value="{$htm['FROM']}" size="19"> 
	 E-mail : <input id="mail" name="mail" type="text" value="{$htm['mail']}" size="19" onChange="checkSage();">
	<input id="sage" type="checkbox" onClick="mailSage();"><label for="sage">sage</label>{$options_ht}<br>
	<textarea id="MESSAGE" rows="{$STYLE['post_msg_rows']}" cols="{$STYLE['post_msg_cols']}" wrap="off" name="MESSAGE">{$htm['MESSAGE']}</textarea>	
	<input type="submit" name="submit" value="{$submit_value}"><br>
	{$htm['be2ch']}
	
	<input type="hidden" name="bbs" value="{$aThread->bbs}">
	<input type="hidden" name="key" value="{$aThread->key}">
	<input type="hidden" name="time" value="{$time}">
	
	<input type="hidden" name="host" value="{$aThread->host}">
	<input type="hidden" name="rescount" value="{$aThread->rescount}">
	<input type="hidden" name="ttitle_en" value="{$ttitle_en}">
</form>
</div>
EOP;
}

// ============================================================
$sid_q = (defined('SID')) ? '&amp;'.strip_tags(SID) : '';

if ($aThread->rescount or ($_GET['one'] && !$aThread->diedat)) { // and (!$_GET['renzokupop'])

	if (!$aThread->diedat) {
		$dores_ht = <<<EOP
	  | <a href="post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rc={$aThread->rescount}{$ttitle_en_q}" target='_self' onClick="return OpenSubWin('post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rc={$aThread->rescount}{$ttitle_en_q}&amp;popup=1',{$STYLE['post_pop_size']},0,0)"{$onmouse_showform_ht}>{$dores_st}</a>
EOP;
		$res_form_ht_pb = $res_form_ht;
	}
	if ($res1['body']) {
		$q_ichi = $res1['body']." | ";
	}
	
	// レスのすばやさ
	$spd_ht = "";
	if ($spd_st = $aThread->getTimePerRes() and $spd_st != "-") {
		$spd_ht = '<span class="spd" title="すばやさ＝時間/レス">'."" . $spd_st."".'</span>';
	}
	
	// {{{ フィルタヒットがあった場合、次Xと続きを読むを更新
	/*
	//if (!$read_navi_next_isInvisible) {
	$read_navi_next = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$after_rnum}{$offline_range_q}&amp;nt={$newtime}{$read_navi_next_anchor}\">{$next_st}{$rnum_range}</a>";
	//}
	
	$read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$offline_q}\" accesskey=\"r\">{$tuduki_st}</a>";
	*/

	if (!empty($GLOBALS['last_hit_resnum'])) {
		$read_navi_next_anchor = "";
		if ($GLOBALS['last_hit_resnum'] == $aThread->rescount) {
			$read_navi_next_anchor = "#r{$aThread->rescount}";
		}
		$after_rnum = $GLOBALS['last_hit_resnum'] + $rnum_range;
		$read_navi_next = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$GLOBALS['last_hit_resnum']}-{$after_rnum}{$offline_range_q}&amp;nt={$newtime}{$read_navi_next_anchor}\">{$next_st}{$rnum_range}</a>";

		// 「続きを読む」
		$read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$GLOBALS['last_hit_resnum']}-{$offline_q}\" accesskey=\"r\">{$tuduki_st}</a>";
	}
	// }}}

	// ■プリント
	echo <<<EOP
<hr>
<table id="footer" width="100%" style="padding:0px 10px 0px 0px;">
	<tr>
		<td align="left">
			{$q_ichi}
			<a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=all">{$all_st}</a> 
			{$read_navi_previous} 
			{$read_navi_next} 
			<a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=l{$latest_show_res_num}">{$latest_st}{$latest_show_res_num}</a> 
			| {$read_footer_navi_new} 
			{$dores_ht}
			{$spd_ht}
		</td>
		<td align="right">
			{$htm['p2frame']}
			{$toolbar_right_ht}
		</td>
		<td align="right">
			<a href="#header">▲</a>
		</td>
	</tr>
</table>
{$res_form_ht_pb}
EOP;

	if ($diedat_msg) {
		echo "<hr>";
		echo $diedat_msg;
		echo "<p>";
		echo  $motothre_ht;
		echo "</p>";
	}
}

// ====
echo '
</body>
</html>
';

?>