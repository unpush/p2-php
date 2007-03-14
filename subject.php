<?php
/*
    p2 -  スレッドサブジェクト表示スクリプト
    フレーム分割画面、右上部分

    subject_new.php と兄弟なので、一緒に面倒をみること
*/

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/threadlist.class.php';
require_once P2_LIB_DIR . '/thread.class.php';
require_once P2_LIB_DIR . '/filectl.class.php';

$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('HEAD');

$_login->authorize(); // ユーザ認証

//============================================================
// 変数設定
//============================================================
$newtime = date('gis');
$nowtime = time();

$abornoff_st = 'あぼーん解除';
$deletelog_st = 'ログを削除';

if (isset($_GET['from']))   { $sb_disp_from = $_GET['from']; }
if (isset($_POST['from']))  { $sb_disp_from = $_POST['from']; }
if (!isset($sb_disp_from))  { $sb_disp_from = 1; }

// {{{ ホスト、板、モード設定

if (isset($_GET['host']))   { $host =   $_GET['host']; }
if (isset($_POST['host']))  { $host =   $_POST['host']; }
if (isset($_GET['bbs']))    { $bbs =    $_GET['bbs']; }
if (isset($_POST['bbs']))   { $bbs =    $_POST['bbs']; }

$spmode = null;
if (isset($_GET['spmode'])) { $spmode = $_GET['spmode']; }
if (isset($_POST['spmode'])){ $spmode = $_POST['spmode']; }

if ((empty($host) || !isset($bbs)) && !$spmode) {
    die('p2 error: 必要な引数が指定されていません');
}

// }}}
// {{{ p2_setting, sb_keys 設定

if ($spmode) {
    $p2_setting_txt = $_conf['pref_dir'] . '/p2_setting_' . $spmode . '.txt';
    $sb_keys_b_txt = null;
    $sb_keys_txt = null;
    
} else {
    $idx_host_dir = P2Util::idxDirOfHost($host);
    $idx_bbs_dir_s = $idx_host_dir . '/' . $bbs . '/';
    
    $p2_setting_txt = $idx_bbs_dir_s . 'p2_setting.txt';
    $sb_keys_b_txt =  $idx_bbs_dir_s . 'p2_sb_keys_b.txt';
    $sb_keys_txt =    $idx_bbs_dir_s . 'p2_sb_keys.txt';

    // 更新しない場合は、2つ前のと１つ前のを比べて、新規スレを調べる
    if (!empty($_REQUEST['norefresh']) || !empty($_REQUEST['word'])) {
        if ($prepre_sb_cont = @file_get_contents($sb_keys_b_txt)) {
            $prepre_sb_keys = unserialize($prepre_sb_cont);
        }
    } else {
        if ($pre_sb_cont = @file_get_contents($sb_keys_txt)) {
            $pre_sb_keys = unserialize($pre_sb_cont);
        }
    }
}

// }}}
// {{{ p2_setting 読み込み、セット

$p2_setting = array();

if ($p2_setting_cont = @file_get_contents($p2_setting_txt)) {
    $p2_setting = unserialize($p2_setting_cont);
}

$pre_setting['viewnum'] = isset($p2_setting['viewnum']) ? $p2_setting['viewnum'] : null;
$pre_setting['sort'] =    isset($p2_setting['sort']) ? $p2_setting['sort'] : null;
$pre_setting['itaj'] =    isset($p2_setting['itaj']) ? $p2_setting['itaj'] : null;

if (isset($_GET['sb_view']))  { $sb_view = $_GET['sb_view']; }
if (isset($_POST['sb_view'])) { $sb_view = $_POST['sb_view']; }
if (empty($sb_view)) { $sb_view = "normal";}

if (isset($_GET['viewnum']))  { $p2_setting['viewnum'] = $_GET['viewnum']; }
if (isset($_POST['viewnum'])) { $p2_setting['viewnum'] = $_POST['viewnum']; }
if (!isset($p2_setting['viewnum'])) {
    $p2_setting['viewnum'] = $_conf['display_threads_num']; // デフォルト値
}

