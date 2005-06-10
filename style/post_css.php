<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 ースタイル設定
// for post.php 情報ウィンドウ

if($STYLE['a_underline_none'] == 2){
	$thre_title_underline_css = 'a.thre_title{text-decoration:none;}';
}

$stylesheet .= <<<EOP

.thre_title{
	color:{$STYLE['read_thread_title_color']};
}
{$thre_title_underline_css}

#original_msg {
	margin:0.5em;
	padding:0.5em;
	line-height:120%;
	font-size:{$STYLE['respop_fontsize']};
	color:{$STYLE['respop_color']};
}

#dpreview {
	display:none;
	margin:0.5em;
	padding:0.5em;
	line-height:130%;
	font-size:{$STYLE['read_fontsize']};
	color:{$STYLE['read_color']};
}

#original_msg legend, #dpreview legend {
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

EOP;

// スタイルの上書き
if (isset($MYSTYLE) && is_array($MYSTYLE)) {
	include_once (P2_STYLE_DIR . '/mystyle_css.php');
	$stylename = str_replace('_css.php', '', basename(__FILE__));
	if (isset($MYSTYLE[$stylename])) {
		$stylesheet .= get_mystyle($stylename);
	}
}

?>
