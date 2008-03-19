<?php
/*
	+live - ハイライトワードに関する共通処理 ../showthreadpc.class.php より読み込まれる
*/

$isHighlightChain = false;
$isHighlightName = false;
$isHighlightMail = false;
$isHighlightId = false;
$isHighlightMsg = false;
//$highlight_chain_info = array();
//$highlight_msg_info = array();

// 連鎖ハイライト
if ($_conf['live.highlight_chain'] && preg_match_all('/(?:&gt;|＞)([1-9][0-9\\-,]*)/', $msg, $matches)) {
	$highlight_chain_nums = array_unique(array_map('intval', split('[-,]+', trim(implode(',', $matches[1]), '-,'))));
	if (array_intersect($highlight_chain_nums, $this->highlight_nums)) {
//		$a_highlight_chain_num = array_shift($highlight_chain_nums);
		$ngaborns_hits['highlight_chain']++;
		$ngaborns_body_hits++;
		$this->highlight_nums[] = $i;
		$isHighlightChain = true;
//		$highlight_chain_info[] = sprintf('&gt;&gt;%d', $a_highlight_chain_num);
	}
}

// ハイライトネームチェック
if ($this->ngAbornCheck('highlight_name', strip_tags($name)) !== false) {
	$ngaborns_hits['highlight_name']++;
	$ngaborns_head_hits++;
	$this->highlight_nums[] = $i;
	$isHighlightName = true;
}

// ハイライトメールチェック
if ($this->ngAbornCheck('highlight_mail', $mail) !== false) {
	$ngaborns_hits['highlight_mail']++;
	$ngaborns_head_hits++;
	$this->highlight_nums[] = $i;
	$isHighlightMail = true;
}

// ハイライトIDチェック
if ($this->ngAbornCheck('highlight_id', $date_id) !== false) {
	$ngaborns_hits['highlight_id']++;
	$ngaborns_head_hits++;
	$this->highlight_nums[] = $i;
	$isHighlightId = true;
}

// ハイライトメッセージチェック
$a_highlight_msg = $this->ngAbornCheck('highlight_msg', $msg);
if ($a_highlight_msg !== false) {
	$ngaborns_hits['highlight_msg']++;
	$ngaborns_body_hits++;
	$this->highlight_nums[] = $i;
	$isHighlightMsg = true;
//	$highlight_msg_info[] = sprintf('%s', htmlspecialchars($a_highlight_msg, ENT_QUOTES));
	if (!preg_match("(^<regex(>|:i>).+$)", $a_highlight_msg)) {
		if (preg_match("(^<i>.+$)", $a_highlight_msg)) {
			$a_highlight_msg = preg_replace("(^<i>)", "", $a_highlight_msg);
			// preg_quote()で2バイト目が0x5B("[")の"ー"なども変換されてしまうので
			// UTF-8にしてから正規表現の特殊文字をエスケープ
			$a_highlight_msg = mb_convert_encoding($a_highlight_msg, 'UTF-8', 'SJIS-win');
			$a_highlight_msg = preg_quote($a_highlight_msg);
			$a_highlight_msg = mb_convert_encoding($a_highlight_msg, 'SJIS-win', 'UTF-8');
			$a_highlight_msg = "<i>" . $a_highlight_msg;
		} else {
			// 上に同じ
			$a_highlight_msg = mb_convert_encoding($a_highlight_msg, 'UTF-8', 'SJIS-win');
			$a_highlight_msg = preg_quote($a_highlight_msg);
			$a_highlight_msg = mb_convert_encoding($a_highlight_msg, 'SJIS-win', 'UTF-8');
		}
	}
	$this->highlight_msgs[] = $a_highlight_msg;
	$highlight_msgs = array_unique($this->highlight_msgs);
}

?>