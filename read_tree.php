<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    expack - スレッドをツリー表示する
    ツリー表示以外のルーチンはread.phpから拝借
*/

require_once 'conf/conf.php';
require_once (P2_LIBRARY_DIR . '/thread.class.php');    //スレッドクラス読込
require_once (P2_LIBRARY_DIR . '/threadread.class.php');    //スレッドリードクラス読込
require_once (P2_LIBRARY_DIR . '/filectl.class.php');
require_once (P2_LIBRARY_DIR . '/ngabornctl.class.php');
require_once (P2_LIBRARY_DIR . '/showthread.class.php');    //HTML表示クラス
require_once (P2_LIBRARY_DIR . '/showthreadpc.class.php');  //HTML表示クラス
require_once (P2_LIBRARY_DIR . '/showthreadtree.class.php'); // ツリー表示クラス

authorize(); // ユーザ認証

/*if (P2Util::isBrowserSafariGroup()) {
    $_conf['meta_charset_ht'] = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    mb_http_output('UTF-8');
    ob_start('mb_output_handler');
}*/

//================================================================
// 変数
//================================================================

$newtime = date('gis'); // 同じリンクをクリックしても再読込しない仕様に対抗するダミークエリー
//$_today = date('y/m/d');

$_info_msg_ht = '';

if (empty($_GET['host']) || empty($_GET['bbs']) || empty($_GET['key'])) {
    die("p2 - read_tree.php: スレッドの指定が変です。");
}

$host = $_GET['host'];
$bbs  = $_GET['bbs'];
$key  = $_GET['key'];


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


//==================================================================
// ■メイン
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
$aThread->ls = 'all';
$aThread->lsToPoint();


//===============================================================
// ■プリント
//===============================================================
$ptitle_ht = htmlspecialchars($aThread->itaj).' / '.$aThread->ttitle_hd;

//===========================================================
// ローカルDatを変換してHTML表示
//===========================================================
if ($aThread->rescount) {

    // ■ヘッダ 表示
    ob_start();
    include (P2_LIBRARY_DIR . '/read_header.inc.php');
    $header = ob_get_clean();

    $form_fmt = '<form id="header" method="GET" action="%s"';
    $form_search = sprintf($form_fmt, $_conf['read_php']);
    $form_replace = sprintf($form_fmt, $_SERVER['PHP_SELF']);

    echo str_replace($form_search, $form_replace, $header);
    flush();


    // レスがあり、検索指定があれば
    if (isset($word) && strlen($word) > 0) {
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


    // ■ツリー 表示
    $aShowThread = &new ShowThreadTree($aThread);

    // async
    $aShowThread->printASyncObjJs();
    // SPM
    if ($_exconf['spm']['*']) {
        $aShowThread->printSPMObjJs();
    }

    $res1 = $aShowThread->quoteOne(); // >>1ポップアップ用
    echo $res1['q'];

    $aShowThread->datToTree();


    // フィルタ結果を表示
    if (isset($word) && strlen($word) > 0) {
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


    // ■フッタ 表示
    include (P2_LIBRARY_DIR . '/read_footer.inc.php');
}

?>
