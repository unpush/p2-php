<?php
// p2 ースタイル設定
// for read.php

echo <<<EOP
<style type="text/css" media="all">
<!--
EOP;

if($STYLE['fontfamily_bold']){
	echo <<<EOP
	h3{font-weight:normal; font-family:"{$STYLE['fontfamily_bold']}";} /* スレッドタイトル*/
EOP;
}

echo <<<EOP
	body{
		background:{$STYLE['read_bgcolor']};
		line-height:130%;
		color:{$STYLE['read_color']};
		/*font-family:"モナー";*/
	}
	body, td{
		font-size:{$STYLE['read_fontsize']};
	}
	
	a:link{color:{$STYLE['read_acolor']};}
	a:visited{color:{$STYLE['read_acolor_v']};}
	a:hover{color:{$STYLE['read_acolor_h']};}
	
	i{font-style:normal;} /* 引用レス*/
	dd.respopup{margin:8px 8px;} /* レスポップアップ*/
	
	.thread_title{margin:6px 0; line-height:120%; font-size:14pt; color:{$STYLE['read_thread_title_color']};}
	.thre_title{color:{$STYLE['read_thread_title_color']};}
	.name{color:{$STYLE['read_name_color']};} /* 投稿者の名前 */
	.mail{color:{$STYLE['read_mail_color']};} /* 投稿者のmail */
	.sage{color:{$STYLE['read_mail_sage_color']};} /* 投稿者のmail(sage) */
	img.thumbnail{border:solid 1px;} /* 画像URLの先読みサムネイル*/
	
	/* 新着レス番号（ここではカラーが新着確認の機能を持っているので特別にfontで
	カラー指定をしている。thread_class.inc - transRes を参照)	*/
	/* .newres{color:{$newres_color};} ← よって現在は無効の設定 */

	.onthefly{ /* on the fly */
		color:#0a0;
		border:1px #0a0 solid;
		padding:2px;
		font-size:11px;
	}
	.ontheflyresorder{
		color:#0a0;
	}
	
	.ngword{color:{$STYLE['read_ngword']};}
	
	.aborned{ font-size:1px; }
	.aborned span{ display:none; }
	
	.respopup{ 	/* 引用レスポップアップ */
		position:absolute;
		visibility:hidden; /* 普段は隠しておく*/
		background-color:#ffffcc;
		border:1px black solid;
		font-size:{$STYLE['respop_fontsize']};
		padding:8px;
		line-height:120%;
	}
	
	#iframespace{ /* HTMLポップアップスペース */
		position:absolute;
		z-index:100;
		/*border:solid 1px;*/
	}

	#closebox{
		width:14px;
		height:14px;
		position:absolute;
		z-index:101;
		border:solid 2px;
		padding:1px;
		line-height:100%;
		background-color:#ceddf7;
	}
	
	div#kakiko{
		display:none;
	}

	/* レスのすばやさ */
	span.spd{
		font-size:8pt;
		color:#777;
	}	
-->
</style>

EOP;
?>