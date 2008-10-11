<?php
/**
 * rep2 - スタイル設定
 * for login_first
 */

echo <<<EOSTYLE

body{
	background-image:url('img/rep2_loginbg.gif');
	background-repeat: no-repeat;
	padding:24px;
	color:#333;
	background-color:#ccc;
}

p.infomsg{
	color:#369;
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
