<?php
/**
 * rep2 - スタイル設定
 * for info.php 情報ウィンドウ
 */

if ($STYLE['a_underline_none'] == 2) {
    $thre_title_underline_css = 'a.thre_title { text-decoration:none; }';
} else {
    $thre_title_underline_css = '';
}

echo <<<EOSTYLE

.thre_title{
	color:{$STYLE['read_thread_title_color']};
}
{$thre_title_underline_css}

.info-msg{
	font-size:{$STYLE['infowin_fontsize']};
}

table{
	border:solid #999;
	border-width:1px 0px;
	padding:12px;
}
td{
	line-height:100%;
	padding:3px;
	font-size:{$STYLE['infowin_fontsize']};
}
td.tdleft{
	text-align:right;
	color:#14a;
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
