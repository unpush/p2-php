<?php
/*
    p2 - スレッド表示スクリプト - 新着まとめ読み
    フレーム分割画面、右下部分
*/

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/threadlist.class.php';
require_once P2_LIB_DIR . '/thread.class.php';
require_once P2_LIB_DIR . '/threadread.class.php';
require_once P2_LIB_DIR . '/ngabornctl.class.php';
require_once P2_LIB_DIR . '/read_new.inc.php';

$_login->authorize(); // ユーザ認証

// まとめよみのキャッシュ読み
if (!empty($_GET['cview'])) {
    $cnum = (isset($_GET['cnum'])) ? intval($_GET['cnum']) : NULL;
    if ($cont = getMatomeCache($cnum)) {
        echo $cont;
    } else {
        echo 'p2 error: 新着まとめ読みのキャッシュがないよ';
    }
    exit;
}

//==================================================================
// 変数
//==================================================================
if (isset($_conf['rnum_all_range']) and $_conf['rnum_all_range'] > 0) {
    $GLOBALS['rnum_all_range'] = $_conf['rnum_all_range'];
}

$sb_view = "shinchaku";
$newtime = date("gis");

$sid_q = defined('SID') ? '&amp;' . strip_tags(SID) : '';

//=================================================
// 板の指定
//=================================================
isset($_GET['host'])    and $host   = $_GET['host'];
isset($_POST['host'])   and $host   = $_POST['host'];
isset($_GET['bbs'])     and $bbs    = $_GET['bbs'];
isset($_POST['bbs'])    and $bbs    = $_POST['bbs'];
isset($_GET['spmode'])  and $spmode = $_GET['spmode'];
isset($_POST['spmode']) and $spmode = $_POST['spmode'];

if ((empty($host) || !isset($bbs)) && !isset($spmode)) {
    P2Util::printSimpleHtml('p2 error: 必要な引数が指定されていません');
    die;
}


// あぼーん&NGワード設定読み込み
$GLOBALS['ngaborns'] = NgAbornCtl::loadNgAborns();

//====================================================================
// メイン
//====================================================================

register_shutdown_function('saveMatomeCache');

$GLOBALS['read_new_html'] = '';
ob_start();

$aThreadList =& new ThreadList();

// 板とモードのセット===================================
$ta_keys = array();
if ($spmode) {
    if ($spmode == 'taborn' or $spmode == 'soko') {
        $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));
    }
    $aThreadList->setSpMode($spmode);
    
} else {
    $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));

    // スレッドあぼーんリスト読込
    $idx_host_dir = P2Util::idxDirOfHost($host);
    $taborn_file = $idx_host_dir."/".$bbs."/p2_threads_aborn.idx";
    
    $ta_num = 0;
    if ($tabornlines = @file($taborn_file)) {
        $ta_num = sizeOf($tabornlines);
        foreach ($tabornlines as $l) {
            $tarray = explode('<>', rtrim($l));
            $ta_keys[$tarray[1]] = true;
        }
    }
}

// ソースリスト読込
$lines = $aThreadList->readList();

// ページヘッダ表示 ===================================
$ptitle_hs = htmlspecialchars($aThreadList->ptitle, ENT_QUOTES);
$ptitle_ht = "{$ptitle_hs} の 新着まとめ読み";

if ($aThreadList->spmode) {
    $sb_ht = <<<EOP
        <a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}" target="subject">{$ptitle_hs}</a>
EOP;
} else {
    $sb_ht = <<<EOP
        <a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}" target="subject">{$ptitle_hs}</a>
EOP;
}

// require_once P2_LIB_DIR . '/read_header.inc.php';

echo $_conf['doctype'];
echo <<<EOHEADER
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle_ht}</title>
EOHEADER;

include_once './style/style_css.inc';
include_once './style/read_css.inc';

