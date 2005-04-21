<?php
// p2 -  サブジェクト - 携帯ヘッダ表示
// for subject.php

require_once './p2util.class.php';

//===============================================================
// HTML表示用変数
//===============================================================
$newtime = date("gis");
$norefresh_q = "&amp;norefresh=1";

// ページタイトル部分URL設定 ====================================
if ($aThreadList->spmode == "taborn" or $aThreadList->spmode == "soko") {
	$ptitle_url = "{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}{$_conf['k_at_a']}";
} elseif ($aThreadList->spmode == "res_hist") {
	$ptitle_url = "./read_res_hist.php{$_conf['k_at_q']}#footer";
} elseif (!$aThreadList->spmode) {
	$ptitle_url = "http://{$aThreadList->host}/{$aThreadList->bbs}/i/";
	if (preg_match("/www\.onpuch\.jp/", $aThreadList->host)) {$ptitle_url = $ptitle_url."index2.html";}
	if (preg_match("/livesoccer\.net/", $aThreadList->host)) {$ptitle_url = $ptitle_url."index2.html";}
	//match登録よりheadなげて聞いたほうがよさそうだが、ワンレスポンス増えるのが困る
}

// ページタイトル部分HTML設定 ====================================
$ptitle_hd = htmlspecialchars($aThreadList->ptitle);

if ($aThreadList->spmode == "taborn") {
	$ptitle_ht = <<<EOP
	<a href="{$ptitle_url}"><b>{$aThreadList->itaj_hd}</b></a>（ｱﾎﾞﾝ中）
EOP;
} elseif ($aThreadList->spmode == "soko") {
	$ptitle_ht = <<<EOP
	<a  href="{$ptitle_url}"><b>{$aThreadList->itaj_hd}</b></a>（dat倉庫）
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

// フォーム ==================================================
$sb_form_hidden_ht = <<<EOP
	<input type="hidden" name="detect_hint" value="◎◇">
	<input type="hidden" name="bbs" value="{$aThreadList->bbs}">
	<input type="hidden" name="host" value="{$aThreadList->host}">
	<input type="hidden" name="spmode" value="{$aThreadList->spmode}">
	{$_conf['k_input_ht']}
EOP;

// フィルタ検索 ==================================================
if (!$aThreadList->spmode) {
	$filter_form_ht = <<<EOP
<form method="GET" action="subject.php" accept-charset="{$_conf['accept_charset']}">
	{$sb_form_hidden_ht}
	<input type="text" id="word" name="word" value="{$word}" size="12">
	<input type="submit" name="submit_kensaku" value="検索">
</form>\n
EOP;
}

// 検索結果 ==
if ($mikke) {
	$hit_ht="<div>\"{$word}\" {$mikke}hit!</div>";
}


//=================================================
//ヘッダプリント
//=================================================
P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html>
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<title>{$ptitle_hd}</title>
</head>
<body>
EOP;

echo $_info_msg_ht;
$_info_msg_ht = "";

include './sb_toolbar_k.inc.php';

echo $filter_form_ht;
echo $hit_ht;
echo "<hr>";
?>
