<?php
/*
みみずんID検索にURLを渡す。

引数:
host:$hostを渡す (任意)
bbs:$bbsを渡す
id:IDを渡す
img:何か含まれていれば画像を表示
*/

include_once './conf/conf.inc.php';

$_login->authorize(); //ユーザ認証

require_once './plugin/mimizun/mimizun.class.php';

$mimizun = new mimizun();
$mimizun->host = $_GET['host'];
$mimizun->bbs  = $_GET['bbs'];

// 画像を表示する場合
if ($_GET['img']) {
    if ($mimizun->isEnable()) {
        header("Content-Type: image/png");
        readfile('./plugin/mimizun/mimizun.png');
    } else {
        header("Content-Type: image/gif");
        readfile('./img/spacer.gif');
    }
    exit;
} else {
    if ($mimizun->isEnable()) {
        $mimizun->id = $_GET['id'];
        header('Location: ' . P2Util::throughIme($mimizun->getIDURL()));
    } else {
        P2Util::printSimpleHtml('この板は対応していません。');
    }
}
