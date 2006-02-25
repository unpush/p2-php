<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    rep2機能拡張パック - スレッド表示プリフィルタ
    
    SPMからのレスフィルタリングで使用
*/

require_once 'conf/conf.inc.php'; //基本設定読込
require_once (P2_LIBRARY_DIR . '/threadread.class.php');
require_once (P2_LIBRARY_DIR . '/showthread.class.php');
require_once (P2_LIBRARY_DIR . '/showthreadpc.class.php');

$_login->authorize(); // ユーザ認証

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
$res_filter = array();
$res_filter['field'] = $field;
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

if (isset($aThread->datlines[$resnum - 1])) {
    $ares = $aThread->datlines[$resnum - 1];
    $resar = $aThread->explodeDatLine($ares);
    $name = $resar[0];
    $mail = $resar[1];
    $date_id = $resar[2];
    $msg = $resar[3];

    $aShowThread = &new ShowThreadPc($aThread);
    $word = $aShowThread->getFilterTarget($ares, $resnum, $name, $mail, $date_id, $msg);
    if (strlen($word) == 0) {
        unset($word);
    } else {
        if ($field == 'date') {
            $date_part = explode(' ', trim($word));
            $word = $date_part[0];
        }
        $_REQUEST['word'] = $_GET['word'] = $word;
    }

    unset($ares, $resar, $name, $mail, $date_id, $msg, $aShowThread);
}

/**
 * read.phpに処理を渡す
 */
include ($_conf['read_php']);

?>
