<?php
// p2 ースタイル設定
// for post.php 情報ウィンドウ

if($STYLE['a_underline_none'] == 2){
	$thre_title_underline_css = "a.thre_title{text-decoration:none;}";
}

echo <<<EOP

<style type="text/css" media="all">
<!--

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

-->
</style>

EOP;
?>