echo <<<EOHEADER
    <script type="text/javascript" src="js/basic.js?v=20061209"></script>
    <script type="text/javascript" src="js/respopup.js?v=20061206"></script>
    <script type="text/javascript" src="js/htmlpopup.js?v=20061206"></script>
    <script type="text/javascript" src="js/setfavjs.js?v=20061206"></script>
    <script type="text/javascript" src="js/delelog.js?v=20061206"></script>

    <script type="text/javascript" src="./js/yui-ext/yui.js"></script>
    <script type="text/javascript" src="./js/yui-ext/yui-ext-nogrid.js"></script>
    <link rel="stylesheet" type="text/css" href="./js/yui-ext/resources/css/resizable.css" />

EOHEADER;

echo <<<EOHEADER
    <script type="text/javascript">
    <!--
    gFade = false;
    gIsPageLoaded = false;
    addLoadEvent(function() {
        gIsPageLoaded = true;
        setWinTitle();
    });
    -->
    </script>\n
EOHEADER;

echo <<<EOP
</head>
<body id="read" onclick="hideHtmlPopUp(event);">
<div id="popUpContainer"></div>\n
EOP;

P2Util::printInfoHtml();

//echo $ptitle_ht . "<br>";

//==============================================================
// それぞれの行解析
//==============================================================

$linesize = sizeof($lines);

for ($x = 0; $x < $linesize ; $x++) {

    if (isset($GLOBALS['rnum_all_range']) and $GLOBALS['rnum_all_range'] <= 0) {
        break;
    }
    
    $l = $lines[$x];
    $aThread =& new ThreadRead();
    
    $aThread->torder = $x + 1;

    // データ読み込み
    // spmodeなら
    if ($aThreadList->spmode) {
        switch ($aThreadList->spmode) {
        case "recent": // 履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "res_hist": // 書き込み履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "fav": // お気に
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "taborn": // スレッドあぼーん
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->host = $aThreadList->host;
            $aThread->bbs = $aThreadList->bbs;
            break;
        case "palace": // スレの殿堂
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        }
    // subject (not spmode)の場合
    } else {
        $aThread->getThreadInfoFromSubjectTxtLine($l);
        $aThread->host = $aThreadList->host;
        $aThread->bbs = $aThreadList->bbs;
    }
    
    // hostもbbsも不明ならスキップ
    if (!($aThread->host && $aThread->bbs)) {
        unset($aThread);
        continue;
    }
    
    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
    
    // 既得スレッドデータをidxから取得
    $aThread->getThreadInfoFromIdx();
        
    // 新着のみ(for subject)
    if (!$aThreadList->spmode and $sb_view == "shinchaku" and !isset($GLOBALS['word'])) { 
        if ($aThread->unum < 1) {
            unset($aThread);
            continue;
        }
    }

    // スレッドあぼーんチェック
    if ($aThreadList->spmode != 'taborn' and !empty($ta_keys[$aThread->key])) { 
        unset($ta_keys[$aThread->key]);
        continue; // あぼーんスレはスキップ
    }

    // spmode(殿堂入りを除く)なら ====================================
    if ($aThreadList->spmode && $sb_view != "edit") { 
        
        // subject.txt が未DLなら落としてデータを配列に格納
        if (empty($subject_txts["$aThread->host/$aThread->bbs"])) {

            require_once P2_LIB_DIR . '/SubjectTxt.class.php';
            $aSubjectTxt =& new SubjectTxt($aThread->host, $aThread->bbs);

            $subject_txts["$aThread->host/$aThread->bbs"] = $aSubjectTxt->subject_lines;
        }
        
        // スレ情報取得
        if ($subject_txts["$aThread->host/$aThread->bbs"]) {
            foreach ($subject_txts["$aThread->host/$aThread->bbs"] as $l) {
                if (@preg_match("/^{$aThread->key}/", $l)) {
                    $aThread->getThreadInfoFromSubjectTxtLine($l); // subject.txt からスレ情報取得
                    break;
                }
            }
        }
        
        // 新着のみ(for spmode)
        if ($sb_view == "shinchaku" and !isset($GLOBALS['word'])) { 
            if ($aThread->unum < 1) {
                unset($aThread);
                continue;
            }
        }
    }
    
    if ($aThread->isonline) { $online_num++; } // 生存数set
    
    P2Util::printInfoHtml();
    
    $GLOBALS['read_new_html'] .= ob_get_flush();
    ob_start();
    
    if (($aThread->readnum < 1) || $aThread->unum) {
        readNew($aThread);
    } elseif ($aThread->diedat) {
        echo $aThread->getdat_error_msg_ht;
        echo "<hr>\n";
    }

    $GLOBALS['read_new_html'] .= ob_get_flush();
    ob_start();

    // リストに追加
    // $aThreadList->addThread($aThread);
    $aThreadList->num++;
    unset($aThread);
}

