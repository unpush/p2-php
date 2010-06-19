<?php
/*
必死チェッカーにURLを渡す。

引数:
host:$hostを渡す (任意)
bbs:$bbsを渡す
date:yyyymmdd形式で日付を渡す
id:IDを渡す
img:何か含まれていれば画像を表示

使用例:
置換日付で
Match=(.*?(\d{4})/(\d{2})/(\d{2}).*)
Replace=$1<a href="hissi.php?bbs=$bbs&date=$2$3$4&id=$id" target="_blank"><img src="hissi.php?img=1&bbs=$bbs" height=12px></a>
とすれば、必死チェッカーに対応してる板では画像が表示され、そうでない板では表示されない。
*/

include_once './conf/conf.inc.php';

$_login->authorize(); //ユーザ認証

require_once './plugin/hissi/hissi.class.php';

$hissi = new hissi();
$hissi->host = $_GET['host'];
$hissi->bbs  = $_GET['bbs'];

// 画像を表示する場合
if ($_GET['img']) {
    if ($hissi->isEnable()) {
        header("Content-Type: image/png");
        readfile('./plugin/hissi/hissi.png');
    } else {
        header("Content-Type: image/gif");
        readfile('./img/spacer.gif');
    }
    exit;
} else {
    if ($hissi->isEnable()) {
        $hissi->date = $_GET['date'];
        $hissi->id   = $_GET['id'];
        $_ime = new P2Ime();
        header('Location: ' . $_ime->through($hissi->getIDURL(), null, false));
    } else {
        P2Util::printSimpleHtml('この板は対応していません。');
    }
}
