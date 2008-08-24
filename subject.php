<?php
/*
    p2 -  スレッドサブジェクト表示スクリプト
    フレーム分割画面、右上部分

    lib/subject_new.inc.php と兄弟なので、一緒に面倒をみること
*/

include_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/threadlist.class.php';
require_once P2_LIB_DIR . '/thread.class.php';
require_once P2_LIB_DIR . '/filectl.class.php';

//$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('HEAD');

$_login->authorize(); // ユーザ認証

//============================================================
// 変数設定
//============================================================
$newtime = date('gis');
$nowtime = time();

$abornoff_st = 'あぼーん解除';
$deletelog_st = 'ログを削除';

$kitoku_only = false;
$online_num = 0;
$shinchaku_num = 0;
$shinchaku_attayo = false;

$sb_disp_from = !empty($_REQUEST['from']) ? $_REQUEST['from'] : 1;

// {{{ ホスト、板、モード設定

$host   = isset($_REQUEST['host'])   ? $_REQUEST['host']   : null;
$bbs    = isset($_REQUEST['bbs'])    ? $_REQUEST['bbs']    : null;
$spmode = isset($_REQUEST['spmode']) ? $_REQUEST['spmode'] : null;

if (!($host && $bbs) && !$spmode) {
    die('p2 error: 必要な引数が指定されていません');
}

// }}}
// {{{ p2_setting, sb_keys 設定

if ($spmode) {
    if ($_conf['expack.misc.multi_favs'] && ($spmode == 'fav' || $spmode == 'merge_favita')) {
        $favset_key = ($spmode == 'fav') ? 'm_favlist_set' : 'm_favita_set';
        $favset_suffix = (empty($_conf[$favset_key])) ? '' : $_conf[$favset_key];
        $p2_setting_txt = $_conf['pref_dir'] . '/p2_setting_' . $spmode . $favset_suffix . '.txt';
    } else {
        $p2_setting_txt = $_conf['pref_dir'] . '/p2_setting_' . $spmode . '.txt';
    }
} else {
    $idx_host_dir = P2Util::idxDirOfHost($host);
    $idx_bbs_dir_s = $idx_host_dir . '/' . $bbs . '/';

    $p2_setting_txt = $idx_bbs_dir_s . 'p2_setting.txt';
    $sb_keys_b_txt =  $idx_bbs_dir_s . 'p2_sb_keys_b.txt';
    $sb_keys_txt =    $idx_bbs_dir_s . 'p2_sb_keys.txt';

    $pre_subject_keys = getSubjectKeys($sb_keys_txt, $sb_keys_b_txt);
    $subject_keys = array();
}

// }}}
// {{{ p2_setting 読み込み、セット

$p2_setting = array('viewnum' => null, 'sort' => null, 'itaj' => null);
if ($p2_setting_cont = FileCtl::file_read_contents($p2_setting_txt)) {
    $p2_setting = array_merge($p2_setting, unserialize($p2_setting_cont));
}

$pre_setting['viewnum'] = isset($p2_setting['viewnum']) ? $p2_setting['viewnum'] : null;
$pre_setting['sort']    = isset($p2_setting['sort'])    ? $p2_setting['sort']    : null;
$pre_setting['itaj']    = isset($p2_setting['itaj'])    ? $p2_setting['itaj']    : null;

$sb_view = !empty($_REQUEST['sb_view']) ? $_REQUEST['sb_view'] : 'normal';

if (!empty($_REQUEST['viewnum'])) {
    $p2_setting['viewnum'] = $_REQUEST['viewnum'];
} elseif (!$p2_setting['viewnum']) {
    $p2_setting['viewnum'] = $_conf['display_threads_num']; // デフォルト値
}

if (isset($_GET['itaj_en'])) {
    $p2_setting['itaj'] = base64_decode($_GET['itaj_en']);
}

// }}}
// {{{ ソートの指定

if (!empty($_REQUEST['sort'])) {
    $now_sort = $_REQUEST['sort'];
} else {
    if ($p2_setting['sort']) {
        $now_sort = $p2_setting['sort'];
    } else {
        if (!$spmode) {
            $now_sort = !empty($_conf['sb_sort_ita']) ? $_conf['sb_sort_ita'] : 'ikioi'; // 勢い
        } else {
            $now_sort = 'midoku'; // 新着
        }
    }
}

// }}}
// {{{ 表示スレッド数設定

$threads_num_max = 2000;

if (!$spmode || $spmode == 'merge_favita') {
    $threads_num = $p2_setting['viewnum'];
} elseif ($spmode == 'recent') {
    $threads_num = $_conf['rct_rec_num'];
} elseif ($spmode == 'res_hist') {
    $threads_num = $_conf['res_hist_rec_num'];
} else {
    $threads_num = 2000;
}

if ($p2_setting['viewnum'] == 'all' or $sb_view == 'shinchaku' or $sb_view == 'edit' or isset($_GET['word']) or $_conf['ktai']) {
    $threads_num = $threads_num_max;
}

// }}}
// {{{ ワードフィルタ設定

