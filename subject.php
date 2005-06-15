<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 -  スレッドサブジェクト表示スクリプト
    フレーム分割画面、右上部分

    subject_new.php と兄弟なので、一緒に面倒をみること
*/

require_once 'conf/conf.php';   // 設定
require_once (P2_LIBRARY_DIR . '/threadlist.class.php');    // スレッドリスト クラス
require_once (P2_LIBRARY_DIR . '/thread.class.php');    // スレッド クラス
require_once (P2_LIBRARY_DIR . '/filectl.class.php');
require_once (P2_LIBRARY_DIR . '/strctl.class.php');

$debug = 0;
if ($debug) {
    require_once 'Benchmark/Profiler.php';
    $profiler = &new Benchmark_Profiler;
    $profiler->start();
}

$debug && $profiler->enterSection('HEAD');

authorize(); // ユーザ認証

//============================================================
// ■変数設定
//============================================================
$newtime = date('gis');

// ■ホスト、板、モード設定 =================================
$host   = isset($_REQUEST['host'])   ? $_REQUEST['host']   : NULL;
$bbs    = isset($_REQUEST['bbs'])    ? $_REQUEST['bbs']    : NULL;
$key    = isset($_REQUEST['key'])    ? $_REQUEST['key']    : NULL;
$spmode = isset($_REQUEST['spmode']) ? $_REQUEST['spmode'] : NULL;
$sb_disp_from = isset($_REQUEST['from']) ? $_REQUEST['from'] : 1;

if ((empty($host) || !isset($bbs)) && !isset($spmode)) {
    die('p2 error: 必要な引数が指定されていません');
}

// ■p2_setting 設定 ======================================
if ($spmode) {
    $p2_setting_txt = $_conf['pref_dir'].'/p2_setting_'.$spmode.'.txt';
} else {
    $datdir_host = P2Util::datdirOfHost($host);
    $p2_setting_txt = $datdir_host.'/'.$bbs.'/p2_setting.txt';
    $sb_keys_b_txt = $datdir_host.'/'.$bbs.'/p2_sb_keys_b.txt';
    $sb_keys_txt = $datdir_host.'/'.$bbs.'/p2_sb_keys.txt';

    // 更新しない場合は、2つ前のと１つ前のを比べて、新規スレを調べる
    if (!empty($_REQUEST['norefresh']) || !empty($_REQUEST['word'])) {
        if (file_exists($sb_keys_b_txt) && ($prepre_sb_cont = file_get_contents($sb_keys_b_txt))) {
            $prepre_sb_keys = unserialize($prepre_sb_cont);
        }
    } else {
        if (file_exists($sb_keys_txt) && ($pre_sb_cont = file_get_contents($sb_keys_txt))) {
            $pre_sb_keys = unserialize($pre_sb_cont);
        }
    }

}

// ■p2_setting 読み込み
if (file_exists($p2_setting_txt) && ($p2_setting_cont = file_get_contents($p2_setting_txt))) {
    $p2_setting = unserialize($p2_setting_cont);
    foreach ($p2_setting as $pre_key => $pre_value) {
        ${$pre_key.'_pre'} = $pre_value;
    }
} else {
    $p2_setting = array();
}
$p2_setting_pre_md5 = md5(serialize($p2_setting));

if (isset($_REQUEST['sb_view'])) { $sb_view = $_REQUEST['sb_view']; }
if (empty($sb_view)) { $sb_view = 'normal'; }

if (isset($_REQUEST['viewnum'])) { $p2_setting['viewnum'] = $_REQUEST['viewnum']; }
if (empty($p2_setting['viewnum'])) { $p2_setting['viewnum'] = $_conf['display_threads_num']; } // デフォルト値

if (isset($_REQUEST['sort'])) { $p2_setting['sort']  = $_REQUEST['sort']; }

if (!empty($_POST['sort'])) {
    $GLOBALS['now_sort'] = $_POST['sort'];
} elseif (!empty($_GET['sort'])) {
    $GLOBALS['now_sort'] = $_GET['sort'];
}