// $aThread =& new ThreadRead();

/**
 * スレッドの新着部分を読み込んで表示する
 */
function readNew(&$aThread)
{
    global $_conf, $_newthre_num, $STYLE;
    global $_info_msg_ht;

    $_newthre_num++;
    
    //==========================================================
    // idxの読み込み
    //==========================================================
    
    // hostを分解してidxファイルのパスを求める
    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
    
    // FileCtl::mkdirFor($aThread->keyidx); // 板ディレクトリが無ければ作る // この操作はおそらく不要

    $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
    if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }

    // idxファイルがあれば読み込む
    if (file_exists($aThread->keyidx)) {
        $lines = file($aThread->keyidx);
        $data = explode('<>', rtrim($lines[0]));
    }
    $aThread->getThreadInfoFromIdx();
    

    // DATのダウンロード
    if (!(isset($GLOBALS['word']) && file_exists($aThread->keydat))) {
        $aThread->downloadDat();
    }
    
    // DATを読み込み
    $aThread->readDat();
    $aThread->setTitleFromLocal(); // ローカルからタイトルを取得して設定
    
    // {{{ 表示レス番の範囲を設定
    
    // 取得済みなら
    if ($aThread->isKitoku()) {
        $from_num = $aThread->readnum +1 - $_conf['respointer'] - $_conf['before_respointer_new'];
        if ($from_num > $aThread->rescount) {
            $from_num = $aThread->rescount - $_conf['respointer'] - $_conf['before_respointer_new'];
        }
        if ($from_num < 1) {
            $from_num = 1;
        }

        //if (!$aThread->ls) {
            $aThread->ls = "$from_num-";
        //}
    }
    
    $aThread->lsToPoint();
    
    // }}}
    
    //==================================================================
    // ヘッダ 表示
    //==================================================================
    $motothre_url = $aThread->getMotoThread();
    
    $ttitle_en = base64_encode($aThread->ttitle);
    $ttitle_urlen = rawurlencode($ttitle_en);
    $ttitle_en_q ="&amp;ttitle_en=".$ttitle_urlen;
    $bbs_q = "&amp;bbs=".$aThread->bbs;
    $key_q = "&amp;key=".$aThread->key;
    $popup_q = "&amp;popup=1";
    
    // require_once P2_LIB_DIR . '/read_header.inc.php';
    
    $prev_thre_num = $_newthre_num - 1;
    $next_thre_num = $_newthre_num + 1;
    if ($prev_thre_num != 0) {
        $prev_thre_ht = "<a href=\"#ntt{$prev_thre_num}\">▲</a>";
    }
    $next_thre_ht = "<a href=\"#ntt{$next_thre_num}\">▼</a> ";
    
    P2Util::printInfoHtml();
    
    // ヘッダ部分HTML
    $read_header_ht = <<<EOP
    <table id="ntt{$_newthre_num}" class="toolbar" width="100%" style="padding:0px 10px 0px 0px;">
        <tr>
            <td align="left">
                <h3 class="thread_title">{$aThread->ttitle_hd}</h3>
            </td>
            <td align="right">
                {$prev_thre_ht}
                {$next_thre_ht}
            </td>
        </tr>
    </table>\n
