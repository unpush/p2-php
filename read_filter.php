<?php
/**
 * rep2expack - スレッド表示プリフィルタ
 *
 * SPMからのレスフィルタリングで使用
 */

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/ThreadRead.php';
require_once P2_LIB_DIR . '/ShowThreadPc.php';

$_login->authorize(); // ユーザ認証

/**
 * 変数の設定
 */
$host = $_GET['host'];
$bbs  = $_GET['bbs'];
$key  = $_GET['key'];
$rc   = $_GET['rescount'];
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
$aThread = new ThreadRead;
$aThread->setThreadPathInfo($host, $bbs, $key);
$aThread->readDat($aThread->keydat);

if (isset($aThread->datlines[$resnum - 1])) {
    $ares = $aThread->datlines[$resnum - 1];
    $resar = $aThread->explodeDatLine($ares);
    $name = $resar[0];
    $mail = $resar[1];
    $date_id = $resar[2];
    $msg = $resar[3];

    $aShowThread = new ShowThreadPc($aThread);
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

// read.phpに処理を渡す
include P2_BASE_DIR . '/read.php';

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
