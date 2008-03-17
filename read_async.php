<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    expack - スレッドをツリー表示する
    ツリー表示以外のルーチンはread.phpから拝借
*/

require_once 'conf/conf.inc.php';
require_once P2_LIBRARY_DIR . '/thread.class.php';    //スレッドクラス読込
require_once P2_LIBRARY_DIR . '/threadread.class.php';    //スレッドリードクラス読込
require_once P2_LIBRARY_DIR . '/filectl.class.php';
require_once P2_LIBRARY_DIR . '/ngabornctl.class.php';
require_once P2_LIBRARY_DIR . '/showthread.class.php';    //HTML表示クラス
require_once P2_LIBRARY_DIR . '/showthreadpc.class.php';  //HTML表示クラス
//require_once P2_LIBRARY_DIR . '/showthreadtree.class.php'; // ツリー表示クラス

$_login->authorize(); // ユーザ認証

//================================================================
// 変数
//================================================================

$newtime = date('gis'); // 同じリンクをクリックしても再読込しない仕様に対抗するダミークエリー
//$_today = date('y/m/d');

$_info_msg_ht = '';

if (empty($_GET['host']) || empty($_GET['bbs']) || empty($_GET['key']) || empty($_GET['ls'])) {
    die("p2 - read_async.php: レスの指定が変です。");
}

$host = $_GET['host'];
$bbs  = $_GET['bbs'];
$key  = $_GET['key'];
$mode = isset($_GET['q']) ? (int)$_GET['q'] : 0;

$_conf['ktai'] = FALSE;

//==================================================================
// メイン
//==================================================================
$aThread = &new ThreadRead;


//==========================================================
// idxの読み込み
//==========================================================

// hostを分解してidxファイルのパスを求める
if (!isset($aThread->keyidx)) {
    $aThread->setThreadPathInfo($host, $bbs, $key);
}

// 板ディレクトリが無ければ作る
//FileCtl::mkdir_for($aThread->keyidx);

$aThread->itaj = P2Util::getItaName($host, $bbs);
if (!$aThread->itaj) {
    $aThread->itaj = $aThread->bbs;
}

// idxファイルがあれば読み込む
if (is_readable($aThread->keyidx)) {
    $lines = @file($aThread->keyidx);
    $l = rtrim($lines[0]);
    $data = explode('<>', $l);
} else {
    $data = array_fill(0, 10, '');
}
$aThread->getThreadInfoFromIdx();


//===========================================================
// DATのダウンロード
//===========================================================
if (empty($_GET['offline'])) {
    $aThread->downloadDat();
}

// DATを読み込み
$aThread->readDat();

// オフライン指定でもログがなければ、改めて強制読み込み
if (empty($aThread->datlines) && !empty($_GET['offline'])) {
    $aThread->downloadDat();
    $aThread->readDat();
}


$aThread->setTitleFromLocal(); // タイトルを取得して設定


//===========================================================
// 表示レス番の範囲を設定
//===========================================================
$aThread->ls = $_GET['ls'];
$rn = (int)$aThread->ls; // string "256n" => integer 256
$rp = $rn - 1;
$aThread->lsToPoint();


//===============================================================
// プリント
//===============================================================
$ptitle_ht = htmlspecialchars($aThread->itaj, ENT_QUOTES).' / '.$aThread->ttitle_hd;

// {{{ HTTPヘッダとXML宣言

P2Util::header_nocache();
header('Content-Type: text/html; charset=Shift_JIS');

// }}}
// {{{ 本体生成

$node = 'ないぽ。';

if ($aThread->rescount) {

    //$aShowThread = &new ShowThreadTree($aThread);
    $aShowThread = &new ShowThreadPc($aThread);

    if (isset($aShowThread->thread->datlines[$rp])) {
        $ares = $aShowThread->thread->datlines[$rp];
        $part = $aShowThread->thread->explodeDatLine($ares);
        switch ($mode) {
            // レスポップアップ
            case 1:
                $node = $aShowThread->qRes($ares, $rn);
                break;
            // コピペ
            case 2:
                $node = $rn;
                $node .= ' ：' . strip_tags($part[0]);
                $node .= ' ：' . strip_tags($part[1]);
                $node .= ' ：' . strip_tags($part[2]) . "\n";
                $node .= trim(preg_replace('/ *<br.*?> */i', "\n", strip_tags($part[3], '<br>')));
                 break;
            default:
                $node = $aShowThread->transMsg($part[3], $rn);
        }
    }

}

// }}}
// {{{ 本体出力

if (P2Util::isBrowserSafariGroup()) {
    $node = P2Util::encodeResponseTextForSafari($node);
}
echo $node;

// }}}

// idx・履歴設定フラグがなければ終了
if (empty($_GET['rec'])) {
    exit;
}


// テレビ番組欄＠2chなどはログ・idx・履歴を保存しない
if (P2Util::isHostNoCacheData($aThread->host)) {
    //@unlink($aThread->keydat); // ThreadRead::readDat()で削除する
    exit;
}


//===========================================================
// idxの値を設定、記録
//===========================================================
if ($aThread->rescount) {
    $aThread->readnum = min($aThread->rescount, max(0, $data[5], $aThread->resrange['to']));

    $newline = $aThread->readnum + 1;   // $newlineは廃止予定だが、旧互換用に念のため

    $sar = array($aThread->ttitle, $aThread->key, $data[2], $aThread->rescount, $aThread->modified,
                 $aThread->readnum, $data[6], $data[7], $data[8], $newline,
                 $data[10], $data[11], $aThread->datochiok);
    P2Util::recKeyIdx($aThread->keyidx, $sar); // key.idxに記録
}

//===========================================================
// 履歴を記録
//===========================================================
$newdata_ar = array($aThread->ttitle, $aThread->key, $data[2], '', '', $aThread->readnum,
                    $data[6], $data[7], $data[8], $newline, $aThread->host, $aThread->bbs);
$newdata = implode('<>', $newdata_ar);
P2Util::recRecent($newdata);

// NGあぼーんを記録
NgAbornCtl::saveNgAborns();
