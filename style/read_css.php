<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 ースタイル設定
// for read.php

if($STYLE['fontfamily_bold']){
    $stylesheet .= <<<EOP
    h3{font-weight: normal; font-family: "{$STYLE['fontfamily_bold']}";} /* スレッドタイトル*/\n
EOP;
}
$spm_before = '';
if ($_exconf['spm']['before'] !== '') {
    $spm_before = "\n\t.spm a:hover:before{ content: \"{$_exconf['spm']['before']}\"; }";
}
$spm_after = '';
if ($_exconf['spm']['after'] !== '') {
    $spm_after = "\n\t.spm a:hover:after{ content: \"{$_exconf['spm']['after']}\"; }";
}
if (!isset($SYTLE['live_b_width'])) { $SYTLE['live_b_width'] = $STYLE['respop_b_width']; }
if (!isset($SYTLE['live_b_color'])) { $SYTLE['live_b_color'] = $STYLE['respop_b_color']; }
if (!isset($SYTLE['live_b_style'])) { $SYTLE['live_b_style'] = $STYLE['respop_b_style']; }

$stylesheet .= <<<EOP
body{
    background: {$STYLE['read_bgcolor']} {$STYLE['read_background']};
    line-height: 130%;
    color: {$STYLE['read_color']};
}
body, td{
    font-size: {$STYLE['read_fontsize']};
}

a:link{color: {$STYLE['read_acolor']};}
a:visited{color: {$STYLE['read_acolor_v']};}
a:hover{color: {$STYLE['read_acolor_h']};}

i{font-style: normal;} /* 引用レス*/
dd.respopup{margin: 8px;} /* レスポップアップ*/

.thread_title{margin: 6px 0; line-height: 120%; font-size: 14pt; color: {$STYLE['read_thread_title_color']};}
.thre_title{color: {$STYLE['read_thread_title_color']};}
.name{color: {$STYLE['read_name_color']};} /* 投稿者の名前 */
.mail{color: {$STYLE['read_mail_color']};} /* 投稿者のmail */
.sage{color: {$STYLE['read_mail_sage_color']};} /* 投稿者のmail(sage) */
img.thumbnail{border: solid 1px;} /* 画像URLの先読みサムネイル*/

/* 新着レス番号（ここではカラーが新着確認の機能を持っているので特別にfontで
lib/カラー指定をしている。thread.class.php - transRes を参照)    */
/* .newres{color: {$STYLE['read_newres_color']};} ← よって現在は無効の設定 */

.onthefly{ /* on the fly */
    color: #0a0;
    border: 1px #0a0 solid;
    padding: 2px;
    font-size: 11px;
}
.ontheflyresorder{
    color: #0a0;
}

.ngword{color: {$STYLE['read_ngword']};}
.aborned{ font-size: 1px; }
.aborned span{ display: none; }

.respopup{     /* 引用レスポップアップ */
    position: absolute;
    visibility: hidden; /* 普段は隠しておく*/
    color: {$STYLE['respop_color']};
    font-size: {$STYLE['respop_fontsize']};
    line-height: 120%;
    padding: 8px;
    background: {$STYLE['respop_bgcolor']} {$STYLE['respop_background']};
    border: {$STYLE['respop_b_width']} {$STYLE['respop_b_color']} {$STYLE['respop_b_style']};
}

span.spd {    /* レスのすばやさ */
    font-size: 8pt;
    color: #777;
}

#iframespace{ /* HTMLポップアップスペース */
    position: absolute;
    z-index: 100;
    /*border: solid 1px;*/
}

#closebox{
    width: 14px;
    height: 14px;
    position: absolute;
    z-index: 101;
    border: solid 2px;
    padding: 1px;
    line-height: 100%;
    background-color: #ceddf7;
}

div#kakiko{
    display: none;
}

a.resnum:link, a.resnum:visited, a.resnum:hover, a.resnum:active{ /* レス番号 */
    color: {$STYLE['read_color']};
    text-decoration: none;
}

a.newres:link, a.newres:visited, a.newres:hover, a.newres:active{ /* 新着レス */
    color: {$STYLE['read_newres_color']};
    text-decoration: none;
}

