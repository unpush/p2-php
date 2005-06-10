<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 ースタイル設定
// for アクティブモナー

$am_aafont = "'" . str_replace(",", "','", $_exconf['aMona']['aafont']) . "'";

$stylesheet .= <<<EOP

/* スイッチ */
span.aMonaSW {
    cursor: pointer;
}

/* アクティブモナー:自動AAスタイル適用 */
.AutoMona {
    font-family: {$am_aafont};
    font-size: {$_exconf['aMona']['auto_fontsize']};
    line-height: 100%;
    white-space: pre;
}

/* アクティブモナー:AAスタイル適用 */
.ActiveMona {
    font-family: {$am_aafont};
    line-height: 100%;
    white-space: pre;
}

/* アクティブモナー:解除 */
.NoMona {
    font-family: "{$STYLE['fontfamily']}";
    font-size: {$STYLE['read_fontsize']};
    line-height: 130%;
    white-space: normal;
}

/* アクティブモナー:解除(レスポップアップ) */
.NoMonaQ {
    font-family: "{$STYLE['fontfamily']}";
    font-size: {$STYLE['respop_fontsize']};
    line-height: 120%;
    white-space: normal;
}

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