// ソートの指定
if (empty($now_sort)) {
    if (!empty($p2_setting['sort'])) {
        $GLOBALS['now_sort'] = $p2_setting['sort'];
    } else {
        if (empty($spmode)) {
            $GLOBALS['now_sort'] = (!empty($_conf['sb_sort_ita'])) ? $_conf['sb_sort_ita'] : 'ikioi';   // 勢い
        } else {
            $GLOBALS['now_sort'] = 'midoku';    // 新着
        }
    }
}

if (isset($_GET['itaj_en'])) {
    $p2_setting['itaj'] = base64_decode($_GET['itaj_en']);
}

// ■表示スレッド数 ====================================
$threads_num_max = 2000;

if (!$spmode || $spmode == 'news') {
    $threads_num = $p2_setting['viewnum'];
} elseif ($spmode == 'recent') {
    $threads_num = $_conf['rct_rec_num'];
} elseif ($spmode == 'res_hist') {
    $threads_num = $_conf['res_hist_rec_num'];
} else {
    $threads_num = 2000;
}

if ($p2_setting['viewnum'] == 'all') { $threads_num = $threads_num_max; }
elseif ($sb_view == 'shinchaku') { $threads_num = $threads_num_max; }
elseif ($sb_view == 'edit') { $threads_num = $threads_num_max; }
elseif (isset($_REQUEST['word'])) { $threads_num = $threads_num_max; }
elseif ($_conf['ktai']) { $threads_num = $threads_num_max; }

$abornoff_st = 'あぼーん解除';
$deletelog_st = 'ログを削除';

// {{{ ワードフィルタ ====================================
// 検索指定があれば
if (empty($_REQUEST['submit_refresh']) || !empty($_REQUEST['submit_kensaku'])) { // 検索
    $word = isset($_REQUEST['word']) ? $_REQUEST['word'] : '';
    $sb_filter_method = isset($_REQUEST['method']) ? $_REQUEST['method'] : '';
    if (!preg_match('/[^. ]/', $word)) {
        $word = null;
    }
    if (strlen($word) > 0)  {
        $word_fm = StrCtl::wordForMatch($word, $sb_filter_method);
        if (P2_MBREGEX_AVAILABLE == 1) {
            $words_fm = mb_split('\s+', $word_fm);
            $word_fm = mb_ereg_replace('\s+', '|', $word_fm);
        } else {
            $words_fm = preg_split('/\s+/u', $word_fm);
            $word_fm = preg_replace('/\s+/u', '|', $word_fm);
        }
    }
} else {
    $word = null;
    $sb_filter_method = '';
}
$word_ht = htmlspecialchars($word);
// }}}

$nowtime = time();

//============================================================
// ■特殊な前置処理
//============================================================

// {{{ 削除
if (!empty($_GET['dele']) || (isset($_POST['submit']) && $_POST['submit'] == $deletelog_st)) {
    if ($host && $bbs) {
        require_once (P2_LIBRARY_DIR . '/dele.inc.php');
        if (!empty($_POST['checkedkeys'])) {
            $dele_keys = $_POST['checkedkeys'];
        } elseif (isset($_GET['key'])) {
            $dele_keys = array($_GET['key']);
        }
        deleteLogs($host, $bbs, $dele_keys);
    }
}
// }}}

// お気に入りスレッド
elseif (isset($_GET['setfav']) && isset($_GET['key']) && $host && $bbs) {
    require_once (P2_LIBRARY_DIR . '/setfav.inc.php');
    setFav($host, $bbs, $_GET['key'], $_GET['setfav']);
}

// 殿堂入り
elseif (isset($_GET['setpal']) && isset($_GET['key']) && $host && $bbs) {
    require_once (P2_LIBRARY_DIR . '/setpalace.inc.php');
    setPal($host, $bbs, $_GET['key'], $_GET['setpal']);
}

// あぼーんスレッド解除
elseif ((isset($_POST['submit']) && $_POST['submit'] == $abornoff_st) && $host && $bbs && !empty($_POST['checkedkeys'])) {
    require_once (P2_LIBRARY_DIR . '/settaborn_off.inc.php');
    settaborn_off($host, $bbs, $_POST['checkedkeys']);
}

