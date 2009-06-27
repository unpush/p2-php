<?php
// p2 ースタイル設定
// for read.php

$STYLE['fontfamily_bold'] = isset($STYLE['fontfamily_bold']) ? $STYLE['fontfamily_bold'] : null;
$STYLE['read_background'] = isset($STYLE['read_background']) ? $STYLE['read_background'] : null;

$_conf['am_auto_fontsize'] = isset($_conf['am_auto_fontsize']) ? $_conf['am_auto_fontsize'] : null;
$_conf['am_aafont'] = isset($_conf['am_aafont']) ? $_conf['am_aafont'] : null;
$_conf['am_aafont'] = "'" . str_replace(",", "','", $_conf['am_aafont']) . "'";

if ($_conf['bottom_res_form'] == 2) {
    $kakiko_display = 'block';
} else {
    $kakiko_display = 'none';
}

if ($STYLE['fontfamily_bold']) {
	echo <<<EOP
    /* スレッドタイトル */
	h3{ font-weight:normal; font-family:"{$STYLE['fontfamily_bold']}"; }\n
EOP;
}

if ($_conf['spm_before'] != "") {
	$spm_before = "\n\t.spm a:hover:before{ content:\"{$_conf['spm_before']}\"; }";
} else {
	$spm_before = "";
}
if ($_conf['spm_after'] != "") {
	$spm_after = "\n\t.spm a:hover:after{ content:\"{$_conf['spm_after']}\"; }";
} else {
	$spm_after = "";
}

echo <<<EOP
body{
	background:{$STYLE['read_bgcolor']} {$STYLE['read_background']};
	line-height:130%;
	color:{$STYLE['read_color']};
	/* font-family:"モナー"; */
}
body, td{
	font-size:{$STYLE['read_fontsize']};
}

/* 2006/09/15 add */
.toolbar{
    font-size:83%;
}

a:link{color:{$STYLE['read_acolor']};}
a:visited{color:{$STYLE['read_acolor_v']};}
a:hover{color:{$STYLE['read_acolor_h']};}

dd {line-height: normal;}

/* 引用レス */
i{ font-style:normal; }

/* レスポップアップ */
dd.respopup{ margin:8px 8px; }

div#kossoriHeadbar{
    position:absolute;
    display: none;
    padding:4px 0px;
    background-color:{$STYLE['read_bgcolor']};
    /* background-image:url("img/kossori.gif"); */
    background-repeat: no-repeat;
    background-position: right top;
    border-bottom:solid 1px #ccc;
}

div#header form.toolbar{
    margin:0px 0px 8px 0px;
}

/* 大きいのと小さいの（レスフォーム） */
.thread_title, .thre_title{
	color:{$STYLE['read_thread_title_color']};
    
    width:100%;
    filter: shadow(color=#e5e5e5,direction=135);
    text-shadow: 2px 2px 2px #e5e5e5;
}
.thread_title{
	margin:6px 0;
	line-height:120%;
}

.name{ color:{$STYLE['read_name_color']}; } /* 投稿者の名前 */
.mail{ color:{$STYLE['read_mail_color']}; } /* 投稿者のmail */
.sage{ color:{$STYLE['read_mail_sage_color']}; } /* 投稿者のmail(sage) */
img.thumbnail{ border:solid 1px; } /* 画像URLの先読みサムネイル*/


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
	color:{$STYLE['respop_color']};
	font-size:{$STYLE['respop_fontsize']};
	line-height:120%;
	padding:8px;
	background:{$STYLE['respop_bgcolor']} {$STYLE['respop_background']};
	border:{$STYLE['respop_b_width']} {$STYLE['respop_b_color']} {$STYLE['respop_b_style']};
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

table#readhere{
	margin: 2em auto 0px auto;
	background:{$STYLE['respop_bgcolor']} {$STYLE['respop_background']};
	border-width:{$STYLE['respop_b_width']};
	border-color:{$STYLE['respop_b_color']};
	border-style:{$STYLE['respop_b_style']};
}
table#readhere td{
	padding: 0.5em;
	text-align: center;
}

/* レス番号 */
a.resnum:link, a.resnum:visited, a.resnum:hover, a.resnum:active{
	color:{$STYLE['read_color']};
	text-decoration:none;
}

/* 新着レス番号（ここではカラーが新着確認の機能を持っているので特別にfontでも
カラー指定をしている。CSS非対応に対応。ShowThreadPc.php - transRes を参照)	*/
.newres{
	color:{$STYLE['read_newres_color']};
}

/* 新着レス */
a.newres:link, a.newres:visited, a.newres:hover, a.newres:active{
	color:{$STYLE['read_newres_color']};
	text-decoration:none;
}

span.Mona a{ /* アクティブモナー */
	color: {$STYLE['read_color']};
	text-decoration: none;
}

div.AutoMona { /* 自動アクティブモナー */
	font-family: {$_conf['am_aafont']};
	font-size: {$_conf['am_auto_fontsize']};
	line-height: 100%;
	white-space: pre;
}

/* レスのすばやさ */
span.spd{
	font-size:8pt;
	color:#777;
}

/* DAT容量 */
span.datsize{
	font-size:8pt;
	color:#777;
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
