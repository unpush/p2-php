<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 ースタイル設定
// for 看板ポップアップ

$background_color = '';
$background_image = '';

if (!empty($_GET['bgcolor'])) {
	$background_color = 'background-color: '.$_GET['bgcolor'].';';
}
if (!empty($_GET['bgimage'])) {
	$background_image = 'background-image: url("'.$_GET['bgimage'].'");';
}

$stylesheet .= <<<EOSTYLE

div#kanbanImage {
	position: absolute;
	visibility: hidden;
	margin: 0px;
	border: 1px #000000 solid;
	padding: 0px;
	{$background_color}
	{$background_image}
}

div#titleImage {
	position: absolute;
	visibility: hidden;
	margin: 0px;
	border: 1px #000000 solid;
	padding: 10px;
	background-color: #ffffff;
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