// スレッドあぼーん
elseif (isset($_GET['taborn']) && $key && $host && $bbs) {
    require_once (P2_LIBRARY_DIR . '/settaborn.inc.php');
    settaborn($host, $bbs, $key, $_GET['taborn']);
}

//============================================================
// ■メイン
//============================================================

$aThreadList = &new ThreadList;

// ■板とモードのセット ===================================
if ($spmode) {
    if ($spmode == 'taborn' || $spmode == 'soko') {
        $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));
    }
    $aThreadList->setSpMode($spmode);
} else {
    //if (!$p2_setting['itaj']) { $p2_setting['itaj'] = P2Util::getItaName($host, $bbs); }
    $aThreadList->setIta($host, $bbs, $p2_setting['itaj']);

    // {{{ スレッドあぼーんリスト読込
    $datdir_host = P2Util::datdirOfHost($aThreadList->host);
    $taborn_idx = $datdir_host."/".$aThreadList->bbs.'/p2_threads_aborn.idx';

    if (file_exists($taborn_idx) && ($tabornlines = file($taborn_idx))) {
        $ta_num = sizeof($tabornlines);
        foreach ($tabornlines as $l) {
            $l = rtrim($l);
            $data = explode('<>', $l);
            $ta_keys[ $data[1] ] = true;
        }
    } else {
        $ta_num = 0;
    }
    // }}}

}

// ■ソースリスト読込
$debug && $profiler->enterSection('readList()');
$lines = $aThreadList->readList();
$debug && $profiler->leaveSection('readList()');

// {{{ お気にスレリスト 読込
if (file_exists($_conf['favlist_file']) && ($favlines = file($_conf['favlist_file']))) {
    foreach ($favlines as $l) {
        $l = rtrim($l);
        $data = explode('<>', $l);
        $fav_keys[ $data[1] ] = true;
    }
}
// }}}

$debug && $profiler->leaveSection('HEAD');

//============================================================
// ■それぞれの行解析
//============================================================
$debug && $profiler->enterSection('FORLOOP');

$linesize = sizeof($lines);

$shinchaku_attayo = false;
$shinokini_attayo = false;
$shinchaku_num = 0;
$shinokini_num = 0;

