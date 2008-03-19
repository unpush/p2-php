<?php
/*
	+live - デフォルトスレッド表示 ../showthreadpc.class.php より読み込まれる
*/

// 番号
$tores .= "<dt id=\"r{$i}\" style=\"{$highlight_res}\">";

if ($this->thread->onthefly) {
	$GLOBALS['newres_to_show_flag'] = true;
	//番号 (オンザフライ時)
	$tores .= "<span class=\"ontheflyresorder spmSW\"{$spmeh}>{$i}</span> ：";
} elseif ($i > $this->thread->readnum) {
	$GLOBALS['newres_to_show_flag'] = true;
	// 番号 (新着レス時)
	$tores .= "<font color=\"{$STYLE['read_newres_color']}\" class=\"spmSW\"{$spmeh}>{$i}</font> ：";
} elseif ($_conf['expack.spm.enabled']) {
	// 番号 (SPM)
	$tores .= "<span class=\"spmSW\"{$spmeh}>{$i}</span> ：";
} else {
	// 番号
	$tores .= "{$i} ：";
}

// 被参照レスポップアップ
$tores .= "$ref_res_pp";

// レスボタン
$tores .= "$res_button";

// 名前
$tores .= "&nbsp;<span class=\"name\"><b>{$name}</b></span>：";

// メール
$tores .= "{$mail} ：";

// 日付とID
$tores .= $date_id;
if ($this->am_side_of_id) {
	$tores .= ' ' . $this->activeMona->getMona($res_id);
}

$tores .= "</dt>";

// 内容
$tores .= "<dd {$res_dblclc} id=\"{$res_id}\"{$automona_class} style=\"{$highlight_res}\">{$msg}<br><br></dd>\n";

// レスポップアップ用引用
$tores .= $rpop;

?>