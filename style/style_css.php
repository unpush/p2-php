<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 ースタイル設定
// 全般

if ($STYLE['a_underline_none'] == 1) {
	$a_underline_none_css = 'a{text-decoration:none;}';
} else {
	$a_underline_none_css = '';
}

// ブラウザが Camino なら
if (strstr($_SERVER['HTTP_USER_AGENT'], "Camino") || strstr($_SERVER['HTTP_USER_AGENT'], "Chimera")) {
	$stylesheet .= <<<EOP
input,option,select,textarea{
	font-size:10px;
	font-family:"Osaka"; /* Camino ではフォームのフォントにヒラギノを指定するとline-heightが崩れる */
}\n
EOP;
} else {
	$stylesheet .= <<<EOP
input,option,select,textarea{
	font-size:{$STYLE['form_fontsize']};
}\n
EOP;
}

if ($STYLE['fontfamily_bold']) {
	$stylesheet .= <<<EOP
b, strong, th {
	font-weight:normal; font-family:"{$STYLE['fontfamily_bold']}";
}\n
EOP;
}

$stylesheet .= <<<EOP

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

EOP;

// スタイルの上書き
if (isset($MYSTYLE) && is_array($MYSTYLE)) {
	include_once (P2_STYLE_DIR . '/mystyle_css.php');
	if (isset($MYSTYLE['style']) || isset($MYSTYLE['base'])) {
		$stylesheet .= get_mystyle('style');
	}
}

?>
