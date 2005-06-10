<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 ースタイル設定
// for 画像キャッシュビューワー

if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
	$button_position = 'absolute';
} else {
	$button_position = 'fixed';
}

$stylesheet .= <<<EOSTYLE

body {
	margin: 0px;
	padding: 0px;
}

div#pct {
}

div#pct img {
	cursor: pointer;
}

div#btn {
	display: none;
	position: {$button_position};
	top: 2px;
	left: 2px;
}

div#btn img {
	margin: 1px;
	cursor: pointer;
}

EOSTYLE;

// スタイルの上書き
if (isset($MYSTYLE) && is_array($MYSTYLE)) {
	include_once (P2_STYLE_DIR . '/mystyle_css.php');
	$stylename = str_replace('_css.php', '', basename(__FILE__));
	if (isset($MYSTYLE[$stylename])) {
		$stylesheet .= get_mystyle($stylename);
	}
}

?>