$word = '';
$do_filtering = false;
$GLOBALS['sb_mikke_num'] = 0;

// デフォルトオプション, $sb_filter は global @see sb_print.inc.php
$sb_filter = array('method' => 'and');

// 検索指定があれば
if (empty($_REQUEST['submit_refresh']) or !empty($_REQUEST['submit_kensaku'])) {
    if (isset($_GET['word'])) {
        $GLOBALS['word'] = $_GET['word'];
    } elseif (isset($_POST['word'])) {
        $GLOBALS['word'] = $_POST['word'];
    }


    if (isset($_GET['method'])) {
        $sb_filter['method'] = $_GET['method'];
    } elseif (isset($_POST['method'])) {
        $sb_filter['method'] = $_POST['method'];
    }

    if ($sb_filter['method'] == 'similar') {
        $GLOBALS['wakati_word'] = $GLOBALS['word'];
        $GLOBALS['wakati_words'] = wakati($GLOBALS['word']);
        if (!$GLOBALS['wakati_words']) {
            unset($GLOBALS['wakati_word'], $GLOBALS['wakati_words']);
        } else {
            include_once P2_LIB_DIR . '/strctl.class.php';
            $wakati_filter = create_function('$s', 'return (preg_match($GLOBALS["KANJI_REGEX"], $s) || (preg_match($GLOBALS["WAKATI_REGEX"], $s) && mb_strlen($s, "UTF-8") > 1));');
            $wakati_words2 = array_filter($GLOBALS['wakati_words'], $wakati_filter);
            if (!$wakati_words2) {
                $GLOBALS['wakati_hl_regex'] = $GLOBALS['wakati_word'];
            } else {
                rsort($wakati_words2, SORT_STRING);
                $GLOBALS['wakati_hl_regex'] = implode(' ', $wakati_words2);
                $GLOBALS['wakati_hl_regex'] = mb_convert_encoding($GLOBALS['wakati_hl_regex'], 'CP932', 'UTF-8');
            }
            $GLOBALS['wakati_hl_regex'] = StrCtl::wordForMatch($GLOBALS['wakati_hl_regex'], 'or');
            $GLOBALS['wakati_hl_regex'] = str_replace(' ', '|', $GLOBALS['wakati_hl_regex']);
            $GLOBALS['wakati_length'] = mb_strlen($GLOBALS['wakati_word'], 'CP932');
            $GLOBALS['wakati_score'] = getSbScore($GLOBALS['wakati_words'], $GLOBALS['wakati_length']);
            if (!isset($_conf['expack.min_similarity'])) {
                $_conf['expack.min_similarity'] = 0.05;
            } elseif ($_conf['expack.min_similarity'] > 1) {
                $_conf['expack.min_similarity'] /= 100;
            }
            $_conf['expack.min_similarity'] = (float) $_conf['expack.min_similarity'];
        }
        $word = '';
    } elseif (preg_match('/^\.+$/', $word)) {
        $word = '';
    }

    if (strlen($word) > 0)  {
        include_once P2_LIB_DIR . '/strctl.class.php';
        $word_fm = StrCtl::wordForMatch($word, $sb_filter['method']);
        if ($sb_filter['method'] != 'just') {
            if (P2_MBREGEX_AVAILABLE == 1) {
                $GLOBALS['words_fm'] = @mb_split('\s+', $word_fm);
                $GLOBALS['word_fm'] = @mb_ereg_replace('\s+', '|', $word_fm);
            } else {
                $GLOBALS['words_fm'] = @preg_split('/\s+/', $word_fm);
                $GLOBALS['word_fm'] = @preg_replace('/\s+/', '|', $word_fm);
            }
        }

        if (strlen($GLOBALS['word_fm']) > 0) {
            $do_filtering = true;
        }
    }
}

// }}}

//============================================================
// 特殊な前処理
//============================================================
// {{{ 削除

if (!empty($_GET['dele']) || (isset($_POST['submit']) && $_POST['submit'] == $deletelog_st)) {
    if ($host && $bbs) {
        include_once P2_LIB_DIR . '/dele.inc.php';
        if ($_POST['checkedkeys']) {
            $dele_keys = $_POST['checkedkeys'];
        } else {
            $dele_keys = array($_GET['key']);
        }
        deleteLogs($host, $bbs, $dele_keys);
    }

// }}}

// お気に入りスレッド
} elseif (isset($_GET['setfav']) && $_GET['key'] && $host && $bbs) {
    include_once P2_LIB_DIR . '/setfav.inc.php';
    setFav($host, $bbs, $_GET['key'], $_GET['setfav']);

// 殿堂入り
} elseif (isset($_GET['setpal']) && $_GET['key'] && $host && $bbs) {
    include_once P2_LIB_DIR . '/setpalace.inc.php';
    setPal($host, $bbs, $_GET['key'], $_GET['setpal']);

// あぼーんスレッド解除
} elseif ((isset($_POST['submit']) && $_POST['submit'] == $abornoff_st) && $host && $bbs && $_POST['checkedkeys']) {
    include_once P2_LIB_DIR . '/settaborn_off.inc.php';
    settaborn_off($host, $bbs, $_POST['checkedkeys']);

// スレッドあぼーん
} elseif (isset($_GET['taborn']) && !is_null($_GET['key']) && $host && $bbs) {
    include_once P2_LIB_DIR . '/settaborn.inc.php';
    settaborn($host, $bbs, $_GET['key'], $_GET['taborn']);
}

