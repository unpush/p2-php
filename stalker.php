<?php
/*
IDストーカーにURLを渡す。

引数:
host:$hostを渡す (任意)
bbs:$bbsを渡す
id:IDを渡す
img:何か含まれていれば画像を表示
*/

include_once './conf/conf.inc.php';

$_login->authorize(); //ユーザ認証

require_once './plugin/stalker/stalker.class.php';

$stalker = new stalker();
$stalker->host = $_GET['host'];
$stalker->bbs  = $_GET['bbs'];
// 画像を表示する場合
if ($_GET['img']) {
    if ($stalker->isEnable()) {
        header("Content-Type: image/png");
        readfile('./plugin/stalker/stalker.png');
    } else {
        header("Content-Type: image/gif");
        readfile('./img/spacer.gif');
    }
    exit;
} else {
    if ($stalker->isEnable()) {
        $stalker->id = $_GET['id'];
        $_ime = new P2Ime();
        header('Location: ' . $_ime->through($stalker->getIDURL(), null, false));
    } else {
        P2Util::printSimpleHtml('この板は対応していません。');
    }
}