EOP;
    
    //==================================================================
    // ローカルDatを読み込んでHTML表示
    //==================================================================
    $aThread->resrange['nofirst'] = true;
    $GLOBALS['newres_to_show_flag'] = false;
    if ($aThread->rescount) {
        // $aThread->datToHtml(); // dat を html に変換表示
        require_once P2_LIB_DIR . '/showthread.class.php';
        require_once P2_LIB_DIR . '/showthreadpc.class.php';
        $aShowThread =& new ShowThreadPc($aThread);

        $res1 = $aShowThread->quoteOne();
        $read_cont_ht = $res1['q'];
        
        $read_cont_ht .= $aShowThread->getDatToHtml();
        
        unset($aShowThread);
    }
    
    //==================================================================
    // フッタ 表示
    //==================================================================
    //include $read_footer_inc;
    
    //----------------------------------------------
    // $read_footer_navi_new  続きを読む 新着レスの表示
    $newtime = date("gis");  // リンクをクリックしても再読込しない仕様に対抗するダミークエリー
    
    $info_st = "情報";
    $delete_st = "削除";
    $prev_st = "前";
    $next_st = "次";

    $read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->rescount}-&amp;nt=$newtime#r{$aThread->rescount}\">新着レスの表示</a>";
    
    if (!empty($_conf['disable_res'])) {
        $dores_ht = <<<EOP
          <a href="{$motothre_url}" target="_blank">レス</a>
EOP;
    } else {
        $dores_ht = <<<EOP
        <a href="post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rescount={$aThread->rescount}{$ttitle_en_q}" target='_self' onClick="return !openSubWin('post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rescount={$aThread->rescount}{$ttitle_en_q}{$popup_q}&amp;from_read_new=1{$sid_q}',{$STYLE['post_pop_size']},0,0)">レス</a>
EOP;
    }
    $dores_ht = '<span style="white-space: nowrap;">' . $dores_ht . '</span>';
    
    // ツールバー部分HTML =======
    
    // お気にマーク設定
    $favmark    = !empty($aThread->fav) ? '★' : '+';
    $favdo      = !empty($aThread->fav) ? 0 : 1;
    $favtitle   = $favdo ? 'お気にスレに追加' : 'お気にスレから外す';
    $favdo_q    = '&amp;setfav='.$favdo;
    $itaj_hd    = htmlspecialchars($aThread->itaj, ENT_QUOTES);
    $similar_q  = '&amp;itaj_en=' . rawurlencode(base64_encode($aThread->itaj)) . '&amp;method=similar&amp;word=' . rawurlencode($aThread->ttitle_hc);
    
    $toolbar_right_ht = <<<EOTOOLBAR
            <a style="white-space: nowrap;" href="{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}" target="subject" title="板を開く">{$itaj_hd}</a>

            <a style="white-space: nowrap;" href="{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$similar_q}" target="subject" title="同じ板からタイトルが似ているスレッドを検索する">似スレ</a>

            <a style="white-space: nowrap;" href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$sid_q}" target="info" onClick="return !openSubWin('info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$popup_q}{$sid_q}',{$STYLE['info_pop_size']},1,0)" title="スレッド情報を表示">{$info_st}</a>

            <span class="favdo" style="white-space: nowrap;"><a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$favdo_q}{$sid_q}" target="info" onClick="return setFavJs('host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$sid_q}', '{$favdo}', {$STYLE['info_pop_size']}, 'read_new', this);" title="{$favtitle}">お気に{$favmark}</a></span>

            <span style="white-space: nowrap;"><a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}&amp;dele=true" target="info" onClick="return deleLog('host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$sid_q}', {$STYLE['info_pop_size']}, 'read_new', this);" title="ログを削除する">{$delete_st}</a></span>

