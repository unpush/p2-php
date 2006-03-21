<?php
// p2 ースタイル設定
// for post.php 情報ウィンドウ
// vim: set fileencoding=cp932 ai et ts=4 sw=0 sts=0 fdm=marker:
// mi: charset=Shift_JIS

if($STYLE['a_underline_none'] == 2){
	$thre_title_underline_css = "a.thre_title{text-decoration:none;}";
}

echo <<<EOSTYLE

.thre_title{color:{$STYLE['read_thread_title_color']};}
{$thre_title_underline_css}

#original_msg {
	margin:0.5em;
	padding:0.5em;
	line-height:120%;
	font-size:{$STYLE['respop_fontsize']};
	color:{$STYLE['respop_color']};
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
