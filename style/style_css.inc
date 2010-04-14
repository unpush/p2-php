<?php
/**
 * rep2 - スタイル設定
 * 全般
 */

if ($STYLE['a_underline_none'] == 1) {
    $a_underline_none_css = "a{text-decoration:none;}";
} else {
    $a_underline_none_css = '';
}

// ブラウザが Camino なら
if (strstr($_SERVER['HTTP_USER_AGENT'], "Camino") || strstr($_SERVER['HTTP_USER_AGENT'], "Chimera")) {
	echo <<<EOSTYLE

input,option,select,textarea{
	font-size:10px;
	font-family:"Osaka"; /*  Camino ではフォームのフォントにヒラギノを指定するとline-heightが崩れる */
}

EOSTYLE;
} else {
	echo <<<EOSTYLE

input,option,select,textarea{
	font-size:{$STYLE['form_fontsize']};
}

EOSTYLE;
}

if (!empty($STYLE['fontfamily_bold'])) {
	$fontstyle_bold = empty($STYLE['fontstyle_bold']) ? 'normal' : $STYLE['fontstyle_bold'];
	$fontweight_bold = empty($STYLE['fontweight_bold']) ? 'normal' : $STYLE['fontweight_bold'];
	echo <<<EOSTYLE

b,strong,th{
	font-family:"{$STYLE['fontfamily_bold']}";
	font-style: {$fontstyle_bold};
	font-weight: {$fontweight_bold};
}

EOSTYLE;
}

echo <<<EOSTYLE

body{
	background:{$STYLE['bgcolor']} {$STYLE['background']};
}
body,td{
	line-height:135%;
	font-size:{$STYLE['fontsize']};
	color:{$STYLE['textcolor']};
	font-family:"{$STYLE['fontfamily']}";
}
a:link{color:{$STYLE['acolor']};}
a:visited{color:{$STYLE['acolor_v']};}
a:hover{color:{$STYLE['acolor_h']};}
{$a_underline_none_css}

a:link.fav{color:{$STYLE['fav_color']};} /* お気にマーク */
a:visited.fav{color:{$STYLE['fav_color']};}
a:hover.fav{color:{$STYLE['acolor_h']};}

img, object{border:none;}

hr{height:1px; color:#ccc;}

div.container{
	width:76%;
	margin:8px auto;
	padding:0px 16px;
	text-align:left;
}

.invisible{visibility: hidden;}

.kakomi{
	padding: 16px;
	border:solid 1px #999;
}

.filtering{background-color:yellow;} /* フィルタのワード色分け */

form.inline-form {
	display: inline;
}

p.info-msg {
	margin: 4px 12px;
	font-size: 12px;
	line-height: 120%;
}

EOSTYLE;

// {{{ 汎用ポップアップと元スレリンクポップアップ (レスポップアップの流用)

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

	echo <<<EOSTYLE

.popup_element {
	position: absolute;
	top: 0;
	left: 0;
	visibility: hidden; /* 普段は隠しておく*/
	{$respop_background}
	{$respop_border}
	{$respop_color}
	font-size:{$STYLE['respop_fontsize']};
	margin: 0;
	padding: 8px;
}

div#motols {
	padding: 1px 2px;
}

div#motols a {
	display: block;
	margin: 0;
	padding: 3px;
	line-height: 100%;
}

EOSTYLE;

// }}}

// スタイルの上書き
if (isset($MYSTYLE) && is_array($MYSTYLE)) {
	require_once P2_STYLE_DIR . '/mystyle_css.php';
	if (isset($MYSTYLE['style']) || isset($MYSTYLE['base'])) {
		disp_mystyle('style');
	}
}

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