<!--            <a style="white-space: nowrap;" href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}&amp;taborn=2" target="info" onClick="return !openSubWin('info.php?host={$aThread->host}{$bbs_q}&amp;key={$aThread->key}{$ttitle_en_q}&amp;popup=2&amp;taborn=2{$sid_q}',{$STYLE['info_pop_size']},0,0)" title="スレッドのあぼーん状態をトグルする">あぼん</a> -->

            <a style="white-space: nowrap;" href="{$motothre_url}" title="板サーバ上のオリジナルスレを表示">元スレ</a>
EOTOOLBAR;

    // レスのすばやさ
    $spd_ht = "";
    if ($spd_st = $aThread->getTimePerRes() and $spd_st != "-") {
        $spd_ht = '<span class="spd" style="white-space: nowrap;" title="すばやさ＝時間/レス">' . $spd_st .'</span>';
    }

    // フッタ部分HTML
    $read_footer_ht = <<<EOP
        <table class="toolbar" width="100%" style="padding:0px 10px 0px 0px;">
            <tr>
                <td align="left">
                    {$res1['body']} | <a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;offline=1&amp;rescount={$aThread->rescount}#r{$aThread->rescount}">{$aThread->ttitle_hd}</a> | {$dores_ht} {$spd_ht}
                </td>
                <td align="right">
                    {$toolbar_right_ht}
                </td>
                <td align="right">
                    <a href="#ntt{$_newthre_num}">▲</a>
                </td>
            </tr>
        </table>\n
EOP;

    // 透明あぼーんで表示がない場合はスキップ
    if ($GLOBALS['newres_to_show_flag']) {
        echo '<div style="width:100%;">' . "\n"; // ほぼIE ActiveXのGray()のためだけに囲ってある
        echo $read_header_ht;
        echo $read_cont_ht;
        echo $read_footer_ht;
        echo '</div>' . "\n\n";
        echo '<hr>' . "\n\n";
    }

    // key.idx の値設定
    if ($aThread->rescount) {
    
        $aThread->readnum = min($aThread->rescount, max(0, $data[5], $aThread->resrange['to']));
        
        $newline = $aThread->readnum + 1; // $newlineは廃止予定だが、旧互換用に念のため

        $sar = array($aThread->ttitle, $aThread->key, $data[2], $aThread->rescount, $aThread->modified,
                    $aThread->readnum, $data[6], $data[7], $data[8], $newline,
                    $data[10], $data[11], $aThread->datochiok);
        P2Util::recKeyIdx($aThread->keyidx, $sar); // key.idx に記録
    }

    unset($aThread);
}

//==================================================================
// ページフッタ表示
//==================================================================
$_newthre_num++;

if (!$aThreadList->num) {
    $GLOBALS['matome_naipo'] = TRUE;
    echo "新着レスはないぽ（´・ω・｀）";
    echo "<hr>";
}

if (!isset($GLOBALS['rnum_all_range']) or $GLOBALS['rnum_all_range'] > 0 or !empty($GLOBALS['limit_to_eq_to'])) {
    if (!empty($GLOBALS['limit_to_eq_to'])) {
        $str = '新着まとめ読みの更新or続き';
    } else {
        $str = '新着まとめ読みを更新';
    }
    echo <<<EOP
    <div id="ntt{$_newthre_num}" align="center">
        {$sb_ht} の <a href="{$_conf['read_new_php']}?host={$aThreadList->host}&bbs={$aThreadList->bbs}&spmode={$aThreadList->spmode}&nt={$newtime}">{$str}</a>
    </div>\n
EOP;
} else {
     echo <<<EOP
    <div id="ntt{$_newthre_num}" align="center">
        {$sb_ht} の <a href="{$_conf['read_new_php']}?host={$aThreadList->host}&bbs={$aThreadList->bbs}&spmode={$aThreadList->spmode}&nt={$newtime}&amp;norefresh=1">新着まとめ読みの続き</a>
    </div>\n
EOP;
}

echo '</body></html>';

$GLOBALS['read_new_html'] .= ob_get_flush();

// 後処理

// NGあぼーんを記録
NgAbornCtl::saveNgAborns();
