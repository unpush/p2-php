<?php
/*
	+live - 実況用スレッド表示 B-Type ../showthreadpc.class.php より読み込まれる
*/

// オートリロードの板で新着レス先頭に目印ラインを挿入
$live_newline = "<table id=\"r{$i}\" cellspacing=\"2\" cellpadding=\"0\" style=\"border-top: {$STYLE['live_b_n']}; {$highlight_res}\" width=\"100%\"><tr>";
$live_oldline = "<table id=\"r{$i}\" cellspacing=\"2\" cellpadding=\"0\" style=\"border-top: {$STYLE['live_b_l']}; {$highlight_res}\" width=\"100%\"><tr>";
// 
$live_td = "<td colspan=\"2\" style=\"color:{$STYLE['read_color']}; font-size:{$STYLE['live_font-size']}; background-color:{$STYLE['live2_color']};\" valign=\"top\">";
// 新着レスの番号色
$live_newnum = "<span class=\"spmSW\"{$spmeh}><b style=\"color:{$STYLE['read_newres_color']};\">{$i}</b></span>：";
$live_oldnum = "<span class=\"spmSW\"{$spmeh}>{$i}</span>：";

// テーブル開始 ～ 番号
if ($this->thread->onthefly) {
	// 番号 (オンザフライ)
	$GLOBALS['newres_to_show_flag'] = true;
	$tores .= "{$live_oldline}{$live_td}<span class=\"ontheflyresorder spmSW\"{$spmeh}>{$i}</span>：";
} elseif ($i == 1) {
	// 番号 (1)
	if ($this->thread->readnum > 1) {
		$tores .= "{$live_oldline}{$live_td}{$live_oldnum}";
	} else {
		$tores .= "{$live_oldline}{$live_td}{$live_newnum}";
	}
} elseif ($i == $this->thread->readnum +1) {
	// 番号 (先頭新着レス)
	$GLOBALS['newres_to_show_flag'] = true;
	if ($nldr_ylr_d) {
		$tores .= "{$live_newline}{$live_td}{$live_newnum}";
	} else {
		$tores .= "{$live_oldline}{$live_td}{$live_newnum}";
	}
} elseif ($i > $this->thread->readnum) {
	// 番号 (後続新着レス)
	$tores .= "{$live_oldline}{$live_td}{$live_newnum}";
} elseif ($_conf['expack.spm.enabled']) {
	// 番号 (SPM)
	$tores .= "{$live_oldline}{$live_td}{$live_oldnum}";
} else {
	// 番号
	$tores .= "{$live_oldline}{$live_td}{$i}：";
}

// 名前
$tores .= "<span class=\"name\"><b>{$name}</b></span>：";

// メール
$tores .= "{$mail}：";

// 日付とID
$tores .= "{$date_id}";

if ($this->am_side_of_id) {
	$tores .= ' ' . $this->activeMona->getMona($res_id);
}

// 仕切 & レスボタン & 被参照レスポップアップ
$tores .= "</td></tr><tr><td width=\"10%\" align=\"center\">&nbsp;{$ref_res_pp}{$res_button}</td>";

// 内容
$tores .= "<td {$res_dblclc} width=\"90%\" id=\"{$res_id}\"{$automona_class} style=\"color:{$STYLE['read_color']}; font-size: {$STYLE['read_fontsize']};\">{$msg}　</td>";

// テーブル終了
$tores .= "</tr></table>\n";

// レスポップアップ用引用
$tores .= $rpop;

?>