for ($x = 0; $x < $linesize ; $x++) {

    if (is_string($lines[$x])) {
        $l = rtrim($lines[$x]);
    } elseif (is_array($lines[$x])) {
        $l = $lines[$x];
    } else {
        continue;
    }

    $aThread = &new Thread;
    if ($aThreadList->spmode != 'taborn' and $aThreadList->spmode != 'soko') {
        $aThread->torder = $x + 1;
    }

    // ■データ読み込み
    // spmode
    if ($aThreadList->spmode) {
        switch ($aThreadList->spmode) {
        case 'recent':  // 履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }
            break;
        case 'res_hist':    // 書き込み履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }
            break;
        case 'fav': // お気に
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }
            break;
        case 'taborn':  // スレッドあぼーん
            $la = explode('<>', $l);
            $aThread->key = $la[1];
            $aThread->host = $aThreadList->host;
            $aThread->bbs = $aThreadList->bbs;
            break;
        case 'soko':    // dat倉庫
            $la = explode('<>', $l);
            $aThread->key = $la[1];
            $aThread->host = $aThreadList->host;
            $aThread->bbs = $aThreadList->bbs;
            break;
        case 'palace':  // スレの殿堂
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }
            break;
        case 'news':    // ニュースチェック
            $aThread->isonline = true;
            $aThread->key = $l['key'];
            $aThread->setTtitle($l['ttitle']);
            $aThread->rescount = $l['rescount'];
            $aThread->host = $l['host'];
            $aThread->bbs = $l['bbs'];
            $aThread->itaj = $l['itaj'];
            if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }
            break;
        }

    // subject (not spmode つまり普通の板)
    } else {
        $debug && $profiler->enterSection('getThreadInfoFromSubjectTxtLine()');
        $aThread->getThreadInfoFromSubjectTxtLine($l);
        $debug && $profiler->leaveSection('getThreadInfoFromSubjectTxtLine()');
        $aThread->host = $aThreadList->host;
        $aThread->bbs = $aThreadList->bbs;
        if (!empty($_REQUEST['norefresh']) || !empty($_REQUEST['word'])) {
            if (!isset($prepre_sb_keys[$aThread->key])) { $aThread->new = true; }
        } else {
            if (!isset($pre_sb_keys[$aThread->key])) { $aThread->new = true; }
            $subject_keys[$aThread->key] = true;
        }
    }

    //hostもbbsもkeyも不明ならスキップ
    if (!($aThread->host && $aThread->bbs && $aThread->key)) {
        unset($aThread);
        continue;
    }

    $debug && $profiler->enterSection('word_filter_for_sb');
    // ■ワードフィルタ(for subject) ====================================
    if (!$aThreadList->spmode || $aThreadList->spmode == 'news' and $word_fm) {
        if (!isset($GLOBALS['sb_mikke_num'])) {
            $GLOBALS['sb_mikke_num'] = 0;
        }
        $target = $aThread->ttitle;
        $sb_filter_match = true;
        if ($sb_filter_method == 'and') {
            foreach ($words_fm as $word_fm_ao) {
                if (!StrCtl::filterMatch($word_fm_ao, $target)) {
                    $sb_filter_match = false;
                    break;
                }
            }
        } else {
            if (!StrCtl::filterMatch($word_fm, $target)) { $sb_filter_match = false; }
        }
        if (!$sb_filter_match) {
            unset($aThread);
            continue;
        } else {
            $GLOBALS['sb_mikke_num']++;
            if ($_conf['ktai']) {
                if ($k_filter_marker) {
                    $aThread->ttitle_ht = StrCtl::filterMarking($word_fm, $aThread->ttitle_hd, $k_filter_marker);
                } else {
                    $aThread->ttitle_ht = $aThread->ttitle_hd;
                }
            } else {
                $aThread->ttitle_ht = StrCtl::filterMarking($word_fm, $aThread->ttitle_hd);
            }
        }
    }
    $debug && $profiler->leaveSection('word_filter_for_sb');

    // ■スレッドあぼーんチェック =====================================
    $debug && $profiler->enterSection('taborn_check_continue');
    if ($aThreadList->spmode != 'taborn' && isset($ta_keys[$aThread->key])) {
        unset($ta_keys[$aThread->key]);
        $debug && $profiler->leaveSection('taborn_check_continue');
        continue; // あぼーんスレはスキップ
    }
    $debug && $profiler->leaveSection('taborn_check_continue');

    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
    // 既得スレッドデータをidxから取得
    $debug && $profiler->enterSection('getThreadInfoFromIdx');
    $aThread->getThreadInfoFromIdx($aThread->keyidx);
    $debug && $profiler->leaveSection('getThreadInfoFromIdx');


    $debug && $profiler->enterSection('favlist_check');
    // ■ favlistチェック =====================================
    //if ($x <= $threads_num) {
        if ($aThreadList->spmode != 'taborn' && isset($fav_keys[$aThread->key])) {
            $aThread->fav = 1;
            unset($fav_keys[$aThread->key]);
        }
    //}
    $debug && $profiler->leaveSection('favlist_check');

    // ■ spmode(殿堂入り、newsを除く)なら ====================================
    if ($aThreadList->spmode && $aThreadList->spmode != 'news' && $sb_view != 'edit') {

        // ■ subject.txt が未DLなら落としてデータを配列に格納
        if (empty($subject_txts["{$aThread->host}/{$aThread->bbs}"])) {
            $datdir_host = P2Util::datdirOfHost($aThread->host);
            $subject_url = "http://{$aThread->host}/{$aThread->bbs}/subject.txt";
            $subjectfile = "{$datdir_host}/{$aThread->bbs}/subject.txt";
            FileCtl::mkdir_for($subjectfile); // 板ディレクトリが無ければ作る
            P2Util::subjectDownload($subject_url, $subjectfile);

            $debug && $profiler->enterSection('subthre_read');
            if ($aThreadList->spmode == 'soko' || $aThreadList->spmode == 'taborn') {

                if (extension_loaded('zlib') && strstr($aThread->host, '.2ch.net')) {
                    $sblines = gzfile($subjectfile);
                } else {
                    $sblines = file($subjectfile);
                }
                if ($sblines) {
                    $it = 1;
                    foreach ($sblines as $asbl) {
                        if (preg_match("/^([0-9]+)\.(dat|cgi)(,|<>)(.+) ?(\(|（)([0-9]+)(\)|）)/", $asbl, $matches)) {
                            $akey = $matches[1];
                            $subject_txts["$aThread->host/$aThread->bbs"][$akey]['ttitle'] = rtrim($matches[4]);
                            $subject_txts["$aThread->host/$aThread->bbs"][$akey]['rescount'] = $matches[6];
                            $subject_txts["$aThread->host/$aThread->bbs"][$akey]['torder'] = $it;
                        }
                        $it++;
                    }
                }

            } else {

                if (extension_loaded('zlib') && strstr($aThread->host, ".2ch.net")) {
                    $subject_txts["$aThread->host/$aThread->bbs"] = gzfile($subjectfile);
                } else {
                    $subject_txts["$aThread->host/$aThread->bbs"] = file($subjectfile);
                }

            }
            $debug && $profiler->leaveSection('subthre_read');
        }

        $debug && $profiler->enterSection('subthre_check');
        // ■スレ情報取得 =============================
        if ($aThreadList->spmode == 'soko' or $aThreadList->spmode == 'taborn') {

            if ($subject_txts[$aThread->host."/".$aThread->bbs][$aThread->key]) {

                // 倉庫はオンラインを含まない
                if ($aThreadList->spmode == 'soko') {
                    unset($aThread);
                    continue;
                } elseif ($aThreadList->spmode == 'taborn') {
                    // subject.txt からスレ情報取得
                    //$aThread->getThreadInfoFromSubjectTxtLine($l);
                    $aThread->isonline = true;
                    $ttitle = $subject_txts["$aThread->host/$aThread->bbs"][$aThread->key]['ttitle'];
                    $aThread->setTtitle($ttitle);
                    $aThread->rescount = $subject_txts["$aThread->host/$aThread->bbs"][$aThread->key]['rescount'];
                    if ($aThread->readnum) {
                        $aThread->unum = $aThread->rescount - $aThread->readnum;
                        // machi bbs はsageでsubjectの更新が行われないそうなので調整しておく
                        if ($aThread->unum < 0) { $aThread->unum = 0; }
                    }
                    $aThread->torder = $subject_txts["$aThread->host/$aThread->bbs"][$aThread->key]['torder'];
                }

            }

        } else {

            if ($subject_txts[$aThread->host."/".$aThread->bbs]) {
                $it = 1;
                foreach ($subject_txts[$aThread->host."/".$aThread->bbs] as $l) {
                    if (@preg_match("/^{$aThread->key}/", $l)) {
                        //subject.txt からスレ情報取得
                        $aThread->getThreadInfoFromSubjectTxtLine($l);
                        break;
                    }
                    $it++;
                }
            }

        }
        $debug && $profiler->leaveSection('subthre_check');

        if ($aThreadList->spmode == 'taborn') {
            if (!$aThread->torder) { $aThread->torder = '-'; }
        }


        // ■新着のみ(for spmode) ===============================
        if ($sb_view == 'shinchaku' && !isset($_GET['word'])) {
            if ($aThread->unum < 1) {
                unset($aThread);
                continue;
            }
        }


        // ■ワードフィルタ(for spmode) ==================================
        if ($word_fm) {
            $target = $aThread->ttitle;
            $sb_filter_match = true;
            if ($sb_filter_method == 'and') {
                foreach ($words_fm as $word_fm_ao) {
                    if (!StrCtl::filterMatch($word_fm_ao, $target)) {
                        $sb_filter_match = false;
                        break;
                    }
                }
            } else {
                if (!StrCtl::filterMatch($word_fm, $target)) { $sb_filter_match = false; }
            }
            if (!$sb_filter_match) {
                unset($aThread);
                continue;
            } else {
                $GLOBALS['sb_mikke_num']++;
                if ($_conf['ktai']) {
                    if ($k_filter_marker) {
                        $aThread->ttitle_ht = StrCtl::filterMarking($word_fm, $aThread->ttitle_hd, $k_filter_marker);
                    } else {
                        $aThread->ttitle_ht = $aThread->ttitle_hd;
                    }
                } else {
                    $aThread->ttitle_ht = StrCtl::filterMarking($word_fm, $aThread->ttitle_hd);
                }
            }
        }
    }

    $debug && $profiler->enterSection('FORLOOP_HIP');
    // subjectからrescountが取れなかった場合は、gotnumを利用する。
    if ((!$aThread->rescount) and $aThread->gotnum) {
        $aThread->rescount = $aThread->gotnum;
    }
    if (!$aThread->ttitle_ht) { $aThread->ttitle_ht = $aThread->ttitle_hd; }

    // 新着あり
    if ($aThread->unum > 0) {
        $shinchaku_attayo = true;
        $shinchaku_num += $aThread->unum; // 新着数set
        if ($aThread->fav) {
            $shinokini_attayo = true;
            $shinokini_num += $aThread->unum; // お気にスレの新着数set
        }
    // お気にスレ
    } elseif ($aThread->fav) {

    // 新規スレ
    } elseif ($aThread->new) {

    // 既得スレ
    } elseif ($_conf['viewall_kitoku'] && $aThread->isKitoku()) {

    } else {
        // 携帯、ニュースチェック以外で
        if (!$_conf['ktai'] and $spmode != 'news') {
            // 指定数を越えていたらカット
            if ($x >= $threads_num) {
                unset($aThread);
                $debug && $profiler->leaveSection('FORLOOP_HIP');
                continue;
            }
        }
    }

    // 新着ソートの便宜上 （未取得スレッドの）unum をセット調整
    if (!isset($aThread->unum)) {
        if ($aThreadList->spmode == 'recent' or $aThreadList->spmode == 'res_hist' or $aThreadList->spmode == 'taborn') {
            $aThread->unum = -0.1;
        } else {
            $aThread->unum = $_conf['sort_zero_adjust'];
        }
    }

    // 勢いのセット
    $aThread->setDayRes($nowtime);

    // 生存数set
    if ($aThread->isonline) {
        if (!isset($online_num)) {
            $online_num = 1;
        } else {
            $online_num++;
        }
    }

    // ■リストに追加
    $debug && $profiler->enterSection('addThread()');
    $aThreadList->addThread($aThread);
    $debug && $profiler->leaveSection('addThread()');
    unset($aThread);

    $debug && $profiler->leaveSection('FORLOOP_HIP');
}

