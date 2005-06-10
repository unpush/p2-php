<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 ースタイル設定
// for editpref.php

$stylesheet .= <<<EOSTYLE

p#pan_men u{
	border-bottom: solid 1px #ccc;
}

table#editpref {
	
}

table#editpref td {
	text-align: center;
	vertical-align: top;
}

fieldset {
	text-align: center;
	padding: 6px;
	border :solid 1px {$STYLE['respop_b_color']};
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
