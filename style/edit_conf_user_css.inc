<?php
// p2 ースタイル設定
// for edit_conf_user.php

$ss = <<<EOSTYLE
<style type="text/css" media="all">
<!--

p#pan_menu{
	border-bottom: solid 1px #ccc;
}

table.edit_conf_user, table.edit_font, fieldset{
	background-color: #fff;
	border: solid 1px #ccc;
}

table.edit_conf_user th, table.edit_conf_user td, table.edit_font th, table.edit_font td {
	font-size: {$STYLE['infowin_fontsize']};
	padding: 4px;
	margin: 4px;
	border: solid 0px;
	border-right: solid 1px #efefef;
	border-bottom: solid 1px #efefef;
}

tr.group{
	background-color: #efefef;
}

ul.tabbernav {
	font-size: {$STYLE['fontsize']};
}

-->
</style>
EOSTYLE;

echo $ss;

// スタイルの上書き
if (isset($MYSTYLE) && is_array($MYSTYLE)) {
    include_once (P2_STYLE_DIR . '/mystyle_css.php');
    $_mystylename = str_replace('_css.inc', '', basename(__FILE__));
    if (isset($MYSTYLE[$_mystylename])) {
        disp_mystyle($_mystylename);
    }
}

?>