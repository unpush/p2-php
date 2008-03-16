<?php
// p2 ースタイル設定
// for editfavita.php

echo <<<EOSTYLE

body, td{
	line-height:120%;
	background:{$STYLE['menu_bgcolor']} {$STYLE['menu_background']};
	font-size:{$STYLE['menu_fontsize']};
}

a:link.te{ color:#999; } /* 並び替え */
a:visited.te{ color:#999; }
a:hover.te{color:{$STYLE['menu_ita_color_h']};}

td.italist, td.feedlist {
	border:1px solid #dddddd;
}
.sortList {
	cursor:move; width:200px; padding:4px 16px 0 0;
}
ul#italist, ul#feedlist {
	list-style-type:none;
}

EOSTYLE;

// スタイルの上書き
if (isset($MYSTYLE) && is_array($MYSTYLE)) {
    include_once (P2_STYLE_DIR . '/mystyle_css.php');
    $_mystylename = str_replace('_css.inc', '', basename(__FILE__));
    if (isset($MYSTYLE[$_mystylename])) {
        disp_mystyle($_mystylename);
    }
}

/*
 * Local variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
