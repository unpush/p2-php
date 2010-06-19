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
        $date = ''; $id = '';
        if ($_GET['id'] && $_GET['date']) {
            $date = $_GET['date'];
            $id   = $_GET['id'];
        } else if ($_GET['key'] && $_GET['resnum']) {
            $id = ''; $date = '';
            $aThread = new ThreadRead;
            $aThread->setThreadPathInfo($_GET['host'], $_GET['bbs'], $_GET['key']);
            $aThread->readDat();
            $resnum = $_GET['resnum'];
            if (isset($aThread->datlines[$resnum - 1])) {
                $ares = $aThread->datlines[$resnum - 1];
                $resar = $aThread->explodeDatLine($ares);
                $m = array();
                if (preg_match('<(ID: ?| )([0-9A-Za-z/.+]{8,11})(?=[^0-9A-Za-z/.+]|$)>', $resar[2], $m)) {
                    $id = $m[2];
                }
                if (preg_match('<(?:\\D|\\b)(\\d{4})/(\\d{2})/(\\d{2})(?:\\D|\\b)>', $resar[2], $m)) {
                    $date = $m[1] . $m[2] . $m[3];
                }
            }
        }
        if ($date && $id) {
            $hissi->date = $date;
            $hissi->id   = $id;
            $_ime = new P2Ime();
            header('Location: ' . $_ime->through($hissi->getIDURL(), null, false));
        } else {
            P2Util::printSimpleHtml('何かが足りないようです。');
        }
    } else {
        P2Util::printSimpleHtml('この板は対応していません。');
    }
}