if (isset($_GET['itaj_en'])) {
    $p2_setting['itaj'] = base64_decode($_GET['itaj_en']);
}

// }}}
// {{{ ソートの指定

if (!empty($_POST['sort'])) {
    $GLOBALS['now_sort'] = $_POST['sort'];
} elseif (!empty($_GET['sort'])) {
    $GLOBALS['now_sort'] = $_GET['sort'];
}

if (empty($GLOBALS['now_sort'])) {
    if (!empty($p2_setting['sort'])) {
        $GLOBALS['now_sort'] = $p2_setting['sort'];
    } else {
        if (empty($spmode)) {
            $GLOBALS['now_sort'] = (!empty($_conf['sb_sort_ita'])) ? $_conf['sb_sort_ita'] : 'ikioi'; // 勢い
        } else {
            $GLOBALS['now_sort'] = 'midoku'; // 新着
        }
    }
}

// }}}
// {{{ 表示スレッド数設定

$threads_num_max = 2000;

if (empty($spmode) || $spmode == 'news') {
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

$GLOBALS['word_fm'] = null;
$GLOBALS['wakati_word'] = null;

// 「更新」ではなくて、検索指定があれば
if (empty($_REQUEST['submit_refresh']) or !empty($_REQUEST['submit_kensaku'])) {
    
    if (isset($_GET['word'])) {
        $GLOBALS['word'] = $_GET['word'];
    } elseif (isset($_POST['word'])) {
        $GLOBALS['word'] = $_POST['word'];
    } else {
        $GLOBALS['word'] = null;
    }
    
    if (isset($_GET['method'])) {
        $sb_filter['method'] = $_GET['method'];
    } elseif (isset($_POST['method'])) {
        $sb_filter['method'] = $_POST['method'];
    }

    if (isset($sb_filter['method']) and $sb_filter['method'] == 'similar') {
        $GLOBALS['wakati_word'] = $GLOBALS['word'];
        $GLOBALS['wakati_words'] = wakati($GLOBALS['word']);
        
        if (!$GLOBALS['wakati_words']) {
            unset($GLOBALS['wakati_word'], $GLOBALS['wakati_words']);
        } else {
            require_once P2_LIB_DIR . '/strctl.class.php';
            $wakati_words2 = array_filter($GLOBALS['wakati_words'], 'wakatiFilter');
            
            if (!$wakati_words2) {
                $GLOBALS['wakati_hl_regex'] = $GLOBALS['wakati_word'];
            } else {
                rsort($wakati_words2, SORT_STRING);
                $GLOBALS['wakati_hl_regex'] = implode(' ', $wakati_words2);
                $GLOBALS['wakati_hl_regex'] = mb_convert_encoding($GLOBALS['wakati_hl_regex'], 'SJIS-win', 'UTF-8');
            }
            
            $GLOBALS['wakati_hl_regex'] = StrCtl::wordForMatch($GLOBALS['wakati_hl_regex'], 'or');
            $GLOBALS['wakati_hl_regex'] = str_replace(' ', '|', $GLOBALS['wakati_hl_regex']);
            $GLOBALS['wakati_length'] = mb_strlen($GLOBALS['wakati_word'], 'SJIS-win');
            $GLOBALS['wakati_score'] = getSbScore($GLOBALS['wakati_words'], $GLOBALS['wakati_length']);
            
            if (!isset($_conf['expack.min_similarity'])) {
                $_conf['expack.min_similarity'] = 0.05;
            } elseif ($_conf['expack.min_similarity'] > 1) {
                $_conf['expack.min_similarity'] /= 100;
            }
            if (count($GLOBALS['wakati_words']) == 1) {
                $_conf['expack.min_similarity'] /= 100;
            }
            $_conf['expack.min_similarity'] = (float) $_conf['expack.min_similarity'];
        }
        $GLOBALS['word'] = '';
        
    } elseif (preg_match('/^\.+$/', $word)) {
        $GLOBALS['word'] = '';
    }
    
    if (strlen($word) > 0)  {
        
        // デフォルトオプション
        if (!$sb_filter['method']) { $sb_filter['method'] = "or"; } // $sb_filter は global @see sb_print.icn.php
        
        require_once P2_LIB_DIR . '/strctl.class.php';
        $GLOBALS['word_fm'] = StrCtl::wordForMatch($word, $sb_filter['method']);
        if ($sb_filter['method'] != 'just') {
            if (P2_MBREGEX_AVAILABLE == 1) {
                $GLOBALS['words_fm'] = mb_split('\s+', $GLOBALS['word_fm']);
                $GLOBALS['word_fm'] = mb_ereg_replace('\s+', '|', $GLOBALS['word_fm']);
            } else {
                $GLOBALS['words_fm'] = preg_split('/\s+/', $GLOBALS['word_fm']);
                $GLOBALS['word_fm'] = preg_replace('/\s+/', '|', $GLOBALS['word_fm']);
            }
        }
    }
}

// }}}

//============================================================
// 特殊な前処理
//============================================================
// {{{ 削除

if (!empty($_GET['dele']) or (isset($_POST['submit']) and $_POST['submit'] == $deletelog_st)) {
    if ($host && $bbs) {
        require_once P2_LIB_DIR . '/dele.inc.php';
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
    require_once P2_LIB_DIR . '/setfav.inc.php';
    setFav($host, $bbs, $_GET['key'], $_GET['setfav']);

// 殿堂入り
} elseif (isset($_GET['setpal']) && $_GET['key'] && $host && $bbs) {
    require_once P2_LIB_DIR . '/setpalace.inc.php';
    setPal($host, $bbs, $_GET['key'], $_GET['setpal']);

// あぼーんスレッド解除
} elseif ((isset($_POST['submit']) and $_POST['submit'] == $abornoff_st) && $host && $bbs && !empty($_POST['checkedkeys'])) {
    require_once P2_LIB_DIR . '/settaborn_off.inc.php';
    settaborn_off($host, $bbs, $_POST['checkedkeys']);

// スレッドあぼーん
} elseif (isset($_GET['taborn']) && !is_null($_GET['key']) && $host && $bbs) {
    require_once P2_LIB_DIR . '/settaborn.inc.php';
    settaborn($host, $bbs, $_GET['key'], $_GET['taborn']);
}

//============================================================
// メイン
//============================================================

$aThreadList =& new ThreadList();

// 板とモードのセット ===================================
if ($spmode) {
    if ($spmode == 'taborn' or $spmode == 'soko') {
        $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));
    }
    $aThreadList->setSpMode($spmode);
} else {
    // if(!$p2_setting['itaj']){$p2_setting['itaj'] = P2Util::getItaName($host, $bbs);}
    $aThreadList->setIta($host, $bbs, $p2_setting['itaj']);
    
    // {{{ スレッドあぼーんリスト読込
    
    $idx_host_dir = P2Util::idxDirOfHost($aThreadList->host);
    $taborn_file = $idx_host_dir.'/'.$aThreadList->bbs.'/p2_threads_aborn.idx';

    $ta_num = 0;
    if ($tabornlines = @file($taborn_file)) {
        $ta_num = sizeof($tabornlines);
        foreach ($tabornlines as $l) {
            $data = explode('<>', rtrim($l));
            $ta_keys[$data[1]] = true;
        }
    }
    
    // }}}

}

