<?php
/*
	+live - ハイライトワードの変換 ../showthreadpc.class.php より読み込まれる
*/

$live_highlight_style = "background-color: {$STYLE['live_highlight']}; font-weight: {$STYLE['live_highlight_word_weight']}; border-bottom: {$STYLE['live_highlight_word_border']};";
$live_highlight_chain_style = "background-color: {$STYLE['live_highlight_chain']}; font-weight: {$STYLE['live_highlight_word_weight']}; border-bottom: {$STYLE['live_highlight_word_border']};";

// 連鎖ハイライト変換
if ($isHighlightChain) {
	$highlight_chain_nums = implode('|', (array_intersect($highlight_chain_nums, $this->highlight_nums)));
	$highlight_chain_nums = "(" . $highlight_chain_nums . ")(?![^<]*>)"; // HTML要素にマッチさせない
	$msg = preg_replace("((?:&gt;|＞)+($highlight_chain_nums))", "<span style=\"{$live_highlight_chain_style}\">&gt;&gt;$1</span>", $msg);
}

// ハイライトネーム変換
if ($isHighlightName && !$isNgName) {
	$name = preg_replace("(<b>|</b>)", "", $name);
	$name = "</b><span style=\"{$live_highlight_style}\">$name</span><b>";
}

// ハイライトメール変換
if ($isHighlightMail && !$isNgMail) {
	$mail = "<span style=\"{$live_highlight_style}\">$mail</span>";
}

// ハイライトID変換
if ($isHighlightId && !$isNgId) {
	$date_id = preg_replace("((ID:))", "<span style=\"{$live_highlight_style}\">$1", $date_id ."</span>");
}

// ハイライトメッセージ変換
if ($isHighlightMsg && !$isNgMsg) {
	$highlight_msgs = implode('|', ($highlight_msgs));
	$highlight_msgs = "(" . $highlight_msgs . ")(?![^<]*>)"; // HTML要素にマッチさせない
	if (preg_match("(<(regex:i|i)>)", $highlight_msgs)) {
		$highlight_msgs = preg_replace("(<(regex|regex:i|i)>)", "", $highlight_msgs);
		$msg = mb_eregi_replace("($highlight_msgs)", "<span style=\"{$live_highlight_style}\">\\1</span>", $msg);
	} else {
		$highlight_msgs = preg_replace("(<(regex|regex:i|i)>)", "", $highlight_msgs);
		$msg = mb_ereg_replace("($highlight_msgs)", "<span style=\"{$live_highlight_style}\">\\1</span>", $msg);
	}
}

?>