$debug && $profiler->leaveSection('FORLOOP');

$debug && $profiler->enterSection('FOOT');

//============================================================
// 既にdat落ちしているスレは自動的にあぼーんを解除する
//============================================================
$debug && $profiler->enterSection('abornoff');
if (!$aThreadList->spmode && !$word && $aThreadList->threads && isset($ta_keys)) {
    include_once (P2_LIBRARY_DIR . '/settaborn_off.inc.php');
    //echo sizeof($ta_keys)."*<br>";
    $ta_vkeys = array_keys($ta_keys);
    settaborn_off($aThreadList->host, $aThreadList->bbs, $ta_vkeys);
    foreach ($ta_vkeys as $k) {
        $ta_num--;
        if ($k) {
            $ks .= "key:$k ";
        }
    }
    $ks && $_info_msg_ht .= "<div class=\"info\">　p2 info: DAT落ちしたスレッドあぼーんを自動解除しました - $ks</div>";
}
$debug && $profiler->leaveSection('abornoff');

//============================================================
// ■ソート
//============================================================
$debug && $profiler->enterSection('sort');
if ($aThreadList->threads) {
    $sort_method = null;
    switch ($now_sort) {
        case 'midoku':
            $sort_method = ($aThreadList->spmode == 'soko') ? 'cmp_key' : 'cmp_midoku';
            break;
        case 'ikioi':
        case 'spd':
            $sort_method = ($_conf['cmp_dayres_midoku']) ? 'cmp_dayres_midoku' : 'cmp_dayres';
            break;
        case 'no':
            $sort_method = ($aThreadList->spmode == 'soko') ? 'cmp_key' : 'cmp_no';
            break;
        case 'dayres_midoku':
        case 'fav':
        case 'ita':
        case 'res':
        case 'title':
            $sort_method = 'cmp_'.$now_sort;
            break;
        case 'bd':
            $sort_method = 'cmp_key';
            break;
    }
    if ($sort_method) {
        usort($aThreadList->threads, $sort_method);
    }
}
$debug && $profiler->leaveSection('sort');

