<?php
// p2 ースタイル設定
// for editfavita.php


echo <<<EOSTYLE

<style type="text/css" media="all">
<!--

	body, td{
		line-height:120%;
		background:{$STYLE['menu_bgcolor']} {$STYLE['menu_background']};
		font-size:{$STYLE['menu_fontsize']};
	}
	
	a:link.te{color:#999;} /* 並び替え */
	a:visited.te{color:#999;}
	a:hover.te{color:{$STYLE['menu_ita_color_h']};}
	
-->
</style>

EOSTYLE;

// スタイルの上書き
if (isset($MYSTYLE) && is_array($MYSTYLE)) {
    include_once (P2_STYLE_DIR . '/mystyle_css.php');
    $_mystylename = str_replace('_css.inc', '', basename(__FILE__));
    if (isset($MYSTYLE[$_mystylename])) {
        disp_mystyle($_mystylename);
    }
}

?>
