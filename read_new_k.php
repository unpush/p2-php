<?php
/**
 * rep2 - スレッド表示スクリプト - 新着まとめ読み（携帯）
 * フレーム分割画面、右下部分
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

//==================================================================
// 変数
//==================================================================
$GLOBALS['rnum_all_range'] = $_conf['mobile.rnum_range'];

$sb_view = "shinchaku";
$newtime = date("gis");

$newthre_num = 0;
$online_num = 0;

//=================================================
// 板の指定
//=================================================
if (isset($_GET['host'])) { $host = $_GET['host']; }
if (isset($_POST['host'])) { $host = $_POST['host']; }
if (isset($_GET['bbs'])) { $bbs = $_GET['bbs']; }
if (isset($_POST['bbs'])) { $bbs = $_POST['bbs']; }
if (isset($_GET['spmode'])) { $spmode = $_GET['spmode']; }
if (isset($_POST['spmode'])) { $spmode = $_POST['spmode']; }

if ((!isset($host) || !isset($bbs)) && !isset($spmode)) {
    p2die('必要な引数が指定されていません');
}

// 未読数制限
if (isset($_POST['unum_limit'])) {
    $unum_limit = (int)$_POST['unum_limit'];
} elseif (isset($_GET['unum_limit'])) {
    $unum_limit = (int)$_GET['unum_limit'];
} else {
    $unum_limit = 0;
}

//=================================================
// あぼーん&NGワード設定読み込み
//=================================================
$GLOBALS['ngaborns'] = NgAbornCtl::loadNgAborns();

//====================================================================
// メイン
//====================================================================

$aThreadList = new ThreadList();

// 板とモードのセット ===================================
$ta_keys = array();
if ($spmode) {
    if ($spmode == "taborn" or $spmode == "soko") {
        $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));
    }
    $aThreadList->setSpMode($spmode);
} else {
    $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));

    // スレッドあぼーんリスト読込
    $taborn_file = $aThreadList->getIdxDir() . 'p2_threads_aborn.idx';
    if ($tabornlines = FileCtl::file_read_lines($taborn_file, FILE_IGNORE_NEW_LINES)) {
        $ta_num = sizeof($tabornlines);
        foreach ($tabornlines as $l) {
            $tarray = explode('<>', $l);
            $ta_keys[ $tarray[1] ] = true;
        }
    }
}

// ソースリスト読込
if ($spmode == 'merge_favita') {
    if ($_conf['expack.misc.multi_favs'] && !empty($_conf['m_favita_set'])) {
        $merged_faivta_read_idx = $_conf['pref_dir'] . '/p2_favita' . $_conf['m_favita_set'] . '_read.idx';
    } else {
        $merged_faivta_read_idx = $_conf['pref_dir'] . '/p2_favita_read.idx';
    }
    $lines = FileCtl::file_read_lines($merged_faivta_read_idx);
    if (is_array($lines)) {
        $have_merged_faivta_read_idx = true;
    } else {
        $have_merged_faivta_read_idx = false;
        $lines = $aThreadList->readList();
    }
} else {
    $lines = $aThreadList->readList();
}

// ページヘッダ表示 ===================================
$ptitle_hd = htmlspecialchars($aThreadList->ptitle, ENT_QUOTES);
$ptitle_ht = "{$ptitle_hd} の 新着まとめ読み";
$matomeCache = new MatomeCache($ptitle_hd, $_conf['matome_cache_max']);
ob_start();

// &amp;sb_view={$sb_view}
if ($aThreadList->spmode) {
    $sb_ht = <<<EOP
<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}{$_conf['k_at_a']}">{$ptitle_hd}</a>
EOP;
    $sb_ht_btm = <<<EOP
<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}{$_conf['k_at_a']}"{$_conf['k_accesskey_at']['up']}>{$_conf['k_accesskey_st']['up']}{$ptitle_hd}</a>
EOP;
} else {
    $sb_ht = <<<EOP
<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}{$_conf['k_at_a']}">{$ptitle_hd}</a>
EOP;
    $sb_ht_btm = <<<EOP
<a href="{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}{$_conf['k_at_a']}"{$_conf['k_accesskey_at']['up']}>{$_conf['k_accesskey_st']['up']}{$ptitle_hd}</a>
EOP;
}

// iPhone
if ($_conf['iphone']) {
    $_conf['extra_headers_ht'] .= <<<EOS
<script type="text/javascript" src="js/respopup_iphone.js?{$_conf['p2_version_id']}"></script>
EOS;
    // ImageCache2
    if ($_conf['expack.ic2.enabled']) {
        $_conf['extra_headers_ht'] .= <<<EOS
<link rel="stylesheet" type="text/css" href="css/ic2_iphone.css?{$_conf['p2_version_id']}">
<script type="text/javascript" src="js/json2.js?{$_conf['p2_version_id']}"></script>
<script type="text/javascript" src="js/ic2_iphone.js?{$_conf['p2_version_id']}"></script>
EOS;
    }
    // SPM
    if ($_conf['expack.spm.enabled']) {
        $_conf['extra_headers_ht'] .= <<<EOS
<script type="text/javascript" src="js/spm_iphone.js?{$_conf['p2_version_id']}"></script>
EOS;
    }
    // Limelight
    if ($_conf['expack.aas.enabled'] || $_conf['expack.ic2.enabled']) {
        $_conf['extra_headers_ht'] .= <<<EOS
<link rel="stylesheet" type="text/css" href="css/limelight.css?{$_conf['p2_version_id']}">
<script type="text/javascript" src="js/limelight.js?{$_conf['p2_version_id']}"></script>
<script type="text/javascript">
// <![CDATA[
window.addEventListener('DOMContentLoaded', function(event) {
    this.removeEventListener(event.type, arguments.callee, false);
    var limelight = new Limelight({ 'savable': true, 'title': true });
    limelight.bind();
    window._IRESPOPG.callbacks.push(function(container) {
        limelight.bind(null, container, true);
    });
}, false);
// ]]>
</script>
EOS;
    }
}

// ========================================================
// require_once P2_LIB_DIR . '/read_header.inc.php';

echo $_conf['doctype'];
echo <<<EOHEADER
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
{$_conf['extra_headers_ht']}
<title>{$ptitle_ht}</title>\n
EOHEADER;

echo "</head><body{$_conf['k_colors']}>";

echo <<<EOP
<div id="read_new_header">{$sb_ht}の新まとめ
<a class="button" id="above" name="above" href="#bottom"{$_conf['k_accesskey_at']['bottom']}>{$_conf['k_accesskey_st']['bottom']}▼</a></div>\n
EOP;

P2Util::printInfoHtml();

//==============================================================
// それぞれの行解析
//==============================================================

$linesize = sizeof($lines);
$subject_txts = array();

for ($x = 0; $x < $linesize; $x++) {

    if (isset($GLOBALS['rnum_all_range']) and $GLOBALS['rnum_all_range'] <= 0) {
        break;
    }

    $l = $lines[$x];
    $aThread = new ThreadRead();

    $aThread->torder = $x + 1;

    // データ読み込み
    if ($aThreadList->spmode) {
        switch ($aThreadList->spmode) {
        case "recent":    // 履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "res_hist":    // 書き込み履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "fav":    // お気に
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "taborn":    // スレッドあぼーん
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->host = $aThreadList->host;
            $aThread->bbs = $aThreadList->bbs;
            break;
        case "palace":    // 殿堂入り
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "merge_favita": // お気に板をマージ
            if ($have_merged_faivta_read_idx) {
                $aThread->getThreadInfoFromExtIdxLine($l);
            } else {
                $aThread->key = $l['key'];
                $aThread->setTtitle($l['ttitle']);
                $aThread->rescount = $l['rescount'];
                $aThread->host = $l['host'];
                $aThread->bbs = $l['bbs'];
                $aThread->torder = $l['torder'];
            }
            break;
        }
    // subject (not spmode)
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

    $subject_id = $aThread->host . '/' . $aThread->bbs;

    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
    $aThread->getThreadInfoFromIdx(); // 既得スレッドデータをidxから取得

    // 新着のみ(for subject) =========================================
    if (!$aThreadList->spmode && $sb_view == 'shinchaku' && empty($_GET['word'])) {
        if ($aThread->unum < 1) {
            unset($aThread);
            continue;
        }
    }

    // スレッドあぼーんチェック =====================================
    if ($aThreadList->spmode != "taborn" && !empty($ta_keys[$aThread->key])) {
        unset($ta_keys[$aThread->key]);
        continue; // あぼーんスレはスキップ
    }

    // spmode(殿堂入りを除く)なら ====================================
    if ($aThreadList->spmode && $sb_view != "edit") {

        // subject.txtが未DLなら落としてデータを配列に格納
        if (empty($subject_txts[$subject_id])) {
            $aSubjectTxt = new SubjectTxt($aThread->host, $aThread->bbs);

            $subject_txts[$subject_id] = $aSubjectTxt->subject_lines;
        }

        // スレ情報取得 =============================
        if (!empty($subject_txts[$subject_id])) {
            $thread_key = (string)$aThread->key;
            $thread_key_len = strlen($thread_key);
            foreach ($subject_txts[$subject_id] as $l) {
                if (strncmp($l, $thread_key, $thread_key_len) == 0) {
                    $aThread->getThreadInfoFromSubjectTxtLine($l); // subject.txt からスレ情報取得
                    break;
                }
            }
        }

        // 新着のみ(for spmode) ===============================
        if ($sb_view == "shinchaku" && empty($_GET['word'])) {
            if ($aThread->unum < 1) {
                unset($aThread);
                continue;
            }
        }
    }

    // 未読数制限
    if ($unum_limit > 0 && $aThread->unum >= $unum_limit) {
        unset($aThread);
        continue;
    }

    if ($aThread->isonline) { $online_num++; } // 生存数set

    P2Util::printInfoHtml();

    $matomeCache->concat(ob_get_flush());
    flush();
    ob_start();

    if (($aThread->readnum < 1) || $aThread->unum) {
        readNew($aThread);
        $matomeCache->addReadThread($aThread);
    } elseif ($aThread->diedat) {
        echo $aThread->getdat_error_msg_ht;
        echo "<hr>\n";
    }

    $matomeCache->concat(ob_get_flush());
    flush();
    ob_start();

    // リストに追加 ========================================
    // $aThreadList->addThread($aThread);
    $aThreadList->num++;
    unset($aThread);
}

//$aThread = new ThreadRead();

//======================================================================
// スレッドの新着部分を読み込んで表示する
//======================================================================
function readNew($aThread)
{
    global $_conf, $newthre_num, $STYLE;
    global $spmode, $word;

    $newthre_num++;

    //==========================================================
    // idxの読み込み
    //==========================================================

    //hostを分解してidxファイルのパスを求める
    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);

    //FileCtl::mkdirFor($aThread->keyidx); // 板ディレクトリが無ければ作る //この操作はおそらく不要

    $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
    if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }

    // idxファイルがあれば読み込む
    if ($lines = FileCtl::file_read_lines($aThread->keyidx, FILE_IGNORE_NEW_LINES)) {
        $data = explode('<>', $lines[0]);
    } else {
        $data = array_fill(0, 12, '');
    }
    $aThread->getThreadInfoFromIdx();

    //==================================================================
    // DATのダウンロード
    //==================================================================
    if (!($word and file_exists($aThread->keydat))) {
        $aThread->downloadDat();
    }

    // DATを読み込み
    $aThread->readDat();
    $aThread->setTitleFromLocal(); // ローカルからタイトルを取得して設定

    //===========================================================
    // 表示レス番の範囲を設定
    //===========================================================
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
            $aThread->ls = "{$from_num}-";
        //}
    }

    $aThread->lsToPoint();

    //==================================================================
    // ヘッダ 表示
    //==================================================================
    $motothre_url = $aThread->getMotoThread();

    $ttitle_en = UrlSafeBase64::encode($aThread->ttitle);
    $ttitle_en_q = '&amp;ttitle_en=' . $ttitle_en;
    $bbs_q = '&amp;bbs=' . $aThread->bbs;
    $key_q = '&amp;key=' . $aThread->key;
    $host_bbs_key_q = 'host=' . $aThread->host . $bbs_q . $key_q;
    $popup_q = '&amp;popup=1';

    // require_once P2_LIB_DIR . '/read_header.inc.php';

    $prev_thre_num = $newthre_num - 1;
    $next_thre_num = $newthre_num + 1;
    if ($prev_thre_num != 0) {
        $prev_thre_ht = "<a class=\"button\" href=\"#ntt{$prev_thre_num}\">▲</a>";
    }
    //$next_thre_ht = "<a href=\"#ntt{$next_thre_num}\">▼</a> ";
    $next_thre_ht = "<a class=\"button\" href=\"#ntt_bt{$newthre_num}\">▼</a> ";

    $itaj_hd = htmlspecialchars($aThread->itaj, ENT_QUOTES, 'Shift_JIS');

    if ($spmode) {
        $read_header_itaj_ht = " ({$itaj_hd})";
    } else {
        $read_header_itaj_ht = '';
    }

    P2Util::printInfoHtml();

    $read_header_ht = <<<EOP
<hr><div id="ntt{$newthre_num}" name="ntt{$newthre_num}"><font color="{$STYLE['mobile_read_ttitle_color']}"><b>{$aThread->ttitle_hd}</b></font>{$read_header_itaj_ht} {$next_thre_ht}</div>
EOP;
    if (!$_conf['iphone']) {
        $read_header_ht .= '<hr>';
    }

    //==================================================================
    // ローカルDatを読み込んでHTML表示
    //==================================================================
    $aThread->resrange['nofirst'] = true;
    $GLOBALS['newres_to_show_flag'] = false;
    $read_cont_ht = '';
    if ($aThread->rescount) {
        $aShowThread = new ShowThreadK($aThread, true);

        if ($_conf['iphone'] && $_conf['expack.spm.enabled']) {
            $read_cont_ht .= $aShowThread->getSpmObjJs();
        }

        $read_cont_ht .= $aShowThread->getDatToHtml();

        unset($aShowThread);
    }

    //==================================================================
    // フッタ 表示
    //==================================================================
    // $read_footer_navi_new  続きを読む 新着レスの表示
    $newtime = date('gis');  // リンクをクリックしても再読込しない仕様に対抗するダミークエリー

    $info_st = '情';
    $delete_st = '削';
    $prev_st = '前';
    $next_st = '次';
    //$dores_st = 'ﾚｽ';

    // 表示範囲
    if ($aThread->resrange['start'] == $aThread->resrange['to']) {
        $read_range_on = $aThread->resrange['start'];
    } else {
        $read_range_on = "{$aThread->resrange['start']}-{$aThread->resrange['to']}";
    }
    $read_range_ht = "{$read_range_on}/{$aThread->rescount}";

    $read_footer_navi_new = "<a href=\"{$_conf['read_php']}?{$host_bbs_key_q}&amp;ls={$aThread->rescount}-&amp;nt={$newtime}{$_conf['k_at_a']}#r{$aThread->rescount}\" target=\"_blank\">新着ﾚｽの表示</a>";

    /*
    if (!empty($_conf['disable_res'])) {
        $dores_ht = <<<EOP
<a href="{$motothre_url}" target="_blank">{$dores_st}</a>
EOP;
    } else {
        $dores_ht = <<<EOP
<a href="post_form.php?{$host_bbs_key_q}&amp;rescount={$aThread->rescount}{$ttitle_en_q}{$_conf['k_at_a']}">{$dores_st}</a>
EOP;
    }
    */

    // ツールバー部分HTML =======
    if ($spmode) {
        $toolbar_itaj_ht = <<<EOP
 (<a href="{$_conf['subject_php']}?{$host_bbs_key_q}{$_conf['k_at_a']}">{$itaj_hd}</a>)
EOP;
    } else {
        $toolbar_itaj_ht = '';
    }

    /*
    $toolbar_right_ht .= <<<EOTOOLBAR
<a href="info.php?{$host_bbs_key_q}{$ttitle_en_q}{$_conf['k_at_a']}">{$info_st}</a>
<a href="info.php?{$host_bbs_key_q}{$ttitle_en_q}&amp;dele=true{$_conf['k_at_a']}">{$delete_st}</a>
<a href="{$motothre_url}" target="_blank">元ｽﾚ</a>\n
EOTOOLBAR;
    */

    $read_footer_ht = <<<EOP
