<?php
/**
 * rep2 - スタイル設定
 * for ImageCache2:iv2.php
 */

if ($STYLE['fontfamily_bold']) {
	$boldstyle = "font-family: \"{$STYLE['fontfamily_bold']}\";";
	if (!empty($STYLE['fontstyle_bold'])) {
		$boldstyle .= "font-style: {$STYLE['fontstyle_bold']};";
	}
	if (!empty($STYLE['fontweight_bold'])) {
		$boldstyle .= "font-weight: {$STYLE['fontweight_bold']};";
	}
} else {
	$boldstyle = 'font-weight: bold;';
}

echo <<<EOSTYLE

/* 全般 */

body {
	margin: 0;
	padding: 0;
	line-height: 100%;
}

a {
	text-decoration: none;
}

a.viewer_title {
	font-size:14px;
	color:{$STYLE['sb_tool_color']};
	{$boldstyle}
}

div#header {
	height: 3.75em;
}

div#content {
	margin: 10px;
}

div#footer {
}

.centered {
	text-align: center;
}

/* ツールバー */

div#toolbar {
	position: absolute;
	position: fixed;
	top: 0;
	left: 0;
	/*z-index: 1;*/
	width: 100%;
	margin: 0;
	padding: 1px;
	border-bottom: 3px double {$STYLE['sb_tool_border_color']};
	background: {$STYLE['sb_tool_bgcolor']} {$STYLE['sb_tool_background']};
}

div#toolbar table td {
	border: dotted 1px {$STYLE['sb_tool_sepa_color']};
	white-space: nowrap;
}

div#toolbar a:link { color: {$STYLE['sb_tool_acolor']}; }
div#toolbar a:visited { color: {$STYLE['sb_tool_acolor_v']}; }
div#toolbar a:hover { color: {$STYLE['sb_tool_acolor_h']}; }

td#toolbarStandard {
}

td#toolbarExtra {
}

td#toolbarSwitch {
	font-family: monospace;
}

a#toolbarSwitchA {
	/*color: #000;*/
}

a#toolbarSwitchB {
	/*color: #000;*/
	display: none;
}

/* 画像リスト */

table.list {
	margin: 10px 0px;
}

table.list th, table.list td {
	line-height: 120%;
	color: {$STYLE['respop_color']};
	font-size: {$STYLE['respop_fontsize']};
	background: {$STYLE['respop_bgcolor']} {$STYLE['respop_background']};
	border: {$STYLE['respop_b_style']} {$STYLE['respop_b_width']} {$STYLE['respop_b_color']};
	white-space: nowrap;
}

div.imginfo {
	margin: 2px;
}

span.rating {
	color: {$STYLE['respop_b_color']};
	cursor: pointer;
}

img.button {
	cursor: pointer;
}

/* 画像情報ポップアップ */
div#popUpContainer div.respopup {
	top: -10000px;
	/*overflow: auto;*/
}

div#popUpContainer div.respopup table {
	border-top: 1px solid #999;
	border-bottom: 1px solid #999;
	padding: 8px;
}

div#popUpContainer div.respopup td {
	line-height: 100%;
	padding: 3px;
	font-size: {$STYLE['infowin_fontsize']};
}

div#popUpContainer div.respopup td.tdleft {
	text-align: right;
}

EOSTYLE;

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