// ニュースチェック
if ($aThreadList->spmode == 'news') {
    $newthreads = array();
    for ($i = 0; $i < $threads_num ; $i++) {
        if ($aThreadList->threads[$i]) {
            $newthreads[] = array_shift($aThreadList->threads);
        }
    }
    $aThreadList->threads = $newthreads;
    $aThreadList->num = sizeof($aThreadList->threads);
}

//===============================================================
// ■プリント
//===============================================================
// ■携帯
if ($_conf['ktai']) {

    // {{{ 倉庫にtorder付与
    if ($aThreadList->spmode == 'soko') {
        if ($aThreadList->threads) {
            $soko_torder = 1;
            $newthreads = array();
            foreach ($aThreadList->threads as $at) {
                $at->torder = $soko_torder++;
                $newthreads[] = &$at;
                unset($at);
            }
            $aThreadList->threads = &$newthreads;
            unset($newthreads);
        }
    }
    // }}}

    // {{{ 表示数制限
    $aThreadList->num = sizeof($aThreadList->threads); // なんとなく念のため
    $sb_disp_all_num = $aThreadList->num;

    $disp_navi = P2Util::getListNaviRange($sb_disp_from , $_conf['k_sb_disp_range'], $sb_disp_all_num);

    $newthreads = array();
    for ($i = $disp_navi['from']; $i <= $disp_navi['end']; $i++) {
        if ($aThreadList->threads[$i-1]) {
            $newthreads[] = &$aThreadList->threads[$i-1];
        }
    }
    $aThreadList->threads = &$newthreads;
    unset($newthreads);
    $aThreadList->num = sizeof($aThreadList->threads);
    // }}}

    // ヘッダプリント
    include (P2_LIBRARY_DIR . '/sb_header_k.inc.php');

    require_once (P2_LIBRARY_DIR . '/sb_print_k.inc.php'); // スレッドサブジェクトメイン部分HTML表示関数
    sb_print_k($aThreadList);

    // フッタプリント
    include (P2_LIBRARY_DIR . '/sb_footer_k.inc.php');

} else {
    //============================================================
    // ヘッダHTMLを表示
    //============================================================
    $debug && $profiler->enterSection('sb_header');
    include (P2_LIBRARY_DIR . '/sb_header.inc.php');
    flush();
    $debug && $profiler->leaveSection('sb_header');

    //============================================================
    // スレッドサブジェクトメイン部分HTML表示
    //============================================================
    require_once (P2_LIBRARY_DIR . '/sb_print.inc.php'); //スレッドサブジェクトメイン部分HTML表示関数

    $debug && $profiler->enterSection('sb_print()');
    sb_print($aThreadList);
    $debug && $profiler->leaveSection('sb_print()');

    //============================================================
    // フッタHTML表示
    //============================================================
    $debug && $profiler->enterSection('sb_footer');
    include (P2_LIBRARY_DIR . '/sb_footer.inc.php');
    $debug && $profiler->leaveSection('sb_footer');
}

