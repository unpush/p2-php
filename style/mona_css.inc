<?php
// p2 ースタイル設定
// for アクティブモナー
// vim: set fileencoding=cp932 ai et ts=4 sw=0 sts=0 fdm=marker:
// mi: charset=Shift_JIS

echo <<<EOSTYLE

/* スイッチ */
span.aMonaSW {
	cursor: pointer;
}

/* アクティブモナー:自動AAスタイル適用 */
.AutoMona {
	font-family: "{$_exconf['aMona']['aafont']}";
	font-size: {$_exconf['aMona']['auto_fontsize']};
	line-height: 100%;
	white-space: nowrap;
}

/* アクティブモナー:AAスタイル適用 */
.ActiveMona {
	font-family: "{$_exconf['aMona']['aafont']}";
	line-height: 100%;
	white-space: nowrap;
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

EOSTYLE;

// スタイルの上書き
if (isset($MYSTYLE) && is_array($MYSTYLE)) {
	include_once (P2_STYLE_DIR . '/mystyle_css.php');
	$_mystylename = str_replace('_css.inc', '', basename(__FILE__));
	if (isset($MYSTYLE[$_mystylename])) {
		disp_mystyle($_mystylename);
	}
}

?>
