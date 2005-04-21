<?php
// p2 ースタイル設定
// for editfavita.php


echo <<<EOSTYLE

<style type="text/css" media="all">
<!--

	body, td{
		line-height:120%;
		background-color:{$STYLE['menu_bgcolor']};
		font-size:{$STYLE['menu_fontsize']};
	}
	
	a:link.te{color:#999;} /* 並び替え */
	a:visited.te{color:#999;}
	a:hover.te{color:{$STYLE['menu_ita_color_h']};}
	
-->
</style>

EOSTYLE;
?>
