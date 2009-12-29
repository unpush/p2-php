<?php
/**
 * rep2 - スタイル設定
 * for ナロー・ツールバー
 */

echo <<<EOSTYLE

table.toolbar {
	display: block;
	height: auto !important;
}

table.toolbar > tbody,
table.toolbar > tbody > tr,
table.toolbar > tr {
	display: block;
}

table.toolbar > tbody > tr > th, table.toolbar > tr > th,
table.toolbar > tbody > tr > td, table.toolbar > tr > td {
	display: inline-block;
}

span.param {
	display: inline-block;
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
