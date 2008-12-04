<?php
// p2 ースタイル設定
// for post.php 情報ウィンドウ

if ($STYLE['a_underline_none'] == 2) {
	$thre_title_underline_css = "a.thre_title{text-decoration:none;}";
}

echo <<<EOP

body,td{
	font-size:{$STYLE['fontsize']};
}

.thre_title{color:{$STYLE['read_thread_title_color']};}

{$thre_title_underline_css}

#original_msg{
	color:{$STYLE['respop_color']};
	font-size:{$STYLE['respop_fontsize']};
	margin:1em;
	padding:0.5em;
	background:{$STYLE['respop_bgcolor']} {$STYLE['respop_background']};
	border-width:{$STYLE['respop_b_width']};
	border-color:{$STYLE['respop_b_color']};
	border-style:{$STYLE['respop_b_style']};
	line-height:120%;
}
#original_msg * {

}

EOP;

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
