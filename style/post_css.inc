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

-->
</style>

EOP;
?>