//============================================================
// p2_setting 記録
//============================================================
$debug && $profiler->enterSection('save_p2_setting');
if ($viewnum_pre != $p2_setting['viewnum'] or $sort_pre != $now_sort or $itaj_pre != $p2_setting['itaj']) {
    if (!empty($_POST['sort'])) {
        $p2_setting['sort'] = $_POST['sort'];
    } elseif (!empty($_GET['sort'])) {
        $p2_setting['sort'] = $_GET['sort'];
    }
    if ($p2_setting) {
        if ($p2_setting_cont = serialize($p2_setting)) {
            if (FileCtl::file_write_contents($p2_setting_txt, $p2_setting_cont) === FALSE) {
                die("Error: {$p2_setting_txt} を更新できませんでした");
            }
        }
    }
}
$debug && $profiler->leaveSection('save_p2_setting');

//============================================================
// $subject_keys をシリアライズして保存
//============================================================
$debug && $profiler->enterSection('save_subject_keys');
//if (file_exists($sb_keys_b_txt)) { unlink($sb_keys_b_txt); }
if (!empty($subject_keys)) {
    if (file_exists($sb_keys_txt)) {
        FileCtl::make_datafile($sb_keys_b_txt, $_conf['p2_perm']);
        copy($sb_keys_txt, $sb_keys_b_txt);
    } else {
        FileCtl::make_datafile($sb_keys_txt, $_conf['p2_perm']);
    }
    if ($sb_keys_cont = serialize($subject_keys)) {
        if (FileCtl::file_write_contents($sb_keys_txt, $sb_keys_cont) === FALSE) {
            die("Error: {$sb_keys_txt} を更新できませんでした");
        }
    }
}
$debug && $profiler->leaveSection('save_subject_keys');

