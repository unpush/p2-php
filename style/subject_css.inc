<?php
/**
 * rep2 - スタイル設定
 * for subject.php スレッドリスト表示部分
 */

if ($STYLE['a_underline_none'] == 2) {
	$thre_title_underline_css = <<<EOSTYLE
a.thre_title, a.thre_title_new, a.thre_title_fav {
	text-decoration: none;
}
EOSTYLE;
} else {
	$thre_title_underline_css = '';
}

echo <<<EOSTYLE

body{
	margin: 0 0 8px 0;
	padding: 0;
	line-height: 130%;
	background: {$STYLE['sb_bgcolor']} {$STYLE['sb_background']};
	color: {$STYLE['sb_color']};
	font-size: {$STYLE['sb_fontsize']};
}

a:link    { color: {$STYLE['sb_acolor']}; }
a:visited { color: {$STYLE['sb_acolor_v']}; }
a:hover   { color: {$STYLE['sb_acolor_h']}; }

p {
	margin:8px
}
form {
	margin: 0;
	padding: 0;
}
form.check {
}
form#urlform {
	margin: 8px;
}

hr {
	width: 98%;
}

/* p2 info message */
.info{
	color: #777;
}

/* ツールバー */
table.toolbar {
	width: 100%;
	margin: 0;
	padding: 0;
	border: solid 1px {$STYLE['sb_tool_border_color']};
	background: {$STYLE['sb_tool_bgcolor']} {$STYLE['sb_tool_background']};
}

table.toolbar td {
	margin: 0;
	padding: 4px;
	white-space: nowrap;
	text-align: left;
	vertical-align: middle;
	color: {$STYLE['sb_tool_sepa_color']};
	font-size: {$STYLE['sb_fontsize']};
	line-height: 100%;
}

table.toolbar td.toolbar-title {
	padding-left: 12px;
}
table.toolbar td.toolbar-update {
}
table.toolbar td.toolbar-filter {
}
table.toolbar td.toolbar-edit {
}
table.toolbar td.toolbar-anchor {
	padding-right: 12px;
	text-align: right;
}

table.toolbar a{
	color: {$STYLE['sb_tool_acolor']};
}
table.toolbar a:visited {
	color: {$STYLE['sb_tool_acolor_v']};
}
table.toolbar a:hover {
	color: {$STYLE['sb_tool_acolor_h']};
}

/* スレッドリスト */
table.threadlist {
	width: 100%;
	margin: 0;
	padding: 0;
}

table.threadlist td, table.threadlist th {
	margin: 0;
	padding: 4px;
	white-space: nowrap;
	text-align: left;
	font-size: {$STYLE['sb_fontsize']};
	line-height: 100%;
}

table.threadlist tr.tableheader th {
	font-family: "{$STYLE['fontfamily']}";
	font-weight: normal;
	background: {$STYLE['sb_th_bgcolor']} {$STYLE['sb_th_background']};
}

/* 板の名前 */
.itatitle{
	display: inline;
	margin: 0;
	padding: 0;
	font-size: 14px;
	color: {$STYLE['sb_tool_color']};
}
a.aitatitle:link    { color: {$STYLE['sb_tool_acolor']}; }
a.aitatitle:visited { color: {$STYLE['sb_tool_acolor_v']}; }
a.aitatitle:hover   { color: {$STYLE['sb_tool_acolor_h']}; }

/* 並替 */
a.narabi { margin: 4px 2px 4px 8px; }
a.narabi:link    { color: {$STYLE['sb_tool_acolor']}; }
a.narabi:visited { color: {$STYLE['sb_tool_acolor_v']}; }
a.narabi:hover   { color: {$STYLE['sb_tool_acolor_h']}; }

/* 新着まとめ読み */
a.matome:link    { color: {$STYLE['sb_tool_acolor']}; }
a.matome:visited { color: {$STYLE['sb_tool_acolor_v']}; }
a.matome:hover   { color: {$STYLE['sb_tool_acolor_h']}; }

/* 新着まとめ読み 新着レス数 */
span.matome_num {
	color: {$STYLE['sb_tool_newres_color']};
}

