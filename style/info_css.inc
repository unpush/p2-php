<?php
// p2 ースタイル設定
// for info.php 情報ウィンドウ

if($STYLE['a_underline_none'] == 2){
	$thre_title_underline_css = "a.thre_title{text-decoration:none;}";
}

echo <<<EOSTYLE

<style type="text/css" media="all">
<!--
	
	.thre_title{
		color:{$STYLE['read_thread_title_color']};
	}
	{$thre_title_underline_css}

	.infomsg{
		font-size:{$STYLE['infowin_fontsize']};
	}

	table{
		border:solid #999;
		border-width:1px 0px;
		padding:12px;
	}
	td{
		line-height:100%;
		padding:3px;
		font-size:{$STYLE['infowin_fontsize']};
	}
	td.tdleft{
		text-align:right;
		color:#14a;
	}

-->
</style>

EOSTYLE;
?>
