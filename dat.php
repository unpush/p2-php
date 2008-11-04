<?php
/**
 * p2 DATをダウンロードする
 */
 
require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/thread.class.php';

$_login->authorize(); // ユーザ認証

//================================================
// 変数設定
//================================================
isset($_GET['host']) and $host = $_GET['host']; // "pc.2ch.net"
isset($_GET['bbs'])  and $bbs  = $_GET['bbs'];  // "php"
isset($_GET['key'])  and $key  = $_GET['key'];  // "1022999539"

// 以下どれか一つがなくてもダメ出し
if (empty($host) || !isset($bbs) || !isset($key)) {
    p2die('引数が正しくありません');
}

//================================================
// メイン処理
//================================================
$aThread = new Thread;

// hostを分解してdatファイルのパスを求める
$aThread->setThreadPathInfo($host, $bbs, $key);

if (!file_exists($aThread->keydat)) {
    p2die("ご指定のDATはありませんでした");
}

//================================================
// レスポンス
//================================================
header('Content-Type: text/plain; name=' . basename($aThread->keydat));
header("Content-Disposition: attachment; filename=" . basename($aThread->keydat));
readfile($aThread->keydat);

exit;

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
