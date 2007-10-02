<?php
// p2 ースタイル設定
// for read.php

$STYLE['fontfamily_bold'] = isset($STYLE['fontfamily_bold']) ? $STYLE['fontfamily_bold'] : null;
$STYLE['read_background'] = isset($STYLE['read_background']) ? $STYLE['read_background'] : null;

if ($_conf['bottom_res_form'] == 2) {
    $kakiko_display = 'block';
} else {
    $kakiko_display = 'none';
}

echo <<<EOP
<style type="text/css" media="all">
<!--
EOP;

if ($STYLE['fontfamily_bold']) {
	echo <<<EOP
/* スレッドタイトル */
h3{ font-weight:normal; font-family:"{$STYLE['fontfamily_bold']}"; }
EOP;
}

if (isset($_conf['spm_before']) && $_conf['spm_before'] != "") {
	$spm_before = "\n\t.spm a:hover:before{ content:\"{$_conf['spm_before']}\"; }";
} else {
	$spm_before = "";
}
if (isset($_conf['spm_before']) && $_conf['spm_after'] != "") {
	$spm_after = "\n\t.spm a:hover:after{ content:\"{$_conf['spm_after']}\"; }";
} else {
	$spm_after = "";
}

echo <<<EOP
body{
	background:{$STYLE['read_bgcolor']};
	line-height:130%;
	color:{$STYLE['read_color']};
	/* font-family:"モナー"; */
}
body, td{
	font-size:{$STYLE['read_fontsize']};
}

a:link{color:{$STYLE['read_acolor']};}
a:visited{color:{$STYLE['read_acolor_v']};}
a:hover{color:{$STYLE['read_acolor_h']};}

/* 引用レス */
i{font-style:normal;}

/* レスポップアップ */
dd.respopup{margin:8px 8px;}

div#header form.toolbar{
	margin:0px 0px 8px 0px;
}

div#kossoriHeadbar{
    position:absolute;
	display: none;
    padding:4px 0px;
    background-color:{$STYLE['read_bgcolor']};
    background-image:url("img/kossori.gif");
    background-repeat: no-repeat;
    background-position: right top;
    border-bottom:solid 1px #ccc;
}

/* 大きいのと小さいの（レスフォーム） */
.thread_title, .thre_title{
	color:{$STYLE['read_thread_title_color']};

	width:100%;
	filter: shadow(color=#e5e5e5,direction=135);
	text-shadow: 2px 2px 2px #e5e5e5;
}
.thread_title{
    margin:6px 0; line-height:120%; font-size:14pt;
}

.name{color:{$STYLE['read_name_color']};} /* 投稿者の名前 */
.mail{color:{$STYLE['read_mail_color']};} /* 投稿者のmail */
.sage{color:{$STYLE['read_mail_sage_color']};} /* 投稿者のmail(sage) */
img.thumbnail{border:solid 1px;} /* 画像URLの先読みサムネイル*/

/* レス番号 */
a.resnum:link, a.resnum:visited, a.resnum:hover, a.resnum:active{
	color:{$STYLE['read_color']};
	text-decoration:none;
}

/* 新着レス番号（ここではカラーが新着確認の機能を持っているので特別にfontで
カラー指定をしている。thread_class.inc - transRes を参照)	*/
/* .newres{color:{$newres_color};} ← よって現在は無効の設定 */

.onthefly{
	color:#0a0;
	border:1px #0a0 solid;
	padding:2px;
	font-size:11px;
}
.ontheflyresorder{
	color:#0a0;
}

.ngword{ color:{$STYLE['read_ngword']}; cursor: hand; }
.ngword_cont{ display:none; }

.aborned{ font-size:1px; }
.aborned span{ display:none; }

/* 引用レスポップアップ */
.respopup{
	position:absolute;
	visibility:hidden; /* 普段は隠しておく*/
	background-color:#ffffcc;
	border:1px black solid;
	font-size:{$STYLE['respop_fontsize']};
	padding:8px;
	line-height:120%;
}

/* スマートポップアップメニュー */
.spm{
	position:absolute;
	visibility:hidden; /* 普段は隠しておく*/
	color:{$STYLE['respop_color']};
	font-size:{$STYLE['respop_fontsize']};
	line-height:150%;
	width:8.5em;
	margin:0px;
	padding:2px 4px;
	background:{$STYLE['respop_bgcolor']} {$STYLE['respop_background']};
	border:{$STYLE['respop_b_width']} {$STYLE['respop_b_color']} {$STYLE['respop_b_style']};
}

/* スマートポップアップメニュー：ヘッダ */
.spm p{
	white-space: nowrap;
	margin:2px;
	padding:0px;
	border-bottom:{$STYLE['respop_b_width']} {$STYLE['respop_b_color']} {$STYLE['respop_b_style']};
	vertical-align:middle;
}

/* スマートポップアップメニュー：リンク */
.spm a{
	display:block;
	white-space: nowrap;
	margin:2px -4px;
	padding:0px 4px;
	vertical-align:middle;
	text-decoration:none;
}
.spm a:hover{ background:{$STYLE['read_bgcolor']} {$STYLE['read_background']}; }{$spm_before}{$spm_after}

/* スマートポップアップメニュー：レス番号 */
.spm .numbox {
	font-size:{$STYLE['respop_fontsize']};
	margin:0px;
	padding:0px;
	border:none;
}

.spm div.spmMona {
	white-space: nowrap;
	margin:2px;
	padding:0px;
	vertical-align:middle;
}

.spm div.spmMona a{
	display:inline;
	color:{$STYLE['respop_color']};
	text-decoration:none;
}
.spm div.spmMona a:hover{ background:transparent none; }

/* HTMLポップアップスペース */
#iframespace{
	position:absolute;
	z-index:100;
	/* border:solid 1px; */
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
	display:{$kakiko_display};
    position:absolute;
    background-color:{$STYLE['read_bgcolor']};
    font-size:10pt;
    padding-bottom:8px;
}

/* レスのすばやさ */
span.spd{
	font-size:8pt;
	color:#777;
}
-->
</style>

EOP;