// お気に板をマージ
if ($spmode == 'merge_favita') {
    $favitas = array();
    $pre_subject_keys = array();
    $subject_keys = array();
    $sb_key_txts = array();

    if (file_exists($_conf['favita_path'])) {
        foreach (file($_conf['favita_path']) as $l) {
            if (preg_match("/^\t?(.+?)\t(.+?)\t.+?\$/", rtrim($l), $matches)) {
                $_host = $matches[1];
                $_bbs  = $matches[2];
                $_id   = $_host . '/' . $_bbs;

                $_idx_bbs_dir   = P2Util::idxDirOfHost($_host) . '/' . $_bbs;
                $_sb_keys_txt   = $_idx_bbs_dir . '/p2_sb_keys.txt';
                $_sb_keys_txt_a = $_idx_bbs_dir . '/p2_sb_keys_m.txt';
                $_sb_keys_txt_b = $_idx_bbs_dir . '/p2_sb_keys_m_b.txt';

                $favitas[$_id] = array('host' => $_host, 'bbs' => $_bbs);
                $pre_subject_keys[$_id] = getSubjectKeys($_sb_keys_txt, $_sb_keys_txt);
                foreach (getSubjectKeys($_sb_keys_txt_a, $_sb_keys_txt_b) as $_key => $_value) {
                    $pre_subject_keys[$_id][$_key] = $_value;
                }
                $subject_keys[$_id] = array();
                $sb_key_txts[$_id] = array($_sb_keys_txt_a, $_sb_keys_txt_b);
            }
        }
    }

    if ($_conf['merge_favita'] == 2) {
        $kitoku_only = true;
    }
}

//============================================================
// 更新する場合、前もって一括＆並列ダウンロード
// (PHP >= 5.2, 要pecl_http)
//============================================================

if (empty($_REQUEST['norefresh']) &&
    !(empty($_REQUEST['refresh']) && isset($_REQUEST['word'])) &&
    extension_loaded('http')
) {
    require_once P2_LIB_DIR . '/p2httpext.class.php';
    switch ($spmode) {
    case 'fav':
        P2HttpRequestPool::fetchSubjectTxt($_conf['favlist_file']);
        $GLOBALS['expack.subject.multi-threaded-download.done'] = true;
        break;
    case 'recent':
        P2HttpRequestPool::fetchSubjectTxt($_conf['rct_file']);
        $GLOBALS['expack.subject.multi-threaded-download.done'] = true;
        break;
    case 'res_hist':
        P2HttpRequestPool::fetchSubjectTxt($_conf['pref_dir'] . '/p2_res_hist.idx');
        $GLOBALS['expack.subject.multi-threaded-download.done'] = true;
        break;
    case 'merge_favita':
        P2HttpRequestPool::fetchSubjectTxt($favitas);
        $GLOBALS['expack.subject.multi-threaded-download.done'] = true;
        break;
    }
}

//============================================================
// メイン
//============================================================

$aThreadList = new ThreadList();

// {{{ 板とモードのセット

$spmode_without_palace_or_favita = false;
$ta_keys = array();
$ta_num = 0;

if ($spmode) {
    if ($spmode == 'taborn' or $spmode == 'soko') {
        $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));
    }

    if ($spmode != 'palace' && $spmode != 'merge_favita') {
        $spmode_without_palace_or_favita = true;
    }

    $aThreadList->setSpMode($spmode);
} else {
    // if(!$p2_setting['itaj']){$p2_setting['itaj'] = P2Util::getItaName($host, $bbs);}
    $aThreadList->setIta($host, $bbs, $p2_setting['itaj']);

    // スレッドあぼーんリスト読込
    $idx_host_dir = P2Util::idxDirOfHost($aThreadList->host);
    $taborn_file = $idx_host_dir.'/'.$aThreadList->bbs.'/p2_threads_aborn.idx';

    if ($tabornlines = FileCtl::file_read_lines($taborn_file, FILE_IGNORE_NEW_LINES)) {
        $ta_num = sizeof($tabornlines);
        foreach ($tabornlines as $l) {
            $data = explode('<>', $l);
            $ta_keys[ $data[1] ] = true;
        }
    }
}

// }}}

// ソースリスト読込
$lines = $aThreadList->readList();

// {{{ お気にスレリスト 読込
if ($favlines = FileCtl::file_read_lines($_conf['favlist_file'], FILE_IGNORE_NEW_LINES)) {
    foreach ($favlines as $l) {
        $data = explode('<>', $l);
        $fav_keys[ $data[1] ] = $data[11];
    }
}
// }}}

//$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('HEAD');

//============================================================
// それぞれの行解析
//============================================================
//$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('FORLOOP');

$linesize = sizeof($lines);
$subject_txts = array();

