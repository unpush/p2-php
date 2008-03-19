<?php
/*
	+live - リロード&スクロール コントロール ./live_frame.php より読み込まれる
*/

include_once './conf/conf.inc.php'; // 基本設定

if ($_GET['live'] && !$_GET['word']) {
	$load_control = "liveon()";
} else {
	$load_control = "liveoff()";
}

// HTMLプリント
P2Util::header_nocache();
P2Util::header_content_type();

if ($_conf['doctype']) { echo $_conf['doctype']; }

echo <<<LIVE
<html lang="ja">
	<head>
		<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
		<meta http-equiv="Content-Style-Type" content="text/css">
		<meta http-equiv="Content-Script-Type" content="text/javascript">
		<link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
		<link rel="stylesheet" href="css.php?css=post&amp;skin={$skin_en}" type="text/css">
		<script type="text/javascript">
		<!--
			function liveon() {
				document.getElementById("lon").innerHTML = "☆";
				document.getElementById("lof").innerHTML = "　";
			}
			function liveoff() {
				document.getElementById("lon").innerHTML = "　";
				document.getElementById("lof").innerHTML = "☆";
			}
		// -->
		</script>
	</head>
	<body onload="{$load_control}";>
		<div align="center">
			<form action="#">
				<p><span class="thre_title" id="lon"></span>&nbsp;<input type="button" value="live on" onclick="javascript:parent.liveread.startlive(); liveon();"></p>
				<p><span class="thre_title" id="lof"></span>&nbsp;<input type="button" value="live off" onclick="javascript:parent.liveread.stoplive(); liveoff();"></p>
			</form>
		</div>
	</body>
</html>
LIVE;

?>