// ソースリスト読込
$lines = $aThreadList->readList();

// お気にスレリスト 読込
$favlines = @file($_conf['favlist_file']);
if (is_array($favlines)) {
    foreach ($favlines as $l) {
        $data = explode('<>', rtrim($l));
        $fav_keys[ $data[1] ] = $data[11];
    }
}

$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('HEAD');

//============================================================
// それぞれの行解析
//============================================================
$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('FORLOOP');

$online_num = 0;
$shinchaku_num = 0;

$linesize = sizeof($lines);

for ($x = 0; $x < $linesize; $x++) {

    $l = rtrim($lines[$x]);
    
    $aThread =& new Thread();
    
    if ($aThreadList->spmode != 'taborn' and $aThreadList->spmode != 'soko') {
        $aThread->torder = $x + 1;
    }

    // ■データ読み込み
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
        case "news":    // ニュースの勢い
            $aThread->isonline = true;
            $aThread->key = $l['key'];
            $aThread->setTtitle($l['ttitle']);
            $aThread->rescount = $l['rescount'];
            $aThread->host = $l['host'];
            $aThread->bbs = $l['bbs'];

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

    // hostかbbsかkeyが不明ならスキップ
    if (!($aThread->host && $aThread->bbs && $aThread->key)) {
        unset($aThread);
        continue;
    } 
    
    
    // ここで一旦スレッドリストにまとめて、キャッシュもさせようかと思ったが、メモリ消費(750K→2M)が激しかったのでやめておいた。
    
    
    // {{{ 新しいかどうか(for subject)
    
    if (!$aThreadList->spmode) {
        if (!empty($_REQUEST['norefresh']) || !empty($_REQUEST['word'])) {
            if (empty($prepre_sb_keys[$aThread->key])) {
                $aThread->new = true;
            }
        } else {
            if (empty($pre_sb_keys[$aThread->key])) {
                $aThread->new = true;
            }
            $subject_keys[$aThread->key] = true;
        }
    }
    
    // }}}
    // {{{ ワードフィルタ(for subject)
    
    $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('word_filter_for_sb');
    if (!$aThreadList->spmode || $aThreadList->spmode == "news" and (strlen($GLOBALS['word_fm']) > 0)) {
        
        $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);

        // マッチしなければスキップ
        if (!matchSbFilter($aThread)) {
            unset($aThread);
            $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('word_filter_for_sb');
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
            $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('word_filter_for_sb');
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
    $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('word_filter_for_sb');
    
    // }}}
    // {{{ スレッドあぼーんチェック
    
    $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('taborn_check_continue');
    if ($aThreadList->spmode != "taborn" and isset($ta_keys[$aThread->key]) && $ta_keys[$aThread->key]) { 
        unset($ta_keys[$aThread->key]);
        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('taborn_check_continue');
        continue; // あぼーんスレはスキップ
    }
    $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('taborn_check_continue');
    
    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
    // 既得スレッドデータをidxから取得
    $aThread->getThreadInfoFromIdx();

    // }}}
    // {{{ favlistチェック
    
    $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('favlist_check');
    // if ($x <= $threads_num) {
        if ($aThreadList->spmode != 'taborn' and isset($fav_keys[$aThread->key]) && $fav_keys[$aThread->key] == $aThread->bbs) {
            $aThread->fav = 1;
            unset($fav_keys[$aThread->key]);
        }
    // }
    $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('favlist_check');
    
    // }}}
    
    // ■ spmode(殿堂入り、newsを除く)なら ====================================
    if ($aThreadList->spmode && $aThreadList->spmode != "news" && $sb_view != "edit") { 
        
        // {{{ subject.txt が未DLなら落としてデータを配列に格納する
        
        if (empty($subject_txts["$aThread->host/$aThread->bbs"])) {

            require_once P2_LIB_DIR . '/SubjectTxt.class.php';
            $aSubjectTxt =& new SubjectTxt($aThread->host, $aThread->bbs);
            
            $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('subthre_read');
            if ($aThreadList->spmode == 'soko' or $aThreadList->spmode == 'taborn') {

                if (is_array($aSubjectTxt->subject_lines)) {
                    $it = 1;
                    foreach ($aSubjectTxt->subject_lines as $asbl) {
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
                $subject_txts["$aThread->host/$aThread->bbs"] = $aSubjectTxt->subject_lines;

            }
            $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('subthre_read');
        }
        
        // }}}
        // {{{ スレ情報を取得する
        
        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('subthre_check');
        
        if ($aThreadList->spmode == "soko" or $aThreadList->spmode == "taborn") {
        
            // オンライン上に存在するなら
            if (!empty($subject_txts[$aThread->host . "/" . $aThread->bbs][$aThread->key])) {
            
                // 倉庫はオンラインを含まない
                if ($aThreadList->spmode == "soko") {
                    unset($aThread);
                    continue;
                    
                } elseif ($aThreadList->spmode == "taborn") {
                    // $aThread->getThreadInfoFromSubjectTxtLine($l); // subject.txt からスレ情報取得
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
        
            if (!empty($subject_txts[$aThread->host . "/" . $aThread->bbs])) {
                $it = 1;
                foreach ($subject_txts[$aThread->host . "/" . $aThread->bbs] as $l) {
                    if (preg_match('/^' . preg_quote($aThread->key, '/') . '/', $l)) {
                        // subject.txt からスレ情報取得
                        $aThread->getThreadInfoFromSubjectTxtLine($l);
                        break;
                    }
                    $it++;
                }
            }
        
        }
        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('subthre_check');
        
        // }}}
        
        if ($aThreadList->spmode == "taborn") {
            if (!$aThread->torder) { $aThread->torder = '-'; }
        }

        
        // {{{ 新着のみ(for spmode)
        
        if ($sb_view == 'shinchaku' and !isset($_REQUEST['word'])) {
            if ($aThread->unum < 1) {
                unset($aThread);
                continue;
            }
        }
        
        // }}}
        // {{{ ワードフィルタ(for spmode)
        
        if (strlen($GLOBALS['word_fm']) > 0) {

            // マッチしなければスキップ
            if (!matchSbFilter($aThread)) {
                unset($aThread);
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
        }
        
        // }}}
    }
    
    $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('FORLOOP_HIP');
    
    // subjexctからrescountが取れなかった場合は、gotnumを利用する。
    if ((!$aThread->rescount) and $aThread->gotnum) {
        $aThread->rescount = $aThread->gotnum;
    }
    if (!$aThread->ttitle_ht) { $aThread->ttitle_ht = $aThread->ttitle_hd; }
    
    // 新着あり
    if ($aThread->unum > 0) {
        $shinchaku_attayo = true;
        $shinchaku_num += $aThread->unum; // 新着数set
    } elseif ($aThread->fav) { // お気にスレ
        ;
    } elseif ($aThread->new) { // 新規スレ
        ;
    // 既得スレ
    } elseif ($_conf['viewall_kitoku'] && $aThread->isKitoku()) {
        ;
        
    } else {
        // 携帯、ニュースチェック以外で
        if (!$_conf['ktai'] and $spmode != "news") {
            // 指定数を越えていたらカット
            if ($x >= $threads_num) {
                unset($aThread);
                $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('FORLOOP_HIP');
                continue;
            }
        }
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
    
    $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('FORLOOP_HIP');
}

$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('FORLOOP');

$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('FOOT');

// 既にdat落ちしているスレは自動的にあぼーんを解除する
autoTAbornOff($aThreadList, $ta_keys);

// ソート
sortThreads($aThreadList);

//===============================================================
// プリント
//===============================================================
// 携帯
if ($_conf['ktai']) {
    
    // {{{ 倉庫にtorder付与
    
    if ($aThreadList->spmode == "soko") {
        if ($aThreadList->threads) {
            $soko_torder = 1;
            $newthreads = array();
            foreach ($aThreadList->threads as $at) {
                $at->torder = $soko_torder++;
                $newthreads[] = $at;
                unset($at);
            }
            $aThreadList->threads =& $newthreads;
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
            $newthreads[] =& $aThreadList->threads[$i-1];
        }
    }
    $aThreadList->threads =& $newthreads;
    unset($newthreads);
    $aThreadList->num = sizeof($aThreadList->threads);
    
    // }}}
    
    // ヘッダHTMLプリント
    require_once P2_LIB_DIR . '/sb_header_k.inc.php';
    
    // メインHTMLプリント
    require_once P2_LIB_DIR . '/sb_print_k.inc.php'; // スレッドサブジェクトメイン部分HTML表示関数
    sb_print_k($aThreadList);
    
    // フッタHTMLプリント
    require_once P2_LIB_DIR . '/sb_footer_k.inc.php';

// PC
} else {
    // ヘッダHTMLを表示
    $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('sb_header');
    require_once P2_LIB_DIR . '/sb_header.inc.php';
    flush();
    $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('sb_header');
    
    // スレッドサブジェクトメイン部分HTML表示
    require_once P2_LIB_DIR . '/sb_print.inc.php'; // スレッドサブジェクトメイン部分HTML表示関数
    sb_print($aThreadList);
    
    // フッタHTML表示
    $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('sb_footer');
    require_once P2_LIB_DIR . '/sb_footer.inc.php';
    $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('sb_footer');
}

//==============================================================
// 後処理
//==============================================================

// p2_setting（sb設定） 記録
saveSbSetting($p2_setting_txt, $p2_setting, $pre_setting);

// $subject_keys をシリアライズして保存する
saveSubjectKeys($subject_keys, $sb_keys_txt, $sb_keys_b_txt);

$debug && $profiler->leaveSection('FOOT');


exit;


//==================================================================
// 関数（このファイル内でのみ利用）
//==================================================================
/**
 * 既にdat落ちしているスレは自動的にあぼーんを解除する
 * $ta_keys はあぼーんリストに入っていたけれど、あぼーんされずに残ったスレたち
 */
function autoTAbornOff(&$aThreadList, &$ta_keys)
{
    global $ta_num;
    
    $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('abornoff');
    
    if (!$aThreadList->spmode and !$GLOBALS['word'] and !$GLOBALS['wakati_word'] and $aThreadList->threads and $ta_keys) {
        require_once P2_LIB_DIR . '/settaborn_off.inc.php';
        // echo sizeof($ta_keys)."*<br>";
        $ta_vkeys = array_keys($ta_keys);
        settaborn_off($aThreadList->host, $aThreadList->bbs, $ta_vkeys);
        $ks = '';
        foreach ($ta_vkeys as $k) {
            $ta_num--;
            if ($k) {
                $ks .= "key:$k ";
            }
        }
        $ks && P2Util::pushInfoHtml("<div class=\"info\">　p2 info: DAT落ちしたスレッドあぼーんを自動解除しました - $ks</div>");
    }
    
    $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('abornoff');
}

/**
 * スレ一覧（$aThreadList->threads）をソートする
 *
 * @return  void
 */
function sortThreads(&$aThreadList)
{
    global $_conf;
    
    $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('sort');
    
    if ($aThreadList->threads) {
        if (!empty($GLOBALS['wakati_words'])) {
            $GLOBALS['now_sort'] = 'title';
            usort($aThreadList->threads, "cmp_similarity");
        } elseif ($GLOBALS['now_sort'] == "midoku") {
            if ($aThreadList->spmode == "soko") {
                usort($aThreadList->threads, "cmp_key");
            } else {
                usort($aThreadList->threads, "cmp_midoku");
            }
        } elseif ($GLOBALS['now_sort'] == "res") {
            usort($aThreadList->threads, "cmp_res");
        } elseif ($GLOBALS['now_sort'] == "title") {
            usort($aThreadList->threads, "cmp_title");
        } elseif ($GLOBALS['now_sort'] == "ita") {
            usort($aThreadList->threads, "cmp_ita");
        } elseif ($GLOBALS['now_sort'] == "ikioi" || $GLOBALS['now_sort'] == "spd") {
            if ($_conf['cmp_dayres_midoku']) {
                usort($aThreadList->threads, "cmp_dayres_midoku");
            } else {
                usort($aThreadList->threads, "cmp_dayres");
            }
        } elseif ($GLOBALS['now_sort'] == "bd") {
            usort($aThreadList->threads, "cmp_key");
        } elseif ($GLOBALS['now_sort'] == "fav") {
            usort($aThreadList->threads, "cmp_fav");
        } if ($GLOBALS['now_sort'] == "no") {
            if ($aThreadList->spmode == "soko") {
                usort($aThreadList->threads, "cmp_key");
            } else {
                usort($aThreadList->threads, "cmp_no");
            }
        }
    }

    // ニュースチェック
    if ($aThreadList->spmode == "news") {
        for ($i = 0; $i < $threads_num ; $i++) {
            if ($aThreadList->threads) {
                $newthreads[] = array_shift($aThreadList->threads);
            }
        }
        $aThreadList->threads = $newthreads;
        $aThreadList->num = sizeof($aThreadList->threads);
    }
    
    $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('sort');
}

/**
 * p2_setting 記録する
 *
 * @return  boolean
 */
function saveSbSetting($p2_setting_txt, $p2_setting, $pre_setting)
{
    global $_conf;

    $pre_setting_itaj = isset($pre_setting['itaj']) ? $pre_setting['itaj'] : null;
    $p2_setting_itaj = isset($p2_setting['itaj']) ? $p2_setting['itaj'] : null;
    
    if ($pre_setting['viewnum'] != $p2_setting['viewnum'] or $pre_setting['sort'] != $GLOBALS['now_sort'] or $pre_setting_itaj != $p2_setting_itaj) {
        if (!empty($_POST['sort'])) {
            $p2_setting['sort'] = $_POST['sort'];
        } elseif (!empty($_GET['sort'])) {
            $p2_setting['sort'] = $_GET['sort'];
        }
        if (false === FileCtl::make_datafile($p2_setting_txt, $_conf['p2_perm'])) {
            return false;
        }
        if ($p2_setting) {
            $p2_setting_cont = serialize($p2_setting);
            if (FileCtl::file_write_contents($p2_setting_txt, $p2_setting_cont) === false) {
                die("Error: cannot write file.");
            }
        }
    }
    
    return true;
}

/**
 * $subject_keys をシリアライズして保存する
 *
 * @return  boolean
 */
function saveSubjectKeys(&$subject_keys, $sb_keys_txt, $sb_keys_b_txt)
{
    global $_conf;
    
    //if (file_exists($sb_keys_b_txt)) { unlink($sb_keys_b_txt); }
    if (!empty($subject_keys)) {
        if (file_exists($sb_keys_txt)) {
            FileCtl::make_datafile($sb_keys_b_txt, $_conf['p2_perm']);
            copy($sb_keys_txt, $sb_keys_b_txt);
        } else {
            FileCtl::make_datafile($sb_keys_txt, $_conf['p2_perm']);
        }
        if ($sb_keys_cont = serialize($subject_keys)) {
            if (false === file_put_contents($sb_keys_txt, $sb_keys_cont, LOCK_EX)) {
                p2die("cannot write file.");
                return false;
            }
        }
    }
    
    return true;
}

/**
 * スレタイ（と本文）でマッチしたらtrueを返す
 *
 * @return  boolean
 */
function matchSbFilter(&$aThread)
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
                   $target_cont = mb_convert_encoding($word_fm_ao, 'eucJP-win', 'SJIS-win');
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
                $target_cont = mb_convert_encoding($target_cont, 'eucJP-win', 'SJIS-win');
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
 *
 * @return  float
 */
function getSbScore($words, $length)
{
    static $bracket_regex = null;
    
    if (!$bracket_regex) {
        $bracket_regex = mb_convert_encoding('/[\\[\\]{}()（）「」【】]/u', 'UTF-8', 'SJIS-win');
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
 * $aThread->similarity がセットされる
 *
 * @return  boolean
 */
function setSbSimilarity(&$aThread)
{
    $common_words = array_intersect(wakati($aThread->ttitle_hc), $GLOBALS['wakati_words']);
    if (!$common_words) {
        $aThread->similarity = 0.0;
        return false;
    }
    $score = getSbScore($common_words, mb_strlen($aThread->ttitle_hc, 'SJIS-win'));
    $aThread->similarity = $score / $GLOBALS['wakati_score'];
    // debug (title 属性)
    //$aThread->ttitle_hd = mb_convert_encoding(htmlspecialchars(implode(' ', $common_words)), 'SJIS-win', 'UTF-8');
    return true;
}

/**
 * すごく適当な分かち書き用正規表現パターンを取得する
 * （関数で取得するのは非効率的だが）
 *
 * @return  string
 */
function getWakatiRegex()
{
    $patterns = array(
        //'[一-龠]+[ぁ-ん]*',
        //'[一-龠]+',
        '[一二三四五六七八九十]+',
        '[丁-龠]+',
        '[ぁ-ん][ぁ-んー～゛゜]*',
        '[ァ-ヶ][ァ-ヶー～゛゜]*',
        //'[a-z][a-z_\\-]*',
        //'[0-9][0-9.]*',
        '[0-9a-z][0-9a-z_\\-]*',
    );
    
    return mb_convert_encoding('/(' . implode('|', $patterns) . ')/u', 'UTF-8', 'SJIS-win');
}

/**
 * 文字列をすごく適当に正規化＆分かち書きして、結果を配列で返す
 *
 * @param   string
 * @return  array
 */
function wakati($str)
{
    $str = mb_convert_encoding($str, 'UTF-8', 'SJIS-win');
    $str = mb_convert_kana($str, 'KVas', 'UTF-8');
    $str = mb_strtolower($str, 'UTF-8');

    $array = preg_split(getWakatiRegex(), $str, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    $array = array_filter(array_map('trim', $array), 'strlen');

    return $array;
}

/**
 * 分かち書きの構成要素として有効ならtrueを返す。for array_filter()
 *
 * @param   string
 * @return  boolean
 */
function wakatiFilter($str)
{
    $kanjiRegex = mb_convert_encoding('/[一-龠]/u', 'UTF-8', 'SJIS-win');
    if (preg_match($kanjiRegex, $str) or preg_match(getWakatiRegex(), $str) && mb_strlen($str, 'UTF-8') > 1) {
        return true;
    } else {
        return false;
    }
}

//============================================================
// ソート関数
//============================================================

/**
 * 新着ソート
 *
 * @return  integer
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
 *
 * @return  integer
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
 *
 * @return  integer
 */
function cmp_title($a, $b)
{ 
    if ($a->ttitle == $b->ttitle) {
        return ($a->torder > $b->torder) ? 1 : -1;
    } else {
        return strcmp($a->ttitle, $b->ttitle);
    }
}

/**
 * 板 ソート
 *
 * @return  integer
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
 *
 * @return  integer
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
 *
 * @return  integer
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
 *
 * @return  integer
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
 *
 * @return  integer
 */
function cmp_key($a, $b)
{
    return ($a->key < $b->key) ? 1 : -1;
}

/**
 * No. ソート
 *
 * @return  integer
 */
function cmp_no($a, $b)
{ 
    return ($a->torder > $b->torder) ? 1 : -1;
} 

/**
 * 類似性ソート
 *
 * @return  integer
 */
function cmp_similarity($a, $b)
{
    if ($a->similarity == $b->similarity) {
        return ($a->key < $b->key) ? 1 : -1;
    } else {
        return ($a->similarity < $b->similarity) ? 1 : -1;
    }
}

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
