<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 ースタイル設定
// for editfavita.php

$stylesheet .= <<<EOSTYLE

body, td{
	line-height:120%;
	background:{$STYLE['menu_bgcolor']} {$STYLE['menu_background']};
	font-size:{$STYLE['menu_fontsize']};
}

a:link.te{color:#999;} /* 並び替え */
a:visited.te{color:#999;}
a:hover.te{color:{$STYLE['menu_ita_color_h']};}

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
