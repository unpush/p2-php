<?php
/**
 * rep2expack - スレッド表示プリフィルタ
 *
 * SPMからのレスフィルタリングで使用
 */

require_once './conf/conf.inc.php';

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
$itaj = P2Util::getItaName($host, $bbs);
if (!$itaj) { $itaj = $bbs; }
$ttitle_name = UrlSafeBase64::decode($ttitle_en);
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
    if ($field == 'rres') {
        $_REQUEST['rf'] = array(
            'field' => ResFilter::FIELD_MESSAGE,
            'method' => ResFilter::METHOD_REGEX,
            'match' => ResFilter::MATCH_ON,
            'include' => ResFilter::INCLUDE_NONE,
        );
        $_REQUEST['rf']['word'] = ShowThread::getAnchorRegex(
            '%prefix%(.+%delimiter%)?' . $resnum . '(?!\\d|%range_delimiter%)'
        );
    } else {
        $params = array(
            'field' => $field,
            'method' => $_GET['method'],
            'match' => $_GET['match'],
            'include' => ResFilter::INCLUDE_NONE,
        );
        $resFilter = ResFilter::configure($params);
        $target = $resFilter->getTarget($ares, $resnum, $name, $mail, $date_id, $msg);
        $_REQUEST['rf'] = $params;
        if ($field == 'date') {
            $date_part = explode(' ', trim($target));
            $_REQUEST['rf']['word'] = $date_part[0];
        } else {
            $_REQUEST['rf']['word'] = $target;
        }
    }

    unset($ares, $resar, $name, $mail, $date_id, $msg, $params, $target, $aShowThread);
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
