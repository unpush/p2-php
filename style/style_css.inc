<?php
// p2 ースタイル設定
// 全般

if ($STYLE['a_underline_none'] == 1) {
	$a_underline_none_css = 'a{ text-decoration:none; }';
} else {
    $a_underline_none_css = '';
}

/* ブラウザが Camino なら */
if (strstr($_SERVER['HTTP_USER_AGENT'], 'Camino') || strstr($_SERVER['HTTP_USER_AGENT'], 'Chimera')) { 
	echo <<<EOP
	input,option,select{
		font-size:10px;
		font-family:"Osaka"; /*  Camino ではフォームのフォントにヒラギノを指定するとline-heightが崩れる */
	}\n
EOP;
} else {
	echo <<<EOP
	input,option,select{
		font-size:{$STYLE['form_fontsize']};
	}\n
EOP;
}

if (isset($STYLE['fontfamily_bold'])) {
	echo <<<EOP
	b, strong{
        font-weight:normal;font-family:"{$STYLE['fontfamily_bold']}";
    }\n
EOP;
}

$STYLE['background'] = isset($STYLE['background']) ? $STYLE['background'] : '';
$STYLE['fontfamily'] = isset($STYLE['fontfamily']) ? $STYLE['fontfamily'] : '';

echo <<<EOP

body{
	background:{$STYLE['bgcolor']} {$STYLE['background']};
}
body,td{
	line-height:130%;
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
	width:95%;
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
