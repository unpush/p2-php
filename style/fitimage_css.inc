<?php
/**
 * rep2 - ƒXƒ^ƒCƒ‹Ý’è
 * for ImageCache2:fitimage.php
 */

if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
	$button_position = 'absolute';
} else {
	$button_position = 'fixed';
}

echo <<<EOSTYLE

body {
	margin: 0;
	padding: 0;
}

div#pct {
}

div#pct img {
	cursor: pointer;
}

div#btn {
	display: none;
	position: {$button_position};
	top: 0;
	left: 0;
	margin: 0;
	padding: 2px;
	font-size: xx-small;
	line-height: 100%;
	background-color: #ffffff;
	border-top: none;
	border-left: none;
	border-right: #000000 solid 1px;
	border-bottom: #000000 solid 1px;
}

div#btn img {
	margin: 1px;
	cursor: pointer;
}

EOSTYLE;

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
