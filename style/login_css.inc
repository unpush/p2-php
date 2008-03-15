<?php
// p2 ースタイル設定
// for login.php
// vim: set fileencoding=cp932 ai et ts=4 sw=0 sts=0 fdm=marker:
// mi: charset=Shift_JIS

echo <<<EOSTYLE

p#pan_menu{
	border-bottom: solid 1px #ccc;
}

p#login_status{
	padding:8px 12px 8px 12px;
}

form#login_change{
	padding:12px;
	border:solid 1px #ccc;
}

EOSTYLE;

// スタイルの上書き
if (isset($MYSTYLE) && is_array($MYSTYLE)) {
	include_once (P2_STYLE_DIR . '/mystyle_css.php');
	$_mystylename = str_replace('_css.inc', '', basename(__FILE__));
	if (isset($MYSTYLE[$_mystylename])) {
		disp_mystyle($_mystylename);
	}
}

?>
