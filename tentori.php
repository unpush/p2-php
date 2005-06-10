<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

require_once 'conf/conf.php';

authorize();

if (empty($_GET['key']) || empty($_GET['resnum'])) {
	$seed = 0;
} else {
	$seed = (int)((float)$_GET['key'] / (float)$_GET['resnum']);
}

$fortune = tentori($seed);

//=====================================================
// tentori
// ランダムにデータファイルから１行取り出す関数
//=====================================================
function tentori($seed = 0)
{
	$fortunes = @file('conf/fortune.txt');
	if (!$fortunes) {
		return 'p2 error: 点取り占いデータファイルを開けませんでした。';
	}

	if ($seed) {
		mt_srand($seed);
	} else {
		mt_srand();
	}
	$i = mt_rand(1, count($fortunes));

	$fortune = rtrim($fortunes[$i]);
	$result = preg_match('/<\d+>/', $fortune, $matches);

	return $fortune;
}

//=====================================================
// HTMLプリント
//=====================================================
P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
?>
<html lang="ja">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<title>点取り占い</title>
	<link rel="stylesheet" href="css.php?css=style&amp;skin=<?php echo $skin_en; ?>" type="text/css">
	<link rel="stylesheet" href="css.php?css=read&amp;skin=<?php echo $skin_en; ?>" type="text/css">
	<script type="text/javascript" src="js/closetimer.js"></script>
</head>
<body style="text-align:center" onload="startTimer(document.getElementById('timerbutton'))">
<hr>
<h3 class="thre_title"><?php echo $fortune; ?></h3>
<hr>
<div><input id="timerbutton" type="button" value="Close Timer" onclick="stopTimer(document.getElementById('timerbutton'))"></div>
</body>
</html>