$debug && $profiler->leaveSection('FOOT');

$debug && $profiler->stop();
$debug && $profiler->display();


//============================================================
// ■ソート関数
//============================================================

/**
 * 新着ソート
 */
function cmp_midoku($a, $b)
{
    if ($a->new == $b->new) {
        if (($a->unum == $b->unum) or ($a->unum < 0) && ($b->unum < 0)) {
            return ($a->torder > $b->torder) ? 1 : -1;
        } else {
            return ($a->unum < $b->unum) ? 1 : -1;
        }
    } else {
        return ($a->new < $b->new) ? 1 : -1;
    }
}

/**
 * レス数 ソート
 */
function cmp_res($a, $b)
{
    if ($a->rescount == $b->rescount) {
        return ($a->torder > $b->torder) ? 1 : -1;
    } else {
        return ($a->rescount < $b->rescount) ? 1 : -1;
    }
}

/**
 * タイトル ソート
 */
function cmp_title($a, $b)
{
    if ($a->ttitle == $b->ttitle) {
        return ($a->torder > $b->torder) ? 1 : -1;
    } else {
        return strcmp($a->ttitle,$b->ttitle);
    }
}

/**
 * 板 ソート
 */
function cmp_ita($a, $b)
{
    if ($a->itaj == $b->itaj) {
        return ($a->torder > $b->torder) ? 1 : -1;
    } else {
        return strcmp($a->itaj,$b->itaj);
    }
}

/**
 * お気に ソート
 */
function cmp_fav($a, $b)
{
    if ($a->fav == $b->fav) {
        return ($a->torder > $b->torder) ? 1 : -1;
    } else {
        return strcmp($b->fav, $a->fav);
    }
}

/**
 * 勢いソート（新着レス優先）
 */
function cmp_dayres_midoku($a, $b)
{
    if ($a->new == $b->new) {
        if (($a->unum == $b->unum) or ($a->unum >= 1) && ($b->unum >= 1)) {
            return ($a->dayres < $b->dayres) ? 1 : -1;
        } else {
            return ($a->unum < $b->unum) ? 1 : -1;
        }
    } else {
        return ($a->new < $b->new) ? 1 : -1;
    }
}

/**
 * 勢いソート
 */
function cmp_dayres($a, $b)
{
    if ($a->new == $b->new) {
        return ($a->dayres < $b->dayres) ? 1 : -1;
    } else {
        return ($a->new < $b->new) ? 1 : -1;
    }
}

/**
 * key ソート
 */
function cmp_key($a, $b)
{
    return ($a->key < $b->key) ? 1 : -1;
}

/**
 * No. ソート
 */
function cmp_no($a, $b)
{
    return ($a->torder > $b->torder) ? 1 : -1;
}

?>
