<?php
/**
 * rep2 - スタイル設定
 * for 書き込みプレビュー
 */

if (!isset($STYLE['respop_bgcolor']))	{ $STYLE['respop_bgcolor'] = '#ffffcc'; }
if (!isset($STYLE['respop_background'])) { $STYLE['respop_background'] = ''; }
$dp_empty_background = "background:{$STYLE['respop_bgcolor']} {$STYLE['respop_background']};";

echo <<<EOSTYLE

#dpreview {
	display: none;
	margin: 0.5em;
	padding: 0.5em;
	line-height: 130%;
	font-size: {$STYLE['read_fontsize']};
	color: {$STYLE['read_color']};
}

#original_msg legend, #dpreview legend {
	padding: 0px;
	line-height: 100%;
	font-size: {$STYLE['fontsize']};
	color: {$STYLE['textcolor']};
}

.prvw_resnum {
	color: {$STYLE['read_newres_color']};
	text-decoration: none;
}

.prvw_name {
	color: {$STYLE['read_name_color']};
}

.prvw_mail {
	color: {$STYLE['read_mail_color']};
}

.prvw_msg {
	white-space: normal;
	line-height: 130%;
	font-family: "{$STYLE['fontfamily']}";
	font-size: {$STYLE['read_fontsize']};
}

.prvw_msg_pre {
	white-space: pre;
	line-height: 115%;
	font-family: "{$STYLE['fontfamily']}";
	font-size: {$STYLE['read_fontsize']};
}

.prvw_msg_mona {
	white-space: nowrap;
	line-height: 100%;
	font-family: "{$_conf['expack.am.fontfamily']}";
	font-size: {$_conf['expack.am.fontsize']};
}

.prvw_msg_pre_mona {
	white-space: pre;
	line-height: 100%;
	font-family: "{$_conf['expack.am.fontfamily']}";
	font-size: {$_conf['expack.am.fontsize']};
}

#orig_msg, #dp_msg {
	margin-left: 25px;
}

#dp_empty {
	display: none;
	margin-left: 25px;
	font-style: italic;
	{$dp_empty_background}
}

#dp_msg_deprecated {
	width: 97%;
	margin-left: 3%;
	overflow: auto; /* W3C */
	overflow-x: auto; /* IE */
	overflow-y: auto; /* IE */
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
