<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2機能拡張パック - スレッド表示プリフィルタ
// SPMからのレスフィルタリングで使用
// *.phpからこのファイル名を検索して見つからなかったからといって削除してはいけない

require_once 'conf/conf.php'; //基本設定読込
require_once (P2_LIBRARY_DIR . '/threadread.class.php');
require_once (P2_LIBRARY_DIR . '/showthread.class.php');
require_once (P2_LIBRARY_DIR . '/showthreadpc.class.php');

authorize(); // ユーザ認証

/**
 * 変数の設定
 */
$host = $_GET['host'];
$bbs  = $_GET['bbs'];
$key  = $_GET['key'];
$rc   = $_GET['rc'];
$ttitle_en = $_GET['ttitle_en'];
$resnum = $_GET['resnum'];
$field  = $_GET['field'];
if (isset($_GET['word'])) {
	unset($_GET['word']);
}
$itaj = P2Util::getItaName($host, $bbs);
if (!$itaj) { $itaj = $bbs; }
$ttitle_name = base64_decode($ttitle_en);
$popup_filter = 1;

/**
 * 対象レスの処理
 */
$aThread = &new ThreadRead;
$aThread->setThreadPathInfo($host, $bbs, $key);
$aThread->readDat($aThread->keydat);
$aShowThread = &new ShowThreadPc($aThread);
if (isset($aShowThread->pDatLines[$resnum])) {
	$pdl = $aShowThread->pDatLines[$resnum];
	switch ($field) {
		case 'name':
			$word = strip_tags($pdl['name']);
			break;
		case 'mail':
			$word = strip_tags($pdl['mail']);
			break;
		case 'date':
			$word = $pdl['p_dateid']['date'];
			break;
		/*case 'epoch':
			$word = (isset($pdl['p_dateid']['epoch'] && $pdl['p_dateid']['epoch'] != -1) ? $pdl['p_dateid']['epoch'] : '';
			break;*/
		case 'id':
			$word = isset($pdl['p_dateid']['idid']) ? $pdl['p_dateid']['idid'] : '';
			break;
		/*case 'beid':
			$word = isset($pdl['p_dateid']['beid']) ? $pdl['p_dateid']['beid'] : '';
			break;*/
		case 'belv':
			$word = isset($pdl['p_dateid']['belv']) ? $pdl['p_dateid']['belv'] : '';
			break;
		default:
			$word = null;
	}
	unset($pdl);
}
unset($aShowThread);

/**
 * read.phpに処理を渡す
 */
include ($_conf['read_php']);

?>
