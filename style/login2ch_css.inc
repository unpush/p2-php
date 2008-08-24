<?php
/**
 * rep2 - スタイル設定
 * for login2ch.php
 */

echo <<<EOSTYLE

body, td{
	line-height:120%;
}

p#pan_menu{
	border-bottom: solid 1px #ccc;
}

div#login_status{
	padding:6px 12px 6px 12px;
}

form#login_with_id{
	padding:12px;
	border:solid 1px #ccc;
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
