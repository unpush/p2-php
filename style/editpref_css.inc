<?php
// p2 ースタイル設定
// for editpref.php


echo <<<EOSTYLE

<style type="text/css" media="all">
<!--

	p#pan_menu{
		border-bottom: solid 1px #ccc;
	}

	table#editpref td {
		text-align: left;
		vertical-align: top;
	}

	fieldset{
		padding:6px;
		border:solid 1px #ccc;
	}

	fieldset table{
		margin-left:auto;
		margin-right:auto;
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