/* subject更新時間表示 */
.time {
	margin: 4px 8px;
	color: {$STYLE['sb_tool_color']}
}

/* ツールバーの▼▲ */
a.toolanchor:link    { color: {$STYLE['sb_tool_acolor']}; }
a.toolanchor:visited { color: {$STYLE['sb_tool_acolor_v']}; }
a.toolanchor:hover   { color: {$STYLE['sb_tool_acolor_h']}; }

/* 現在のソート形式 */
a.now_sort:link    { color: {$STYLE['sb_now_sort_color']}; }
a.now_sort:visited { color: {$STYLE['sb_now_sort_color']}; }
a.now_sort:hover   { color: {$STYLE['sb_acolor_h']}; }

/* 現在のソート形式 (逆順) */
a.now_sort.rsort { text-decoration: underline overline; }

/* スレタイトル */
a.thre_title:link    { color: {$STYLE['thre_title_color']}; }
a.thre_title:visited { color: {$STYLE['thre_title_color_v']}; }
a.thre_title:hover   { color: {$STYLE['thre_title_color_h']}; }

/* スレタイトル 新規 */
a.thre_title_new:link    { color: {$STYLE['sb_thre_title_new_color']}; }
a.thre_title_new:visited { color: {$STYLE['thre_title_color_v']}; }
a.thre_title_new:hover   { color: {$STYLE['thre_title_color_h']}; }

/* スレタイトル お気にスレ */
/*
a.thre_title_fav:link    { color: #369; } 
a.thre_title_fav:visited { color: #369; }
a.thre_title_fav:hover   { color: {$STYLE['thre_title_color_h']}; }
*/

{$thre_title_underline_css}

/* 元スレリンク */
a.moto_thre { margin-right: 0.5em; }

/* スレ一覧の番号 */
a.info { color: {$STYLE['sb_order_color']}; }
a.info:hover { color: {$STYLE['sb_tacolor_h']}; }

/* 新着レス数 */
a.un_a:link    { color: {$STYLE['sb_newres_color']}; }
a.un_a:visited { color: {$STYLE['sb_newres_color']}; }
a.un_a:hover   { color: {$STYLE['sb_acolor_h']}; }

/* 「-」（dat落ちのスレ） */
.un_n { color: #999; } 
a.un_n:link    { color: #999; }
a.un_n:visited { color: #999; }
a.un_n:hover   { color: {$STYLE['sb_acolor_h']}; }

/* 並び替え */
a.te:link    { color: #999; }
a.te:visited { color: #999; }
a.te:hover   { color: {$STYLE['sb_tacolor_h']}; }

/* r スレッドリスト 行 */
table.threadlist tr.r1{
	background: {$STYLE['sb_tbgcolor1']} {$STYLE['sb_tbackground1']};
}
table.threadlist tr.r2 {
	background: {$STYLE['sb_tbgcolor']} {$STYLE['sb_tbackground']};
}

/* t スレッドリスト テーブル欄 基本 */
table.threadlist td.t , table.threadlist th.t {
}

/* te スレッドリスト 並び替え欄 */
table.threadlist td.te, table.threadlist th.te {
	width: 70px;
}

/* tu スレッドリスト 新着レス数欄 */
table.threadlist td.tu, table.threadlist th.tu {
	width: 26px;
	text-align: right;
}

/* tn スレッドリストのレス数欄 */
table.threadlist td.tn, table.threadlist th.tn {
	width: 36px;
	color: {$STYLE['sb_ttcolor']};
}

/* tc スレッドリスト チェックボックス欄 */
table.threadlist td.tc, table.threadlist th.tc {
	width: 20px;
	padding: 0px 2px;
	text-align: right;
}

/* to スレッドリスト オーダー番号欄 */
table.threadlist td.to, table.threadlist th.to {
	width: 26px;
	text-align: right;
}

/* tl スレッドリスト タイトル名欄 */
table.threadlist td.tl, table.threadlist th.tl {
}

/* ti スレッドリスト すばやさ欄 */
table.threadlist td.ts, table.threadlist th.ts {
	text-align: right;
}

/* ti スレッドリスト 勢い欄 */
table.threadlist td.ti, table.threadlist th.ti {
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
