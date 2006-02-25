<?php
// p2 ースタイル設定
// for login_first

echo <<<EOSTYLE

<style type="text/css" media="all">
<!--
	
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
	
-->
</style>

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