for ($x = 0; $x < $linesize; $x++) {
    $aThread = new Thread();

    if ($aThreadList->spmode == 'merge_favita') {
        $l = $lines[$x];
    } else {
        $l = rtrim($lines[$x]);
        if ($aThreadList->spmode != 'soko' && $aThreadList->spmode != 'taborn') {
            $aThread->torder = $x + 1;
        }
    }

    // データ読み込み
    // spmode
    if ($aThreadList->spmode) {
        switch ($aThreadList->spmode) {
        case "recent":  // 履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) {$aThread->itaj = $aThread->bbs;}
            break;
        case "res_hist":    // 書き込み履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) {$aThread->itaj= $aThread->bbs;}
            break;
        case "fav":     // お気に
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) {$aThread->itaj = $aThread->bbs;}
            break;
        case "taborn":  // スレッドあぼーん
            $la = explode('<>', $l);
            $aThread->key = $la[1];
            $aThread->host = $aThreadList->host;
            $aThread->bbs = $aThreadList->bbs;
            break;
        case "soko":    // dat倉庫
            $la = explode('<>', $l);
            $aThread->key = $la[1];
            $aThread->host = $aThreadList->host;
            $aThread->bbs = $aThreadList->bbs;
            break;
        case "palace":  // スレの殿堂
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) {$aThread->itaj = $aThread->bbs;}
            break;
        case "merge_favita": // お気に板をマージ
            $aThread->isonline = true;
            $aThread->key = $l['key'];
            $aThread->setTtitle($l['ttitle']);
            $aThread->rescount = $l['rescount'];
            $aThread->host = $l['host'];
            $aThread->bbs = $l['bbs'];
            $aThread->torder = $l['torder'];

            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) {$aThread->itaj = $aThread->bbs;}
            break;
        }

    // subject (not spmode つまり普通の板)
    } else {
        $aThread->getThreadInfoFromSubjectTxtLine($l);

        $aThread->host = $aThreadList->host;
        $aThread->bbs = $aThreadList->bbs;
    }

    // メモリ節約（特にmerge_favita）のため
    $lines[$x] = null;

    // hostかbbsかkeyが不明ならスキップ
    if (!($aThread->host && $aThread->bbs && $aThread->key)) {
        unset($aThread);
        continue;
    }

    $subject_id = $aThread->host . '/' . $aThread->bbs;

    // ここで一旦スレッドリストにまとめて、キャッシュもさせようかと思ったが、メモリ消費(750K→2M)が激しかったのでやめておいた。


    // {{{ 新しいかどうか(for subject)

    if (!$aThreadList->spmode) {
        if (!isset($pre_subject_keys[$aThread->key])) {
            $aThread->new = true;
        }
        $subject_keys[$aThread->key] = true;
    } elseif ($aThreadList->spmode == 'merge_favita') {
        if (!isset($pre_subject_keys[$subject_id][$aThread->key])) {
            $aThread->new = true;
        }
        $subject_keys[$subject_id][$aThread->key] = true;
    }

    // }}}
    // {{{ ■ワードフィルタ(for subject)

    //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('word_filter_for_sb');
    if ($do_filtering && !$spmode_without_palace_or_favita) {

        $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);

        // マッチしなければスキップ
        if (!matchSbFilter($aThread)) {
            unset($aThread);
            //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('word_filter_for_sb');
            continue;

        // マッチした時
        } else {
            $GLOBALS['sb_mikke_num']++;
            if ($_conf['ktai']) {
                if (is_string($_conf['k_filter_marker'])) {
                    $aThread->ttitle_ht = StrCtl::filterMarking($GLOBALS['word_fm'], $aThread->ttitle_hd, $_conf['k_filter_marker']);
                } else {
                    $aThread->ttitle_ht = $aThread->ttitle_hd;
                }
            } else {
                $aThread->ttitle_ht = StrCtl::filterMarking($GLOBALS['word_fm'], $aThread->ttitle_hd);
            }
        }
    } elseif (!$aThreadList->spmode && !empty($GLOBALS['wakati_words'])) {
        // 類似スレ検索
        if (!setSbSimilarity($aThread) || $aThread->similarity < $_conf['expack.min_similarity']) {
            unset($aThread);
            //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('word_filter_for_sb');
            continue;
        }
        if ($_conf['ktai']) {
            if (is_string($_conf['k_filter_marker'])) {
                $aThread->ttitle_ht = StrCtl::filterMarking($GLOBALS['wakati_hl_regex'], $aThread->ttitle_ht, $_conf['k_filter_marker']);
            }
        } else {
            $aThread->ttitle_ht = StrCtl::filterMarking($GLOBALS['wakati_hl_regex'], $aThread->ttitle_ht);
        }
    }
    //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('word_filter_for_sb');

    // }}}
    // {{{ ■スレッドあぼーんチェック

    //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('taborn_check_continue');
    if ($aThreadList->spmode != "taborn" && !empty($ta_keys[$aThread->key])) {
        unset($ta_keys[$aThread->key]);
        //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('taborn_check_continue');
        continue; // あぼーんスレはスキップ
    }
    //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('taborn_check_continue');

    // }}}

    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);

    // 既得スレッドデータをidxから取得
    $aThread->getThreadInfoFromIdx();

    if ($kitoku_only && !$aThread->isKitoku()) {
        unset($aThread);
        if ($do_filtering) {
            $GLOBALS['sb_mikke_num']--;
        }
        continue;
    }

    // {{{ ■ favlistチェック

    //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('favlist_check');
    // if ($x <= $threads_num) {
        if ($aThreadList->spmode != 'taborn' and isset($fav_keys[$aThread->key]) && $fav_keys[$aThread->key] == $aThread->bbs) {
            $aThread->fav = 1;
            unset($fav_keys[$aThread->key]);
        }
    // }
    //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('favlist_check');

    // }}}

    //  spmode(殿堂入り、merge_favitaを除く)なら ====================================
    if ($spmode_without_palace_or_favita && $sb_view != 'edit') {

        //  subject.txt が未DLなら落としてデータを配列に格納
        if (!isset($subject_txts[$subject_id])) {
            $subject_txts[$subject_id] = array();

            require_once P2_LIB_DIR . '/SubjectTxt.class.php';
            $aSubjectTxt = new SubjectTxt($aThread->host, $aThread->bbs);

            //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('subthre_read');
            if ($aThreadList->spmode == 'soko' or $aThreadList->spmode == 'taborn') {

                if (is_array($aSubjectTxt->subject_lines)) {
                    $it = 1;
                    foreach ($aSubjectTxt->subject_lines as $asbl) {
                        if (preg_match("/^([0-9]+)\.(?:dat|cgi)(?:,|<>)(.+) ?(?:\(|（)([0-9]+)(?:\)|）)/", $asbl, $matches)) {
                            $akey = $matches[1];
                            $subject_txts[$subject_id][$akey] = array(
                                'ttitle' => rtrim($matches[2]),
                                'rescount' => (int)$matches[3],
                                'torder' => $it,
                            );
                        }
                        $it++;
                    }
                }

            } else {
                $subject_txts[$subject_id] = $aSubjectTxt->subject_lines;

            }
            //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('subthre_read');
        }

        //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('subthre_check');
        // スレ情報取得 =============================
        if ($aThreadList->spmode == "soko" or $aThreadList->spmode == "taborn") {

            if (isset($subject_txts[$subject_id][$aThread->key])) {

                // 倉庫はオンラインを含まない
                if ($aThreadList->spmode == "soko") {
                    unset($aThread);
                    continue;
                } elseif ($aThreadList->spmode == "taborn") {
                    // $aThread->getThreadInfoFromSubjectTxtLine($l); // subject.txt からスレ情報取得
                    $aThread->isonline = true;
                    $ttitle = $subject_txts[$subject_id][$aThread->key]['ttitle'];
                    $aThread->setTtitle($ttitle);
                    $aThread->rescount = $subject_txts[$subject_id][$aThread->key]['rescount'];
                    if ($aThread->readnum) {
                        $aThread->unum = $aThread->rescount - $aThread->readnum;
                        // machi bbs はsageでsubjectの更新が行われないそうなので調整しておく
                        if ($aThread->unum < 0) { $aThread->unum = 0; }
                    }
                    $aThread->torder = $subject_txts[$subject_id][$aThread->key]['torder'];
                }

            }

        } else {

            if (isset($subject_txts[$subject_id])) {
                $it = 1;
                $thread_key = (string)$aThread->key;
                $thread_key_len = strlen($aThread->key);
                foreach ($subject_txts[$subject_id] as $l) {
                    if (substr($l, 0, $thread_key_len) == $thread_key) {
                        // subject.txt からスレ情報取得
                        $aThread->getThreadInfoFromSubjectTxtLine($l);
                        break;
                    }
                    $it++;
                }
            }

        }
        //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('subthre_check');

        if ($aThreadList->spmode == "taborn") {
            if (!$aThread->torder) { $aThread->torder = '-'; }
        }


        // {{{ ■新着のみ(for spmode)

        if ($sb_view == 'shinchaku' and !isset($_REQUEST['word'])) {
            if ($aThread->unum < 1) {
                unset($aThread);
                continue;
            }
        }

        // }}}
        // {{{ ■ワードフィルタ(for spmode)

        if ($do_filtering) {

            // マッチしなければスキップ
            if (!matchSbFilter($aThread)) {
                unset($aThread);
                continue;

            // マッチした時
            } else {
                $GLOBALS['sb_mikke_num']++;
                if ($_conf['ktai']) {
                    $aThread->ttitle_ht = $aThread->ttitle_hd;
                } else {
                    $aThread->ttitle_ht = StrCtl::filterMarking($GLOBALS['word_fm'], $aThread->ttitle_hd);
                }
            }
        }

        // }}}
    }

    //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('FORLOOP_HIP');

    // subjexctからrescountが取れなかった場合は、gotnumを利用する。
    if ((!$aThread->rescount) and $aThread->gotnum) {
        $aThread->rescount = $aThread->gotnum;
    }
    if (!$aThread->ttitle_ht) { $aThread->ttitle_ht = $aThread->ttitle_hd; }

    // 新着あり
    if ($aThread->unum > 0) {
        $shinchaku_attayo = true;
        $shinchaku_num = $shinchaku_num + $aThread->unum; // 新着数set

    /*
    // お気にスレ
    } elseif ($aThread->fav) {
        ;

    // 新規スレ
    } elseif ($aThread->new) {
        ;
    */

    }

    // {{{ 新着ソートの便宜上 （未取得スレッドの）unum をセット調整

    if (!isset($aThread->unum)) {
        if ($aThreadList->spmode == "recent" or $aThreadList->spmode == "res_hist" or $aThreadList->spmode == "taborn") {
            $aThread->unum = -0.1;
        } else {
            $aThread->unum = $_conf['sort_zero_adjust'];
        }
    }

    // }}}

    // 勢いのセット
    $aThread->setDayRes($nowtime);

    // 生存数set
    if ($aThread->isonline) { $online_num++; }

    // リストに追加
    $aThreadList->addThread($aThread);

    unset($aThread);

    //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('FORLOOP_HIP');
}

