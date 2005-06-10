<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 - スレッド表示スクリプト
    フレーム分割画面、右下部分
*/

require_once 'conf/conf.php';   //基本設定読込
require_once (P2_LIBRARY_DIR . '/thread.class.php');    //スレッドクラス読込
require_once (P2_LIBRARY_DIR . '/threadread.class.php');    //スレッドリードクラス読込
require_once (P2_LIBRARY_DIR . '/filectl.class.php');
require_once (P2_LIBRARY_DIR . '/ngabornctl.class.php');
require_once (P2_LIBRARY_DIR . '/showthread.class.php');    //HTML表示クラス

/*
$debug = 0;
if ($debug) {
    require_once 'Benchmark/Profiler.php';
    $prof = &new Benchmark_Profiler;
    $prof->start();
}
*/

authorize(); // ユーザ認証

//================================================================
// 変数
//================================================================

$newtime = date('gis'); // 同じリンクをクリックしても再読込しない仕様に対抗するダミークエリー
//$_today = date('y/m/d');

if ($_conf['ktai'] && isset($_GET['ktool_name']) && isset($_GET['ktool_value'])) {
    $ktv = (int)$_GET['ktool_value'];
    switch ($_GET['ktool_name']) {
        case 'goto':
            $_GET['ls'] = $ktv . '-' . ($ktv + $_conf['k_rnum_range']);
            break;
        case 'bkmk':
            $_GET['bkmk'] = $ktv;
            break;
        case 'copy_quote':
            //$_GET['quote'] = 1;
            $_GET['resnum'] = $ktv;
            $_GET['inyou'] = 1;
            if (!defined('P2_NO_SAVE_PACKET')) {
                define('P2_NO_SAVE_PACKET', 1);
            }
            include 'post_form.php';
            exit;
        case 'copy':
            $_GET['copy'] = $ktv;
            include 'read_copy_k.php';
            exit;
    }
}

//=================================================
// スレの指定
//=================================================
list($host, $bbs, $key, $ls) = P2Util::detectThread();

//=================================================
// レスフィルタ
//=================================================
$_conf['filtering'] = false;

if (isset($_REQUEST['word']))   { $word = $_REQUEST['word']; }
if (isset($_REQUEST['field']))  { $res_filter['field']  = $field = $_REQUEST['field']; }
if (isset($_REQUEST['match']))  { $res_filter['match']  = $_REQUEST['match']; }
if (isset($_REQUEST['method'])) { $res_filter['method'] = $_REQUEST['method']; }

if (isset($word) && strlen($word) > 0 &&
    !((!$_exconf['flex']['*'] || $res_filter['method'] == 'regex') && preg_match('/^\.+$/', $word))
) {

    // デフォルトオプション
    if (empty($res_filter['field']))  { $res_filter['field']  = 'hole'; }
    if (empty($res_filter['match']))  { $res_filter['match']  = 'on'; }
    if (empty($res_filter['method'])) { $res_filter['method'] = 'or'; }

    include_once (P2_LIBRARY_DIR . '/strctl.class.php');
    $word_fm = StrCtl::wordForMatch($word, $res_filter['method']);
    if (!preg_match('/[^. ]/', $word)) {
        $word = null;
    } else {
        $word = htmlspecialchars($word);
    }
    $_conf['filtering'] = true;
    if ($res_filter['method'] != 'just') {
        if (P2_MBREGEX_AVAILABLE == 1) {
            $words_fm = mb_split('\s+', $word_fm);
            $word_fm = mb_ereg_replace('\s+', '|', $word_fm);
        } else {
            $words_fm = preg_split('/\s+/u', $word_fm);
            $word_fm = preg_replace('/\s+/u', '|', $word_fm);
        }
    }
    if ($_conf['ktai']) {
        $page = (isset($_REQUEST['page'])) ? max(1, intval($_REQUEST['page'])) : 1;
        $filter_range = array();
        $filter_range['start'] = ($page - 1) * $_conf['k_rnum_range'] + 1;
        $filter_range['to'] = $filter_range['start'] + $_conf['k_rnum_range'] - 1;
    }
    $last_hit_resnum = 1;
} else {
    $word = null;
}

//=================================================
// フィルタ値保存
//=================================================
$cachefile = $_conf['pref_dir'] . '/p2_res_filter.txt';

