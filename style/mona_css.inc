<?php
/**
 * rep2 - スタイル設定
 * for アクティブモナー
 */

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