table#readhere{
    margin: 2em auto 0px auto;
    background: {$STYLE['respop_bgcolor']} {$STYLE['respop_background']};
    border: {$STYLE['respop_b_width']} {$STYLE['respop_b_color']} {$STYLE['respop_b_style']};
}
table#readhere td{
    padding: 0.5em;
    text-align: center;
}

/* {{{ スマートポップアップメニュー */

.spm {
    position: absolute;
    visibility: hidden; /* 普段は隠しておく*/
    color: {$STYLE['respop_color']};
    font-size: {$STYLE['respop_fontsize']};
    line-height: 150%;
    width: 8.5em;
    margin: 0px;
    padding: 2px 4px;
    background: {$STYLE['respop_bgcolor']} {$STYLE['respop_background']};
    border: {$STYLE['respop_b_width']} {$STYLE['respop_b_color']} {$STYLE['respop_b_style']};
}

.spm p {    /* スマートポップアップメニュー：ヘッダ */
    white-space: nowrap;
    margin: 2px;
    padding: 0px;
    border-bottom: {$STYLE['respop_b_width']} {$STYLE['respop_b_color']} {$STYLE['respop_b_style']};
    vertical-align: middle;
}

.spm a {    /* スマートポップアップメニュー：リンク */
    display: block;
    white-space: nowrap;
    margin: 2px -4px;
    padding: 0px 4px;
    vertical-align: middle;
    text-decoration: none;
}
.spm a:hover {
    background: {$STYLE['read_bgcolor']} {$STYLE['read_background']};
}
{$spm_before}
{$spm_after}
.spm a.closemenu {
    text-align: right;
}

.spm a.closebox {    /* スマートポップアップメニュー：クローズボックス */
    position: absolute;
    top: 0;
    right: 0;
    width: 14px;
    height: 14px;
    margin: {$STYLE['respop_b_width']};
    padding: 1px;
    border: 1px {$STYLE['respop_b_color']} {$STYLE['respop_b_style']};
}

.spm div.spmMona {
    white-space: nowrap;
    margin: 2px;
    padding: 0px;
    vertical-align: middle;
}

.spm div.spmMona a {
    display: inline;
    color: {$STYLE['respop_color']};
    text-decoration: none;
}
.spm div.spmMona a:hover{ background: transparent none; }

.spmMonoSpace { white-space: pre; font-family: monospace; }

/* }}} */
/* {{{ 実況モード */

dd.jikkyo {
    margin: 2px;
    padding: 0px;
}

table.jikkyo_res {
    margin: 0px;
    padding: 0px;
    width: 100%;
    border-top-width: {$SYTLE['live_b_width']};
    border-top-color: {$SYTLE['live_b_color']};
    border-top-style: {$SYTLE['live_b_style']};
}

td.jikkyo_info {
    width: 5em;
    white-space: nowrap;
    text-align: left;
    vertical-align: top;
}

span.jikkyo_dateid {
    font-size: {$STYLE['respop_fontsize']};
}

td.jikkyo_all {
    width: 1em;
    text-align: center;
    vertical-align: top;
}

td.jikkyo_all a {
    text-decoration: none;
}

td.jikkyo_msg {
    text-align: left;
    vertical-align: top;
}

div.jikkyo_ryaku {
    text-align: right;
    font-size: {$STYLE['respop_fontsize']};
}

/* }}} */
/* {{{ ツリー */

span.node_marker { /* ┣ ┗ */
    color: {$STYLE['read_color']};
    cursor: pointer;
}


span.node_opener { /* + - */
    color: {$STYLE['read_newres_color']};
    cursor: pointer;
    font-family: monospace;
}

/* }}} */

EOP;

// スタイルの上書き
if (isset($MYSTYLE) && is_array($MYSTYLE)) {
    include_once (P2_STYLE_DIR . '/mystyle_css.php');
    $stylename = str_replace('_css.php', '', basename(__FILE__));
    if (isset($MYSTYLE[$stylename])) {
        $stylesheet .= get_mystyle($stylename);
    }
}

?>