<div id="ntt_bt{$newthre_num}" name="ntt_bt{$newthre_num}" class="read_new_toolbar">
{$read_range_ht}
<a class="button" href="info.php?{$host_bbs_key_q}{$ttitle_en_q}{$_conf['k_at_a']}">{$info_st}</a>
<a class="button" href="spm_k.php?{$host_bbs_key_q}&amp;ls={$aThread->ls}&amp;spm_default={$aThread->resrange['to']}&amp;from_read_new=1{$_conf['k_at_a']}">特</a>
<br>
<a href="{$_conf['read_php']}?{$host_bbs_key_q}&amp;offline=1&amp;rescount={$aThread->rescount}{$_conf['k_at_a']}#r{$aThread->rescount}">{$aThread->ttitle_hd}</a>{$toolbar_itaj_ht}
<a class="button" href="#ntt{$newthre_num}">▲</a>
</div>
<hr>\n
EOP;

    // 透明あぼーんや表示数制限で新しいレス表示がない場合はスキップ
    if ($GLOBALS['newres_to_show_flag']) {
        echo $read_header_ht;
        echo $read_cont_ht;
        echo $read_footer_ht;
    }

    //==================================================================
    // key.idxの値設定
    //==================================================================
    if ($aThread->rescount) {

        $aThread->readnum = min($aThread->rescount, max(0, $data[5], $aThread->resrange['to']));

        $newline = $aThread->readnum + 1; // $newlineは廃止予定だが、旧互換用に念のため

        $sar = array($aThread->ttitle, $aThread->key, $data[2], $aThread->rescount, $aThread->modified,
                    $aThread->readnum, $data[6], $data[7], $data[8], $newline,
                    $data[10], $data[11], $aThread->datochiok);
        P2Util::recKeyIdx($aThread->keyidx, $sar); // key.idxに記録
    }

    unset($aThread);
}