unset($lines);

//$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('FORLOOP');

//$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('FOOT');

// 既にdat落ちしているスレは自動的にあぼーんを解除する
autoTAbornOff($aThreadList, $ta_keys);

// ソート
sortThreads($aThreadList);

// ソート後、お気に板の既得スレidxを作成 (新着まとめ読みの効率を良くするためのキャッシュ)
if ($spmode == 'merge_favita') {
    if ($_conf['expack.misc.multi_favs'] && !empty($_conf['m_favita_set'])) {
        $merged_faivta_read_idx = $_conf['pref_dir'] . '/p2_favita' . $_conf['m_favita_set'] . '_read.idx';
    } else {
        $merged_faivta_read_idx = $_conf['pref_dir'] . '/p2_favita_read.idx';
    }

    FileCtl::make_datafile($merged_faivta_read_idx, $_conf['p2_perm']);
    $fp = fopen($merged_faivta_read_idx, 'wb');
    if (!$fp || !flock($fp, LOCK_EX)) {
        die("Error: cannot write file {$merged_faivta_read_idx}.");
    }

    foreach ($aThreadList->threads as $aThread) {
        if ($aThread->isKitoku()) {
            fwrite($fp,
                   sprintf("%s<>%d<><><><>%d<><><><>%d<>%s<>%s\n",
                           $aThread->ttitle,
                           $aThread->key,
                           $aThread->readnum,
                           $aThread->readnum + 1, // newline 旧互換のため
                           $aThread->host,
                           $aThread->bbs
                           )
                   );
        }
    }

    flock($fp, LOCK_UN);
    fclose($fp);
}

