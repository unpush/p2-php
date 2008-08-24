<?php
/**
 * rep2 - スタイル設定
 * for ImageCache2:fitimage.php
 */

if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
	$button_position = 'absolute';
} else {
	$button_position = 'fixed';
}

echo <<<EOSTYLE

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

div#fi_stars {
    display: inline;
    display: inline-block;
}

EOSTYLE;

// スタイルの上書き
if (isset($MYSTYLE) && is_array($MYSTYLE)) {
	require_once P2_STYLE_DIR . '/mystyle_css.php';
	$_mystylename = str_replace('_css.inc', '', basename(__FILE__));
	if (isset($MYSTYLE[$_mystylename])) {
		disp_mystyle($_mystylename);
	}
}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
