<?php
// p2 ースタイル設定
// for menu

echo <<<EOP

<style type="text/css" media="all">
<!--
	body{
		line-height:136%;
		background-color:{$STYLE['menu_bgcolor']};
		font-size:{$STYLE['menu_fontsize']};
		color:{$STYLE['menu_color']};
	}
	
	div.menu_cate{margin:0.5em 0;} /* 板カテゴリー */
	
	/* a{text-decoration:none;} */
	a:link.menu_cate{color:{$STYLE['menu_cate_color']};}
	a:visited.menu_cate{color:{$STYLE['menu_cate_color']};}
	a:hover.menu_cate{color:{$STYLE['menu_cate_color']};}
	
	.itas a:link{color:{$STYLE['menu_ita_color']};} /* 板名 リンク */
	.itas a:visited{color:{$STYLE['menu_ita_color_v']};}
	.itas a:hover{color:{$STYLE['menu_ita_color_h']};}
	.itas_hide a:link{color:{$STYLE['menu_ita_color']};}
	.itas_hide a:visited{color:{$STYLE['menu_ita_color_v']};}
	.itas_hide a:hover{color:{$STYLE['menu_ita_color_h']};}
	/* .itas_hide{display:none;} js/showHide.jsで*/
	
	a:link.fav{color:{$STYLE['fav_color']};} /* お気にマーク */
	a:visited.fav{color:{$STYLE['fav_color']};}
	a:hover.fav{color:{$STYLE['acolor_h']};}
	
	a:link.newres_num{color:{$STYLE['menu_newres_color']};} /* 新着レス数 */
	a:visited.newres_num{color:{$STYLE['menu_newres_color']};}
	a:hover.newres_num{color:{$STYLE['menu_newres_color']};}
	
	a:link.newres_num_zero{color:{$STYLE['menu_color']};} /* 新着レス数ゼロ */
	a:visited.newres_num_zero{color:{$STYLE['menu_color']};}
	a:hover.newres_num_zero{color:{$STYLE['menu_color']};}
	
	.newthre_num{color:{$STYLE['menu_newthre_color']};}	/* 新規スレッド数 */
	
-->
</style>

EOP;
?>