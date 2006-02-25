<?php
// p2 ースタイル設定
// for 書き込みプレビュー

if($STYLE['a_underline_none'] == 2){
	$thre_title_underline_css = 'a.thre_title{text-decoration:none;}';
}

echo <<<EOSTYLE

<style type="text/css" media="all">
<!--

#dpreview {
	display:none;
	margin:0.5em;
	padding:0.5em;
	line-height:130%;
	font-size:{$STYLE['read_fontsize']};
	color:{$STYLE['read_color']};
}

#dpreview legend {
	padding:0px;
	line-height:100%;
	font-size:{$STYLE['fontsize']};
	color:{$STYLE['textcolor']};
}

.prvw_resnum {
	color:{$STYLE['read_newres_color']};
	text-decoration:none;
}

.prvw_name {
	color:{$STYLE['read_name_color']};
}

.prvw_mail {
	color:{$STYLE['read_mail_color']};
}

.prvw_msg {
	margin-left:2em;
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