//==================================================================
// ページフッタ表示
//==================================================================
$newthre_num++;

if (!$aThreadList->num) {
    $GLOBALS['matome_naipo'] = TRUE;
    echo "新着ﾚｽはないぽ";
    echo "<hr>";
}

if ($unum_limit > 0) {
    $unum_limit_at_a = "&amp;unum_limit={$unum_limit}";
} else {
    $unum_limit_at_a = '';
}

$shinchaku_matome_url = "{$_conf['read_new_k_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}&amp;nt={$newtime}{$unum_limit_at_a}{$_conf['k_at_a']}";

if ($aThreadList->spmode == 'merge_favita') {
    $shinchaku_matome_url .= $_conf['m_favita_set_at_a'];
}

if (!isset($GLOBALS['rnum_all_range']) or $GLOBALS['rnum_all_range'] > 0 or !empty($GLOBALS['limit_to_eq_to'])) {
    if (!empty($GLOBALS['limit_to_eq_to'])) {
        $str = '新着まとめの更新/続き';
    } else {
        $str = '新まとめを更新';
    }
} else {
    $str = '新まとめの続き';
    $shinchaku_matome_url .= '&amp;norefresh=1';
}

echo <<<EOP
<div id="read_new_footer">{$sb_ht_btm}の<a href="{$shinchaku_matome_url}"{$_conf['k_accesskey_at']['next']}>{$_conf['k_accesskey_st']['next']}{$str}</a>
<a class="button" id="bottom" name="bottom" href="#above"{$_conf['k_accesskey_at']['above']}>{$_conf['k_accesskey_st']['above']}▲</a></div>\n
EOP;

echo "<hr><div class=\"center\">{$_conf['k_to_index_ht']}</div>";

// iPhone
if ($_conf['iphone']) {
    // ImageCache2
    if ($_conf['expack.ic2.enabled']) {
        if (!function_exists('ic2_loadconfig')) {
            include P2EX_LIB_DIR . '/ic2/bootstrap.php';
        }
        $ic2conf = ic2_loadconfig();
        if ($ic2conf['Thumb1']['width'] > 80) {
            include P2EX_LIB_DIR . '/ic2/templates/info-v.tpl.html';
        } else {
            include P2EX_LIB_DIR . '/ic2/templates/info-h.tpl.html';
        }
    }
    // SPM
    if ($_conf['expack.spm.enabled']) {
        echo ShowThreadK::getSpmElementHtml();
    }
}

echo '</body></html>';

$matomeCache->concat(ob_get_flush());

// NGあぼーんを記録
NgAbornCtl::saveNgAborns();

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
