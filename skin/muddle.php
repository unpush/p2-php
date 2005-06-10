<?php
/* ‚Æ‚É‚©‚­ƒ‰ƒ“ƒ_ƒ€‚É’l‚ðŒˆ’èI */

/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

srand(time());
$units = array('px','em','ex','in','cm','mm','pt','pc');
$b_styles = array('none','hidden','solid','double','groove','ridge','inset','outset','dashed','dotted');

function mkHexColor() {
	$R = dechex(rand(0,255));
	$G = dechex(rand(0,255));
	$B = dechex(rand(0,255));
	if (strlen($R) == 1) $R = '0' . $R;
	if (strlen($G) == 1) $G = '0' . $G;
	if (strlen($B) == 1) $B = '0' . $B;
	return '#' . $R . $G . $B;
}
function mkFontSize($min=6, $max=16) {
	//global $units;
	$n = rand($min,$max);
	//return $n . 'px';
	$u = (rand(0,1) == 0) ? 'px' : 'pt';
	return $n . $u;
	/*$m = rand(0,7);
	return $n . $units[$m];*/
}
function mkBorderColor() {
	$m = rand(1,4);
	$n = array();
	for ($i=0; $i<$m; $i++) {
		$o = mkHexColor();
		array_push($n, $o);
	}
	return implode(' ', $n);
}
function mkBorderWidth($min=1, $max=5) {
	$m = rand(1,4);
	$n = array();
	for ($i=0; $i<$m; $i++) {
		$o = rand($min,$max);
		array_push($n, $o . 'px');
	}
	return implode(' ', $n);
}
function mkBorderStyle() {
	global $b_styles;
	$m = rand(1,4);
	$n = array();
	for ($i=0; $i<$m; $i++) {
		$o = rand(0,9);
		array_push($n, $b_styles[$o]);
	}
	return implode(' ', $n);
}

$STYLE['a_underline_none'] = rand(0,2); 

$STYLE['fontfamily'] = "ƒqƒ‰ƒMƒmŠpƒS Pro W3"; 

$STYLE['fontsize'] = mkFontSize();
$STYLE['menu_fontsize'] = mkFontSize();
$STYLE['sb_fontsize'] = mkFontSize();
$STYLE['read_fontsize'] = mkFontSize();
$STYLE['respop_fontsize'] = mkFontSize();
$STYLE['infowin_fontsize'] = mkFontSize();
$STYLE['form_fontsize'] = mkFontSize();

$STYLE['bgcolor'] = mkHexColor();
$STYLE['textcolor'] = mkHexColor();
$STYLE['acolor'] = mkHexColor();
$STYLE['acolor_v'] = mkHexColor();
$STYLE['acolor_h'] = mkHexColor();

$STYLE['fav_color'] = mkHexColor();

$STYLE['menu_bgcolor'] = mkHexColor();
$STYLE['menu_cate_color'] = mkHexColor();

$STYLE['menu_acolor_h'] = mkHexColor();

$STYLE['menu_ita_color'] = mkHexColor();
$STYLE['menu_ita_color_v'] = mkHexColor();
$STYLE['menu_ita_color_h'] = mkHexColor();

$STYLE['sb_bgcolor'] = mkHexColor();
$STYLE['sb_color'] = mkHexColor();

$STYLE['sb_acolor'] = mkHexColor();
$STYLE['sb_acolor_v'] = mkHexColor();
$STYLE['sb_acolor_h'] = mkHexColor();

$STYLE['sb_th_bgcolor'] = mkHexColor();
$STYLE['sb_tbgcolor'] = mkHexColor();
$STYLE['sb_tbgcolor1'] = mkHexColor();

$STYLE['sb_ttcolor'] = mkHexColor();
$STYLE['sb_tacolor'] = mkHexColor();
$STYLE['sb_tacolor_h'] = mkHexColor();

$STYLE['sb_order_color'] = mkHexColor();

$STYLE['thre_title_color'] = mkHexColor();
$STYLE['thre_title_color_v'] = mkHexColor();
$STYLE['thre_title_color_h'] = mkHexColor();

$STYLE['sb_tool_bgcolor'] = mkHexColor();
$STYLE['sb_tool_border_color'] = mkHexColor();
$STYLE['sb_tool_color'] = mkHexColor();
$STYLE['sb_tool_acolor'] = mkHexColor();
$STYLE['sb_tool_acolor_v'] = mkHexColor();
$STYLE['sb_tool_acolor_h'] = mkHexColor();
$STYLE['sb_tool_sepa_color'] = mkHexColor();

$STYLE['newres_color'] = mkHexColor();

$STYLE['read_bgcolor'] = mkHexColor();
$STYLE['read_color'] = mkHexColor();

$STYLE['read_acolor'] = mkHexColor();
$STYLE['read_acolor_v'] = mkHexColor();
$STYLE['read_acolor_h'] = mkHexColor();

$STYLE['read_thread_title_color'] = mkHexColor();
$STYLE['read_name_color'] = mkHexColor();
$STYLE['read_mail_color'] = mkHexColor();
$STYLE['read_mail_sage_color'] = mkHexColor();
$STYLE['read_ngword'] = mkHexColor();

$STYLE['post_pop_size'] = "610,350"; 
$STYLE['post_msg_rows'] = 10; 
$STYLE['post_msg_cols'] = 70; 

$STYLE['respop_color'] = mkHexColor();
$STYLE['respop_bgcolor'] = mkHexColor();
$STYLE['respop_b_width'] = mkBorderWidth();
$STYLE['respop_b_color'] = mkBorderColor();
$STYLE['respop_b_style'] = mkBorderStyle(); 

$STYLE['info_pop_size'] = "600,380"; 

?>