// フィルタ指定がなければ前回保存を読み込む（フォームのデフォルト値で利用）
if (!isset($word) || strlen($word) == 0) {

    if (file_exists($cachefile) && ($res_filter_cont = file_get_contents($cachefile))) {
        $res_filter = unserialize($res_filter_cont);
    }

// フィルタ指定があれば
} else {

    // ボタンが押されていたなら、ファイルに設定を保存
    if (isset($_REQUEST['submit_filter'])) {    // !isset($_REQUEST['idpopup'])
        FileCtl::make_datafile($cachefile, $_conf['p2_perm']); // ファイルがなければ生成
        if ($res_filter) {
            $res_filter_cont = serialize($res_filter);
        }
        if ($res_filter_cont) {
            $fp = @fopen($cachefile, 'wb') or die("Error: $cachefile を更新できませんでした");
            @flock($fp, LOCK_EX);
            fputs($fp, $res_filter_cont);
            @flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

}

//=================================================
// あぼーん&NGワード設定読み込み
//=================================================
$GLOBALS['ngaborns'] = NgAbornCtl::loadNgAborns();

//==================================================================
// ■メイン
//==================================================================

if (!isset($aThread)) {
    $aThread = &new ThreadRead;
}

// lsのセット
if (!empty($ls)) {
    $aThread->ls = $ls;
}

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

// ==========================================================
// preview >>1
// ==========================================================

if (!empty($_GET['one'])) {
    $body = $aThread->previewOne();
    $ptitle_ht = htmlspecialchars($aThread->itaj).' / '.$aThread->ttitle_hd;
    include (P2_LIBRARY_DIR . '/read_header.inc.php');
    echo $body;
    include (P2_LIBRARY_DIR . '/read_footer.inc.php');
    return;
}

//===========================================================
// DATのダウンロード
//===========================================================
if (empty($_GET['offline'])) {
    if (!(isset($word) && strlen($word) > 0 && file_exists($aThread->keydat))) {
        $aThread->downloadDat();
    }
}

// ■DATを読み込み
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
if ($_conf['ktai']) {
    $before_respointer = $_conf['before_respointer_k'];
} else {
    $before_respointer = $_conf['before_respointer'];
}

// 取得済みなら
if ($aThread->isKitoku()) {

    //「新着レスの表示」の時は特別にちょっと前のレスから表示
    if (!empty($_GET['nt'])) {
        if (substr($aThread->ls, -1) == '-') {
            $n = $aThread->ls - $before_respointer;
            if ($n < 1) { $n = 1; }
            $aThread->ls = "$n-";
        }

    } elseif (!$aThread->ls) {
        $from_num = $aThread->readnum + 1 - $_conf['respointer'] - $before_respointer;
        if ($from_num < 1) {
            $from_num = 1;
        } elseif ($from_num > $aThread->rescount) {
            $from_num = $aThread->rescount - $_conf['respointer'] - $before_respointer;
        }
        $aThread->ls = "$from_num-";
    }

    if ($_exconf['bookmark']['*']) {
        $readhere_file = P2Util::datdirOfHost($aThread->host) . '/' . $aThread->bbs . '/p2_read_here.txt';
        $set_bookmark = false;
        if ($_conf['ktai']) {
            $set_bookmark = intval($_GET['bkmk']);
            if ($set_bookmark > 0) {
                $ls = $set_bookmark;
            } else {
                $set_bookmark = false;
            }
        }
        if (file_exists($readhere_file)) {
            $readhere_arr = @file($readhere_file);
            if ($set_bookmark) {
                $bookmark_data = $aThread->key . ':' . $set_bookmark . "\n";
                if ($readhere_arr) {
                    foreach ($readhere_arr as $value) {
                        $value = trim($value);
                        if ($value && !preg_match("/^{$aThread->key}:/", $value)) {
                            $bookmark_data .= $value . "\n";
                        }
                    }
                }
                if (FileCtl::file_write_contents($readhere_file, $bookmark_data) === FALSE) {
                    $_info_msg_ht .= "<p>p2 error: {$readhere_file} に書き込めませんでした。</p>";
                }
            } else {
                foreach ($readhere_arr as $value) {
                    list($rhKey, $rhNum) = explode(':', $value);
                    if ($aThread->key == $rhKey) {
                        $aThread->readhere = $rhNum;
                        break;
                    }
                }
            }
        } elseif ($set_bookmark) {
            $bookmark_data = $aThread->key . ':' . $set_bookmark . "\n";
            if (FileCtl::file_write_contents($readhere_file, $bookmark_data) === FALSE) {
                $_info_msg_ht .= "<p>p2 error: {$readhere_file} に書き込めませんでした。</p>";
            }
            $aThread->readhere = $set_bookmark;
        }
    }

    if ($_conf['ktai'] && (!strstr($aThread->ls, 'n'))) {
        $aThread->ls = $aThread->ls . 'n';
    }

// 未取得なら
} else {
    if (!$aThread->ls) { $aThread->ls = $_conf['get_new_res_l']; }
}

// フィルタリングの時は、all固定とする
if (isset($word) && strlen($word) > 0) {
    $aThread->ls = 'all';
}

$aThread->lsToPoint();

//===============================================================
// ■プリント
//===============================================================
$ptitle_ht = htmlspecialchars($aThread->itaj).' / '.$aThread->ttitle_hd;

if ($_conf['ktai']) {

    include_once (P2_LIBRARY_DIR . '/showthreadk.class.php'); // HTML表示クラス
    $aShowThread = &new ShowThreadK($aThread);

    if (isset($word) && strlen($word) > 0) {
        $filter_hits = 0;
    } else {
        $filter_hits = NULL;
    }

    // ■ヘッダプリント
    include (P2_LIBRARY_DIR . '/read_header_k.inc.php');

    // ■ボディプリント
    $aShowThread->datToHtml();

    // ■フッタプリント
    if ($filter_hits !== NULL) {
        resetReadNaviFooterK();
    }
    include (P2_LIBRARY_DIR . '/read_footer_k.inc.php');

} else {

    // ■ヘッダ 表示
    include (P2_LIBRARY_DIR . '/read_header.inc.php');
    flush();

    //===========================================================
    // ローカルDatを変換してHTML表示
    //===========================================================
    // レスがあり、検索指定があれば
    if (isset($word) && strlen($word) > 0 && $aThread->rescount) {

        $all = $aThread->rescount;

        $GLOBALS['filter_hits'] = 0;

        $hits_line = "<p><b id=\"filerstart\">{$all}レス中 <span id=\"searching\">{$GLOBALS['filter_hits']}</span>レスがヒット</b></p>";
        echo <<<EOP
<script type="text/javascript">
<!--
document.writeln('{$hits_line}');
var searching = document.getElementById('searching');

function filterCount(n){
    if (searching) {
        searching.innerHTML = n;
    }
}
-->
</script>
EOP;

    }

    //$debug && $prof->enterSection('datToHtml');

    if ($aThread->rescount) {

        include_once (P2_LIBRARY_DIR . '/showthreadpc.class.php'); // HTML表示クラス
        $aShowThread = &new ShowThreadPc($aThread);

        // async
        if ($_exconf['etc']['async_respop']) {
            $aShowThread->printASyncObjJs();
        }
        // SPM
        if ($_exconf['spm']['*']) {
            $aShowThread->printSPMObjJs();
        }

        $res1 = $aShowThread->quoteOne(); // >>1ポップアップ用
        echo $res1['q'];

        $aShowThread->datToHtml();
    }

    //$debug && $prof->leaveSection('datToHtml');

    // フィルタ結果を表示
    if (isset($word) && strlen($word) > 0 && $aThread->rescount) {
        echo <<<EOP
<script type="text/javascript">
<!--
var filerstart = document.getElementById('filerstart');
if (filerstart) {
    filerstart.style.backgroundColor = 'yellow';
    filerstart.style.fontWeight = 'bold';\n
EOP;
        if (isset($GLOBALS['MYSTYLE']['base']['.filtering'])) {
            // set my-filter-style
            foreach ($GLOBALS['MYSTYLE']['base']['.filtering'] as $_mfs_prop => $_mfs_value) {
                $_mfs_prop = strtolower($_mfs_prop);
                $_mfs_value = addslashes($_mfs_value);
                if (strstr('-', $_mfs_prop)) {
                    $_prop_parts = explode('-', $_mfs_prop);
                    $_mfs_prop = array_shift($_prop_parts);
                    $_mfs_prop .= implode('', array_map('ucfirst', $_prop_parts));
                }
                echo "\tfilerstart.style.{$_mfs_prop} = '{$_mfs_value}';\n";
            }
        }
        echo <<<EOP
}
-->
</script>\n
EOP;
        if ($GLOBALS['filter_hits'] > 5) {
            echo "<p><b class=\"filtering\">{$all}レス中 {$GLOBALS['filter_hits']}レスがヒット</b></p>\n";
        }
    }

    //$debug && $prof->stop();
    //$debug && $prof->display();

    // ■フッタ 表示
    include (P2_LIBRARY_DIR . '/read_footer.inc.php');

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
                 $aThread->readnum, $data[6], $data[7], $data[8], $newline);
    $s = implode('<>', $sar);
    P2Util::recKeyIdx($aThread->keyidx, $s); // key.idxに記録
}

//===========================================================
// 履歴を記録
//===========================================================
$newdata_ar = array($aThread->ttitle, $aThread->key, $data[2], '', '', $aThread->readnum,
                    $data[6], $data[7], $data[8], $newline, $aThread->host, $aThread->bbs);
$newdata = implode('<>', $newdata_ar);
P2Util::recRecent($newdata);

// ■NGあぼーんを記録
NgAbornCtl::saveNgAborns();

?>
