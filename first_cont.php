<?php
// p2 -  スレッド表示部分の初期表示
// フレーム3分割画面、右下部分

include_once './conf/conf.inc.php';  // 基本設定ファイル読込
require_once './p2util.class.php';

P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html lang="ja">
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<title>p2</title>
EOP;

@include("./style/style_css.inc"); // 基本スタイルシート 読込

echo <<<EOP
</head>
<body>
<br>
<div class="container">
	<h1><img src="img/p2.gif" alt="p2" width="98" height="86"></h1>
</div>
</body>
</html>
EOP;

?>
