<?php
/*
	+live - オートスクロール&オートリロード ./live_header.inc.php より読み込まれる
*/

echo <<<xmht
<script type="text/javascript">
<!--

// XMLHttpRequest
function getIndex(getFile) {
	xmlhttp = new XMLHttpRequest();
	if (xmlhttp) {
		xmlhttp.onreadystatechange = check;
		xmlhttp.open('GET', getFile, true);
		xmlhttp.send(null);
	}
}

function check() {
	if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
		document.getElementById("live_view").innerHTML = xmlhttp.responseText;
	}
}

// オートスクロール
var speed = {$_conf['live.scroll_speed']}; // 速度（max 1）
var move = {$_conf['live.scroll_move']}; // 滑らかさとon-off（max 1）

var ascr;

function ascroll() {
	window.scrollBy(0, move); // スクロール処理
	ascr = setTimeout("ascroll()", speed);
}

// オートリロード
var arel;

function areload() {
	arel = setInterval("getIndex('./read.php?host={$aThread->host}&bbs={$aThread->bbs}&key={$aThread->key}&live=1')", {$reload_time});
}

// 開始
function startlive() {
	if (ascr) clearTimeout(ascr);
	if (arel) clearInterval(arel);
	getIndex('./read.php?host={$aThread->host}&bbs={$aThread->bbs}&key={$aThread->key}&live=1');
	ascroll();
	areload();
}

// 停止
function stoplive() {
	if (ascr) clearTimeout(ascr);
	if (arel) clearInterval(arel);
}

// -->
</script>\n
xmht;

?>