//===============================================================
// プリント
//===============================================================
// 携帯
if ($_conf['ktai']) {

    // {{{ 倉庫にtorder付与

    if ($aThreadList->spmode == "soko") {
        if ($aThreadList->threads) {
            $soko_torder = 1;
            foreach ($aThreadList->threads as $at) {
                $at->torder = $soko_torder++;
            }
        }
    }

    // }}}
    // {{{ 表示数制限

    // 念のため、補正しておく
    $aThreadList->num = count($aThreadList->threads);
    $sb_disp_all_num = $aThreadList->num;

    $disp_navi = P2Util::getListNaviRange($sb_disp_from , $_conf['k_sb_disp_range'], $sb_disp_all_num);
    if ($aThreadList->threads) {
        $aThreadList->threads = array_slice($aThreadList->threads, $disp_navi['offset'], $disp_navi['limit']);
    }
    $aThreadList->num = sizeof($aThreadList->threads);

    // }}}

    // ヘッダプリント
    include_once P2_LIB_DIR . '/sb_header_k.inc.php';

    // メインプリント
    include_once P2_LIB_DIR . '/sb_print_k.inc.php'; // スレッドサブジェクトメイン部分HTML表示関数
    sb_print_k($aThreadList);

    // フッタプリント
    include_once P2_LIB_DIR . '/sb_footer_k.inc.php';

// PC
} else {
    // {{{ 表示数制限

    // 念のため、補正しておく
    $aThreadList->num = count($aThreadList->threads);
    $threads_num = max(1, (int)$threads_num);

    if ($_conf['viewall_kitoku']) {
        if (!$kitoku_only) {
            $read_threads = array();

            while ($aThreadList->num > $threads_num) {
                $x = --$aThreadList->num;
                if ($aThreadList->threads[$x]->isKitoku()) {
                    $read_threads[] = $aThreadList->threads[$x];
                }
                unset($aThreadList->threads[$x]);
            }

            foreach ($read_threads as $aThread) {
                $aThreadList->threads[] = $aThread;
            }

            unset($read_threads);
        }
    } else {
        while ($aThreadList->num > $threads_num) {
            unset($aThreadList->threads[--$aThreadList->num]);
        }
    }

    // }}}

    // ヘッダHTMLを表示
    //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('sb_header');
    include_once P2_LIB_DIR . '/sb_header.inc.php';
    flush();
    //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('sb_header');

    // スレッドサブジェクトメイン部分HTML表示
    include_once P2_LIB_DIR . '/sb_print.inc.php'; // スレッドサブジェクトメイン部分HTML表示関数
    sb_print($aThreadList);

    // フッタHTML表示
    //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('sb_footer');
    include_once P2_LIB_DIR . '/sb_footer.inc.php';
    //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('sb_footer');
}

