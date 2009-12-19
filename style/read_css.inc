<?php
/**
 * rep2 - スタイル設定
 * for read.php
 */

if (!isset($STYLE['respop_bgcolor']))	{ $STYLE['respop_bgcolor'] = '#ffffcc'; }
if (!isset($STYLE['respop_background'])) { $STYLE['respop_background'] = ''; }
$respop_background = "background:{$STYLE['respop_bgcolor']} {$STYLE['respop_background']};";

if (!isset($STYLE['respop_b_style'])) { $STYLE['respop_b_style'] = 'solid'; }
if (!isset($STYLE['respop_b_width'])) { $STYLE['respop_b_width'] = '1px';   }
if (!isset($STYLE['respop_b_color'])) { $STYLE['respop_b_color'] = 'black'; }
$respop_border = "border:{$STYLE['respop_b_style']} {$STYLE['respop_b_width']} {$STYLE['respop_b_color']};";

$respop_color = '';
if (isset($STYLE['respop_color']) && strlen($STYLE['respop_color']) > 0) {
	$respop_color = "color:{$STYLE['respop_color']};";
}

if ($STYLE['fontfamily_bold']) {
	$fontstyle_bold = empty($STYLE['fontstyle_bold']) ? 'normal' : $STYLE['fontstyle_bold'];
	$fontweight_bold = empty($STYLE['fontweight_bold']) ? 'normal' : $STYLE['fontweight_bold'];
	echo <<<EOSTYLE

h3, h4 {
	font-family:"{$STYLE['fontfamily_bold']}";
	font-style: {$fontstyle_bold};
	font-weight: {$fontweight_bold};
}

EOSTYLE;
}

echo <<<EOSTYLE

body{
	background:{$STYLE['read_bgcolor']} {$STYLE['read_background']};
	line-height:130%;
	color:{$STYLE['read_color']};
}
body, td{
	font-size:{$STYLE['read_fontsize']};
}

a:link{color:{$STYLE['read_acolor']};}
a:visited{color:{$STYLE['read_acolor_v']};}
a:hover{color:{$STYLE['read_acolor_h']};}

h3.thread_title {
	margin: 6px 0;
	line-height:120%;
	font-size: 18px;
	color: {$STYLE['read_thread_title_color']};
}
h4.thread_title {
	margin: 0 0 12px 0;
	line-height:100%;
	font-size: 14px;
	color: {$STYLE['read_thread_title_color']};
}

.thre_title{color:{$STYLE['read_thread_title_color']};}
.name{color:{$STYLE['read_name_color']};} /* 投稿者の名前 */
.mail{color:{$STYLE['read_mail_color']};} /* 投稿者のmail */
.sage{color:{$STYLE['read_mail_sage_color']};} /* 投稿者のmail(sage) */

/* 画像URLの先読みサムネイル*/
img.thumbnail {
	/*border-color: inherit;*/
	border-color: {$STYLE['read_color']};
	border-style: solid;
	border-width: 1px;
}
a img.thumbnail {
	border-color: {$STYLE['read_acolor']};
}

div.thread {
	margin: 0;
	padding: 1.0em 0 0 0;
}

/* レス */
div.res {
	margin: 0 0 2.0em 0;
	padding: 0;
}

/* 引用レスポップアップ */
div.respopup {
	position: absolute;
	visibility: hidden; /* 普段は隠しておく*/
	{$respop_background}
	{$respop_border}
	{$respop_color}
	font-size:{$STYLE['respop_fontsize']};
	margin: 0;
	padding: 8px;
	line-height: 120%;
}

/* あぼーんレス */
div.res.aborned {
	margin: 0;
	padding: 0;
	font-size: xx-small;
	line-height: 100%;
}

div.res.aborned * {
	display: none;
}

/* レスヘッダ */
div.res-header {
	margin: 0;
	padding: 0;
}

/* レスボディ (通常) */
div.res div.message {
	margin: 0;
	padding: 2px 0 0 2.5em;
}

/* レスボディ (引用レスポップアップ) */
div.respopup div.message {
	margin: 0;
	padding: 2px 0 0 1.0em;
}

/* レスボディ内リンクの折り返し */
div.message a {
	word-wrap: break-word;
	word-break: break-all;
}

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

.ngword, .stripped {
	text-decoration: line-through;
	color: {$STYLE['read_ngword']};
}

span.ngword {
	cursor: pointer;
}

div.ngmsg {
	display: none;
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

a.resnum:link, a.resnum:visited, a.resnum:hover, a.resnum:active{ /* レス番号 */
	color: {$STYLE['read_color']};
	text-decoration: none;
}

a.newres:link, a.newres:visited, a.newres:hover, a.newres:active{ /* 新着レス */
	color: {$STYLE['read_newres_color']};
	text-decoration: none;
}

/* {{{ アクティブモナー */

.aMonaSW {
	cursor: pointer;
}

.ActiveMona {
	white-space: nowrap;
	line-height: 100%;
	font-family: "{$_conf['expack.am.fontfamily']}";
	font-size: {$_conf['expack.am.fontsize']};
}

.NoMona {
	white-space: normal;
	line-height: 130%;
	font-family: "{$STYLE['fontfamily']}";
	font-size: {$STYLE['read_fontsize']};
}

.NoMonaQ {
	white-space: normal;
	line-height: 120%;
	font-family: "{$STYLE['fontfamily']}";
	font-size: {$STYLE['respop_fontsize']};
}

/* }}}*/
/* {{{ スマートポップアップメニュー */

.spmSW {
	cursor: pointer;
}

.spm {
	position: absolute;
	visibility: hidden; /* 普段は隠しておく*/
	{$respop_background}
	{$respop_border}
	{$respop_color}
	font-size: {$STYLE['respop_fontsize']};
	line-height: 150%;
	width: 8.5em;
	margin: 0px;
	padding: 2px 4px;
}

.spm p {	/* スマートポップアップメニュー：ヘッダ */
	white-space: nowrap;
	margin: 2px;
	padding: 0px;
	border-bottom: {$STYLE['respop_b_width']} {$STYLE['respop_b_color']} {$STYLE['respop_b_style']};
	vertical-align: middle;
}

.spm a {	/* スマートポップアップメニュー：リンク */
	display: block;
	white-space: nowrap;
	margin: 2px -4px;
	padding: 0px 4px;
	vertical-align: middle;
	text-decoration: none;
}
.spm a:hover {
	background: {$STYLE['read_bgcolor']} {$STYLE['read_background']};
}
.spm a.closemenu {
	text-align: right;
}

.spm a.closebox {	/* スマートポップアップメニュー：クローズボックス */
	position: absolute;
	top: 0;
	right: 0;
	width: 14px;
	height: 14px;
	margin: {$STYLE['respop_b_width']};
	padding: 1px;
	border: {$STYLE['respop_b_style']} 1px {$STYLE['respop_b_color']};
}

/* }}} */

.preview-video-switch {
	cursor: pointer;
}

/* ツールバー */
table.toolbar {
	width: 100%;
	margin: 0;
	padding: 0 10px 0 0;
}

table.toolbar td.lblock {
	text-align: left;
} 

table.toolbar td.cblock {
	text-align: center;
} 

table.toolbar td.rblock {
	text-align: right;
} 

EOSTYLE;

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
