<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 ースタイル設定
// for info.php 情報ウィンドウ

if($STYLE['a_underline_none'] == 2){
    $thre_title_underline_css = 'a.thre_title{text-decoration:none;}';
}

$stylesheet .= <<<EOSTYLE

.thre_title{
    color:{$STYLE['read_thread_title_color']};
}
{$thre_title_underline_css}

.infomsg{
    font-size:{$STYLE['infowin_fontsize']};
}

table {
    border-top: 1px solid #999;
    border-bottom: 1px solid #999;
    padding: 8px;
}
td {
    line-height: 100%;
    padding: 3px;
    font-size: {$STYLE['infowin_fontsize']};
}
td.tdleft {
    text-align: right;
    color: #14a;
}

EOSTYLE;

// スタイルの上書き
if (isset($MYSTYLE) && is_array($MYSTYLE)) {
    include_once (P2_STYLE_DIR . '/mystyle_css.php');
    $stylename = str_replace('_css.php', '', basename(__FILE__));
    if (isset($MYSTYLE[$stylename])) {
        $stylesheet .= get_mystyle($stylename);
    }
}

?>