//==============================================================
// 後処理
//==============================================================

// p2_setting（sb設定） 記録
saveSbSetting($p2_setting_txt, $p2_setting, $pre_setting);

// $subject_keys をシリアライズして保存する
if (!$spmode) {
    saveSubjectKeys($subject_keys, $sb_keys_txt, $sb_keys_b_txt);
} elseif ($spmode == 'merge_favita') {
    foreach ($sb_key_txts as $id => $txts) {
        saveSubjectKeys($subject_keys[$id], $txts[0], $txts[1]);
    }
}

//$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('FOOT');

// ここまで
exit;


//==============================================================
// 関数
//==============================================================
/**
 * 既にdat落ちしているスレは自動的にあぼーんを解除する
 * $ta_keys はあぼーんリストに入っていたけれど、あぼーんされずに残ったスレたち
 */
function autoTAbornOff($aThreadList, $ta_keys)
{
    global $_info_msg_ht;

    //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('abornoff');

    if (!$aThreadList->spmode && !empty($GLOBALS['word']) && !empty($GLOBALS['wakati_word']) && $aThreadList->threads && $ta_keys) {
        include_once P2_LIB_DIR . '/settaborn_off.inc.php';
        // echo sizeof($ta_keys)."*<br>";
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

    //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('abornoff');

    return true;
}

/**
 * スレ一覧（$aThreadList->threads）をソートする
 */
function sortThreads($aThreadList)
{
    global $_conf;

    if (!$aThreadList->threads) {
        return;
    }

    //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('sort');

    $cmp = null;

    if (!empty($GLOBALS['wakati_words'])) {
        $GLOBALS['now_sort'] = 'title';
        $cmp = 'cmp_similarity';
    } else {
        switch ($GLOBALS['now_sort']) {
        case 'midoku':
            if ($aThreadList->spmode == 'soko') {
                $cmp = 'cmp_key';
            } else {
                $cmp = 'cmp_midoku';
            }
            break;
        case 'ikioi':
        case 'spd':
            if ($_conf['cmp_dayres_midoku']) {
                $cmp = 'cmp_dayres_midoku';
            } else {
                $cmp = 'cmp_dayres';
            }
            break;
        case 'no':
            if ($aThreadList->spmode == 'soko') {
                $cmp = 'cmp_key';
            } else {
                $cmp = 'cmp_no';
            }
            break;
        case 'bd':
        case 'fav':
        case 'ita':
        case 'res':
        case 'title':
            $cmp = 'cmp_' . $GLOBALS['now_sort'];
            break;
        }
    }

    if ($cmp) {
        usort($aThreadList->threads, $cmp);
    }

    if (!empty($_REQUEST['rsort'])) {
        $aThreadList->threads = array_reverse($aThreadList->threads);
    }

    //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('sort');

    return true;
}

/**
 * p2_setting 記録する
 */
function saveSbSetting($p2_setting_txt, $p2_setting, $pre_setting)
{
    global $_conf;

    //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('save_p2_setting');
    if ($pre_setting['viewnum'] != $p2_setting['viewnum'] or $pre_setting['sort'] != $GLOBALS['now_sort'] or $pre_setting['itaj'] != $p2_setting['itaj']) {
        if (!empty($_POST['sort'])) {
            $p2_setting['sort'] = $_POST['sort'];
        } elseif (!empty($_GET['sort'])) {
            $p2_setting['sort'] = $_GET['sort'];
        }
        FileCtl::make_datafile($p2_setting_txt, $_conf['p2_perm']);
        if ($p2_setting) {
            if ($p2_setting_cont = serialize($p2_setting)) {
                if (FileCtl::file_write_contents($p2_setting_txt, $p2_setting_cont) === false) {
                    die("Error: cannot write file.");
                }
            }
        }
    }
    //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('save_p2_setting');

    return true;
}

/**
 * $subject_keys を取得する
 */
function getSubjectKeys($sb_keys_txt, $sb_keys_b_txt)
{
    // 更新しない場合は、2つ前のと１つ前のを比べて、新規スレを調べる
    if (!empty($_REQUEST['norefresh']) || (empty($_REQUEST['refresh']) && isset($_REQUEST['word']))) {
        $file = $sb_keys_b_txt;
    } else {
        $file = $sb_keys_txt;
    }

    if (file_exists($file) && $cont = FileCtl::file_read_contents($file)) {
        if (is_array($subject_keys = @unserialize($cont))) {
            return $subject_keys;
        }
    }
    return array();
}

/**
 * $subject_keys をシリアライズして保存する
 */
function saveSubjectKeys($subject_keys, $sb_keys_txt, $sb_keys_b_txt)
{
    global $_conf;

    //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('saveSubjectKeys()');
    //if (file_exists($sb_keys_b_txt)) { unlink($sb_keys_b_txt); }
    if (empty($_REQUEST['norefresh']) && !empty($subject_keys)) {
        if (file_exists($sb_keys_txt)) {
            FileCtl::make_datafile($sb_keys_b_txt, $_conf['p2_perm']);
            copy($sb_keys_txt, $sb_keys_b_txt);
        } else {
            FileCtl::make_datafile($sb_keys_txt, $_conf['p2_perm']);
        }
        if ($sb_keys_cont = serialize($subject_keys)) {
            if (FileCtl::file_write_contents($sb_keys_txt, $sb_keys_cont) === false) {
                die("Error: cannot write file.");
            }
        }
    }
    //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('saveSubjectKeys()');

    return true;
}

/**
 * スレタイ（と本文）でマッチしたらtrueを返す
 */
function matchSbFilter($aThread)
{
    // 全文検索でdatがあれば、内容を読み込む
    if (!empty($_REQUEST['find_cont']) && file_exists($aThread->keydat)) {
        $dat_cont = file_get_contents($aThread->keydat);
    }

    if ($GLOBALS['sb_filter']['method'] == "and") {
        reset($GLOBALS['words_fm']);
        foreach ($GLOBALS['words_fm'] as $word_fm_ao) {
            // 全文検索でdatがあれば、内容を検索
            if (!empty($_REQUEST['find_cont']) && file_exists($aThread->keydat)) {
                // be.2ch.net はEUC
                if (P2Util::isHostBe2chNet($aThread->host)) {
                   $target_cont = mb_convert_encoding($word_fm_ao, 'CP51932', 'CP932');
                }
                if (!StrCtl::filterMatch($target_cont, $dat_cont)) {
                   return false;
                }

            // スレタイを検索
            } elseif (!StrCtl::filterMatch($word_fm_ao, $aThread->ttitle)) {
                return false;
            }
        }

    } else {
        // 全文検索でdatがあれば、内容を検索
        if (!empty($_REQUEST['find_cont']) && file_exists($aThread->keydat)) {
            $target_cont = $GLOBALS['word_fm'];
            // be.2ch.net はEUC
            if (P2Util::isHostBe2chNet($aThread->host)) {
                $target_cont = mb_convert_encoding($target_cont, 'CP51932', 'CP932');
            }
            if (!StrCtl::filterMatch($target_cont, $dat_cont)) {
                return false;
            }

        // スレタイだけ検索
        } elseif (!StrCtl::filterMatch($GLOBALS['word_fm'], $aThread->ttitle)) {
            return false;
        }
    }

    return true;
}

/**
 * スレッドタイトルのスコアを計算して返す
 */
function getSbScore($words, $length)
{
    static $bracket_regex = null;
    if (!$bracket_regex) {
        $bracket_regex = mb_convert_encoding('/[\\[\\]{}()（）「」【】]/u', 'UTF-8', 'CP932');
    }
    $score = 0.0;
    if ($length) {
        foreach ($words as $word) {
            $chars = mb_strlen($word, 'UTF-8');
            if ($chars == 1 && preg_match($bracket_regex, $word)) {
                $score += 0.1 / $length;
            } elseif ($word == 'part') {
                $score += 1.0 / $length;
            } else {
                $revision = strlen($word) / mb_strwidth($word, 'UTF-8');
                //$score += pow($chars * $revision, 2) / $length;
                $score += $chars * $chars * $revision / $length;
                //$score += $chars * $chars / $length;
            }
        }
        if ($length > $GLOBALS['wakati_length']) {
            $score *= $GLOBALS['wakati_length'] / $length;
        } else {
            $score *= $length / $GLOBALS['wakati_length'];
        }
    }
    return $score;
}

/**
 * スレッドタイトルの類似性を計算して返す
 */
function setSbSimilarity($aThread)
{
    $common_words = array_intersect(wakati($aThread->ttitle_hc), $GLOBALS['wakati_words']);
    if (!$common_words) {
        $aThread->similarity = 0.0;
        return false;
    }
    $score = getSbScore($common_words, mb_strlen($aThread->ttitle_hc, 'CP932'));
    $aThread->similarity = $score / $GLOBALS['wakati_score'];
    // debug (title 属性)
    //$aThread->ttitle_hd = mb_convert_encoding(htmlspecialchars(implode(' ', $common_words)), 'CP932', 'UTF-8');
    return true;
}

//============================================================
// ソート関数
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
    if ($a->host != $b->host) {
        return strcmp($a->host, $b->host);
    } else {
        if ($a->itaj != $b->itaj) {
            return strcmp($a->itaj, $b->itaj);
        } else {
            return ($a->torder > $b->torder) ? 1 : -1;
        }
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

/**
 * 類似性ソート
 */
function cmp_similarity($a, $b)
{
    if ($a->similarity == $b->similarity) {
        return ($a->key < $b->key) ? 1 : -1;
    } else {
        return ($a->similarity < $b->similarity) ? 1 : -1;
    }
}
