<?php
// p2 ースタイル設定
// 全般

if ($STYLE['a_underline_none'] == 1) {
	$a_underline_none_css = 'a{ text-decoration:none; }';
} else {
    $a_underline_none_css = '';
}

echo <<<EOP
<style type="text/css" media="all">
<!--
EOP;

// ブラウザが Camino なら
if (strstr($_SERVER['HTTP_USER_AGENT'], "Camino") || strstr($_SERVER['HTTP_USER_AGENT'], "Chimera")) {
	echo <<<EOP
	input,option,select{
		font-size:10px;
		font-family:"Osaka"; /*  Camino ではフォームのフォントにヒラギノを指定するとline-heightが崩れる */
	}
EOP;
} else {
	echo <<<EOP
	input,option,select{
		font-size:{$STYLE['form_fontsize']};
	}
EOP;
}

if (isset($STYLE['fontfamily_bold'])) {
	echo <<<EOP
	b{
        font-weight:normal; font-family:"{$STYLE['fontfamily_bold']}";
    }
EOP;
}

$STYLE['background'] = isset($STYLE['background']) ? $STYLE['background'] : '';
$STYLE['fontfamily'] = isset($STYLE['fontfamily']) ? $STYLE['fontfamily'] : '';

echo <<<EOP

	body{
		background:{$STYLE['bgcolor']};
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

-->
</style>\n
EOP;
?>
