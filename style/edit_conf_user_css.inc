<?php
/**
 * rep2 - ƒXƒ^ƒCƒ‹Ý’è
 * for edit_conf_user.php
 */

if (!isset($STYLE['respop_bgcolor']))    { $STYLE['respop_bgcolor'] = '#ffffcc'; }
if (!isset($STYLE['respop_background'])) { $STYLE['respop_background'] = ''; }
if (!isset($STYLE['respop_b_style'])) { $STYLE['respop_b_style'] = 'solid'; }
if (!isset($STYLE['respop_b_width'])) { $STYLE['respop_b_width'] = '1px';   }
if (!isset($STYLE['respop_b_color'])) { $STYLE['respop_b_color'] = 'black'; }
if (!isset($STYLE['conf_btn_bgcolor']))	{ $STYLE['conf_btn_bgcolor'] = $STYLE['respop_bgcolor']; }
if (!isset($STYLE['conf_btn_background'])) { $STYLE['conf_btn_background'] = $STYLE['respop_background']; }
if (!isset($STYLE['conf_b_style'])) { $STYLE['conf_b_style'] = $STYLE['respop_b_style']; }
if (!isset($STYLE['conf_b_width'])) { $STYLE['conf_b_width'] = $STYLE['respop_b_width']; }
if (!isset($STYLE['conf_b_color'])) { $STYLE['conf_b_color'] = $STYLE['respop_b_color']; }

$conf_border = "{$STYLE['conf_b_style']} {$STYLE['conf_b_width']} {$STYLE['conf_b_color']}";

$ss = <<<EOSTYLE

p#pan_menu{
	border-bottom: {$conf_border};
}

table.edit_conf_user, table.edit_font, fieldset {
	background: {$STYLE['bgcolor']} {$STYLE['background']};
	border-top: {$conf_border};
	border-left: {$conf_border};
}

table.edit_conf_user th, table.edit_conf_user td, table.edit_font th, table.edit_font td {
	background: {$STYLE['bgcolor']} {$STYLE['background']};
	font-size: {$STYLE['infowin_fontsize']};
	padding: 4px;
	margin: 4px;
	border: {$STYLE['conf_b_style']} 0px;
	border-right: {$conf_border};
	border-bottom: {$conf_border};
}

tr.group td{
	background: {$STYLE['conf_btn_bgcolor']} {$STYLE['conf_btn_background']};
}

ul.tabbernav {
	border-bottom: 1px solid {$STYLE['sb_tool_border_color']} !important;
	font-size: {$STYLE['fontsize']} !important;
}

ul.tabbernav li a {
	border: 1px solid {$STYLE['sb_tool_border_color']} !important;
	border-bottom: none;
	color: {$STYLE['sb_tool_acolor']} !important;
	background: {$STYLE['sb_tool_bgcolor']} {$STYLE['sb_tool_background']} !important;
	vertical-align: bottom;
}

ul.tabbernav li a:hover {
	color: {$STYLE['sb_tool_acolor_h']} !important;
	background: {$STYLE['sb_tool_bgcolor']} {$STYLE['sb_tool_background']} !important;
	border-color: {$STYLE['respop_b_color']} !important;
}

ul.tabbernav li.tabberactive a {
	color: {$STYLE['textcolor']} !important;
	background: {$STYLE['bgcolor']} {$STYLE['background']} !important;
	border-bottom: 1px solid {$STYLE['bgcolor']} !important;
}

ul.tabbernav li.tabberactive a:hover {
	color: {$STYLE['respop_color']} !important;
	background: {$STYLE['respop_bgcolor']} {$STYLE['respop_background']} !important;
	border-bottom: 1px solid {$STYLE['bgcolor']} !important;
}

.tabberlive .tabbertab {
	padding:5px;
	border: 1px solid {$STYLE['sb_tool_border_color']};
	border-top:0;
}

EOSTYLE;

echo $ss;

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
